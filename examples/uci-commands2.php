<?php

require("../chess.php");

$engine_path = "C:/Users/nicol/Documents/Scid-4.7.0/bin/engines/stockfish.exe";

$uci = new UCI();
$uci->init($engine_path);


$uci->_uci();
$uci->_newgame();
$uci->_isready();

$uci->_position($position, $move);
$uci->_go("movetime", $movetime);
while($uci->is_thinking() === true) {
	$uci->process();
}

$result = $uci->_go("movetime", $movetime, $timeout);


$uci->send(UCI::$uci);
$uci->send(UCI::$ucinewgame);
$uci->send(UCI::$isready);
while($uci->is_synched() !== true) {
	$uci->wait();
}

$uci->send(UCI::$position, ["fen"=>"4kb1r/p2rqppp/5n2/1B2p1B1/4P3/1Q6/PPP2PPP/2K4R w k - 0 14"]);
$uci->send(UCI::$go, ["movetime"=>5000]);
while($uci->is_synched() !== true) {
	$uci->wait();
}

$uci->send(UCI::$ucinewgame);
$uci->send(UCI::$isready);
while($uci->is_synched() !== true) {
	$uci->wait();
}
$uci->send(UCI::$position, ["fen"=>"4kb1r/p2rqppp/5n2/1B2p1B1/4P3/1Q6/PPP2PPP/2K4R w k - 0 14", "moves"=>"h1d1"]);
$uci->send(UCI::$go, ["movetime"=>15000]);
while($uci->is_synched() !== true) {
	$uci->wait();
}

$uci->send(UCI::$quit);



function _uci() : void {}
function _uci_sync(int $timeout=1) : bool {}

function _isready() : void {}
function _isready_sync(int $timeout=1) : bool {}

function _option(string $name, $value) : void {}
function _register(string $name, string $code) : void {}
function _newgame() : void {}
function _position(string $position, string $moves="") : void {}

function _go(array $options) : void {}
function _go_sync(array $options, int $timeout=0) : ?array {}

function _stop() : void {}
function _stop_sync(int $timeout=1) : ?array {}	// ?? should return bestmove & ponder ??

function _ponderhit() : void {}

function _quit() : void {}

function init() : void {}
function deinit() : void {}

function send(string $command, string $response="") : ?string {} // ? is this a good idea ?
function sync(int $timeout=1) : bool {}

function get_status() : int {} // registering | synching | ok
function get_id_name() : string {}
function get_id_author() : string {}

function set_timeout(int $timeout) : void {}

?>