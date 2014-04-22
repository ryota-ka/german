(function() {
	$(document).ready(function() {
		util = new Util();
		util.connect();

		$('#container').hide();

		$('#button-draw').click(function() {
			util.send('game/draw', '');
		});

		$('#button-declareReady').click(function() {
			util.send('game/declareReady', '');
		});

		$('#button-kyushukyuhai').click(function() {
			util.send('game/kyushukyuhai', '');
		});

		$('#button-winByDraw').click(function() {
			util.send('game/winByDraw', '');
		});

		$('#button-winByDiscard').click(function() {
			util.send('game/winByDiscard', true);
		});

		$("#status").click(function() {
			if ($(this).hasClass('offline')) {
				util.connect();
			} else {
				util.close();
			}
		});

		$('#rooms .room').click(function() {
			var index, list;
			index = $('#rooms .room').index(this);
			list = [405, 403, 402, 401, 305, 303, 302, 301, 205, 203, 202, 201, 105, 103, 102];
			if ((index >= 0) && (index <= 14)) {
				util.send('command/room', list[index]);
			} else if (index === 15) {
				// config mode
			}
		});

		$(window).keyup(function(e) {
			if (((e.which && e.which === 32) || (e.keyCode && e.keyCode === 32)) && !$('#chatbox').focus()) {

			} else if (((e.which && e.which === 191) || (e.keyCode && e.keyCode === 191)) && !$('#chatbox').focus()) {
				$('#chatbox').focus().val('/');
			}
		});

		$('#chatform').keydown(function(e) {
			if ((e.which && e.which === 13) || (e.keyCode && e.keyCode === 13)) {
				$(this).submit();
			} else if ($('#chatbox').val() === ' ') {
				$('#chatbox').val('');
			} else if ((e.which && e.which === 27) || (e.keyCode && e.keyCode === 27)) {
				$('#chatbox').blur().val('');
			}
		});

		$('#chatform').submit(function() {
			var text, command, spaceIndex, data;
			text = $('#chatbox').val();
			if (text !== '') {
				if (text.indexOf('/') === 0) {
					spaceIndex = text.indexOf(' ');
					if (spaceIndex === -1) {
						command = text.substring(1);
						data = 0;
					} else {
						command = text.substring(1, spaceIndex);
						data = text.substring(spaceIndex + 1);
					}
					if (command === 'r') {
						location.reload();
						return false;
					}
					if (command === 'name') {
						util.cookie.write('name', data);
					}
					util.send('command/' + command, data);
				} else {
					util.send('chat/chat', text);
				}
				$('#chatbox').val('');
			}
			return false;
		});

		log = function(msg) {
			if ($('#log').children().length > 25) {
				$('#log p:last-child').remove();
			}
			return $('#log').prepend("<p>" + msg + "</p>");
		};

		$('#myHand').sortable({axis: 'x', forcePlaceholderSize: true, distance: 10, revert: true, scrollSensitivity: 10, tolerance: 'pointer'});
		$('#container').disableSelection();
	});
}).call(this);
