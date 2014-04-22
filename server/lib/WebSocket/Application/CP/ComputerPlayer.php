<?php

namespace WebSocket\Application\CP;

abstract class ComputerPlayer extends \WebSocket\Application\User {

	private $yaochu = array(1, 9, 11, 19, 21, 29, 31, 33, 35, 37, 41, 43, 35);
	private $chunchan = array(2, 3, 4, 5, 6, 7, 8, 12, 13, 14, 15, 16, 17, 18, 22, 23, 24, 25, 26, 27, 28);
	protected $myWind;
	protected $round;
	protected $deposits;
	protected $counter;
	protected $wantsToDo;
	private $drawnTile;
	private $lastDiscardedTile;
	protected $doraIndicators;
	protected $discardTileId;
	protected $wallNumber;
	private $handTiles;
	private $handTilesArray;
	private $isHandTilesChanged = false;
	protected $discards;
	protected $openMelds;
	private $whatToDo;
	private $availableCallings;
	private $waitedTiles = array();
	private $isReady = false;
	private $readyDiscardingKind;

	/**
	 *
	 * @param \WebSocket\Application\GermanApplication $app
	 * @param \WebSocket\Application\UserRoomHandler $userRoomHandler
	 * @param type $clientId
	 */
	public function __construct(\WebSocket\Application\GermanApplication $app, \WebSocket\Application\UserRoomHandler $userRoomHandler, $clientId, $room) {
		parent::__construct($app, $userRoomHandler, $clientId);
		$this->name = 'Computer Player';
		$this->room = $room;
	}

	final public function doSomething() {
		echo "CPwait/{$this->wantsToDo} : {$this->clientId} has been approved.\n";
		$temp = $this->wantsToDo;
		$this->wantsToDo = null;
		switch ($temp) {
			case 'draw':
				$this->send('game/draw', 0);
				break;
			case 'discard':
				if (!is_null($this->discardTileId)) {
					$this->send('game/discardedTile', array('tileId' => $this->discardTileId));
					$this->discardTileId = null;
				}
				break;
			case 'call/0':
				// 鳴く処理
				break;
			case 'call/1':
				break;
			case 'call/2':
				$this->send('game/called', 2);
				break;
			case 'call/3':
				$this->send('game/called', 3);
				break;
			case 'call/4':
				$this->send('game/called', 4);
				break;
			case 'winByDraw':
				$this->send('game/winByDraw', 0);
				break;
			case 'winByDiscard':
				$this->send('game/winByDiscard', true);
				break;
			case 'declareReady':
				$this->send('game/declareReady', 0);
				break;
			case 'notWinByDiscard':
				$this->send('game/winByDiscard', false);
				break;
			case 'kyushukyuhai':
				$this->send('game/kyushukyuhai', 0);
		}
	}

