<?php

namespace WebSocket\Application;

abstract class User implements \JsonSerializable {

	protected $app;
	protected $userRoomHandler;
	protected $clientId;
	protected $name;
	protected $room;

	/**
	 *
	 * @param \WebSocket\Application\GermanApplication $app
	 * @param \WebSocket\Application\UserRoomHandler $userRoomHandler
	 * @param type $clientId
	 */
	public function __construct(GermanApplication $app, UserRoomHandler $userRoomHandler, $clientId) {
		$this->app = $app;
		$this->userRoomHandler = $userRoomHandler;
		$this->clientId = $clientId;
		$this->room = 0;
	}

	final public function getClientId() {
		return $this->clientId;
	}

	final public function getName() {
		return $this->name;
	}

	final public function setName($name) {
		$name = trim($name);
		if (!empty($name)) {
			// info: changed name
			return ($this->name = trim($name));
		} else {
			// error: empty name
			return false;
		}
	}

	final public function getRoom() {
		return $this->room;
	}

	final public function setRoom($room) {
		$this->room = $room;
		$this->sendToMe('room', $room);
		// info: 〇〇さんが××号室に入室しました
		return;
	}

	/**
	 *
	 * @return Game
	 */
	final public function getGame() {
		return $this->userRoomHandler->getGameByRoomNumber($this->room);
	}

	abstract public function sendToMe($action, $data);

	final public function sendToRoommates($action, $data) {
		$this->userRoomHandler->sendToRoomMembers($this->getRoom(), $action, $data);
	}

	abstract public function isObserver();

	final public function jsonSerialize() {
		return array(
			'clientId' => $this->clientId,
			'name' => $this->name,
			'room' => $this->room,
		);
	}

	public function __destruct() {
		echo "U";
	}

}
