<?php

namespace WebSocket\Application;

class TileHandler {

	private $app;
	private $userRoomHandler;
	private $game;
	private $hand;
	private $tiles = array();
	private $wall = array();
	private $deadWall = array();
	private $doraIndicators = array();
	private $underneathDoraIndicators = array();
	private $supplementalTiles = array();
	private $hands = array(array(), array(), array(), array());
	private $discards = array(array(), array(), array(), array());
	private $openMelds = array(array(), array(), array(), array());
	//private $kinds = array(1, 1, 1, 1, 2, 2, 2, 2, 3, 3, 3, 3, 4, 4, 4, 4, 5, 5, 5, 5, 6, 6, 6, 6, 7, 7, 7, 7, 8, 8, 8, 8, 9, 9, 9, 9, 11, 11, 11, 11, 12, 12, 12, 12, 13, 13, 13, 13, 14, 14, 14, 14, 15, 15, 15, 15, 16, 16, 16, 16, 17, 17, 17, 17, 18, 18, 18, 18, 19, 19, 19, 19, 21, 21, 21, 21, 22, 22, 22, 22, 23, 23, 23, 23, 24, 24, 24, 24, 25, 25, 25, 25, 26, 26, 26, 26, 27, 27, 27, 27, 28, 28, 28, 28, 29, 29, 29, 29, 31, 31, 31, 31, 33, 33, 33, 33, 35, 35, 35, 35, 37, 37, 37, 37, 41, 41, 41, 41, 43, 43, 43, 43, 45, 45, 45, 45);
	private $isClosed = array(true, true, true, true);
	private $isNagashiManganAvailable = array(true, true, true, true);
	private $isDeadWallDrawAvailable = false;
	//private $kinds = array(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 11, 19, 19, 21, 21, 21, 21, 22, 22, 22, 22, 23, 23, 23, 23, 24, 24, 24, 24, 25, 25, 25, 25, 26, 26, 26, 26, 27, 27, 27, 27, 28, 28, 28, 28, 29, 29, 29, 29, 31, 31, 31, 31, 33, 33, 33, 33, 35, 35, 35, 35, 37, 37, 37, 37, 41, 41, 41, 41, 43, 43, 43, 43, 45, 45, 45, 45);
	private $kinds = array(1, 1, 1, 1, 2, 2, 2, 2, 3, 3, 3, 3, 4, 4, 4, 4, 5, 5, 5, 5, 6, 6, 6, 6, 7, 7, 7, 7, 8, 8, 8, 8, 9, 9, 9, 9, 1, 1, 1, 1, 2, 2, 2, 2, 3, 3, 3, 3, 4, 4, 4, 4, 5, 5, 5, 5, 6, 6, 6, 6, 7, 7, 7, 7, 8, 8, 8, 8, 9, 9, 9, 9, 1, 1, 1, 1, 2, 2, 2, 2, 3, 3, 3, 3, 4, 4, 4, 4, 5, 5, 5, 5, 6, 6, 6, 6, 7, 7, 7, 7, 8, 8, 8, 8, 9, 9, 9, 9, 31, 31, 31, 31, 33, 33, 33, 33, 35, 35, 35, 35, 37, 37, 37, 37, 41, 41, 41, 41, 43, 43, 43, 43, 45, 45, 45, 45);

	//private $kinds = array(31, 31, 31, 31, 31, 31, 19, 19, 19, 19, 19, 19, 19, 19, 4, 4, 19, 11, 11, 11, 11, 11, 6, 6, 7, 11, 11, 7, 21, 21, 21, 21, 9, 9, 9, 9, 21, 31, 31, 31, 21, 21, 29, 29, 31, 31, 31, 31, 29, 29, 29, 29, 29, 29, 29, 29, 29, 29, 33, 33, 33, 33, 33, 33, 33, 33, 33, 8, 9, 9, 9, 9, 31, 31, 31, 31, 35, 35, 35, 35, 35, 37, 37, 37, 37, 37, 37, 37, 41, 41, 41, 41, 41, 41, 41, 43, 43, 43, 43, 43, 43, 43, 43, 43, 45, 45, 45, 45, 45, 45, 45, 45, 33, 33, 33, 33, 35, 35, 35, 35, 37, 37, 37, 37, 41, 41, 41, 41, 43, 43, 43, 43, 45, 45, 45, 45);

	public function __construct(GermanApplication $app, UserRoomHandler $userRoomHandler, Game $game, Hand $hand) {
		$this->app = $app;
		$this->userRoomHandler = $userRoomHandler;
		$this->game = $game;
		$this->hand = $hand;
	}

	public function init() {
		shuffle($this->kinds);

		$dices = $this->hand->getDices();
		$diceSum = $dices[0] + $dices[1];
		$offset = (512 - 32 * $diceSum) % 136;
		$slicedTiles = array_slice($this->kinds, $offset);
		array_splice($this->kinds, $offset);
		$this->kinds = array_merge($this->kinds, $slicedTiles);

		for ($i = 0; $i < 136; $i++) {
			$this->tiles[] = new Tile($i, $this->kinds[$i]);
		}
		$this->wall = array_slice($this->tiles, 0, 122);

		// dead wall として扱うよう調整
		$this->supplementalTiles = array($this->tiles[134], $this->tiles[135], $this->tiles[132], $this->tiles[133]);
		$this->doraIndicators[] = $this->tiles[130];
		$this->underneathDoraIndicators[] = $this->tiles[131];


		for ($i = 0; $i < 3; $i++) { // 3回繰り返す
			for ($j = 0; $j < 4; $j++) { // 4人のプレイヤーが取る
				for ($k = 0; $k < 4; $k++) { // 4枚取る
					$this->draw($j);
				}
			}
		}
		for ($i = 0; $i < 4; $i++) { // 最後は1枚ずつ
			$this->draw($i);
			$this->sendHand($i);
			$this->hand->checkWaitedTiles($i);
		}

		$this->sendDiscards();
		$this->sendOpenMelds();

		$this->game->sendAll('doraIndicators', $this->doraIndicators);

		$this->hand->setTurn(0);
		$this->hand->setTurnStatus(1);
		$this->draw(0); //親の第1ツモ
	}

	public function addToHand(Tile $tile, $wind) {
		if (in_array($wind, array(0, 1, 2, 3))) {
			array_push($this->hands[$wind], $tile);
		} else {
			// error
		}
	}

