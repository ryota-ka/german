<?php

namespace WebSocket\Application;

class Hand {

	private $app;
	private $userRoomHandler;
	private $game;
	private $tileHandler;
	private $turn = 0;
	private $turnStatus;
	private $dices;
	private $availableCallings = array(array(), array(), array(), array());
	private $calling;
	private $readyDeclared = array(false, false, false, false);
	private $readyDeclaredTurn = array(null, null, null, null);
	private $waitedTiles = array(array(), array(), array(), array());
	private $oneShotAvailable = array(false, false, false, false);
	private $whatToDo;
	private $notChankanDeclared = array(null, null, null, null);
	private $winByDiscardDeclared = array(null, null, null, null);
	private $sacredDiscard = array(array(false, false, false, false), array(false, false, false, false), array(false, false, false, false), array(false, false, false, false));

	public function __construct(GermanApplication $app, UserRoomHandler $userRoomHandler, Game $game) {
		$this->app = $app;
		$this->userRoomHandler = $userRoomHandler;
		$this->game = $game;

		$this->dices = array(rand(1, 6), rand(1, 6));

		$this->tileHandler = new TileHandler($this->app, $this->userRoomHandler, $this->game, $this);

		for ($i = 0; $i < 4; $i++) {
			$this->game->sendByWind('startHand', array(
				'wind' => $i,
				'round' => $this->game->getRound(),
				'deposits' => $this->game->getDeposits(),
				'counters' => $this->game->getCounters(),
				'dices' => $this->dices
					), $i);
		}
		$this->game->getPlayerHandler()->init();
	}

	public function start() {
		$this->tileHandler->init();
	}

	public function destruct() {
		if (isset($this->tileHandler)) {
			unset($this->tileHandler);
		}
	}

	/**
	 *
	 * @return TileHandler
	 */
	public function getTileHandler() {
		return $this->tileHandler;
	}

	public function draw() {
		$this->completeReady();
		$this->tileHandler->draw($this->turn);
	}

	public function exhaustiveDraw() {
		echo "荒牌流局やで\n";
		$isTempai = array(false, false, false, false);


		/* --- 流し満貫チェック --- */
		for ($i = 0; $i < 4; $i++) {
			$isNagashiMangan[$i] = $this->tileHandler->isNagashiManganAvailable($i);
		}

		/* --- 流し満貫チェック --- */

		/* --- ノーテン罰符 --- */
		$count = 0;
		for ($i = 0; $i < 4; $i++) {
			$isTempai[$i] = $this->tileHandler->checkTempaiByWind($i);
			$count += $isTempai[$i];
		}
		var_dump($isTempai);

		switch ($count) {
			case 1:
				break;
			case 2:
				break;
			case 3:
				break;
		}
		/* --- ノーテン罰符 --- */

		$this->game->finishHand($isTempai[0], true);
	}

	/**
	 *
	 * @param int $type
	 * 0 : 九種九牌
	 * 1 : 四風子連打
	 * 2 : 四家立直
	 * 3 : 四開槓
	 */
	public function abortiveDraw($type) {
		$this->game->sendAll('abortiveDraw', $type);
		$this->game->finishHand(true, true);
	}

	public function getDices() {
		return $this->dices;
	}

	public function getTurn() {
		return $this->turn;
	}

	public function setTurn($turn) {
		return $this->turn = $turn;
	}

	public function addCalling($wind, $kind) {
		if (is_null($this->calling) || ($this->calling->getKind() == 0)) {
			$this->calling = new Calling($wind, $kind);
		}
	}

	/**
	 *
	 * @return Calling
	 */
	public function getCalling() {
		return $this->calling;
	}

