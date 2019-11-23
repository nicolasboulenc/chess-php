<?php

require("../chess.php");

// $engine_path = "C:/Users/nicol/Documents/Scid-4.7.0/bin/engines/stockfish.exe";
$engine_path = "C:/Users/Nicolas/Documents/Scid-4.6.4/bin/engines/stockfish.exe";

$uci = new UCI();
$uci->init($engine_path);

$uci->_uci();
$uci->_newgame();
$uci->_isready();
while($uci->sync() === false) {
	// use this time to do other things...
}
echo "Setup completed." . PHP_EOL;

$uci->_position("4kb1r/p2rqppp/5n2/1B2p1B1/4P3/1Q6/PPP2PPP/2K4R w k - 0 14");
$uci->_go(["movetime" => 3]);
while($uci->sync() === false) {
	// use this time to do other things...
}
var_dump($uci->get_result());
$uci->_quit();

?>