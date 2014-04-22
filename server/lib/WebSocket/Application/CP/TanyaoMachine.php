<?php

namespace WebSocket\Application\CP;

class Tanyao extends ComputerPlayer {

	public function __construct(\WebSocket\Application\GermanApplication $app, \WebSocket\Application\UserRoomHandler $userRoomHandler, $clientId, $room) {
		parent::__construct($app, $userRoomHandler, $clientId, $room);
	}

	protected function chooseDiscard() {
		return false;
	}

}