	/**
	 *
	 * @param type $wind
	 * @return type
	 */
	public function draw($wind = null) {
		if ($this->getWallNumber()) {
			if (is_null($wind)) {
				$wind = ($this->hand->getTurn() + 1) % 4;
			}
			if (in_array($wind, array(0, 1, 2, 3), true)) {
				$tile = array_shift($this->wall);
				array_push($this->hands[$wind], $tile);
				if (!is_null($this->hand->getTurnStatus())) {
					$this->hand->completeReady();
					$this->game->getPlayerHandler()->getPlayerByWind($wind)->sendToMe('drawTile', $tile);
					$this->hand->setTurn($wind);
					$this->hand->resetOverlooking($wind);
					$this->hand->setTurnStatus(0);
					$this->game->sendAll('wallNumber', $this->getWallNumber());
					$this->hand->whatToDo();
				}
			}
		}
	}

	/**
	 *
	 * @param int $wind
	 * @return Tile[]
	 */
	public function getHandByWind($wind) {
		if (in_array($wind, array(0, 1, 2, 3), true)) {
			return $this->hands[$wind];
		} else {
			// error
		}
	}

	/**
	 *
	 * @param type $wind
	 * @return OpenMeld[]
	 */
	public function getOpenMeldsByWind($wind) {
		if (in_array($wind, array(0, 1, 2, 3), true)) {
			return $this->openMelds[$wind];
		}
	}

	/**
	 *
	 * @param int $wind
	 * @return Tile
	 */
	public function getDiscardByWind($wind) {
		if (in_array($wind, array(0, 1, 2, 3), true)) {
			return $this->discards[$wind];
		}
	}

	public function drawSupplementalTile() {
		$count = count($this->supplementalTiles);
		if ($count === 1) {
			foreach ($this->openMelds[$this->hand->getTurn()] as $openMeld) {
				if ($openMeld->getKind() < 2) {
					$this->hand->abortiveDraw(3); //四開槓
					return;
				}
			}
		}
		if (count($this->supplementalTiles) === 1) { // 四開槓判定
			$flag = false;
			for ($i = 0; $i < 4; $i++) {
				foreach ($this->openMelds[$i] as $openMeld) {
					if ($openMeld->getKind() >= 2) {
						if ($flag) {
							$this->hand->abortiveDraw(3);
							return;
						} else {
							$flag = true;
							continue 2;
						}
					}
				}
			}
		}
		array_push($this->hands[$this->hand->getTurn()], $tile = array_shift($this->supplementalTiles));
		array_unshift($this->deadWall, array_pop($this->wall));
		$this->game->getPlayerHandler()->getPlayerByWind($this->hand->getTurn())->sendToMe('drawTile', $tile);
		$this->hand->setTurnStatus(0);
		$this->game->sendAll('wallNumber', $this->getWallNumber());
		$this->isDeadWallDrawAvailable = true;
		$this->doraIndicators[] = $this->tiles[134 - 2 * $count];
		$this->underneathDoraIndicators[] = $this->tiles[135 - 2 * $count];
		$this->game->sendAll('doraIndicators', $this->doraIndicators);
		$this->hand->whatToDo();
		return;
	}

	public function getDoraIndicators() {
		return;
	}

	public function discard($wind, $id) {
		foreach ($this->hands[$wind] as $key => $tile) {
			if ($tile->getId() === $id) {
				$lastDrawnTileKind = end($this->hands[$wind])->getKind();
				$kind = $tile->getKind();
				array_push($this->discards[$wind], $this->hands[$wind][$key]);
				unset($this->hands[$wind][$key]);

				if ($this->isNagashiManganAvailable[$wind]) {
					(bool) $this->isNagashiManganAvailable[$wind] &= in_array($tile->getKind(), array(1, 9, 11, 19, 21, 29, 31, 33, 35, 37, 41, 43, 45), true);
				}

				if ((count($this->discards[0]) === 1) && (count($this->discards[1]) === 1) && (count($this->discards[2]) === 1) && (count($this->discards[3]) === 1) && (count($this->openMelds[0]) === 0) && (count($this->openMelds[1]) === 0) && (count($this->openMelds[2]) === 0) && (count($this->openMelds[3]) === 0)) {
					$kind = reset($this->discards[3])->getKind();
					for ($i = 0; $i < 3; $i++) {
						if (reset($this->discards[$i])->getKind() !== $kind) {
							break;
						}
						if (($i === 2) && in_array($kind, array(31, 33, 35, 37), true)) {
							$this->hand->abortiveDraw(1); //四風子連打
							return;
						}
					}
				}
				echo $this->getWallNumber() . " tiles are remaining in the wall\n";
				$this->hand->setTurnStatus(1);


				$this->hand->disableOneShot($wind);
				$this->isDeadWallDrawAvailable = false;
				$this->hand->checkOverlooking($kind);
				if ($kind !== $lastDrawnTileKind) {
					$this->hand->checkWaitedTiles($wind);
				}
				$this->sendHand($wind);
				$this->sendDiscards();
				$this->hand->whatToDo();
				if ($this->getWallNumber() === 0) {
					$this->game->sendAll('discardedLastTile', 0);
					$clientId = $this->game->getPlayerHandler()->getPlayerByWind($wind)->getClientId();
					$this->app->getMessenger()->processData('game/winByDiscard', false, $clientId);
				}
				return;
			}
		}
		echo "Hand : $wind\n";
		var_dump($this->hands[$wind]);
		echo "id\n";
		var_dump($id);
		echo "No such tile in hand\n";
	}

	public function getWallNumber() {
		return count($this->wall);
	}

