<?php

require("../src/pgn.php");
require("../src/eco.php");

// load pgn file
$pgn = new nicolasboulenc\Chess\PGN();
$pgn->load("chess-game+annotations.pgn");

// load eco file
$eco = new nicolasboulenc\Chess\ECO();
$eco->load("scid.eco");

// identify opening using iterable moves
$moves = $pgn->getSANs();
$opening = $eco->identifyMoves($moves);
echo "{$opening["eco"]} {$opening["name"]}" . PHP_EOL;

// identify opening using move text
$moves = $pgn->getMoveText();
$opening = $eco->identifyMoveText($moves);
echo "{$opening["eco"]} {$opening["name"]}" . PHP_EOL;
