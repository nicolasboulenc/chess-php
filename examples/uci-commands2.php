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

?>