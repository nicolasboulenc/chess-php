<?php

require("../src/pgn.php");
require("../src/eco.php");
use nicolasboulenc;

// load pgn file
$file_content = file_get_contents("chess-game+annotations.pgn");
$pgn = new Chess\PGN();
$pgn->load($file_content);

// load eco file
$eco = new Chess\ECO();
$eco->load("scid.eco");

// identify opening using iterable moves
$moves = $pgn->getSANs();
$opening = $eco->identifyMoves($moves);
echo "{$opening["eco"]} {$opening["name"]}" . PHP_EOL;

// identify opening using move text
$moves = $pgn->getMoveText();
$opening = $eco->identifyMoveText($moves);
echo "{$opening["eco"]} {$opening["name"]}" . PHP_EOL;