	public function processCalling(Array $tileIds) {
		if (!is_null($this->getCalling())) {
			$this->completeReady();

			$calling = $this->getCalling();

			$this->tileHandler->addOpenMeld($calling->getWind(), $calling->getKind(), $tileIds);

			$this->turn = $this->getCalling()->getWind();

			if ($calling->getKind() <= 1) {
				$this->setTurnStatus(3 + $calling->getKind());
				$this->whatToDo();
			} else {
				if ($calling->getKind() === 3) {
					$this->setTurnStatus(7);
					$this->notChankanDeclared = array(false, false, false, false);
					$this->notChankanDeclared[$this->getTurn()] = true;
					$this->whatToDo();
				} else {
					$this->tileHandler->drawSupplementalTile();
				}
			}
			$this->clearCalling();
		}
	}

	public function clearCalling() {
		$this->calling = null;
	}

	public function tellToSelectTilesForCalling(Array $choices) {
		if ($this->getCalling()) {
			$this->getCalling()->setChoices($choices);
			$data = array(
				'kind' => $this->getCalling()->getKind(),
				'choices' => $choices
			);
			if ($this->getCalling()->getKind() === 0) {
				$data['tile'] = end($this->tileHandler->getDiscardByWind($this->getTurn()))->getKind();
			} else if ($this->getCalling()->getKind() === 3) {
				$data['sidewayIndices'] = array();
				foreach ($this->tileHandler->getOpenTriplets($this->getCalling()->getWind()) as $triplet) {
					$data['sidewayIndices'][] = 3 - ($triplet->getFrom() - $this->getCalling()->getWind() + 4) % 4;
				}
			}
			$this->game->sendByWind('selectTilesForCalling', $data, $this->getCalling()->getWind());
			$this->setTurnStatus(2);
			$this->whatToDo();
		}
	}

	public function isReadyDeclared($wind) {
		return $this->readyDeclared[$wind];
	}

	public function isOneShotAvailable($wind) {
		return $this->oneShotAvailable[$wind];
	}

	public function disableOneShot($wind = null) {
		if (is_null($wind)) {
			$this->oneShotAvailable = array(false, false, false, false);
		} else {
			$this->oneShotAvailable[$wind] = false;
		}
	}

	public function isReady($wind) {
		if (in_array($wind, array(0, 1, 2, 3), true)) {
			return !is_null($this->readyDeclaredTurn[$wind]);
		} else {
			exit("そんな風おまへんで"); // @error
		}
	}

	public function declareReady($wind) {
		if ($this->game->getPlayerHandler()->getPlayerByWind($wind)->declareReady()) {
			$this->readyDeclared[$wind] = true;
			$this->game->sendAll('declaredReady', array('wind' => $wind));
			$this->setTurnStatus(5);
			$this->whatToDo();
		}
	}

	public function completeReady() {
		for ($i = 0; $i < 4; $i++) {
			if (!$this->sacredDiscard[$i][1] && $this->sacredDiscard[$i][3]) { // リーチ後見逃しの際はもはや判定しなくても良いが、そうでない場合、見逃したフラグが立っていれば、
				if ($this->isReady($i)) { // 既にリーチしていればリーチ後見逃し
					$this->sacredDiscard[$i][1] = true;
					echo "$i さんリーチ後に見逃してもうたな……\n";
				} else { // そうでなければ同巡内フリテンのみ
					$this->sacredDiscard[$i][2] = true;
					echo "$i さん同巡内フリテンやで\n";
				}
			}
		}
		foreach ($this->readyDeclared as $key => $value) {
			if ($value === true) {
				$this->game->getPlayerHandler()->getPlayerByWind($key)->completeReady();
				$this->game->sendAll('completedReady', array('wind' => $key));
				$this->readyDeclaredTurn[$key] = count($this->tileHandler->getDiscardByWind($key)) - 1;
				$this->readyDeclared[$key] = false;
				$this->oneShotAvailable[$key] = true;
				if (!in_array(null, $this->getReadyDeclaredTurn(), true)) {
					$this->abortiveDraw(2); // 四家立直
					return;
				}
				$this->waitedTiles[$key] = $this->tileHandler->checkTempaiByWind($key, true);
			}
		}
	}

	public function checkOverlooking($tile) {
		for ($i = 0; $i < 4; $i++) {
			if ($i !== $this->turn && in_array($tile, $this->waitedTiles[$i], true)) {
				$this->sacredDiscard[$i][3] = true;
				echo "$i さん $tile 当たり牌やで\n";
			}
		}
	}

