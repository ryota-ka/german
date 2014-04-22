<?php

namespace WebSocket\Application;

class Tile implements \JsonSerializable {

	private $id;
	private $kind;

	public function __construct($id, $kind) {
		$this->id = $id;
		$this->kind = (int) $kind;
	}

	public function getId() {
		return $this->id;
	}

	public function getKind() {
		return $this->kind;
	}

	public function jsonSerialize() {
		return array(
			'id' => $this->id,
			'kind' => $this->kind,
		);
	}

	public function setKind($kind) {
		$this->kind = (int) $kind;
	}

	public function __destruct() {
		echo ".";
	}

}
