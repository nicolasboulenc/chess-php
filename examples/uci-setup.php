<?php

require("../src/uci.php");

$engine_path = "C:/Users/nicol/Documents/Scid-4.7.0/bin/engines/stockfish.exe";

$uci = new nicolasboulenc\Chess\UCI();
$uci->init($engine_path);

$uci->_uci();
while ($uci->sync() === false) {
    // you may want to do something else
}

echo $uci->getIdName() . PHP_EOL;
echo $uci->getIdAuthor() . PHP_EOL;

$option = $uci->getOption("Threads");
if ($option !== null) {
    $uci->_setoption("Threads", 4);
    while ($uci->sync() === false) {
        // you may want to do something else
    }
}

$uci->_uci();
while ($uci->sync() === false) {
    // you may want to do something else
}

$uci->_quit();
