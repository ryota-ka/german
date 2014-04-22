<?php

namespace WebSocket\Application\CP;

class NicoNico extends ComputerPlayer {

	public function __construct(\WebSocket\Application\GermanApplication $app, \WebSocket\Application\UserRoomHandler $userRoomHandler, $clientId, $room) {
		parent::__construct($app, $userRoomHandler, $clientId, $room);
	}

	protected function chooseDiscard() {
		$handTiles = $this->getHandTilesArray();
		$myDiscards = $this->getDiscardedKinds($this->myWind);
		foreach ($handTiles as $kind => $number) {
			if (in_array($kind, $myDiscards, true) && $number === 1) {
				return $this->kindToId($kind);
			}
			if ($number === 3) {
				return $this->kindToId($kind);
			}
		}
		// この辺りからは他人の河とかを見て判断したい
		shuffle($handTiles);
		foreach ($handTiles as $kind => $number) {
			if ($number === 1) {
				return $this->kindToId($kind);
			}
		}
		return $this->getDrawnTileId();
	}

}