	final public function sendToMe($action, $data) {
		$data = json_decode(json_encode($data));
		//echo "Action: $action\n";
		switch ($action) {
			case 'clientConnected':
				break;
			case 'clientDisconnected':
				break;

			case 'room':
				break;

			case 'chat':
				if ($data->message === 'さいなら〜') {
					$this->send('chat/chat', 'さいなら〜');
				}
				break;

			case 'startGame':
				break;

			case 'startHand':
				$this->myWind = $data->wind;
				$this->round = $data->round;
				$this->deposits = $data->deposits;
				$this->counter = $data->counters;
				$this->isReady = false;
				break;

			case 'succession':
				$this->myWind = $data->wind;
				$this->round = $data->round;
				$this->deposits = $data->deposits;
				$this->counter = $data->counters;
				break;

			case 'doraIndicators':
				$this->doraIndicators = $data;
				break;

			case 'whatToDo':
				$this->whatToDo = $data->whatToDo;
				//$tempAc = (Array) $data->availableCallings;
				$ac = array(null, null, null, null, null);
				foreach ($data->availableCallings as $key => $value) {
					$ac[(int) $key] = $value;
				}
				$this->availableCallings = $ac;
				if (!empty($data)) {
					$this->whatToDo($this->whatToDo, $this->availableCallings);
				}
				break;

			case 'drawTile':
				$this->drawnTile = $data;
				$this->handTiles[] = $data;
				$this->handTiles = array_merge($this->handTiles, array());
				$this->isHandTilesChanged = true;
				break;

			case 'selectTilesForCalling':
				break;

			case 'hand':
				$this->handTiles = $data;
				$this->isHandTilesChanged = true;
				break;

			case 'called':
				break;

			case 'declaredReady':
				break;

			case 'wallNumber':
				$this->wallNumber = $data;
				break;

			case 'discards':
				for ($i = 0; $i < 4; $i++) {
					if (count($this->discards[$i]) + 1 === count($data[$i])) {
						$this->lastDiscardedTile = end($data[$i]);
						break;
					}
				}
				$this->discards = $data;
				break;

			case 'openMelds':
				$this->openMelds = $data;
				break;

			case 'agari':
				break;

			case 'winByDraw':
				break;

			case 'winByDiscard':
				$this->wantsToDo = 'notWinByDiscard';
				$this->cpWait(500);
				break;

			case 'discardedLastTile':
				$this->wantsToDo = 'notWinByDiscard';
				$this->cpWait(500);
				break;

			case 'exhaustiveDraw':
				break;

			case 'abortiveDraw':
				break;

			case 'CPwait':
				break;

			default:
				echo "Undefined Action : $action\n";
				break;
		}
	}

	final private function whatToDo($whatToDo, $availableCallings) {
		if (in_array('winByDraw', $whatToDo, true)) {
			if (in_array($this->getDrawnTileKind(), $this->waitedTiles, true) && $this->checkTsumo()) {
				$this->winByDraw();
				return;
			}
		} elseif (in_array('winByDiscard', $whatToDo, true)) {
			if (in_array($this->getLastDiscardedTileKind(), $this->waitedTiles, true) && $this->checkRon()) {
				$this->winByDiscard();
				return;
			}
		}
		if (in_array('discardOnlyDrawn', $whatToDo, true)) {
			$this->discard($this->getDrawnTileId());
			return;
		}
		if ($this->isReady && $this->readyDiscardingKind) {
			$this->discard($this->kindToId($this->readyDiscardingKind));
			$this->readyDiscardingKind = null;
			return;
		}

		if (in_array('kyushukyuhai', $whatToDo, true)) {
			if ($this->checkKyushuKyuhai()) {
				$this->wantsToDo = 'kyushukyuhai';
				$this->cpWait(500);
				return;
			}
		}
		/*
		  if (in_array('declareReady', $whatToDo, true)) {
		  $availableKinds = array();
		  for ($i = 0; $i < 14; $i++) {
		  $kind = $this->handTiles[$i]->kind;
		  if (!in_array($kind, $availableKinds, true) && $this->isTempaiWithout($kind)) {
		  $availableKinds[] = $kind;
		  }
		  }
		  var_dump($availableKinds);
		  if ($availableKinds && in_array($kind = $this->checkRiichi($availableKinds), $availableKinds, true)) {
		  $this->declareReady($kind);
		  return;
		  }
		  }
		 */
		if (in_array('draw', $whatToDo, true)) {
			$this->draw();
		} elseif (in_array('discard', $whatToDo, true)) {
			$this->discard($this->chooseDiscard());
		}
	}

	final private function send($action, $data) {
		$this->app->getMessenger()->processData($action, $data, $this->clientId);
	}

	final protected function say($message) {
		$this->send('chat/chat', $message);
	}

	final public function isObserver() {
		return false;
	}

	final protected function draw() {
		$this->wantsToDo = 'draw';
		$this->cpWait(2000);
	}

	final protected function getDrawnTileId() {
		return $this->drawnTile->id;
	}

	final protected function getDrawnTileKind() {
		return $this->drawnTile->kind;
	}

	final protected function getLastDiscardedTileId() {
		return $this->lastDiscardedTile->id;
	}

