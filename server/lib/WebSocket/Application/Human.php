<?php

namespace WebSocket\Application;

class Human extends User {

	protected $isObserver;
	private $reconnectionId;

	/**
	 *
	 * @param \WebSocket\Application\GermanApplication $app
	 * @param \WebSocket\Application\UserRoomHandler $userRoomHandler
	 * @param type $clientId
	 */
	public function __construct(GermanApplication $app, UserRoomHandler $userRoomHandler, $clientId, $name = null) {
		parent::__construct($app, $userRoomHandler, $clientId);
		$this->name = !is_null($name) ? $name : 'Guest ' . rand(10000, 99999);
		$this->room = 0;
		$this->isObserver = false;
		$this->reconnectionId = spl_object_hash($this);
		$app->getMessenger()->send($this->clientId, 'reconnectionId', $this->reconnectionId);
	}

	/**
	 *
	 * @return boolean
	 */
	public function isObserver() {
		return $this->isObserver;
	}

	/**
	 *
	 * @return boolean
	 */
	public function toggleObserver() {
		$this->isObserver = !$this->isObserver();
		//return ($this->isObserver = !$this->isObserver());
		return $this->isObserver;
	}

	public function sendToMe($action, $data) {
		$this->app->getMessenger()->send($this->getClientId(), $action, $data);
	}

}