	public function addOpenMeld($wind, $kind, $tileIds, $isRon = false) {
		$tiles = array();

		switch ($kind) {
			case 0:
			case 1:
			case 2:
				if ($kind <= 2) {
					$tiles[] = array_pop($this->discards[$this->hand->getTurn()]);
				}

				foreach ($this->hands[$wind] as $key => $tile) {
					if (in_array($tile->getId(), $tileIds, true)) {
						$tiles[] = $this->hands[$wind][$key];
						unset($this->hands[$wind][$key]);
					}
				}

				$openMeld = new OpenMeld($kind, $tiles, $wind, $this->hand->getTurn());
				$this->openMelds[$wind][] = $openMeld;

				$this->sendDiscards();
				break;

			case 3:
				$tileId = reset($tileIds);
				foreach ($this->hands[$wind] as $key => $value) {
					if ($value->getId() === $tileId) {
						$addedTile = $this->hands[$wind][$key];
						foreach ($this->openMelds[$wind] as $key_ => $value_) {
							if (($value_->getKind() === 1) && (reset($value_->getTiles())->getKind() === $addedTile->getKind())) {
								$this->openMelds[$wind][$key_]->addedQuad($addedTile);
								unset($this->hands[$wind][$key]);
								break;
							}
						}
						break;
					}
				}
				break;

			case 4:
				foreach ($this->hands[$wind] as $key => $tile) {
					if (in_array($tile->getId(), $tileIds, true)) {
						$tiles[] = $this->hands[$wind][$key];
						unset($this->hands[$wind][$key]);
					}
				}
				$openMeld = new OpenMeld(4, $tiles, $wind);
				$this->openMelds[$wind][] = $openMeld;
				break;
		}

		if (!$isRon && ($kind !== 4)) {
			$this->isClosed[$wind] = false;
			$this->isNagashiManganAvailable[$this->hand->getTurn()] = false;
		}

		$this->hand->completeReady();
		$this->hand->disableOneShot();
		$this->sendHand($wind);
		$this->sendOpenMelds();
	}

	/**
	 *
	 * @param type $wind
	 * @param type $kind
	 * @return array
	 */
	public function getSequencableTiles($wind, $kind) {
		$seq = array();
		if ($kind < 30) {
			$tiles = array();
			foreach ($this->getHandByWind($wind) as $tile) {
				$tiles[$tile->getId()] = $tile->getKind();
			}

			$seq['right'] = array(array_search($kind - 1, $tiles, true), array_search($kind - 2, $tiles, true));
			$seq['center'] = array(array_search($kind - 1, $tiles, true), array_search($kind + 1, $tiles, true));
			$seq['left'] = array(array_search($kind + 1, $tiles, true), array_search($kind + 2, $tiles, true));

			foreach ($seq as $key => $value) {
				if (in_array(false, $value, true)) {
					unset($seq[$key]);
				}
			}
		}
		return $seq;
	}

	public function getDuplicativeTiles($wind) {
		if (in_array($wind, array(0, 1, 2, 3), true)) {
			$duplicates = array(
				1 => array(),
				2 => array(),
				3 => array(),
				4 => array(),
			);
			foreach ($this->hands[$wind] as $tile) {
				if (array_key_exists($tile->getKind(), $duplicates[3])) {
					$duplicates[4][$tile->getKind()] = $duplicates[3][$tile->getKind()];
					$duplicates[4][$tile->getKind()][] = $tile->getId();
				} elseif (array_key_exists($tile->getKind(), $duplicates[2])) {
					$duplicates[3][$tile->getKind()] = $duplicates[2][$tile->getKind()];
					$duplicates[3][$tile->getKind()][] = $tile->getId();
				} elseif (array_key_exists($tile->getKind(), $duplicates[1])) {
					$duplicates[2][$tile->getKind()] = $duplicates[1][$tile->getKind()];
					$duplicates[2][$tile->getKind()][] = $tile->getId();
				} else {
					$duplicates[1][$tile->getKind()] = array($tile->getId());
				}
			}
			return $duplicates;
		}
	}

	/**
	 *
	 * @param type $wind
	 * @return OpenMeld[]
	 */
	public function getOpenTriplets($wind) {
		if (in_array($wind, array(0, 1, 2, 3), true)) {
			$availables = array();
			foreach ($this->openMelds[$wind] as $openMeld) {
				if ($openMeld->getKind() === 1) {
					$tile = reset($openMeld->getTiles());
					$availables[$tile->getKind()] = $openMeld;
				}
			}
			return $availables;
		}
	}

	/**
	 *
	 * @param type $wind
	 * @return boolean
	 */
	public function isClosed($wind) {
		return $this->isClosed[$wind];
	}

	public function isNagashiManganAvailable($wind) {
		return $this->isNagashiManganAvailable[$wind];
	}

	public function sendHand($wind) {
		$this->hands[$wind] = array_merge($this->hands[$wind]);
		$this->game->sendByWind('hand', $this->hands[$wind], $wind);
	}

	public function sendOpenMelds() {
		for ($i = 0; $i < 4; $i++) {
			$this->openMelds[$i] = array_merge($this->openMelds[$i]);
		}
		$this->game->sendAll('openMelds', $this->openMelds);
	}

	public function sendDiscards() {
		$turns = $this->hand->getReadyDeclaredTurn();
		for ($i = 0; $i < 4; $i++) {
			$this->discards[$i] = array_merge($this->discards[$i]);
			if ($this->hand->isReadyDeclared($i)) {
				$turns[$i] = count($this->discards[$i]) - 1;
			}
		}
		$data = $this->discards;
		$data[4] = $turns;
		$this->game->sendAll('discards', $data);
	}

