<?php

namespace WebSocket\Application\CP;

class ThirteenOrphans extends ComputerPlayer {

	public function __construct(\WebSocket\Application\GermanApplication $app, \WebSocket\Application\UserRoomHandler $userRoomHandler, $clientId, $room) {
		parent::__construct($app, $userRoomHandler, $clientId, $room);
	}

	public function checkRon() {
		return true;
	}

	protected function chooseDiscard() {
		if ($this->isChunchan($this->getDrawnTileKind())) {
			return $this->getDrawnTileId();
		} else {
			foreach ($this->getHandTiles() as $tile) {
				if ($this->isChunchan($tile->kind)) {
					return $tile->id;
				}
			}
			$max = 0;
			$maxKey = 0;
			foreach ($this->getHandTilesArray() as $key => $value) {
				if ($max < $value) {
					$max = $value;
					$maxKey = $key;
				}
			}
			return $this->kindToId($maxKey);
		}
	}

}
