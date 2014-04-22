<?php

namespace WebSocket\Application;

class GermanApplication extends Application {

	private $messenger;
	private $userRoomHandler;
	private $_clients = array();
	private $_serverClients = array();
	private $_serverInfo = array();
	private $_serverClientCount = 0;
	private $logOutside = false;
	private $logInsde = false;

	public function __construct() {
		parent::__construct();
		$this->messenger = new Messenger($this);
		$this->userRoomHandler = new UserRoomHandler($this);
	}

	/**
	 *
	 * @return Messenger
	 */
	public function getMessenger() {
		return $this->messenger;
	}

	/**
	 *
	 * @return UserRoomHandler
	 */
	public function getUserRoomHandler() {
		return $this->userRoomHandler;
	}

	/**
	 *
	 * @param type $client
	 */
	public function onConnect($client) {
		$clientId = $client->getClientId();
		$this->_clients[$clientId] = $client;
		$this->_sendServerinfo($client);
		$this->clientConnected($client->getClientIp(), $client->getClientPort(), $client->getClientId());
	}

	/**
	 *
	 * @param type $client
	 */
	public function onDisconnect($client) {
		$clientId = $client->getClientId();
		unset($this->_clients[$clientId]);
		$this->userRoomHandler->removeUser($clientId);
		$this->clientDisconnected($client->getClientIp(), $client->getClientPort(), $client->getClientId());
	}

	/**
	 *
	 * @param type $msg
	 * @param \WebSocket\Connection $client
	 */
	public function onData($msg, \WebSocket\Connection $client) {
		$decodedData = $this->_decodeData($msg);
		$action = $decodedData['action'];
		$data = $decodedData['data'];

		// for debug
		if ($this->logInsde) {
			echo "\n[Data Received]\nAction: $action\n";
			var_dump($data);
			echo "\n";
		}
		// for debug

		$this->messenger->processData($action, $data, $client->getClientId());
	}

	/**
	 *
	 * @param type $serverInfo
	 * @return boolean
	 */
	public function setServerInfo($serverInfo) {
		if (is_array($serverInfo)) {
			$this->_serverInfo = $serverInfo;
			return true;
		}
		return false;
	}

	/**
	 *
	 * @param type $ip
	 * @param type $port
	 * @param type $id
	 */
	public function clientConnected($ip, $port, $id) {
		$this->_serverClients[$port] = $ip;
		$this->_serverClientCount++;
		$this->statusMsg('Client connected: ' . $ip . ':' . $port);
		$data = array(
			'ip' => $ip,
			'port' => $port,
			'clientId' => $id,
			'clientCount' => $this->_serverClientCount,
		);
		$this->_sendAll('clientConnected', $data);
	}

	/**
	 *
	 * @param type $ip
	 * @param type $port
	 * @param type $id
	 * @return boolean
	 */
	public function clientDisconnected($ip, $port, $id) {
		if (!isset($this->_serverClients[$port])) {
			return false;
		}
		unset($this->_serverClients[$port]);
		$this->_serverClientCount--;
		$this->statusMsg('Client disconnected: ' . $ip . ':' . $port);
		echo "{$this->_serverClientCount} clients connected.\n";
		$data = array(
			'ip' => $ip,
			'port' => $port,
			'id' => $id,
			'clientCount' => $this->_serverClientCount,
		);
		$this->_sendAll('clientDisconnected', $data);
	}

	/**
	 *
	 * @param type $port
	 */
	public function clientActivity($port) {
		$encodedData = $this->_encodeData('clientActivity', $port);
		$this->_sendAll($encodedData);
	}

	/**
	 *
	 * @param type $text
	 * @param type $type
	 */
	public function statusMsg($text, $type = 'info') {
		$data = array(
			'type' => $type,
			'text' => '[' . strftime('%m-%d %H:%M', time()) . '] ' . $text,
		);
		$this->_sendAll('statusMsg', $data);
	}

	/**
	 *
	 * @param type $client
	 * @return boolean
	 */
	private function _sendServerinfo($client) {
		if (count($this->_clients) < 1) {
			return false;
		}
		$currentServerInfo = $this->_serverInfo;
		$currentServerInfo['clientCount'] = count($this->_serverClients);
		$currentServerInfo['clients'] = $this->_serverClients;
		$encodedData = $this->_encodeData(null, 'serverInfo', $currentServerInfo);
		$client->send($encodedData);
	}

	/**
	 *
	 * @param type $action
	 * @param type $data
	 * @return boolean
	 */
	public function _sendAll($action, $data) {
		if (count($this->_clients) < 1) {
			return false;
		}
		$encodedData = $this->_encodeData(0, $action, $data);
		foreach ($this->_clients as $client) {
			$client->send($encodedData);
		}

		// for debug
		if ($this->logOutside) {
			echo "\n[Data Sent To All Clients]\nAction: $action\n";
			var_dump($this->_decodeData($encodedData));
			echo "\n";
		}
		// for debug
	}

	/**
	 *
	 * @param type $clientId
	 * @param type $id
	 * @param type $action
	 * @param type $data
	 */
	public function _send($id, $clientId, $action, $data) {
		$encodedData = $this->_encodeData($id, $action, $data);
		if (!empty($this->_clients[$clientId])) {
			$this->_clients[$clientId]->send($encodedData);

			// @debug
			if ($this->logOutside) {
				if ($action != 'startHand') {
					echo "\n[Data Sent to Client $clientId]\nAction: $action\n";
					var_dump($this->_decodeData($encodedData));
					echo "\n";
				}
			}
			// @debug
		}
	}

	public function _encodeData($id, $action, $data) {
		if (empty($action)) {
			return false;
		}

		$payload = array(
			'id' => $id,
			'action' => $action,
			'data' => $data,
		);

		return json_encode($payload);
	}

}

function oreq($var, $bit) {
	if (!is_int($var)) {
		echo "illegal use of oreq\n";
	}
	return (($var | $bit) === $bit);
}