	public function getYaku($wind, $pickedUp = null) {
		$hand = $this->hands[$wind];
		$openMelds = $this->openMelds[$wind];

		$tiles = array_fill(0, 46, 0);
		$allTiles = array_fill(0, 46, 0);
		$sqAbbr = array(null, 0, 1, 2, 3, 4, 5, 6, null, null, null, 7, 8, 9, 10, 11, 12, 13, null, null, null, 14, 15, 16, 17, 18, 19, 20);
		$abbr = array(null, 0, 1, 2, 3, 4, 5, 6, 7, 8, null, 9, 10, 11, 12, 13, 14, 15, 16, 17, null, 18, 19, 20, 21, 22, 23, 24, 25, 26, null, 27, null, 28, null, 29, null, 30, null, null, null, 31, null, 32, null, 33);
		$doraList = array(null, 2, 3, 4, 5, 6, 7, 8, 9, 1, null, 12, 13, 14, 15, 16, 17, 18, 19, 11, null, 22, 23, 24, 25, 26, 27, 28, 29, 21, null, 33, null, 35, null, 37, null, 31, null, null, null, 43, null, 45, null, 41);

		$quads = 0;
		$closedTripletsDef = 0;
		$proposals = array();

		foreach ($hand as $tile) {
			$tiles[$tile->getKind()] ++;
			$allTiles[$tile->getKind()] ++;
		}

		$sqDef = array_fill(0, 21, 0);
		$trDef = array_fill(0, 34, 0);

		foreach ($openMelds as $openMeld) {
			if ($openMeld->getKind() === 0) {
				$min = 100;
				foreach ($openMeld->getTiles() as $tile) {
					if ($tile->getKind() < $min) {
						$min = $tile->getKind();
					}
					$allTiles[$tile->getKind()] ++;
				}
				$sqDef[$sqAbbr[$min]] ++;
			} else {
				$openMeldTiles = $openMeld->getTiles();
				$kind = reset($openMeldTiles)->getKind();
				$allTiles[$kind] += 3;
				$trDef[$abbr[$kind]] ++;

				if ($openMeld->getKind() >= 2) {
					$quads++;
					$allTiles[$kind] ++;
					if ($openMeld->getKind() === 4) {
						$closedTripletsDef++;
					}
				}
			}
		}

		if (is_null($pickedUp)) {
			$waitedTile = end($hand)->getKind();
		} else {
			$waitedTile = $pickedUp->getKind();
			$tiles[$waitedTile] ++;
			$allTiles[$waitedTile] ++;
		}

		$pairs = array();
		foreach ($tiles as $key => $value) {
			if ($value >= 2) {
				$pairs[] = $key;
			}
		}

		if ($tiles[1] && $tiles[9] && $tiles[11] && $tiles[19] && $tiles[21] && $tiles[29] && $tiles[31] && $tiles[33] && $tiles[35] && $tiles[37] && $tiles[41] && $tiles[43] && $tiles[45] && (($tiles[1] + $tiles[9] + $tiles[11] + $tiles[19] + $tiles[21] + $tiles[29] + $tiles[31] + $tiles[33] + $tiles[35] + $tiles[37] + $tiles[41] + $tiles[43] + $tiles[45]) == 14)) {
			echo "国士無双\n";
			$thirteenOrphans = array('yakuman' => array_fill(0, 13, false), 'yaku' => array_fill(0, 33, 0), 'fu' => 0);
			$thirteenOrphans['yakuman'][0] = true;
		} elseif (count($pairs) === 7) {
			echo "七対子\n";
			$sevenPairs = $this->ResolveYakuSevenPairs($pairs);
			$sevenPairs['fu'] = 25;
		}
		foreach ($pairs as $pair) {
			$sqWaitingStyle = array(false, false, false, false, false);
			$trWaitingStyle = array(false, false, false, false, false);
			$mxWaitingStyle = array(false, false, false, false, false);
			// array(単騎, 双碰, 嵌張, 辺張, 両面)

			if ($waitedTile === $pair) { // 単騎待ち
				$sqWaitingStyle[0] = true;
				$trWaitingStyle[0] = true;
				$mxWaitingStyle[0] = true;
			}

			/* --- 順子優先処理 --- */

			$tempSq = $tiles;
			$tempSq[$pair] -= 2;

			$sqSq = $sqDef;
			$sqTr = $trDef;

			$closedTripletsSq = $closedTripletsDef;

			while ($result = $this->getSequenceFromHand($tempSq)) {
				$tempSq = $result[0];
				$sqSq[$sqAbbr[$result[1]]] ++;

				if ($waitedTile - 1 === $result[1]) {
					$sqWaitingStyle[2] = true;
				} elseif ((($result[1] % 10 === 1) && ($waitedTile - 2 === $result[1])) || (($result[1] % 10 === 7) && ($waitedTile === $result[1]))) {
					$sqWaitingStyle[3] = true;
				} elseif (($waitedTile === $result[1]) || ($waitedTile === $result[1] + 2)) {
					$sqWaitingStyle[4] = true;
				}
			}

			while ($result = $this->getTripletFromHand($tempSq)) {
				$closedTripletsSq++;
				$tempSq = $result[0];
				$sqTr[$abbr[$result[1]]] ++;
				if ($waitedTile === $result[1]) {
					$sqWaitingStyle[1] = true;
				}
			}

			if (((int) implode($tempSq)) === 0) {
				echo "sq 和了\n";

				$tempProposal = $this->resolveYaku($sqSq, $sqTr, $abbr[$pair], $wind);
				for ($i = 0; $i < 5; $i++) {
					if ($sqWaitingStyle[$i]) {
						$proposal = $tempProposal;
						$proposal['fu'] = $this->getFu($wind, $abbr[$pair], $sqTr, $i, $openMelds, is_null($pickedUp));

						if ($this->isClosed($wind)) {
							if (is_null($pickedUp) && ($proposal['fu'] === 22)) { // ピンヅモ
								$proposal['yaku'][4] = 1;
								echo "平和\n";
								$proposal['fu'] = 20;
							} elseif (!is_null($pickedUp) && ($proposal['fu'] === 30)) {
								echo "平和\n";

								$proposal['yaku'][4] = 1;
							}
						} elseif ($proposal['fu'] === 20) { // 喰い平和形
							$proposal['fu'] = 30;
						}

						if (($i === 1) && (!is_null($pickedUp))) { //双碰待ちかつロン和了のときは明刻扱い
							$proposal['fu'] -= in_array($waitedTile, array(1, 9, 11, 19, 21, 29, 31, 33, 35, 37, 41, 43, 45), true) ? 4 : 2;
							$closedTripletsSq--;
						}
						if ($closedTripletsSq === 4) {
							$proposal['yakuman'][1] = true;
						} elseif ($closedTripletsSq === 3) {
							$proposal['yaku'][22] = 2;
						}

						$proposals[] = $proposal;
					}
				}
			}

			/* --- 順子優先処理 --- */

			/* --- 刻子優先処理 --- */
			$tempTr = $tiles;
			$tempTr[$pair] -= 2;

			$trSq = $sqDef;
			$trTr = $trDef;

			$closedTripletsTr = $closedTripletsDef;

			while ($result = $this->getTripletFromHand($tempTr)) {
				$tempTr = $result[0];
				$trTr[$abbr[$result[1]]] ++;
				if ($waitedTile === $result[1]) {
					$trWaitingStyle[1] = true;
				}
			}

			while ($result = $this->getSequenceFromHand($tempTr)) {
				$tempTr = $result[0];
				$trSq[$sqAbbr[$result[1]]] ++;

				if ($waitedTile - 1 === $result[1]) {
					$trWaitingStyle[2] = true;
				} elseif ((($result[1] % 10 === 1) && ($waitedTile - 2 === $result[1])) || (($result[1] % 10 === 7) && ($waitedTile === $result[1]))) {
					$trWaitingStyle[3] = true;
				} elseif (($waitedTile === $result[1]) || ($waitedTile === $result[1] + 2)) {
					$trWaitingStyle[4] = true;
				}
			}

			if ((((int) implode($tempTr)) === 0) && (($sqSq !== $trSq) || ($sqTr !== $trTr) || ($sqWaitingStyle !== $trWaitingStyle))) {
				echo "tr 和了\n";

				$tempProposal = $this->resolveYaku($trSq, $trTr, $abbr[$pair], $wind);
				for ($i = 0; $i < 5; $i++) {
					if ($trWaitingStyle[$i]) {
						$proposal = $tempProposal;
						$proposal['fu'] = $this->getFu($wind, $abbr[$pair], $trTr, $i, $openMelds, is_null($pickedUp));

						if (($i === 1) && (!is_null($pickedUp))) { //双碰待ちかつロン和了のときは明刻扱い
							$proposal['fu'] -= in_array($waitedTile, array(1, 9, 11, 19, 21, 29, 31, 33, 35, 37, 41, 43, 45), true) ? 4 : 2;
							$closedTripletsTr--;
						}
						if ($closedTripletsTr === 4) {
							$proposal['yakuman'][1] = true;
						} elseif ($closedTripletsTr === 3) {
							$proposal['yaku'][22] = 2;
						}

						$proposals[] = $proposal;
					}
				}
			}
			/* --- 刻子優先処理 --- */


			/* --- 刻子1つ → 順子 --- */
			if (((int) implode($tempSq) !== 0) && ((int) implode($tempTr) !== 0)) {
				$tempMx = $tiles;
				$tempMx[$pair] -= 2;

				$mxSq = $sqDef;
				$mxTr = $trDef;

				$closedTripletsMx = $closedTripletsDef;

				$result = $this->getTripletFromHand($tempMx);

				if ($result !== false) {
					$tempMx = $result[0];
					$mxTr[$abbr[$result[1]]] ++;

					if ($waitedTile === $result[1]) {
						$mxWaitingStyle[1] = true;
					}

					while ($result = $this->getSequenceFromHand($tempMx)) {
						$tempMx = $result[0];
						$mxSq[$sqAbbr[$result[1]]] ++;

						if ($waitedTile - 1 === $result[1]) {
							$mxWaitingStyle[2] = true;
						} elseif ((($result[1] % 10 === 1) && ($waitedTile - 2 === $result[1])) || (($result[1] % 10 === 7) && ($waitedTile === $result[1]))) {
							$mxWaitingStyle[3] = true;
						} elseif (($waitedTile === $result[1]) || ($waitedTile === $result[1] + 2)) {
							$mxWaitingStyle[4] = true;
						}
					}

					if (((int) implode($tempMx)) === 0) {
						echo "mx 和了\n";

						$tempProposal = $this->resolveYaku($mxSq, $mxTr, $abbr[$pair], $wind);
						for ($i = 0; $i < 5; $i++) {
							if ($mxWaitingStyle[$i]) {
								$proposal = $tempProposal;
								$proposal['fu'] = $this->getFu($wind, $abbr[$pair], $mxTr, $i, $openMelds, is_null($pickedUp));

								if (($i === 1) && (!is_null($pickedUp))) { //双碰待ちかつロン和了のときは明刻扱い
									$proposal['fu'] -= in_array($waitedTile, array(1, 9, 11, 19, 21, 29, 31, 33, 35, 37, 41, 43, 45), true) ? 4 : 2;
									$closedTripletsMx--;
								}
								if ($closedTripletsMx === 4) {
									$proposal['yakuman'][1] = true;
								} elseif ($closedTripletsMx === 3) {
									$proposal['yaku'][22] = 2;
								}

								$proposals[] = $proposal;
							}
						}
					}
				}
			}
			/* --- 刻子1つ → 順子 --- */


			foreach ($proposals as $agariKey => $agariValue) {
				if ($quads === 4) {
					echo "四槓子\n";
					$proposals[$agariKey]['yakuman'][8] = true;
				} elseif ($quads === 3) {
					echo "三槓子\n";
					$proposals[$agariKey]['yaku'][25] = 2;
				}
				if ($this->isClosed($wind) && $proposals[$agariKey]['yaku'][31]) { /// チンイツかつ門前
					for ($i = 0; $i < 3; $i++) {
						if (($tiles[10 * $i + 1] >= 3) && ($tiles[10 * $i + 2] >= 1) && ($tiles[10 * $i + 3] >= 1) && ($tiles[10 * $i + 4] >= 1) && ($tiles[10 * $i + 5] >= 1) && ($tiles[10 * $i + 6] >= 1) && ($tiles[10 * $i + 7] >= 1) && ($tiles[10 * $i + 8] >= 1) && ($tiles[10 * $i + 9] >= 3)) {
							echo "九蓮宝燈\n";
							$proposals[$agariKey]['yakuman'][9] = true;
						}
					}
				}
			}
		}

		if (isset($thirteenOrphans)) {
			$proposals[] = $thirteenOrphans;
		} elseif (isset($sevenPairs)) {
			$proposals[] = $sevenPairs;
		}

		if ($proposals) {
			$maxYakuman = 0;
			$maxBasicPoint = 0;

			foreach ($proposals as $key => $value) {
				echo "\n--------役だよ---------\n";
				echo "役満 : " . array_sum($value['yakuman']) . "\n";
				echo array_sum($value['yaku']) . "飜 {$value['fu']}符\n\n";
				$yakumanSum = array_sum($value['yakuman']);
				if ($yakumanSum > $maxYakuman) {
					$maxYakuman = $yakumanSum;
					$maxKey = $key;
				}
				if (!$maxYakuman) {
					if ($value['fu'] !== 25) {
						$proposals[$key]['fu'] = ceil($value['fu'] / 10) * 10;
					}
					$basicPoints = $this->game->calculateBasicPoints(array_sum($value['yaku']), $value['fu']);
					echo "基本点 [$key] : $basicPoints\n";
					if (($basicPoints > $maxBasicPoint) || (($basicPoints === $maxBasicPoint) && (array_sum($value['yaku']) > array_sum($proposals[$maxKey]['yaku'])))) {
						$maxKey = $key;
						$maxBasicPoint = $basicPoints;
					}
				}
			}

			$agari = $proposals[$maxKey];
			echo "\n\nmaxKey : $maxKey\n\n";

			if (array_sum($agari['yakuman']) === 0) {
				if (!is_null($this->hand->getReadyDeclaredTurn()[$wind])) {
					if ($this->hand->getReadyDeclaredTurn()[$wind] === 0) {
						echo "ダブル立直\n";
						$agari['yaku'][27] = 2;
					} else {
						echo "立直\n";
						$agari['yaku'][0] = 1;
					}
					if ($this->hand->isOneShotAvailable($wind)) {
						echo "一発\n";
						$agari['yaku'][1] = 1;
					}
					foreach ($this->underneathDoraIndicators as $underneathDoraIndicator) {
						foreach ($allTiles as $kind => $number) {
							if ($number && ($kind === $doraList[$underneathDoraIndicator->getKind()])) {
								echo "裏ドラ $number 枚($kind)\n";
								$agari['yaku'][32] += $number;
							}
						}
					}
					if ($this->hand->getNotChankanDeclaredPlayers() !== array(null, null, null, null)) {
						$agari['yaku'][14] = 1;
					}
				}
			}

			if (is_null($pickedUp)) { // ツモ和了のとき
				if (array_sum($agari['yakuman']) === 0) {

					if ($this->isClosed($wind)) {
						echo "門前清自摸和\n";
						$agari['yaku'][2] = 1;
						if ($this->getWallNumber() === 0) {
							echo "海底摸月\n";
							$agari['yaku'][15] = 1;
						}
					}
					if ($this->isDeadWallDrawAvailable) {
						echo "嶺上開花\n";
						$agari['yaku'][13] = 1;
					}
				}
				if (count($this->discards[$wind]) === 0) {
					if (($wind === 0) && (count($this->openMelds[0]) === 0)) {
						echo "天和\n";
						$agari['yakuman'][10] = true;
					} elseif ((count($this->openMelds[0]) === 0) && (count($this->openMelds[1]) === 0) && (count($this->openMelds[2]) === 0) && (count($this->openMelds[3]) === 0)) {
						echo "地和\n";
						$agari['yakuman'][11] = true;
					}
				}
			} else { // ロン和了のとき
				if ($this->getWallNumber() === 0) {
					echo "河底撈魚\n";
					$agari['yaku'][16] = 1;
				}
				if ((count($this->discards[$wind]) === 0) && (count($this->openMelds[0]) === 0) && (count($this->openMelds[1]) === 0) && (count($this->openMelds[2]) === 0) && (count($this->openMelds[3]) === 0)) {
					echo "人和\n";
					$agari['yakuman'][12] = true;
				}
			}
			if (array_sum($agari['yakuman']) === 0) {
				if (array_sum($agari['yaku'])) {
					foreach ($this->doraIndicators as $doraIndicator) {
						foreach ($allTiles as $kind => $number) {
							if ($number && ($kind === $doraList[$doraIndicator->getKind()])) {
								echo "ドラ $number 枚($kind)\n";
								$agari['yaku'][32] += $number;
							}
						}
					}
				}
			}
			$yakumanSum = array_sum($agari['yakuman']);
			if ($yakumanSum) {
				echo "\n最終結果 : 役満 " . (32000 * $yakumanSum) . "点\n";
				$agari['han'] = -$yakumanSum;
				$agari['basicPoints'] = 8000 * $yakumanSum;
			} else {
				echo "\n最終結果 : " . array_sum($agari['yaku']) . "飜 {$agari['fu']}符 " . $this->game->calculateBasicPoints(array_sum($agari['yaku']), $agari['fu']) * 4 . "点\n";
				$agari['han'] = array_sum($agari['yaku']);
				$agari['basicPoints'] = $this->game->calculateBasicPoints(array_sum($agari['yaku']), $agari['fu']);
			}
			echo "----------------終わり NHK----------------\n\n\n";
			return $agari;
		} else {
			return false;
		}
	}

