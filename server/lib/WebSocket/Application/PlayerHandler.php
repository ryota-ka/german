<?php

namespace WebSocket\Application;

class PlayerHandler {

	private $app;
	private $userRoomHandler;
	private $game;
	private $players = array();

	public function __construct(GermanApplication $app, UserRoomHandler $userRoomHandler, Game $game) {
		$this->app = $app;
		$this->userRoomHandler = $userRoomHandler;
		$this->game = $game;

		$clientIds = $this->userRoomHandler->getPlayerClientIdsByRoom($this->game->getRoom());

		shuffle($clientIds);
		foreach ($clientIds as $key => $clientId) {
			$this->players[] = new Player($this->app, $this->userRoomHandler, $this->game, $this, $clientId, $key);
		}
	}

	public function init() {
		foreach ($this->players as $player) {
			$player->init();
		}
	}

	public function destruct() {
		foreach ($this->players as $key => $value) {
			unset($this->players[$key]);
		}
	}

	/**
	 *
	 * @return Player[]
	 */
	public function getPlayers() {
		return $this->players;
	}

	/**
	 *
	 * @param type $clientId
	 * @return Player
	 */
	public function getPlayerByClientId($clientId) {
		foreach ($this->players as $player) {
			if ($clientId === $player->getClientId()) {
				return $player;
			}
		}
		return false;
	}

	/**
	 *
	 * @param type $wind
	 * @return Player
	 */
	public function getPlayerByWind($wind) {
		return $this->getPlayerBySeat(($wind + $this->game->getRound() - 1) % 4);
	}

	/**
	 *
	 * @param type $seat
	 * @return Player
	 */
	public function getPlayerBySeat($seat) {
		if (in_array($seat, array(0, 1, 2, 3), true)) {
			return $this->players[$seat];
		}
	}

	public function transferPoints($points, $from, $to) {
		if (in_array($from, array(0, 1, 2, 3), true) && in_array($to, array(0, 1, 2, 3), true)) {
			if ($from === $to) {
				return 0;
			} else {
				$diff = $this->getPlayerByWind($from)->addPoints(-$points);
				return $this->getPlayerByWind($to)->addPoints(-$diff);
			}
		}
	}

	public function __destruct() {
		echo "[P]";
	}

}