	final protected function getLastDiscardedTileKind() {
		return $this->lastDiscardedTile->kind;
	}

	final private function discard($id) {
		foreach ($this->handTiles as $tile) {
			if ($tile->id === $id) {
				$this->discardTileId = $id;
				break;
			}
		}
		if (is_null($this->discardTileId)) {
			$this->discardTileId = $this->getDrawnTileId();
		}
		$this->wantsToDo = 'discard';
		$this->cpWait(500);
		$hand = array();
		foreach ($this->handTiles as $tile) {
			if ($tile->id !== $id) {
				$hand[] = $tile->kind;
			}
		}
		$this->waitedTiles = $this->getGame()->getHand()->getTileHandler()->checkTempai($hand, true);
	}

	final protected function kindToId($kind) {
		if ($kind === $this->getDrawnTileKind()) {
			return $this->getDrawnTileId();
		} else {
			foreach ($this->handTiles as $tile) {
				if ($tile->kind === $kind) {
					return $tile->id;
				}
			}
			return false;
		}
	}

	final protected function call($kind) {
		if (isset($this->availableCallings[$kind])) {
			$this->wantsToDo = "call/$kind";
			$this->cpWait(($kind === 0) ? 1500 : 500);
		}
	}

	final private function winByDraw() {
		$this->wantsToDo = 'winByDraw';
		$this->cpWait(500);
	}

	final private function winByDiscard() {
		$this->wantsToDo = 'winByDiscard';
		$this->cpWait(500);
	}

	final protected function declareReady($kind) {
		$this->isReady = true;
		$this->readyDiscardingKind = $kind;
		$this->wantsToDo = 'declareReady';
		$this->cpWait(500);
	}

	final private function cpWait($delay = 1500) {
		echo "CPwait/{$this->wantsToDo} : {$this->clientId}\n";
		if ($delay < 500) {
			$delay = 500;
		}
		$this->sendToRoommates('CPwait', array('clientId' => $this->clientId, 'delay' => $delay + mt_rand(0, 200) - 100));
	}

	final protected function isYaochu($kind) {
		return in_array($kind, $this->yaochu, true);
	}

	final protected function isChunchan($kind) {
		return in_array($kind, $this->chunchan, true);
	}

	final protected function isTempai() {
		return ($this->waitedTiles !== array());
	}

	final protected function isTempaiWithout($kind) {
		$handTiles = $this->getHandTilesArray();
		foreach ($handTiles as $key => $value) {
			if ($value === $kind) {
				unset($handTiles[$key]);
				return $this->getGame()->getHand()->getTileHandler()->checkTempai($handTiles);
			}
		}
		return false;
	}

	final protected function getDiscardedKinds($wind) {
		if (in_array($wind, array(0, 1, 2, 3), true)) {
			$kinds = array();
			foreach ($this->discards[$wind] as $tile) {
				if (!in_array($tile->kind, $kinds, true)) {
					$kinds[] = $tile->kind;
				}
			}
			return $kinds;
		} else {
			return false;
		}
	}

	final protected function getWaitedTiles() {
		return $this->waitedTiles;
	}

	final protected function getHandTiles() {
		return $this->handTiles;
	}

	final protected function getHandTilesArray() {
		if ($this->isHandTilesChanged) {
			echo "HandTilesArray も更新します\n";
			foreach ($this->handTiles as $tile) {
				$this->handTilesArray[] = $tile->kind;
			}
			$this->isHandTilesChanged = false;
		}
		return $this->handTilesArray;
	}

	protected function checkTsumo() {
		return true;
	}

	protected function checkRon() {
		return true;
	}

	protected function checkRiichi($kinds) {
		return reset($kinds);
	}

	protected function checkKyushuKyuhai() {
		return true;
	}

	protected function checkChi() {
		return false;
	}

	protected function checkPon() {
		return false;
	}

	protected function checkDaiminkan() {
		return false;
	}

	protected function checkKakan() {
		return false;
	}

	protected function checkAnkan() {
		return false;
	}

	abstract protected function chooseDiscard();
}
