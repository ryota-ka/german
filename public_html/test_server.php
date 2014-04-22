<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'lib/class.websocket_client.php';

$clients = array();
$testClients = 30;
$testMessages = 500;
for($n = 0; $n < $testClients; $n++)
{
	$clients[$n] = new WebsocketClient;
	$clients[$n]->connect('127.0.0.1', 8000, '/demo', 'foo.lh');
}
usleep(5000);

$payload = json_encode(array(
	'action' => 'echo',
	'data' => 'dos'
));

for($n = 0; $n < $testMessages; $n++)
{
	$clientId = rand(0, $testClients-1);
	$clients[$clientId]->sendData($payload);
	usleep(5000);
}
usleep(5000);