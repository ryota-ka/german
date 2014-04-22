Array.prototype.inArray = function(value) {
	return (this.indexOf(value) === -1 ? false : true);
};

function Util() {
	this.socket = null;
	this.clientId = null;

	this.send = function(action, data) {
		var payload;
		payload = new Object();
		payload.action = action;
		payload.data = data;
		return this.socket.send(JSON.stringify(payload));
	};

	this.connect = function() {
		var serverUrl, clientId;
		//serverUrl = 'ws://117.109.218.210:8000/german';
		//serverUrl = 'ws://192.168.11.2:8000/german';
		serverUrl = 'ws://127.0.0.1:8000/german';
		if (window.MozWebSocket) {
			this.socket = new MozWebSocket(serverUrl);
		} else if (window.WebSocket) {
			this.socket = new WebSocket(serverUrl);
		}
		this.socket.onopen = function(msg) {
			$('#status').removeClass().addClass('online').text('connected');
			util.send('other/connect', {name: util.cookie.read('name'), reconnectionId: util.cookie.read('reconnectionId')});
		};
		this.socket.onmessage = function(msg) {
			var response = JSON.parse(msg.data);
			var id = response.id;
			var action = response.action;
			var data = response.data;
			var receivedId = [];
			if (receivedId.length > 10) {
				receivedId.shift();
			}
			receivedId.push(id);

			switch (action) {
				case 'info':
					log('info : ' + data);
					break;

				case 'status':
					log('Status: ' + data);
					break;

				case 'clientConnected':
					break;

				case 'clientDisconnected':
					break;

				case 'population':
					//if ($('#rooms').is(':visible')) {
					var cls = ['player', 'observer', 'computerPlayer'];
					for (var key in data) {
						var room = $('#room-' + key + ' .roomMembers');
						if (key === '0') {
							var lobby = $('#lobbyMembers').text('');
							var len = data[key].length;
							for (var i = 0; i < len; i++) {
								var elm = document.createElement('p');
								elm.textContent = data[key][i].name;
								lobby.append(elm);
							}
						} else if (room) {
							room.text('');
							var len = data[key].length;
							for (var i = 0; i < len; i++) {
								var j = len - i - 1;
								var elm = document.createElement('p');
								elm.className = cls[data[key][j].type];
								elm.style.right = (4 + 10 * i) + 'px';
								room.append(elm);
								$(elm).mouseover(function() {
									log(data[key][j].name);
								});
							}
						}
					}
					//}
					break;

				case 'room':
					$('#log').text('');
					util.q.add(function(arg) {
						var data = arg.data;

						if (data === 0) {
							$('#container').hide();
							$('#rooms').show();
						} else {
							$('#sidebar .room').text(data);
							$('#rooms').hide();
							$('#container').show();
							$('#roomNumberDisplay').html(document.createTextNode(data)).show().fadeOut(1500);
						}
					}, 0, {data: data}, 1500);
					break;

				case 'chat':
					log(data.name + ' : ' + data.message);
					break;

				case 'chatLog':
					var len = data.length;
					for (var i = 0; i < len; i++) {
						log(data[i].name + ' : ' + data[i].message);
					}
					break;

				case 'startGame':
					util.q.add(function(arg) {
						util.rollDices(arg.dices);
					}, 0, {dices: data.dices});
					util.q.add(function(arg) {
						var wind = arg.data.wind;
						var users = arg.data.users;
						// @todo なんかいろいろ
					}, 2000, {data: data});
					game = new GameFunc();
					break;

				case 'finishGame':
					util.q.add(function(arg) {
						var data = arg.data;
						var gameResultTable = $('#game-result').html('');
						for (var i = 0; i < 4; i++) {
							var record = document.createElement('tr');
							var rank = document.createElement('td');
							var name = document.createElement('td');
							var points = document.createElement('td');
							rank.appendChild(document.createTextNode(data[i].rank + '位'));
							name.appendChild(document.createTextNode(data[i].name));
							points.appendChild(document.createTextNode(data[i].points));
							$(record).append(rank).append(name).append(points);
							gameResultTable.append(record);
						}
						$('#finishGame').show();
					}, 0, {data: data});
					util.q.add(function() {
						$('#finishGame').hide();
					}, 5000);
					break;

				case 'startHand':
					util.q.add(function(arg) {
						var data = arg.data;
						$('#startHand').show();
						$('.round').text((data.round <= 4 ? '東' : '南') + (((data.round - 1) % 4) + 1) + '局 ' + data.counters + '本場');
						game.setMyWind(data.wind);
						$('#deposits span').text(data.deposits);
						$('#counters span').text(data.counters);
					}, 0, {data: data});
					util.q.add(function(arg) {
						var data = arg.data;
						util.rollDices(data.dices);
					}, 500, {data: data});
					util.q.add(function() {
						$('#startHand').hide();
					}, 2000);

					break;

				case 'succession':
					util.q.add(function(arg) {
						data = arg.data;
						$('.round').text((data.round <= 4 ? '東' : '南') + (((data.round - 1) % 4) + 1) + '局 ' + data.counters + '本場');
						game.setMyWind(data.wind);
						$('#deposits span').text(data.deposits);
						$('#counters span').text(data.counters);
					}, 0, {data: data});
					break;

				case 'doraIndicators':
					util.q.add(function(arg) {
						var data = arg.data;
						var dora;
						dora = new OpenTile(null, null, false, true);
						$('#doraIndicatorsWrapper > .tile').replaceWith(dora.getHtmlElement());
						for (var i = 0; i < Object.keys(data).length; i++) {
							dora = new OpenTile(data[i].id, data[i].kind, false, false);
							$('#doraIndicatorsWrapper > .tile.reversed:first').replaceWith(dora.getHtmlElement());
						}
					}, 0, {data: data});
					break;

				case 'whatToDo':
					util.q.add(function(arg) {
						var data = arg.data.whatToDo;

						util.hideAllButtons();
						if (data.inArray('draw')) {
							util.showButton('draw');
						}
						if (data.inArray('discard')) {
							util.q.add(function() {
								game.myHand.setSelectable(function(tile) {
									util.send('game/discardedTile', {tileId: tile.id});
								});
							});
						}
						if (data.inArray('discardOnlyDrawn')) {
							util.q.add(function() {
								game.myHand.setSelectable(function(tile) {
									util.send('game/discardedTile', {tileId: tile.id});
								}, true);
							});
						}
						if (data.inArray('declareReady')) {
							util.showButton('declareReady');
						}
						if (data.inArray('kyushukyuhai')) {
							util.showButton('kyushukyuhai');
						}
						if (data.inArray('winByDraw')) {
							util.showButton('winByDraw');
						}
						if (data.inArray('winByDiscard')) {
							util.showButton('winByDiscard');
						}
						if (data.inArray('chankan')) {
							util.showButton('winByDiscard');
							util.q.add(function() {
								util.send('notChankan', '');
							}, 2000);
						}
					}, 0, {data: data});

					util.q.add(function(arg) {
						var data = arg.data.availableCallings;
						if (data) {
							var indices = [];
							if (data[0]) {
								if (Object.keys(data[0]).length === 1) {
									util.showButton('chi').unbind().mouseenter({obj: data[0]}, function(event) {
										for (var key in event.data.obj) {
										}
										var kind = parseInt($('#leftDiscards p.tile:last-child').text());
										switch (key) {
											case 'right':
												var kindsInHand = [kind - 2, kind - 1];
												break;
											case 'center':
												var kindsInHand = [kind - 1, kind + 1];
												break;
											case 'left':
												var kindsInHand = [kind + 1, kind + 2];
												break;
										}

										for (var i = 0; i < kindsInHand.length; i++) {
											$('#myHand .tile').each(function(index) {
												if (kindsInHand[i] === parseInt($(this).text())) {
													indices.push(index);
													return false;
												}
											});
										}
										game.myHand.addCursor(indices);
									}).mouseleave(function() {
										game.myHand.removeCursor();
									}).click(function() {
										util.send('game/called', 0);
									});
								} else {
									util.showButton('chi').unbind().click(function() {
										util.send('game/called', 0);
									});
								}
							}
							if (data[1]) {
								util.showButton('pon').unbind().mouseenter({kind: data[1]}, function(event) {
									$('#myHand .tile').each(function(index) {
										if (event.data.kind === parseInt($(this).text())) {
											indices.push(index);
											if (indices.length === 2) {
												return false;
											}
										}
									});
									game.myHand.addCursor(indices);
								}).mouseleave(function() {
									indices = [];
									game.myHand.removeCursor();
								}).click(function() {
									util.send('game/called', 1);
								});
							}
							if (data[2]) {
								util.showButton('daiminkan').unbind().mouseenter({kind: data[2]}, function(event) {
									$('#myHand .tile').each(function(index) {
										if (event.data.kind === parseInt($(this).text())) {
											indices.push(index);
											if (indices.length === 3) {
												return false;
											}
										}
									});
									game.myHand.addCursor(indices);
								}).mouseleave(function() {
									indices = [];
									game.myHand.removeCursor();
								}).click(function() {
									util.send('game/called', 2);
								});
							}
							if (data[3]) {
								if (Object.keys(data[3]).length === 1) {
									for (var kind in data[3]) {
									}
									util.showButton('kakan').unbind().mouseenter({kind: kind}, function(event) {
										$('#myHand .tile').each(function(index) {
											if (event.data.kind === parseInt($(this).text())) {
												indices.push(index);
												return false;
											}
										});
										game.myHand.addCursor(indices);
									}).mouseleave(function() {
										indices = [];
										game.myHand.removeCursor();
									}).click(function() {
										util.send('game/called', 3);
									});
								} else {
									util.showButton('daiminkan').unbind().click(function() {
										util.send('game/called', 3);
									});
								}
							}
							if (data[4]) {
								if (Object.keys(data[4]).length === 1) {
									util.showButton('ankan').unbind().mouseenter({kind: data[4][0]}, function(event) {
										$('#myHand .tile').each(function(index) {
											if (event.data.kind === parseInt($(this).text())) {
												indices.push(index);
												if (indices.length === 4)
													return false;
											}
										});
										game.myHand.addCursor(indices);
									}).mouseleave(function() {
										indices = [];
										game.myHand.removeCursor();
									}).click(function() {
										util.send('game/called', 4);
									});
								} else {
									util.showButton('ankan').unbind().click(function() {
										util.send('game/called', 4);
									});
								}
							}
						}
					}, 0, {data: data});

					break;

				case 'drawTile':
					util.q.add(function(arg) {
						var data = arg.data;
						game.myHand.sort();
						game.myHand.addTile(data.id, data.kind, true);
					}, 0, {data: data});
					break;

				case 'selectTilesForCalling':
					util.q.add(function(arg) {
						var data = arg.data;

						game.myHand.setUnselectable();
						$('#layer').show();
						var box = document.createElement('div');
						var melds = [];
						kindsInHand = [];
						for (var i = 0; i < Object.keys(data.choices).length; i++) {
							if (data.kind === 0) {
								switch (data.choices[i]) {
									case 'right':
										var index = [-2, -1];
										kindsInHand[i] = [data.tile - 2, data.tile - 1];
										break;
									case 'center':
										var index = [-1, 1];
										kindsInHand[i] = [data.tile - 1, data.tile + 1];
										break;
									case 'left':
										var index = [1, 2];
										kindsInHand[i] = [data.tile + 1, data.tile + 2];
										break;
								}
								melds[i] = new OpenMeld(0, [new OpenTile(null, data.tile, null, false), new OpenTile(null, data.tile + index[0], null, false), new OpenTile(null, data.tile + index[1], null, false)], 0);
							} else if (data.kind === 3) {
								kindsInHand[i] = [data.choices[i]];
								melds[i] = new OpenMeld(3, [new OpenTile(null, data.choices[i], null, false), new OpenTile(null, data.choices[i], null, false), new OpenTile(null, data.choices[i], null, false), new OpenTile(null, data.choices[i], null, false)], data.sidewayIndices[i]);
							} else if (data.kind === 4) {
								kindsInHand[i] = [data.choices[i]];
								melds[i] = new OpenMeld(4, [new OpenTile(null, data.choices[i], null, false), new OpenTile(null, data.choices[i], null, true), new OpenTile(null, data.choices[i], null, true), new OpenTile(null, data.choices[i], null, false)]);
							}
							var meldElement = melds[i].getHtmlElement();
							$(meldElement).click(function() {
								util.send('game/selectedTilesForCalling', $('#layer .openMeld').index(this));
								$('#layer').text('').hide();
							});
							if (data.kind === 4) {
								$(meldElement).mouseenter({kinds: kindsInHand[i]}, function(event) {
									$('#myHand .tile').each(function(index) {
										var indexof = event.data.kinds.indexOf(parseInt($(this).text()));
										var indices;
										if (indexof !== -1) {
											indices.push(index);
										}
									});
									game.myHand.addCursor(indices);
								});
							} else {
								$(meldElement).mouseenter({kinds: kindsInHand[i]}, function(event) {
									var indices;
									for (var j = 0; j < event.data.kinds.length; j++) {
										$('#myHand .tile').each(function(index) {
											if (event.data.kinds[j] === parseInt($(this).text())) {
												indices.push(index);
												return false;
											}
										});
									}
									game.myHand.addCursor(indices);
								});
							}
							$(meldElement).mouseleave(function() {
								game.myHand.removeCursor();
							});
							box.appendChild(meldElement);
						}
						$('#layer').append(box);
					}, 0, {data: data});

					break;

				case 'hand':
					util.q.add(function(arg) {
						var data = arg.data;
						game.myHand.clear();
						for (var i = 0; i < Object.keys(data).length; i++) {
							game.myHand.addTile(data[i].id, data[i].kind);
						}
						game.myHand.sort();
					}, 0, {data: data});
					break;

				case 'called':
					var list = ['チー', 'ポン', 'カン', 'カン', 'カン'];
					util.showArrowBox(util.windToSeat(data.wind), list[data.kind]);
					switch (data.kind) {
						case 0:
							util.sound.play('chi');
							break;
						case 1:
							util.sound.play('pon');
							break;
						case 2:
						case 3:
						case 4:
							util.sound.play('kan');
							break;
					}
					break;

				case 'declaredReady':
					util.showArrowBox(util.windToSeat(data.wind), 'リーチ');
					util.sound.play('riichi');
					break;

				case 'wallNumber':
					var canvas = document.getElementById('wallNumber');
					var ctx = canvas.getContext('2d');
					var xPos = canvas.offsetWidth / 2;
					var yPos = canvas.offsetHeight / 2;
					var radius = 45;
					var percentage = data.wallNumber / 69;
					var boundary;
					ctx.clearRect(0, 0, 100, 100);
					if ((percentage === 0) || (percentage === 1)) {
						percentage = 1 - percentage;
					}
					boundary = (0.75 - percentage) * Math.PI * 2;
					ctx.lineWidth = 2;
					ctx.beginPath();
					ctx.arc(xPos, yPos, radius, -0.5 * Math.PI, boundary, true);
					ctx.strokeStyle = 'rgb(64,204,64)';
					ctx.stroke();
					ctx.beginPath();
					ctx.arc(xPos, yPos, radius, boundary, 1.5 * Math.PI, true);
					ctx.strokeStyle = 'rgba(96,96,96,.5)';
					ctx.stroke();
					break;

				case 'discards':
					util.q.add(function(arg) {
						data = arg.data;
						game.discardPiles.clear();
						for (var i = 0; i < 4; i++) {
							for (var j = 0; j < Object.keys(data[i]).length; j++) {
								game.discardPiles.addTile(i, data[i][j].id, data[i][j].kind, (j === data[4][i]));
							}
						}
					}, 0, {data: data});
					break;

				case 'openMelds':
					util.q.add(function(arg) {
						var data = arg.data;
						game.openMeldHandler.clear();
						for (var i = 0; i < 4; i++) {
							game.openMeldHandler.addMelds(i, data[i]);
						}
					}, 0, {data: data});
					break;

				case 'deposits':
					$('#deposits span').text(data);
					break;

				case 'agari':
					for (var i = 0; i < 4; i++) {
						if (data[i]) {
							util.q.add(function() {
								$('#agari-screen').css({width: 0, height: 0}).show().animate({width: '80%', height: '80%'}, 300);
								var yakuTable = document.createElement('table');
								yakuTable.id = 'yakuTable';
								$('#agari-screen').append(yakuTable);
							}, 1000);
							if (data[i].yaku.han > 0) {
								for (var j = 0; j < 33; j++) {
									if (data[i].yaku.yaku[j]) {
										util.q.add(function(arg) {
											var record = document.createElement('tr');
											var yakuCell = document.createElement('td');
											var yakuHan = document.createElement('td');
											yakuCell.appendChild(document.createTextNode(arg.yakuName));
											yakuHan.appendChild(document.createTextNode(arg.han + '飜'));
											record.appendChild(yakuCell);
											record.appendChild(yakuHan);
											$('#yakuTable').append(record);
										}, 1000, {yakuName: game.yakuList[j], han: data[i].yaku.yaku[j]});
									}
								}
							} else if (data[i].yaku.han < 0) {
								for (var j = 0; j < 13; j++) {
									if (data[i].yaku.yakuman[j]) {
										util.q.add(function(arg) {
											var record = document.createElement('tr');
											var yakuCell = document.createElement('td');
											var yakuHan = document.createElement('td');
											yakuCell.appendChild(document.createTextNode(arg.yakuName));
											record.appendChild(yakuCell);
											record.appendChild(yakuHan);
											$('#yakuTable').append(record);
										}, 1000, {yakuName: game.yakumanList[j]});
									}
								}
							}

							if (data[i].yaku.han !== 0) {
								util.q.add(function() {
									var hrr = document.createElement('tr');
									var hrc = document.createElement('td');
									hrr.appendChild(hrc);
									hrc.appendChild(document.createElement('hr'));
									hrc.setAttribute('colspan', '2');
									hrc.style.paddingLeft = '5px';
									hrc.style.paddingRight = '5px';
									$('#yakuTable').append(hrr);
									$('#yakuTable hr').css('width', 0).animate({width: '100%'}, 500);
								}, 1000);
							}

							util.q.add(function(arg) {
								var data = arg.data;
								var han = data.yaku.han;
								var tr = document.createElement('tr');
								var td = document.createElement('td');
								var str;
								if (han < 0) {
									var times = [null, '', 'ダブル', 'トリプル', 'クアドラプル'];
									str = times[-han] + '役満';
								} else if (han > 0) {
									if (han <= 4) {
										str = han + '飜 ' + data.yaku.fu + '符' + (data.yaku.basicPoints === 2000 ? ' 満貫' : '');
									} else if (han === 5) {
										str = han + '飜 満貫';
									} else if (han <= 7) {
										str = han + '飜 跳満';
									} else if (han <= 10) {
										str = han + '飜 倍満';
									} else if (han <= 12) {
										str = han + '飜 三倍満';
									} else {
										str = han + '飜 数え役満';
									}
								} else {
									switch (data.yaku.chomboStatus) {
										case 0:
											str = '振聴錯和';
											break;
										case 1:
											str = '誤ロン';
											break;
										case 2:
											str = '役無し';
											break;
										case 3:
											str = '誤ツモ';
									}
								}
								td.setAttribute('colspan', '2');
								td.style.textAlign = 'right';
								td.appendChild(document.createTextNode(str + ' ' + data.points + '点'));
								tr.appendChild(td);
								$('#yakuTable').append(tr);
							}, 1000, {data: data[i]});

							util.q.add(function() {
								$('#agari-screen').text('').animate({width: 0, height: 0}, 300);
							}, 5000, {}, 500);

							util.q.add(function(arg) {
								var data = arg.data;
								log('- - - - - - - - - - - - - - -');
								log('東家 : ' + data.formerPoints[0] + ' → ' + (data.formerPoints[0] + data.diffs[0] + data.adiffs[0]));
								log('南家 : ' + data.formerPoints[1] + ' → ' + (data.formerPoints[1] + data.diffs[1] + data.adiffs[1]));
								log('西家 : ' + data.formerPoints[2] + ' → ' + (data.formerPoints[2] + data.diffs[2] + data.adiffs[2]));
								log('北家 : ' + data.formerPoints[3] + ' → ' + (data.formerPoints[3] + data.diffs[3] + data.adiffs[3]));
								log('- - - - - - - - - - - - - - -');
							}, 0, {data: data[i]});
						}
					}
					break;

				case 'winByDraw':
					util.showArrowBox(util.windToSeat(data.wind), 'ツモ');
					util.sound.play('tsumo');
					break;

				case 'winByDiscard':
					util.showArrowBox(util.windToSeat(data.wind), 'ロン');
					util.sound.play('ron');
					if (data.wind === game.myWind) {
						util.hideButton('winByDiscard');
					}
					util.q.add(function() {
						util.send('game/winByDiscard', false);
						util.hideButton('winByDiscard');
					}, 1500, {}, -1);
					break;

				case 'discardedLastTile':
					util.q.add(function() {
						util.send('game/winByDiscard', false);
					}, 1500);
					break;

				case 'exhaustiveDraw':
					break;

				case 'abortiveDraw':
					util.q.add(function(arg) {
						var type = ['九種九牌', '四風子連打', '四家立直', '四開槓'];
						$('#draw').show().append(document.createTextNode(type[arg.data]));
					}, 0, {data: data});
					util.q.add(function() {
						$('#draw').hide();
					}, 5000);

					break;

				case 'clientActivity':
					return clientActivity(data);
					break;

				case 'CPwait':
					game.CPwaitProcessor.send(data.clientId, data.delay);
					break;

				default:
					log(action);
			}
		};
		this.socket.onclose = function(msg) {
			return $('#status').removeClass().addClass('offline').text('disconnected');
		};
	};

	this.close = function() {
		this.socket.close();
		this.clientId = null;
	};

	this.rollDices = function(dices) {
		var i = 0;
		var diceRoller = setInterval(function() {
			if (i === 1) {
				$('.dice').css('background-image', "url('/images/dice/rolling.png')");
				$('#diceBox').css({position: 'absolute', right: '0%', top: '25%'}).show();
			} else if (i <= 15) {
				$('#dice1').css('background-position-x', (-40 * (i % 5)) + 'px');
				$('#dice2').css('background-position-x', (-40 * ((i + 3) % 5)) + 'px');
				$('#diceBox').css({right: (i <= 10 ? i * 5 : 50) + '%', top: (i <= 10 ? -0.4 * i * i + 8 * i : -(i - 20) * (i - 20) * 4 / 9 + 50) + '%'});//(i <= 12 ? -0.4 * i * i + 8 * i : -(i - 20) * (i - 20) * 4 / 9 + 50)
			} else if (i === 16) {
				$('#dice1').css('background-image', "url('/images/dice/" + dices[0] + ".png')");
				$('#dice2').css('background-image', "url('/images/dice/" + dices[1] + ".png')");
			} else if (i === 40) {
				clearInterval(diceRoller);
				$('#diceBox').hide();
			}
			i++;
		}, 50);
	};

	this.showButton = function(buttonName) {
		return $('#button-' + buttonName).show();
	};

	this.hideButton = function(buttonName) {
		return $('#button-' + buttonName).hide();
	};
	this.hideAllButtons = function() {
		$('#buttons > p').hide();
	};

	this.showArrowBox = function(seat, message) {
		var list = ['bottom', 'right', 'top', 'left'];
		$('.arrow-box').hide();
		$('#arrow-box-' + list[seat]).show().html(document.createTextNode(message));
		setTimeout(function() {
			$('#arrow-box-' + list[seat]).hide();
		}, 1000);
	};

	this.windToSeat = function(wind) {
		return (wind - game.myWind + 4) % 4;
	};

	this.cookie = new function() {
		this.read = function(key) {
			return $.cookie(key);
		};

		this.write = function(key, data) {
			return $.cookie(key, data, {expires: 365});
		};
	};

	this.sound = new function() {
		this.soundSet = 'm01';

		this.play = function(type) {
			document.getElementById('sound-' + this.soundSet + '-' + type).play();
		};

		this.useSoundSet = function(soundSet) {
			return soundSet;
		};
	};

	this.q = new function() {
		this.functions = [];
		this.delays = [];
		this.args = [];
		this.afterDelays = [];
		this.isFiring = false;

		this.add = function(fnc, delay, arg, afterDelay) {
			if (typeof fnc === 'function') {
				this.functions.push(fnc);
				this.delays.push(typeof delay === 'undefined' ? 0 : delay);
				this.args.push(arg);
				this.afterDelays.push(afterDelay);

				if (this.isFiring === false) {
					this.isFiring = true;
					this.fire();
				}
			}
		};
		this.fire = function() {
			var fnc = this.functions.shift();
			var delay = this.delays.shift();
			var arg = this.args.shift();
			var afterDelay = this.afterDelays.shift();
			this.functions.unshift();
			this.delays.unshift();
			this.args.unshift();
			this.afterDelays.unshift();
			if (typeof fnc === 'function') {
				if (afterDelay === -1) {
					setTimeout(function() {
						fnc(arg);
					}, delay);
					util.q.fire();
				} else {
					setTimeout(function() {
						fnc(arg);
						if (afterDelay) {
							setTimeout(function() {
								util.q.fire();
							}, afterDelay);
						} else {
							util.q.fire();
						}
					}, delay);
				}
			} else {
				this.isFiring = false;
			}
		};
	};
}


