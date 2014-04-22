<?php

namespace WebSocket\Application;

class Calling {

	private $wind;
	private $kind;
	private $choices = array();

	public function __construct($wind, $kind) {
		$this->wind = $wind;
		$this->kind = $kind;
	}

	public function getWind() {
		return $this->wind;
	}

	public function getKind() {
		return $this->kind;
	}

	public function getChoices() {
		return $this->choices;
	}

	public function setChoices(Array $choices) {
		$this->choices = $choices;
	}

	public function __destruct() {
		echo "鳴";
	}

}
