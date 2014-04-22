<?php

namespace WebSocket\Application;

class Player {

	protected $app;
	protected $userRoomHandler;
	protected $game;
	protected $playerHandler;
	protected $clientId;
	protected $seat;
	protected $points = 25000;
	protected $declaredReady = false;
	protected $isReady = false;

	/**
	 *
	 * @param \WebSocket\Application\GermanApplication $app
	 * @param \WebSocket\Application\UserRoomHandler $userRoomHandler
	 * @param \WebSocket\Application\Game $game
	 * @param \WebSocket\Application\PlayerHandler $playerHandler
	 * @param type $clientId
	 * @param type $seat
	 */
	public function __construct(GermanApplication $app, UserRoomHandler $userRoomHandler, Game $game, PlayerHandler $playerHandler, $clientId, $seat) {
		$this->app = $app;
		$this->userRoomHandler = $userRoomHandler;
		$this->game = $game;
		$this->playerHandler = $playerHandler;
		$this->clientId = $clientId;
		$this->seat = $seat;
	}

	public function init() {
		$this->declaredReady = false;
		$this->isReady = false;
	}

	/**
	 *
	 * @return string
	 */
	public function getClientId() {
		return $this->clientId;
	}

	public function setClientId($clientId) {
		return $this->clientId = $clientId;
	}

	/**
	 *
	 * @return User
	 */
	public function getUser() {
		return $this->userRoomHandler->getUserByClientId($this->getClientId());
	}

	/**
	 *
	 * @return integer
	 */
	public function getSeat() {
		return $this->seat;
	}

	/**
	 *
	 * @return integer
	 */
	public function getWind() {
		return (3 * ($this->game->getRound() - 1) + $this->seat) % 4;
	}

	public function declareReady() {
		if (!$this->declaredReady) {
			if ($this->points >= 1000) {
				$this->declaredReady = true;
			} else {
				echo "得点足れへんで\n";
				echo $this->points . "点しか持ってへんで\n";
				// send message '得点が足りないため立直出来ません'
				return false;
			}
			return true;
		} else {
			echo "もうリーチしてんで\n";
			return false;
		}
	}

	public function completeReady() {
		if ($this->declaredReady) {
			$this->isReady = true;
			$this->points -= 1000;
			$this->game->addDeposit();
		}
	}

	public function getPoints() {
		return $this->points;
	}

	public function addPoints($diff) {
		if ($this->points + $diff < 0) {
			$all = $this->points;
			$this->points = 0;
			$this->game->hako();
			return -$all;
		} else {
			$this->points += $diff;
			return $diff;
		}
	}

	public function sendToMe($action, $data) {
		$this->getUser()->sendToMe($action, $data);
	}

	public function __destruct() {
		echo "P";
	}

}
