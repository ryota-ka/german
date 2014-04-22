<?php

namespace WebSocket\Application;

class UserRoomHandler {

	private $app;
	private $users = array();
	private $games = array();
	private $chatLogs = array();
	private $roomList = array(0, 405, 403, 402, 401, 305, 303, 302, 301, 205, 203, 202, 201, 205, 103, 102, 101);

	public function __construct(GermanApplication $app) {
		$this->app = $app;
	}

	/**
	 *
	 * @param type $clientId
	 * @return User
	 */
	public function getUserByClientId($clientId) {
		if (isset($this->users[$clientId])) {
			return $this->users[$clientId];
		} else {
			return false;
		}
	}

	/**
	 *
	 * @param type $clientId
	 * @return string
	 */
	public function getNameByClientId($clientId) {
		if (($user = $this->getUserByClientId($clientId)) !== false) {
			return $user->getName();
		} else {
			return false;
		}
	}

	/**
	 *
	 * @param type $clientId
	 * @return type
	 */
	public function getRoomByClientId($clientId) {
		if (($user = $this->getUserByClientId($clientId)) !== false) {
			return $user->getRoom();
		} else {
			return false;
		}
	}

	/**
	 *
	 * @param type $roomNumber
	 * @return type
	 */
	public function getClientIdsByRoom($room) {
		$cliendIds = array();
		foreach ($this->users as $user) {
			if ($user->getRoom() === $room) {
				$cliendIds[] = $user->getClientId();
			}
		}
		return $cliendIds;
	}

	/**
	 *
	 * @param type $room
	 * @return User[]
	 */
	public function getUsersByRoom($room) {
		$users = array();
		foreach ($this->users as $user) {
			if ($user->getRoom() === $room) {
				$users[] = $user;
			}
		}
		return $users;
	}

	/**
	 *
	 * @param type $room
	 * @return type
	 */
	public function getPlayerClientIdsByRoom($room) {
		$cliendIds = array();
		foreach ($this->users as $user) {
			if (($user->getRoom() === $room) && ($user->isObserver() === false)) {
				$cliendIds[] = $user->getClientId();
			}
		}
		return $cliendIds;
	}

	/**
	 *
	 * @param type $room
	 * @return User[]
	 */
	public function getPlayerObjectsByRoom($room) {
		$users = array();
		foreach ($this->users as $user) {
			if (($user->getRoom() === $room) && ($user->isObserver() === false)) {
				$users[] = $user;
			}
		}
		return $users;
	}

	/**
	 *
	 * @param type $room
	 * @return type
	 */
	public function getObserverClientIdsByRoom($room) {
		$cliendIds = array();
		foreach ($this->users as $user) {
			if (($user->getRoom() === $room) && ($user->isObserver() === true)) {
				$cliendIds[] = $user->getClientId();
			}
		}
		return $cliendIds;
	}

	/**
	 *
	 * @param type $room
	 * @return User[]
	 */
	public function getObserverObjectsByRoom($room) {
		$users = array();
		foreach ($this->users as $user) {
			if (($user->getRoom() === $room) && ($user->isObserver() === true)) {
				$users[] = $user;
			}
		}
		return $users;
	}

	/**
	 *
	 * @param type $clientId
	 * @return Game
	 */
	public function getGameByClientId($clientId) {
		$room = $this->getRoomByClientId($clientId);
		if (isset($this->games[$room])) {
			return $this->games[$room];
		} else {
			return false;
		}
	}

	/**
	 *
	 * @param type $room
	 * @return Game
	 */
	public function getGameByRoomNumber($room) {
		if (isset($this->games[$room])) {
			return $this->games[$room];
		} else {
			return false;
		}
	}

	/**
	 *
	 * @param type $clientId
	 */
	public function addUser($clientId, $name = null) {
		$this->users[$clientId] = new Human($this->app, $this, $clientId, $name);
		$this->setRoom($clientId, 0);
		$this->sendPopulation();

		// info: client connected
	}

