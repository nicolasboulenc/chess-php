<?php

require("../src/pgn.php");
require("../src/eco.php");


// load pgn file
$pgn = new nicolasboulenc\Chess\PGN();
$pgn->load("spasky-fischer.pgn");

// load eco file
$eco = new nicolasboulenc\Chess\ECO();
$eco->load("scid.eco");

// display players
$white = $pgn->getTag("white");
$black = $pgn->getTag("black");
echo "{$white} vs. {$black}" . PHP_EOL;

// identify opening
$moves = $pgn->getSANs();
$opening = $eco->identifyMoves($moves);
echo "{$opening["eco"]} {$opening["name"]}" . PHP_EOL;

$moves = $pgn->getMoveText();
$opening = $eco->identifyMoveText($moves);
echo "{$opening["eco"]} {$opening["name"]}" . PHP_EOL;


// write eco tag back to different pgn file
$pgn->setTag("eco", $opening["eco"]);
$file_content = $pgn->stringify();

// file_put_contents("chess-game.pgn", $file_content);