	public function resetOverlooking($wind) {
		if ($this->sacredDiscard[$wind][2]) {
			echo "$wind さん戻りましたわ\n";
		}
		$this->sacredDiscard[$wind][2] = false;
		$this->sacredDiscard[$wind][3] = false;
	}

	public function isSacredDiscard($wind) {
		return ($this->sacredDiscard[$wind][0] || $this->sacredDiscard[$wind][1] || $this->sacredDiscard[$wind][2]);
	}

	public function checkWaitedTiles($wind) {
		if (in_array($wind, array(0, 1, 2, 3), true)) {
			if (!$this->isReady($wind)) {
				$this->waitedTiles[$wind] = $this->tileHandler->checkTempaiByWind($wind, true);
				echo "待ち牌更新！\n";
			}
			echo "$wind さん待ち牌:\n";
			var_dump($this->waitedTiles[$wind]);
		}
	}

	public function getReadyDeclaredTurn() {
		return $this->readyDeclaredTurn;
	}

	public function getNotChankanDeclaredPlayers() {
		return $this->notChankanDeclared;
	}

	public function addNotChankanDeclaredPlayer($wind) {
		if (in_array($wind, array(0, 1, 2, 3), true)) {
			$this->notChankanDeclared[$wind] = true;
			if ($this->notChankanDeclared === array(true, true, true, true)) {
				$this->notChankanDeclared = array(null, null, null, null);
				foreach ($this->tileHandler->getOpenMeldsByWind($this->turn) as $openMeld) {
					$kind = reset($openMeld->getTiles())->getKind();
				}
				$this->checkOverlooking($kind);
				$this->tileHandler->drawSupplementalTile();
			} else {
				$this->whatToDo();
			}
		}
	}

	public function addWinByDiscardDeclaredPlayer($wind, $state) {
		var_dump($wind);
		var_dump($state);

		if (in_array($wind, array(0, 1, 2, 3), true)) {
			$this->winByDiscardDeclared[$wind] = (bool) $state || $this->winByDiscardDeclared[$wind];
			var_dump($this->winByDiscardDeclared);
			if (!in_array(null, $this->winByDiscardDeclared, true)) {
				$this->winByDiscard();
			}
		}
	}

	public function getWinByDiscardDeclared() {
		return $this->winByDiscardDeclared;
	}

	public function winByDraw($wind) {
		if (in_array($wind, array(0, 1, 2, 3), true)) {
			$winds = array(false, false, false, false);
			$winds[$wind] = true;
			$this->win($winds);
		}
	}

	public function winByDiscard() {
		if (($this->tileHandler->getWallNumber() === 0) && ($this->winByDiscardDeclared === array(false, false, false, false))) {
			$this->exhaustiveDraw();
		} else {
			if ($this->notChankanDeclared !== array(null, null, null, null)) {
				foreach ($this->tileHandler->getOpenMeldsByWind($this->getTurn()) as $openMeld) {
					if ($openMeld->getKind() === 3) {
						$pickedUp = end($openMeld->getTiles());
					}
				}
			} else {
				$pickedUp = end($this->tileHandler->getDiscardByWind($this->turn));
			}
			$this->win($this->winByDiscardDeclared, $pickedUp);
			$this->winByDiscardDeclared = array(null, null, null, null);
		}
	}