	public function resolveYaku(Array $sq, Array $tr, $pair, $wind) {
		$yaku = array_fill(0, 33, 0);
		$yakuman = array_fill(0, 13, false);
		$isClosed = $this->isClosed($wind);
		$isOpen = (int) (!$isClosed);

		$sqStr = implode($sq);
		$trStr = implode($tr);
		$sqStrs = str_split($sqStr, 7);
		$trStrs = str_split($trStr, 9);
		$sqStrReplaced = str_replace('4', '1', str_replace('3', '1', str_replace('2', '1', $sqStr)));
		$sqStrsReplaced = str_split($sqStrReplaced, 7);
		$sqDec = bindec($sqStrReplaced);
		$sqDecs = array(bindec($sqStrsReplaced[0]), bindec($sqStrsReplaced[1]), bindec($sqStrsReplaced[2]));
		$trDecs = array(bindec($trStrs[0]), bindec($trStrs[1]), bindec($trStrs[2]), bindec($trStrs[3]));

		echo "\npair : \n";
		var_dump($pair);
		echo "\nsqStr : \n";
		var_dump($sqStr);
		echo "\ntrStr : \n";
		var_dump($trStr);
		echo "\nsqStrs : \n";
		var_dump($sqStrs);
		echo "\ntrStrs : \n";
		var_dump($trStrs);
		echo "\nsqDec : \n";
		var_dump($sqDec);
		echo "\nsqDecs : \n";
		var_dump($sqDecs);
		echo "\ntrDecs : \n";
		var_dump($trDecs);


		if (!in_array($pair, array(0, 8, 9, 17, 18, 26, 27, 28, 29, 30, 31, 32, 33), true)) { // 雀頭が中張牌
			if (oreq($sqDec, 0b011111001111100111110) && oreq($trDecs[0], 0b011111110) && oreq($trDecs[1], 0b011111110) && oreq($trDecs[2], 0b011111110) && oreq($trDecs[3], 0b000000000)) {
				echo "断么九\n";
				$yaku[3] = 1;
			}
		}

		if ($tr[27]) {
			if ($wind === 0) {
				echo "東\n";
				$yaku[6] ++;
			}
			if ($this->game->getRound() <= 4) {
				echo "東\n";
				$yaku[6] ++;
			}
		}if ($tr[28]) {
			if ($wind === 1) {
				echo "南\n";
				$yaku[7] ++;
			}
			if ($this->game->getRound() >= 5) {
				echo "南\n";
				$yaku[7] ++;
			}
		}
		if (($wind === 2) && $tr[29]) {
			echo "西\n";
			$yaku[8] ++;
		} elseif (($wind === 3) && $tr[30]) {
			echo "北\n";
			$yaku[9] ++;
		}
		if ($tr[31]) {
			echo "白\n";
			$yaku[10] = 1;
		}
		if ($tr[32]) {
			echo "發\n";
			$yaku[11] = 1;
		}
		if ($tr[33]) {
			echo "中\n";
			$yaku[12] = 1;
		}

		if ($isClosed) {
			$offset = strpos($sqStr, '2');
			$offset_ = strpos($sqStr, '2', $offset + 1);
			if ($offset_ !== false) {
				echo "二盃口\n";
				$yaku[30] = 3;
			} elseif ($offset !== false) {
				echo "一盃口\n";
				$yaku[5] = 1;
			}
		}


		for ($i = 0; $i < 3; $i++) {
			if ($sq[$i * 7] && $sq[$i * 7 + 3] && $sq[$i * 7 + 6]) {
				echo "一気通貫\n";
				$yaku[18] = 2 - $isOpen;
			}
		}

		if ((oreq($sqDec, 0b111111100000000000000) && ($trDecs[1] === 0) && ($trDecs[2] === 0) && in_array($pair, array(0, 1, 2, 3, 4, 5, 6, 7, 8, 27, 28, 29, 30, 31, 32, 33), true)) || (oreq($sqDec, 0b000000011111110000000) && ($trDecs[0] === 0) && ($trDecs[2] === 0) && in_array($pair, array(9, 10, 11, 12, 13, 14, 15, 16, 17, 27, 28, 29, 30, 31, 32, 33), true)) || (oreq($sqDec, 0b000000000000001111111) && ($trDecs[0] === 0) && ($trDecs[1] === 0) && in_array($pair, array(18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33), true))) {
			if (oreq($sqDec, 0b000000000000000100000) && ($trDecs[0] === 0) && ($trDecs[1] === 0) && oreq($trDecs[2], 0b011101010) && oreq($trDecs[3], 0b0000010)) {
				echo "緑一色";
				$yakuman[6] = true;
			} elseif (($trDecs[3] === 0) && !in_array($pair, array(27, 28, 29, 30, 31, 32, 33), true)) {
				echo "清一色\n";
				$yaku[31] = 6 - $isOpen;
			} else {
				echo "混一色\n";
				$yaku[28] = 3 - $isOpen;
			}
		}

		for ($i = 0; $i < 9; $i++) {
			if ($i < 7) {
				if ($sq[$i] && $sq[$i + 7] && $sq[$i + 14]) {
					echo "三色同順\n";
					$yaku[17] = 2 - $isOpen;
					break;
				}
			}
			if ($tr[$i] && $tr[$i + 9] && $tr[$i + 18]) {
				echo "三色同刻\n";
				$yaku[24] = 2;
				break;
			}
		}

		if ((($tr[31] + $tr[32] + $tr[33]) >= 2) && in_array($pair, array(31, 32, 33), true)) {
			echo "小三元\n";
			$yaku[26] = 2;
		}

		if (oreq($sqDec, 0b100000110000011000001) && oreq($trDecs[0], 0b100000001) && oreq($trDecs[1], 0b100000001) && oreq($trDecs[2], 0b100000001) && in_array($pair, array(0, 8, 9, 17, 18, 26, 27, 28, 29, 30, 31, 32, 33), true)) {
			if (($trDecs[3] === 0) && in_array($pair, array(0, 8, 9, 17, 18, 26))) {
				echo "純全帯么九\n";
				$yaku[29] = 3 - $isOpen;
			} else {
				echo "混全帯么九\n";
				$yaku[19] = 2 - $isOpen;
			}
		}

		if ($sqDec === 0) {
			echo "対々和\n";
			$yaku[21] = 2;

			if (oreq($trDecs[0], 0b100000001) && oreq($trDecs[1], 0b100000001) && oreq($trDecs[2], 0b100000001) && in_array($pair, array(0, 8, 9, 17, 18, 26, 27, 28, 29, 30, 31, 32, 33), true)) {
				if (($trDecs[3] === 0) && in_array($pair, array(0, 8, 9, 17, 18, 26))) {
					echo "清老頭\n";
					$yakuman[7] = true;
				} else {
					echo "混老頭\n";
					$yaku[23] = 2;
				}
			}

			if (!($trDecs[0] || $trDecs[1] || $trDecs[2])) {
				echo "字一色\n";
				$yakuman[3] = true;
			}
			if ($tr[27] && $tr[28] && $tr[29] && $tr[30]) {
				echo "大四喜\n";
				$yakuman[5] = true;
			}
			if ((($tr[27] + $tr[28] + $tr[29] + $tr[30]) >= 3) && in_array($pair, array(27, 28, 29, 30), true)) {
				echo "小四喜\n";
				$yakuman[4] = true;
			}
		}

		if ($tr[31] && $tr[32] && $tr[33]) {
			echo "大三元\n";
			$yakuman[2] = true;
		}

		return array('yakuman' => $yakuman, 'yaku' => $yaku);
	}