function MyTile(id, kind) {
	this.id = id;
	this.kind = kind;
}

MyTile.prototype.getHtmlElement = function(isDrawn) {
	var elm = document.createElement('p');
	elm.className = 'tile myTile' + (isDrawn ? ' drawn' : '');
	elm.style.backgroundImage = 'url(\'/images/tiles/' + this.kind + '.png\')';
	elm.textContent = this.kind;
	return elm;
};


function OpenTile(id, kind, isSideway, isReversed) {
	this.id = id;
	this.kind = kind;
	this.isSideway = isSideway;
	this.isReversed = isReversed;
}

OpenTile.prototype.getHtmlElement = function() {
	var element = document.createElement('p');
	element.textContent = this.kind;
	element.className = 'tile openTile' + (this.isSideway ? ' sideway' : '') + (this.isReversed ? ' reversed' : '');
	if (!this.isReversed) {
		element.style.backgroundImage = 'url(\'/images/tiles/' + this.kind + '.png\')';
	}
	element.textContent = this.kind;
	return element;
};


function OpenMeld(kind, tiles, sidewayIndex) {
	this.tiles = [];
	this.kind = kind;
	this.sidewayIndex = sidewayIndex;
	var isSideway;
	for (var i = 0; i < tiles.length; i++) {
		if (this.kind <= 1) {
			isSideway = (i === this.sidewayIndex);
		} else if (this.kind === 2) {
			if (this.sidewayIndex === 2) {
				isSideway = (i === 3);
			} else {
				isSideway = (i === this.sidewayIndex);
			}
		} else if (this.kind === 3) {
			isSideway = ((i === this.sidewayIndex) || (i === this.sidewayIndex + 1));
		} else {
			isSideway = false;
		}
		this.tiles.push(new OpenTile(tiles[i].id, tiles[i].kind, isSideway, tiles[i].isReversed));
	}
}

