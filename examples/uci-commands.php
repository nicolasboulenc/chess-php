<?php

require("../src/uci.php");

$engine_path = "C:/Users/nicol/Documents/Scid-4.7.0/bin/engines/stockfish.exe";

$uci = new nicolasboulenc\Chess\UCI();
$uci->init($engine_path);

$uci->_uci();
$uci->_setoption("Threads", 8);
$uci->_newgame();
$uci->_isready();
while ($uci->sync() === false) {
    // you may want to do something else
}
echo "Setup completed." . PHP_EOL;

$uci->_position("4kb1r/p2rqppp/5n2/1B2p1B1/4P3/1Q6/PPP2PPP/2K4R w k - 0 14");
$uci->_go(["movetime" => 20]);
while ($uci->sync() === false) {
    // you may want to do something else
}
var_dump($uci->getResult());
$uci->_quit();
