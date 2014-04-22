<?php

namespace WebSocket\Application;

class Game {

	private $app;
	private $userRoomHandler;
	private $room;
	private $playerHandler;
	private $round = 1;
	private $deposits = 0;
	private $counters = 0;
	private $hand;
	private $isHako = false;

	/**
	 *
	 * @param \WebSocket\Application\GermanApplication $app
	 * @param \WebSocket\Application\UserRoomHandler $userRoomHandler
	 * @param type $room
	 */
	public function __construct(GermanApplication $app, UserRoomHandler $userRoomHandler, $room) {
		$this->app = $app;
		$this->userRoomHandler = $userRoomHandler;
		$this->room = $room;

		$this->playerHandler = new PlayerHandler($this->app, $this->userRoomHandler, $this);
	}

	public function destruct() {
		if (isset($this->hand)) {
			$this->hand->destruct();
			unset($this->hand);
		}
		if (isset($this->playerHandler)) {
			$this->playerHandler->destruct();
			unset($this->playerHandler);
		}
	}

	/**
	 *
	 * @return PlayerHandler
	 */
	public function getPlayerHandler() {
		return $this->playerHandler;
	}

	public function start() {
		$dices = array(rand(1, 6), rand(1, 6));
		$users = $this->userRoomHandler->getPlayerObjectsByRoom($this->room);
		$i = 0;
		foreach ($users as $user) {
			$user->sendToMe('startGame', array(
				'wind' => $i,
				'users' => $users,
				'dices' => $dices,
			));
			$i++;
		}

		if (isset($this->hand)) {
			$this->hand->destruct();
			unset($this->hand);
		}
		$this->hand = new Hand($this->app, $this->userRoomHandler, $this);
		$this->hand->start();
	}

	/**
	 *
	 * @return Room
	 */
	public function getRoom() {
		return $this->room;
	}

	/**
	 *
	 * @return User[]
	 */
	public function getPlayerObjects() {
		return $this->userRoomHandler->getPlayerObjectsByRoom($this->getRoom());
	}

	public function changeRound() {
		$this->round++;
	}

	/**
	 *
	 * @return Hand
	 */
	public function getHand() {
		return $this->hand;
	}

	public function getRound() {
		return $this->round;
	}

	public function getCounters() {
		return $this->counters;
	}

	public function addCounter() {
		return ++$this->counters;
	}

	public function resetCounters() {
		$this->counters = 0;
	}

	public function getDeposits() {
		return $this->deposits;
	}

	public function addDeposit() {
		$this->deposits++;
		$this->sendAll('deposits', $this->deposits);
		return $this->deposits;
	}

	public function resetDeposits() {
		$this->deposits = 0;
		$this->sendAll('deposits', 0);
	}

	public function transferDeposits($wind) {
		if ($this->getDeposits() === 0) {
			return 0;
		} else {
			if (in_array($wind, array(0, 1, 2, 3), true)) {
				$points = $this->getDeposits() * 1000;
				$this->playerHandler->getPlayerByWind($wind)->addPoints($points);
				$this->resetDeposits();
			}
			return $points;
		}
	}

	public function hako() {
		$this->isHako = true;
	}

	public function calculateBasicPoints($han, $fu) {
		if ($han >= 13) {
			$p = 8000;
		} elseif ($han >= 11) {
			$p = 6000;
		} elseif ($han >= 8) {
			$p = 4000;
		} elseif ($han >= 6) {
			$p = 3000;
		} elseif (($p = $fu * pow(2, $han) * 4) > 2000) {
			$p = 2000;
		}
		return $p;
	}

	public function finishHand($continueDealer = false, $addCounter = false) {
		if ($this->isHako || (!$continueDealer && $this->getRound() === 8)) {
			$this->userRoomHandler->finishGame($this->room);
		} else {
			if (!$continueDealer) {
				$this->changeRound();
			}
			if ($addCounter) {
				$this->addCounter();
			} else {
				$this->resetCounters();
			}
			$this->hand->destruct();
			$this->hand = new Hand($this->app, $this->userRoomHandler, $this);
			$this->hand->start();
		}
	}

	public function succeed($wind, $clientId = null) {
		if ($this->hand && in_array($wind, array(0, 1, 2, 3), true)) {
			$data = array(
				'wind' => $wind,
				'round' => $this->round,
				'deposits' => $this->getDeposits(),
				'counters' => $this->getCounters()
					// 誰が立直しているか
			);
			if (!is_null($clientId)) {
				$this->playerHandler->getPlayerByWind($wind)->setClientId($clientId);
			}
			$this->sendByWind('succession', $data, $wind);
			$this->hand->getTileHandler()->sendHand($wind);
			$this->hand->getTileHandler()->sendDiscards();
			$this->hand->getTileHandler()->sendOpenMelds();
			$this->hand->whatToDo();
		}
	}

	public function sendByWind($action, $data, $wind) {
		if (in_array($wind, array(0, 1, 2, 3), true)) {
			$this->playerHandler->getPlayerByWind($wind)->sendToMe($action, $data);
		}
	}

	public function sendExceptWind($action, $data, $wind) {
		if (in_array($wind, array(0, 1, 2, 3), true)) {
			for ($i = 0; $i < 4; $i++) {
				if ($i !== $wind) {
					$this->playerHandler->getPlayerByWind($wind)->sendToMe($action, $data);
				}
			}
		}
	}

	public function sendAll($action, $data) {
		$players = $this->playerHandler->getPlayers();
		foreach ($players as $player) {
			$player->sendToMe($action, $data);
		}
	}

	public function __destruct() {
		echo "G";
	}

}
