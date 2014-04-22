<?php

namespace WebSocket\Application;

class OpenMeld implements \JsonSerializable {

	private $kind;
	protected $tiles = array();
	protected $wind;
	protected $from;

	public function __construct($kind, $tiles, $wind, $from = null) {
		$this->kind = $kind;
		$this->tiles = $tiles;
		$this->wind = $wind;
		$this->from = $from;
	}

	public function getKind() {
		return $this->kind;
	}

	/**
	 *
	 * @return Tile[]
	 */
	public function getTiles() {
		return $this->tiles;
	}

	public function getWind() {
		return $this->wind;
	}

	public function getFrom() {
		return $this->from;
	}

	public function addedQuad($addedTile) {
		if ($this->kind === 1) {
			$this->kind = 3;
			$this->tiles[] = $addedTile;
		}
	}

	public function jsonSerialize() {
		$tiles = array();
		$i = 0;
		foreach ($this->tiles as $tile) {
			$tiles[] = array(
				'id' => $tile->getId(),
				'kind' => $tile->getKind(),
				'isReversed' => (($this->kind == 4) && (($i == 1) || ($i == 2)))
			);
			$i++;
		}
		return array(
			'kind' => $this->kind,
			'tiles' => $tiles,
			'sidewayIndex' => 3 - (($this->from - $this->wind + 4) % 4)
		);
	}

	public function __destruct() {
		echo "[...]";
	}

}