	public function ResolveYakuSevenPairs($pairs) {
		$yaku = array_fill(0, 33, 0);
		$yakuman = array_fill(0, 13, false);

		$yaku[20] = 2;

		$group = array();
		$isTerminal = array();
		$isWindDragon = array();
		$isYaochu = array();

		for ($i = 0; $i < 7; $i++) {
			$group[$i] = (int) floor($pairs[$i] / 10);
			if ($group[$i] === 4) {
				$group[$i] = 3;
			}

			$isTerminal[$i] = in_array($pairs[$i], array(1, 9, 11, 19, 21, 29), true);
			$isWindDragon[$i] = in_array($pairs[$i], array(31, 33, 35, 37, 41, 43, 45), true);
			$isYaochu[$i] = $isTerminal[$i] || $isWindDragon[$i];
		}

		$uniqueGroup = array_unique($group);

		if (!in_array(false, $isYaochu, true)) {
			echo "混老頭\n";
			$yaku[23] = 2;
		}
		if (!in_array(false, $isWindDragon, true)) {
			echo "字一色\n";
			$yakuman[3] = true;
		} elseif (count($uniqueGroup) === 1) {
			echo "清一色\n";
			$yaku[31] = 6;
		} elseif ((count($uniqueGroup) === 2) && (in_array(true, $isWindDragon, true))) {
			echo "混一色\n";
			$yaku[28] = 3;
		}
		if (!in_array(true, $isYaochu, true)) {
			echo "断么九\n";
			$yaku[3] = 1;
		}
		return array('yakuman' => $yakuman, 'yaku' => $yaku);
	}

