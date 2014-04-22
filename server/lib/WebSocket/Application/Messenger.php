<?php

namespace WebSocket\Application;

class Messenger {

	private $app;

	public function __construct(GermanApplication $app) {
		$this->app = $app;
	}

	public function send($clientIds, $action, $data) {
		$clientIds = is_array($clientIds) ? $clientIds : array($clientIds);

		foreach ($clientIds as $clientId) {
			$id = md5($clientId . microtime() . rand(0, 99999));
			$this->app->_send($id, $clientId, $action, $data);
		}
	}

	public function sendInfo($clientIds, $message) {
		$this->send($clientIds, 'info', $message);
	}

	public function sendError($clientIds, $message) {
		$this->send($clientIds, 'error', $message);
	}

	public function sendAll($action, $data) {
		$this->app->_sendAll($action, $data);
	}

	public function processData($action, $data, $clientId) {
		$exp = explode('/', $action);
		$category = $exp[0];
		$action = $exp[1];

		switch ($category) {

			case 'command':
				switch ($action) {
					case 'exit':
					case 'q':
					case 'quit':
						exit;
						break;

					case 'room':
						$this->app->getUserRoomHandler()->setRoom($clientId, $data);
						break;

					case 'name':
						$this->app->getUserRoomHandler()->setName($clientId, $data);
						break;

					case 'observer':
						$this->app->getUserRoomHandler()->toggleObserver($clientId);
						break;

					case 'cp':
						$room = $this->app->getUserRoomHandler()->getRoomByClientId($clientId);
						$this->app->getUserRoomHandler()->addComputerPlayer($room, 0);
						break;

					case 'w':
						$game = $this->app->getUserRoomHandler()->getGameByClientId($clientId);
						$game->getHand()->whatToDo();
						var_dump($game->getHand()->getWhatToDo());
						break;

					case 'hshs':
						$game = $this->app->getUserRoomHandler()->getGameByClientId($clientId);
						$wind = $game->getPlayerHandler()->getPlayerByClientId($clientId)->getWind();
						$game->getHand()->getTileHandler()->hshs($data, $wind);
						break;

					default:
						$this->sendError($clientId, 'undefined command : ' . $action);
						break;
				}
				break;

			case 'chat':
				$room = $this->app->getUserRoomHandler()->getRoomByClientId($clientId);
				$name = $this->app->getUserRoomHandler()->getNameByClientId($clientId);
				$this->app->getUserRoomHandler()->sendToRoomMembers($room, 'chat', array(
					'name' => $name,
					'message' => $data,
				));
				$this->app->getUserRoomHandler()->addChatLog($room, $clientId, $name, $data);
				break;

			case 'status':
				switch ($data) {
					case 'readyForGame':
						$roomNumber = $this->app->getUserRoomHandler()->getRoomByUser($clientId);
						$this->app->getUserRoomHandler()->getRoomByRoomNumber($roomNumber)->addReadyUser($clientId);
						break;
				}
				break;

			case 'game':
				$game = $this->app->getUserRoomHandler()->getGameByClientId($clientId);

				if ($game !== false) {
					$wind = $game->getPlayerHandler()->getPlayerByClientId($clientId)->getWind();

					switch ($action) {

						case 'discardedTile':
							$onlyDrawn = in_array('discardOnlyDrawn', $game->getHand()->getWhatToDo()[$wind], true);
							if ($onlyDrawn || in_array('discard', $game->getHand()->getWhatToDo()[$wind], true)) {
								if ($onlyDrawn) {
									if ((end($drawn = $game->getHand()->getTileHandler()->getHandByWind($wind))->getId() !== $data['tileId'])) {
										echo "ちゃう牌やないか！\n";
										break;
									}
								}
								$game->getHand()->getTileHandler()->discard($wind, $data['tileId']);
							}
							break;

						case 'draw':
							if (in_array('draw', $game->getHand()->getWhatToDo()[$wind], true)) {
								$game->getHand()->getTileHandler()->draw();
							}
							break;

						case 'called':
							$game->sendAll('called', array('wind' => $wind, 'kind' => $data));
							$game->getHand()->addCalling($wind, $data);
							$discards = $game->getHand()->getTileHandler()->getDiscardByWind($game->getHand()->getTurn());
							$lastDiscard = end($discards);
							if ($data === 0) {
								$sequencableTiles = $game->getHand()->getTileHandler()->getSequencableTiles($wind, $lastDiscard->getKind());
								if (count($sequencableTiles) === 1) { //チーのパターンが1通りしかないのなら実行
									$tileIds = reset($sequencableTiles);
									array_unshift($tileIds, $lastDiscard->getId());
									$game->getHand()->processCalling($tileIds);
								} else { // そうでないなら選択肢を与える*/
									$game->getHand()->tellToSelectTilesForCalling(array_keys($sequencableTiles));
								}
							} elseif (($data === 1) || ($data === 2)) {
								$tileIds = array();
								foreach ($game->getHand()->getTileHandler()->getHandByWind($wind) as $tile) {
									if ($tile->getKind() === $lastDiscard->getKind()) {
										$tileIds[] = $tile->getId();
										if (count($tileIds) === $data + 1) {
											$game->getHand()->processCalling($tileIds);
											break;
										}
									}
								}
							} elseif ($data === 3) {
								$triplets = $game->getHand()->getTileHandler()->getOpenTriplets($wind);
								$openTripletKinds = array_keys($triplets);
								$hand = $game->getHand()->getTileHandler()->getDuplicativeTiles($wind)[1];
								$handKinds = array_keys($hand);
								$intersect = array_intersect($openTripletKinds, $handKinds);
								if ($game->getHand()->getTurnStatus() === 4) {
									$intersect = array_diff($intersect, array(end($openTripletKinds)));
								}
								if (count($intersect) === 1) { // 加槓のパターンが1通りしかないのなら実行
									foreach ($game->getHand()->getTileHandler()->getHandByWind($wind) as $tile) {
										if ($tile->getKind() === reset($intersect)) {
											$game->getHand()->processCalling(array($tile->getId()));
											break;
										}
									}
								} else {
									$game->getHand()->tellToSelectTilesForCalling($intersect);
								}
							} elseif ($data === 4) {
								$quads = $game->getHand()->getTileHandler()->getDuplicativeTiles($wind)[4];
								if (count($quads) === 1) {
									$game->getHand()->processCalling(reset($quads));
								} else {
									$game->getHand()->tellToSelectTilesForCalling(array_keys($quads));
								}
							}
							break;

						case 'selectedTilesForCalling':
							if ($game->getHand()->getCalling() && ($game->getHand()->getCalling()->getWind() == $wind)) {
								$tileIds = array();
								switch ($game->getHand()->getCalling()->getKind()) {
									case 0:
										$discards = $game->getHand()->getTileHandler()->getDiscardByWind($game->getHand()->getTurn());
										$lastDiscard = end($discards);
										if (in_array($data, array(0, 1, 2))) {
											$tileIds = $game->getHand()->getTileHandler()->getSequencableTiles($wind, $lastDiscard->getKind())[$game->getHand()->getCalling()->getChoices()[$data]];
										}
										break;

									case 3:
										$kind = $game->getHand()->getCalling()->getChoices()[$data];
										$tileIds = $game->getHand()->getTileHandler()->getDuplicativeTiles($wind)[1][$kind];
										break;

									case 4:
										$kind = $game->getHand()->getCalling()->getChoices()[$data];
										$tileIds = $game->getHand()->getTileHandler()->getDuplicativeTiles($wind)[4][$kind];
										break;
								}
								$game->getHand()->processCalling($tileIds);
							}
							break;

						case 'declareReady':
							if (in_array('declareReady', $game->getHand()->getWhatToDo()[$wind], true)) {
								$game->getHand()->declareReady($wind);
							} else {
								echo "おまはんリーチできまへんがな\n";
							}
							break;

						case 'kyushukyuhai':
							if (in_array('kyushukyuhai', $game->getHand()->getWhatToDo()[$wind], true)) {
								$game->getHand()->abortiveDraw(0);
							} else {
								echo "おまはん么九牌九種類も持ってへんがな\n";
							}
							break;

						case 'winByDraw':
							if (in_array('winByDraw', $game->getHand()->getWhatToDo()[$wind], true)) {
								$game->getHand()->winByDraw($wind);
								$this->sendAll('winByDraw', array('wind' => $wind));
							} else {
								echo "ツモ和了なんかできるおもてんのか？\n";
							}
							break;

						case 'winByDiscard':
							if (in_array('winByDiscard', $game->getHand()->getWhatToDo()[$wind]) && is_null($game->getHand()->getWinByDiscardDeclared()[$wind])) {
								echo "Your request has been accepted : $wind\n";
								if ($data) {
									if ($game->getHand()->getTurnStatus() !== 6) {
										$game->getHand()->setTurnStatus(6);
										$game->getHand()->addWinByDiscardDeclaredPlayer($game->getHand()->getTurn(), false);
									}
									$game->sendAll('winByDiscard', array('wind' => $wind));
								}
								if ($game->getHand()->getTurnStatus() === 6) {
									$game->getHand()->addWinByDiscardDeclaredPlayer($wind, $data);
								}
								if ($game->getHand()) {
									$game->getHand()->whatToDo();
								}
							} else {
								echo "Your request cannot be accepted any more : $wind\n";
							}
							break;

						default:
							echo "Undefined action: $action\n";
							break;
					}
					break;
				}

			case 'other':
				switch ($action) {
					case 'connect':
						$this->app->getUserRoomHandler()->addUser($clientId, isset($data['name']) ? $data['name'] : null);
						break;

					case 'CPwait':
						$cp = $this->app->getUserRoomHandler()->getUserByClientId($data);
						if ($cp instanceof CP\ComputerPlayer) {
							$cp->doSomething();
						}
						break;
				}
				break;
		}
	}

}