	private function win($winds, $pickedUp = null) {
		$this->setTurnStatus(null);
		$this->whatToDo();
		$data = array();
		$isAllChombo = true;

		for ($h = 0; $h < 4; $h++) {
			$i = ($this->turn + $h) % 4;
			if ($winds[$i]) {

				/* --- フリテンチェック --- */
				$isSacredDiscard = false;
				if (!is_null($pickedUp)) {
					if ($this->isSacredDiscard($i)) {
						$isSacredDiscard = true;
					} else {
						foreach ($this->tileHandler->getDiscardByWind($i) as $tile) {
							if (in_array($tile->getKind(), $this->waitedTiles[$i], true)) {
								$isSacredDiscard = true;
								break;
							}
						}
					}
				}
				/* --- フリテンチェック --- */

				if ($isSacredDiscard) {
					$yaku = 0;
				} else {
					$yaku = $this->tileHandler->getYaku($i, $pickedUp);
				}

				$adiffs = array(0, 0, 0, 0);

				if ($yaku === 0 || $yaku === false || $yaku['han'] === 0) {
					echo "yaku:";
					var_dump($yaku);
					$single = -2000;
					$double = -4000;
					if ($yaku === 0) {
						$chomboStatus = 0;
					} elseif ($yaku === false) {
						if (is_null($pickedUp)) {
							$chomboStatus = 3;
						} else {
							$chomboStatus = 1;
						}
					} elseif ($yaku === 0) {
						$chomboStatus = 2;
					}
					$yaku = array('han' => 0, 'basicPoints' => -2000, 'chomboStatus' => $chomboStatus);
					$pickedUp = null;
				} else {
					if ($isAllChombo && !isset($chomboStatus)) {
						$isAllChombo = false;
						if (is_null($pickedUp)) {
							$adiffs = array_fill(0, 4, -$this->game->getCounters() * 100);
						} else {
							$adiffs[$this->turn] -= $this->game->getCounters() * 300;
						}
					}
					$single = ceil($yaku['basicPoints'] / 100) * 100;
					$double = ceil($yaku['basicPoints'] / 50) * 100;
					$quadruple = ceil($yaku['basicPoints'] / 25) * 100;
					$sextuple = ceil($yaku['basicPoints'] * 6 / 100) * 100;
				}
				if (is_null($pickedUp)) { // ツモ和了
					if ($i === 0) {
						$diffs = array($double * 3, -$double, -$double, -$double);
					} else {
						$diffs = array(-$double, -$single, -$single, -$single);
					}
				} else { //ロン和了
					if ($i === 0) {
						$diffs = array($sextuple, 0, 0, 0);
						$diffs[$this->turn] = -$sextuple;
					} else {
						$diffs = array(0, 0, 0, 0);
						$diffs[$this->turn] = -$quadruple;
					}
				}

				$diffs[$i] = 0;
				$adiffs[$i] = 0;

				$formerPoints[$i] = $this->game->getPlayerHandler()->getPlayerByWind($i)->getPoints();
				for ($j = 0; $j < 4; $j++) {
					if ($i !== $j) {
						$formerPoints[$j] = $this->game->getPlayerHandler()->getPlayerByWind($j)->getPoints();
						$diffs[$i] -= ($diffs[$j] = -$this->game->getPlayerHandler()->transferPoints(-$diffs[$j], $j, $i));
						$adiffs[$i] -= ($adiffs[$j] = -$this->game->getPlayerHandler()->transferPoints(-$adiffs[$j], $j, $i));
					}
				}

				$adiffs[$i] += $this->game->transferDeposits($i);

				$data[$i] = array(
					'wind' => $i,
					'hand' => $this->tileHandler->getHandByWind($i),
					'openMelds' => $this->tileHandler->getOpenMeldsByWind($i),
					'yaku' => $yaku,
					'points' => (int) ceil($yaku['basicPoints'] * ($i === 0 ? 6 : 4) / 100) * 100,
					'formerPoints' => $formerPoints,
					'diffs' => $diffs,
					'adiffs' => $adiffs
				);


				for ($i = 0; $i < 4; $i++) {
					echo $formerPoints[$i] . ' + ' . $diffs[$i] . ' + ' . $adiffs[$i] . ' = ' . ($formerPoints[$i] + $diffs[$i] + $adiffs[$i]) . "\n";
				}
			}
		}

		$this->game->sendAll('agari', $data);
		if ($isAllChombo) {
			$this->game->finishHand(true, false);
		} else {
			$this->game->finishHand($winds[0], $this->game->getRound() === 0 && $winds[0]);
		}
	}