	public function getFu($wind, $pair, $triplets, $waitingStyle, $openMelds, $isDrawn) {
		$fu = 20;

		// 雀頭
		if (in_array($pair, array(31, 32, 33, (($this->game->getRound() - 1) % 4) + 27, $wind + 27))) {
			$fu += 2;
		}

		// 待ち
		if (in_array($waitingStyle, array(0, 2, 3))) {
			$fu += 2;
		}

		// 面子
		foreach ($triplets as $kind => $bool) {
			if ($bool) {
				if (in_array($kind, array(0, 8, 9, 17, 18, 26, 27, 28, 29, 30, 31, 32, 33), true)) {
					$fu += 8;
				} else {
					$fu += 4;
				}
			}
		}
		foreach ($openMelds as $openMeld) {
			$tile = reset($openMeld->getTiles())->getKind();
			$isYaochu = in_array($tile, array(1, 9, 11, 19, 21, 29, 31, 33, 35, 37, 41, 43, 45), true);
			switch ($openMeld->getKind()) {
				case 1:
					if ($isYaochu) {
						$fu -= 4;
					} else {
						$fu -= 2;
					}
					break;
				case 2:
				case 3:
					if ($isYaochu) {
						$fu += 8;
					} else {
						$fu += 4;
					}
					break;
				case 4;
					if ($isYaochu) {
						$fu += 24;
					} else {
						$fu += 12;
					}
					break;
			}
		}

		if ($isDrawn) {
			$fu += 2; // 自摸符
		} elseif ($this->isClosed($wind)) {
			$fu += 10; //門前加符
		}

		return $fu;
	}