OpenMeld.prototype.getHtmlElement = function() {
	var element = document.createElement('div');
	element.className = 'openMeld';
	for (var i = 0; i < this.tiles.length; i++) {
		element.appendChild(this.tiles[i].getHtmlElement());
	}
	return element;
};


function GameFunc() {

	this.myWind = null;
	this.yakuList = ['立直', '一発', '門前清自摸和', '断么九', '平和', '一盃口', '東', '南', '西', '北', '白', '發', '中', '嶺上開花', '槍槓', '海底摸月', '河底撈魚', '三色同順', '一気通貫', '混全帯么九', '七対子', '対々和', '三暗刻', '混老頭', '三色同刻', '三槓子', '小三元', 'ダブル立直', '混一色', '純全帯么九', '二盃口', '清一色', 'ドラ'];
	this.yakumanList = ['国士無双', '四暗刻', '大三元', '字一色', '小四喜', '大四喜', '緑一色', '清老頭', '四槓子', '九蓮宝燈', '天和', '地和', '人和'];

	this.setMyWind = function(wind) {
		this.myWind = wind;
	};

	this.myHand = new function() {
		this.tiles = [];
		this.isReady = false;
		this.won = false;
		this.addTile = function(id, kind, isDrawn) {
			var tile = new MyTile(id, kind);
			this.tiles.push(tile);
			$('#myHand').append(tile.getHtmlElement(isDrawn));
		};
		this.setSelectable = function(callback, isOnlyDrawn) {
			this.setUnselectable();
			$('.myTile' + (isOnlyDrawn ? ':last-child' : '')).addClass('clickable');
			$('.myTile.clickable').animate({marginTop: '0px', opacity: 0.6}, 200);

			$('.myTile.clickable').click(function() {
				$(this).addClass('selected');
				callback(game.myHand.tiles[$('.myTile').index(this)]);
				game.myHand.setUnselectable();
				$(this).animate({marginTop: '0px', opacity: 1}, 200);
			}).hover(function() {
				$(this).stop().animate({marginTop: '0px', opacity: 1}, 200);
				$(this).siblings('.clickable').stop().animate({marginTop: '0px', opacity: 0.6}, 200);
			}, function() {
				$(this).stop().animate({marginTop: '0px', opacity: 0.6}, 200);
			});
		};
		this.setUnselectable = function() {
			$('.myTile').removeClass('clickable').fadeTo(200, 1);
			$('.myTile.clickable').unbind();
		};
		this.sort = function() {
			this.tiles.sort(function(a, b) {
				return a.kind - b.kind;
			});
			$('#myHand').text('');
			for (var i = 0; i < this.tiles.length; i++) {
				$('#myHand').append(this.tiles[i].getHtmlElement());
			}
		};
		this.clear = function() {
			this.tiles = [];
			$('#myHand').text('');
		};
		this.addCursor = function(indices) {
			this.removeCursor();
			for (var i = 0; i < indices.length; i++) {
				$('#myHand p.tile:nth-child(' + (indices[i] + 1) + ')').addClass('selected');
			}
		};
		this.removeCursor = function() {
			$('.selected').removeClass('selected');
		};
	};

	this.discardPiles = new function() {
		this.discards = [];
		this.discards[0] = [];
		this.discards[1] = [];
		this.discards[2] = [];
		this.discards[3] = [];

		this.addTile = function(wind, id, kind, isSideway) {
			var discard = new OpenTile(id, kind, isSideway, false);
			var seat = ['my', 'right', 'opposite', 'left'];
			var number = util.windToSeat(wind);
			var discardsWrapper = $('#' + seat[number] + 'Discards');
			var index;
			this.discards[number].push(discard);
			if (discardsWrapper.children().eq(1).children().length === 6) {
				index = 2;
			} else if (discardsWrapper.children().eq(0).children().length === 6) {
				index = 1;
			} else {
				index = 0;
			}
			discardsWrapper.children().eq(index).append(discard.getHtmlElement());
		};
		this.clear = function() {
			this.discards = [];
			this.discards[0] = [];
			this.discards[1] = [];
			this.discards[2] = [];
			this.discards[3] = [];
			$('.discardsRow').text('');
		};

	};

	this.openMeldHandler = new function() {
		this.openMelds = [];
		this.openMelds[0] = [];
		this.openMelds[1] = [];
		this.openMelds[2] = [];
		this.openMelds[3] = [];
		this.addMelds = function(wind, melds) {
			var meld;
			for (var i = 0; i < Object.keys(melds).length; i++) {
				meld = new OpenMeld(melds[i].kind, melds[i].tiles, melds[i].sidewayIndex);
				var number = util.windToSeat(wind);
				var seat = ['my', 'right', 'opposite', 'left'];
				this.openMelds[number].push(meld);
				$('#' + seat[number] + 'OpenMeldsWrapper').append(meld.getHtmlElement());
			}
		};
		this.clear = function() {
			this.openMelds = [];
			this.openMelds[0] = [];
			this.openMelds[1] = [];
			this.openMelds[2] = [];
			this.openMelds[3] = [];
			$('.openMeldsWrapper').text('');
		};
	};

	this.CPwaitProcessor = new function() {
		this.queue = [];
		this.processor = null;

		this.addQueue = function(clientId, delay) {
			util.q.add(function(arg) {
				util.send('other/CPwait', arg.clientId);
				game.CPwaitProcessor.removeQueue(arg.clientId);
			}, delay, {clientId: clientId}, -1);
		};

		this.send = function(clientId, delay) {
			this.addQueue(clientId, delay);
			if (this.queue.inArray(clientId)) {
				this.queue.push(clientId);
			}
			this.processor = setTimeout(function() {
				if (game.CPwaitProcessor.getQueue().inArray(clientId)) {
					this.send(clientId, 500);
				}
			}, 1000 + delay);
		};

		this.getQueue = function() {
			return this.queue;
		};

		this.removeQueue = function(clientId) {
			for (var i in this.queue) {
				if (this.queue[i] === clientId) {
					delete this.queue[i];
					return;
				}
			}
		};
	};
}