	/**
	 *
	 * @param type $clientId
	 */
	public function removeUser($clientId) {
		// info: client disconnected
		if (isset($this->users[$clientId])) {
			$user = $this->getUserByClientId($clientId);
			if (($user->getRoom() !== 0) && isset($this->games[$user->getRoom()]) && (!$user->isObserver())) {
				$room = $user->getRoom();
				unset($this->users[$clientId]);
				$this->users[$clientId] = new CP\DrawDiscardMachine($this->app, $this, $clientId, $room);
				$players = $this->getPlayerObjectsByRoom($room);
				$deleteKeys = array();
				$shouldBeDeletedAll = true;
				foreach ($players as $value) {
					if ($value instanceof CP\ComputerPlayer) {
						$deleteKeys[] = $value->getClientId();
					} else {
						$shouldBeDeletedAll = false;
						break;
					}
				}
				if ($shouldBeDeletedAll) {
					$this->clearGame($room);
				} elseif ($this->users[$clientId]->getGame()) {
					$this->games[$room]->succeed($this->games[$room]->getPlayerHandler()->getPlayerByClientId($clientId)->getWind());
				}
			} else {
				unset($this->users[$clientId]);
			}
			$this->sendPopulation();
		}
	}

	public function addComputerPlayer($room, $type = null) {
		if (($room !== 0) && in_array($room, $this->roomList, true) && count($this->getClientIdsByRoom($room) < 4)) {
			$clientId = md5($room . $type . microtime());
			$this->users[$clientId] = new CP\DrawDiscardMachine($this->app, $this, $clientId, $room);
			//$this->users[$clientId] = new CP\ThirteenOrphans($this->app, $this, $clientId, $room);
			//$this->users[$clientId] = new CP\NicoNico($this->app, $this, $clientId, $room);
			$this->sendPopulation();
			$this->checkGameStart($room);
		}
	}

	public function reconnect($reconnectionId, $clientId) {

	}