	public function getSequenceFromHand($hand) {
		for ($i = 0; $i < 3; $i++) {
			for ($j = 0; $j < 7; $j++) {
				if ($hand[$i * 10 + $j + 1] && $hand[$i * 10 + $j + 2] && $hand[$i * 10 + $j + 3]) {
					$hand[$i * 10 + $j + 1] --;
					$hand[$i * 10 + $j + 2] --;
					$hand[$i * 10 + $j + 3] --;
					$sequence = $i * 10 + $j + 1;
					return array($hand, $sequence);
				}
			}
		}
		return false;
	}

	public function getTripletFromHand($hand) {
		foreach ($hand as $key => $value) {
			if ($value >= 3) {
				$hand[$key] -= 3;
				return array($hand, $key);
			}
		}
		return false;
	}

	public function checkTempai($hand, $getWaitedTiles = false) {
		$list = array();

		if (count($hand) % 3 === 1) {
			$tilesDef = array_fill(0, 46, 0);

			foreach ($hand as $tile) {
				$tilesDef[$tile] ++;
			}

			foreach (array(1, 2, 3, 4, 5, 6, 7, 8, 9, 11, 12, 13, 14, 15, 16, 17, 18, 19, 21, 22, 23, 24, 25, 26, 27, 28, 29, 31, 33, 35, 37, 41, 43, 45) as $waitedTile) {
				$tiles = $tilesDef;
				$tiles[$waitedTile] ++;

				$pairs = array();
				foreach ($tiles as $key => $value) {
					if ($value >= 2) {
						$pairs[] = $key;
					}
				}

				if ($tiles[1] && $tiles[9] && $tiles[11] && $tiles[19] && $tiles[21] && $tiles[29] && $tiles[31] && $tiles[33] && $tiles[35] && $tiles[37] && $tiles[41] && $tiles[43] && $tiles[45] && (($tiles[1] + $tiles[9] + $tiles[11] + $tiles[19] + $tiles[21] + $tiles[29] + $tiles[31] + $tiles[33] + $tiles[35] + $tiles[37] + $tiles[41] + $tiles[43] + $tiles[45]) == 14)) {
					if ($getWaitedTiles) {
						$list[] = $waitedTile;
						continue;
					} else {
						return true;
					}
				} elseif (count($pairs) === 7) {
					if ($getWaitedTiles) {
						$list[] = $waitedTile;
						continue;
					} else {
						return true;
					}
				}
				foreach ($pairs as $pair) {
					$tempSq = $tiles;
					$tempSq[$pair] -= 2;
					while ($result = $this->getSequenceFromHand($tempSq)) {
						$tempSq = $result[0];
					}
					while ($result = $this->getTripletFromHand($tempSq)) {
						$tempSq = $result[0];
					}
					if (((int) implode($tempSq)) === 0) {
						if ($getWaitedTiles) {
							$list[] = $waitedTile;
							continue 2;
						} else {
							return true;
						}
					}
					/* --- 順子優先処理 --- */

					/* --- 刻子優先処理 --- */
					$tempTr = $tiles;
					$tempTr[$pair] -= 2;

					while ($result = $this->getTripletFromHand($tempTr)) {
						$tempTr = $result[0];
					}
					while ($result = $this->getSequenceFromHand($tempTr)) {
						$tempTr = $result[0];
					}
					if (((int) implode($tempTr)) === 0) {
						if ($getWaitedTiles) {
							$list[] = $waitedTile;
							continue 2;
						} else {
							return true;
						}
					}
					/* --- 刻子優先処理 --- */


					/* --- 刻子1つ → 順子 --- */
					$tempMx = $tiles;
					$tempMx[$pair] -= 2;

					$result = $this->getTripletFromHand($tempMx);

					if ($result !== false) {
						$tempMx = $result[0];

						while ($result = $this->getSequenceFromHand($tempMx)) {
							$tempMx = $result[0];
						}

						if (((int) implode($tempMx)) === 0) {
							if ($getWaitedTiles) {
								$list[] = $waitedTile;
								continue 2;
							} else {
								return true;
							}
						}
					}
				}
			}
		}
		if ($getWaitedTiles) {
			return $list;
		} else {
			return false;
		}
	}

	public function checkTempaiByWind($wind, $getWaitedTiles = false) {
		if (in_array($wind, array(0, 1, 2, 3), true)) {
			$hand = array();
			foreach ($this->hands[$wind] as $tile) {
				$hand[] = $tile->getKind();
			}
			return $this->checkTempai($hand, $getWaitedTiles);
		}
	}

	function hshs($kind, $wind) {
		if (in_array($kind, array(1, 2, 3, 4, 5, 6, 7, 8, 9, 11, 12, 13, 14, 15, 16, 17, 18, 19, 21, 22, 23, 24, 25, 26, 27, 28, 29, 31, 33, 35, 37, 41, 43, 45))) {
			$this->wall[0]->setKind($kind);
			$this->game->sendAll('chat', array('name' => 'info', 'message' => $this->game->getPlayerHandler()->getPlayerByWind($wind)->getUser()->getName() . "が $kind をハスハスしました"));
		}
	}

	public function __destruct() {
		echo "[.]";
	}

}