	public function getTurnStatus() {
		return $this->turnStatus;
	}

	/**
	 *
	 * @param int $status
	 *
	 * 0 : ツモ後 打牌待ち
	 * 1 : 打牌後 ツモ・鳴き待ち
	 * 2 : 鳴き宣言後 牌選択待ち
	 * 3 : チー後 打牌待ち
	 * 4 : ポン後 打牌待ち
	 * 5 : 立直宣言後 打牌待ち
	 * 6 : 他家ロン宣言後
	 * 7 : 加槓後 打牌待ち
	 */
	public function setTurnStatus($status) {
		$this->turnStatus = $status;
		echo "Turn status set: $status\n";
	}

	public function getWhatToDo() {
		return $this->whatToDo;
	}

	/**
	 *
	 * @return array
	 */
	public function whatToDo() {
		if (is_null($this->tileHandler)) {
			return;
		}

		$this->availableCallings = array(array(), array(), array(), array());

		$turn = $this->getTurn();

		if (is_null($this->getTurnStatus())) {
			$whatToDo = array_fill(0, 4, array());
		} elseif (($this->getTurnStatus() === 0) || ($this->getTurnStatus() === 3) || ($this->getTurnStatus() === 4)) {
			$whatToDo = array_fill(0, 4, array());
			if ($this->getTurnStatus() === 0) {
				$whatToDo[$turn][] = 'winByDraw';
				if (!$this->isReady($turn)) {
					$whatToDo[$turn][] = 'discard';

					if ($this->game->getPlayerHandler()->getPlayerByWind($turn)->getPoints() >= 1000 && $this->tileHandler->isClosed($turn) && ($this->tileHandler->getWallNumber() >= 4)) { // 門前かつ山牌4枚以上
						$whatToDo[$turn][] = 'declareReady';
					}
					/* --- 九種九牌 --- */
					if (($this->tileHandler->getOpenMeldsByWind(0) === array()) && ($this->tileHandler->getOpenMeldsByWind(1) === array()) && ($this->tileHandler->getOpenMeldsByWind(2) === array()) && ($this->tileHandler->getOpenMeldsByWind(3) === array()) && ($this->tileHandler->getDiscardByWind(3) === array())) {
						$terminalHonorKinds = array(1, 9, 11, 19, 21, 29, 31, 33, 35, 37, 41, 43, 45);
						foreach ($this->getTileHandler()->getHandByWind($turn) as $tile) {
							if (in_array($tile->getKind(), $terminalHonorKinds, true)) {
								$terminalHonorKinds = array_diff($terminalHonorKinds, array($tile->getKind()));
							}
						}
						if (count($terminalHonorKinds) <= 4) {
							$whatToDo[$turn][] = 'kyushukyuhai';
						}
					}
					/* --- 九種九牌 --- */
				} else {
					$whatToDo[$turn][] = 'discardOnlyDrawn';
				}
			} else {
				$whatToDo[$turn][] = 'discard';
			}

			if ($this->tileHandler->getWallNumber() > 0) {
				$handTiles = $this->tileHandler->getHandByWind($turn);

				/* --- 加槓 --- */
				if (!$this->isReady($turn)) {
					$openTripletKinds = array_keys($this->tileHandler->getOpenTriplets($turn));
					$availableKinds = array();
					foreach ($handTiles as $tile) {
						if (in_array($tile->getKind(), $openTripletKinds, true) && !in_array($tile->getKind(), $availableKinds, true)) {
							$availableKinds[] = $tile->getKind();
						}
					}
					if ($this->getTurnStatus() === 4) {
						$availableKinds = array_diff($availableKinds, array(end($openTripletKinds)));
					}
					if (count($availableKinds) !== 0) {
						$this->availableCallings[$turn][3] = $availableKinds;
						$whatToDo[$turn][] = 'kakan';
					}
				}
				/* --- 加槓 --- */

				/* --- 暗槓 --- */
				$quads = $this->tileHandler->getDuplicativeTiles($turn)[4];
				if ($quads) {
					if ($this->isReady($turn)) {
						$availableClosedQuad = array();
						$hand = array();
						foreach ($handTiles as $tile) {
							$hand[] = $tile->getKind();
						}
						foreach (array_keys($quads) as $kind) {
							$temp = array_diff($hand, array($kind, $kind, $kind, $kind));
							$newWaitedTiles = $this->tileHandler->checkTempai($temp, true);
							var_dump($this->waitedTiles[$turn]);
							echo "から\n";
							var_dump($newWaitedTiles);
							echo "になるで\n";
							if ($newWaitedTiles === $this->waitedTiles[$turn]) {
								$availableClosedQuad[] = $kind;
							} else {
								echo "待ち変わるから $kind の暗槓アカンで\n";
							}
						}
						if ($availableClosedQuad) {
							$this->availableCallings[$turn][4] = $availableClosedQuad;
							$whatToDo[$turn][] = 'ankan';
						}
					} else {
						$this->availableCallings[$turn][4] = array_keys($quads);
						$whatToDo[$turn][] = 'ankan';
					}
				}
				/* --- 暗槓 --- */
			}
		} elseif ($this->getTurnStatus() === 1) {
			$whatToDo = array_fill(0, 4, array('winByDiscard'));

			if ($this->tileHandler->getWallNumber() > 0) {
				$discards = $this->tileHandler->getDiscardByWind($turn);
				$lastDiscard = end($discards);

				for ($i = 0; $i < 4; $i++) {
					if (!$this->isReady($i)) {
						$duplicates = $this->tileHandler->getDuplicativeTiles($i);
						if (($i !== $turn) && array_key_exists($lastDiscard->getKind(), $duplicates[3])) {
							$whatToDo[$i][] = 'daiminkan';
							$this->availableCallings[$i][2] = $lastDiscard->getKind();
							$whatToDo[$i][] = 'pon';
							$this->availableCallings[$i][1] = $lastDiscard->getKind();
						} elseif (($i !== $turn) && array_key_exists($lastDiscard->getKind(), $duplicates[2])) {
							$whatToDo[$i][] = 'pon';
							$this->availableCallings[$i][1] = $lastDiscard->getKind();
						}
					}
				}

				$sequencableTiles = $this->tileHandler->getSequencableTiles(($turn + 1) % 4, $lastDiscard->getKind());
				if (!$this->isReady(($turn + 1) % 4) && $sequencableTiles) {
					$this->availableCallings[($turn + 1) % 4][0] = $sequencableTiles;
					$whatToDo[($turn + 1) % 4][] = 'chi';
				}

				$whatToDo[($turn + 1) % 4][] = 'draw';
			}
			$whatToDo[$turn] = array();
		} elseif ($this->getTurnStatus() === 2) {
			$whatToDo = array_fill(0, 4, array());
			$whatToDo[$turn][] = 'selectTilesForCalling';
			$whatToDo[$turn][] = 'cancelCalling';
		} elseif ($this->getTurnStatus() === 5) {
			$whatToDo = array_fill(0, 4, array());
			$whatToDo[$turn] = array('discard');
		} elseif ($this->getTurnStatus() === 6) {
			$whatToDo = array(array(), array(), array(), array());
			for ($i = 0; $i < 4; $i++) {
				if (is_null($this->winByDiscardDeclared[$i])) {
					$whatToDo[$i] = array('winByDiscard');
				}
			}
		} elseif ($this->getTurnStatus() === 7) {
			for ($i = 0; $i < 4; $i++) {
				if (!$this->notChankanDeclared[$i]) {
					$whatToDo[$i] = array('winByDiscard');
				} else {
					$whatToDo[$i] = array();
				}
			}
		}

		$this->whatToDo = $whatToDo;
		for ($i = 0; $i < 4; $i++) {
			$this->game->sendByWind('whatToDo', array('whatToDo' => $whatToDo[$i], 'availableCallings' => $this->availableCallings[$i]), $i);
		}
	}

	public function __destruct() {
		echo "H";
	}

}