	/**
	 *
	 * @param type $clientId
	 * @param type $roomNumber
	 */
	public function setRoom($clientId, $room) {
		if (($user = $this->getUserByClientId($clientId)) !== false) {
			if ((string) (int) $room === $room) {
				$room = (int) $room;
			}
			if (in_array($room, $this->roomList, true) && $this->getGameByRoomNumber($user->getRoom()) === false) {
				$oldRoom = $user->getRoom();
				$user->setRoom($room);
				$this->sendPopulation();
				$this->sendChatLog($clientId, $room);
				$this->sendInfoToRoomMembers($room, $user->getName() . 'さんが入室しました');
				$this->sendInfoToRoomMembers($oldRoom, $user->getName() . 'さんが退室しました');
				$this->checkGameStart($room);
				if ($room === 101) {
					for ($i = 0; $i < 3; $i++) {
						$this->addComputerPlayer(101);
					}
				}
				return true;
			} else {
				// error: no such room
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 *
	 * @param type $clientId
	 * @param type $name
	 */
	public function setName($clientId, $name) {
		$user = $this->getUserByClientId($clientId);
		if ($user) {
			$oldName = $user->getName();
			$user->setName($name);
			$this->sendInfoToRoomMembers($user->getRoom(), $oldName . ' が ' . $name . ' に名前を変更しました');
		}
	}

	/**
	 *
	 * @param type $clientId
	 * @return boolean
	 */
	public function toggleObserver($clientId) {
		if (($user = $this->getUserByClientId($clientId)) !== false) {
			$user->toggleObserver();
			if ($user->isObserver() === false) {
				$this->checkGameStart($user->getRoom());
			}
			$this->sendPopulation();
			return true;
		} else {
			return false;
		}
	}

	/**
	 *
	 * @param type $room
	 * @param type $action
	 * @param type $data
	 */
	public function sendToRoomMembers($room, $action, $data) {
		if (($users = $this->getUsersByRoom($room)) !== array()) {
			foreach ($users as $user) {
				$user->sendToMe($action, $data);
			}
		}
	}

	/**
	 *
	 * @param int $room
	 * @param string $message
	 */
	public function sendInfoToRoomMembers($room, $message) {
		$this->sendToRoomMembers($room, 'info', $message);
		$this->addChatLog($room, 0, 'info', $message);
	}

	/**
	 *
	 * @param type $room
	 */
	public function checkGameStart($room) {
		if (($room !== 0) && (count($this->getPlayerClientIdsByRoom($room)) === 4)) {
			if (isset($this->games[$room])) {
				$this->games[$room]->destruct();
				unset($this->games[$room]);
			}
			$this->games[$room] = new Game($this->app, $this, $room);
			$this->games[$room]->start();
		}
	}

	public function clearGame($room) {
		if (isset($this->games[$room])) {
			foreach ($this->getClientIdsByRoom($room) as $clientId) {
				if ($this->users[$clientId] instanceof Human) {
					$this->setRoom($clientId, 0);
				} else {
					unset($this->users[$clientId]);
				}
			}
			$this->games[$room]->destruct();
			unset($this->games[$room]);
		}
	}

	public function finishGame($room) {
		if (isset($this->games[$room])) {
			// @todo ゲーム終了処理 結果表示など

			$data = array();
			for ($i = 0; $i < 4; $i++) {
				$player = $this->games[$room]->getPlayerHandler()->getPlayerByWind($i);

				$data[$i] = array(
					'name' => $this->getUserByClientId($player->getClientId())->getName(),
					'points' => $player->getPoints()
				);
			}
			$data_ = array();
			for ($i = 0; $i < 4 && $data; $i++) {
				$values = array();
				$keys = array();
				$rank = 5 - count($data);
				foreach ($data as $value) {
					$values[] = $value['points'];
				}
				foreach ($data as $key => $value) {
					if ($value['points'] === max($values)) {
						$keys[] = $key;
					}
				}
				foreach ($keys as $key) {
					$data[$key]['rank'] = $rank;
					$data_[] = $data[$key];
					unset($data[$key]);
				}
			}
			var_dump($data_);
			$this->games[$room]->sendAll('finishGame', $data_);
			$this->clearGame($room);
		}
	}

	public function sendPopulation() {
		foreach ($this->roomList as $roomNumber) {
			$population[$roomNumber] = array();
			$types[$roomNumber] = array();
		}
		foreach ($this->users as $user) {
			$room = $user->getRoom();
			if ($room === 0) {
				$population[0][] = array('name' => $user->getName(), 'type' => 0);
				$types[$room][] = 0;
			} elseif ($user instanceof CP\ComputerPlayer) {
				$population[$room][] = array('name' => $user->getName(), 'type' => 2);
				$types[$room][] = 2;
			} else {
				if ($user->isObserver()) {
					$population[$room][] = array('name' => $user->getName(), 'type' => 1);
					$types[$room][] = 1;
				} else {
					$population[$room][] = array('name' => $user->getName(), 'type' => 0);
					$types[$room][] = 0;
				}
			}
		}
		foreach ($population as $key => $value) {
			array_multisort($types[$key], SORT_ASC, $population[$key]);
		}
		$this->app->getMessenger()->sendAll('population', $population);
	}

	public function addChatLog($room, $clientId, $name, $message) {
		if (!isset($this->chatLogs[$room])) {
			$this->chatLogs[$room] = array();
		} elseif (count($this->chatLogs[$room]) >= 50) {
			array_shift($this->chatLogs[$room]);
		}
		array_push($this->chatLogs[$room], array(
			'clientId' => $clientId,
			'name' => $name,
			'message' => $message
		));
	}

	public function sendChatLog($clientId, $room) {
		if (isset($this->chatLogs[$room])) {
			$this->getUserByClientId($clientId)->sendToMe('chatLog', $this->chatLogs[$room]);
		}
	}

}
