<?php

require("../pgn.php");
require("../eco.php");

// load pgn file
$file_content = file_get_contents("chess-game+annotations.pgn");
$pgn = new Chess\PGN();
$pgn->load($file_content);

// load eco file
$eco_content = file_get_contents("scid.eco");
$eco = new Chess\ECO();
$eco->load($eco_content);

// display players
$white = $pgn->getTag("white");
$black = $pgn->getTag("black");
echo "{$white} vs. {$black}" . PHP_EOL;

// get moves
$moves = $pgn->getSANs();

// identify opening
$opening = $eco->identify($moves);
echo "{$opening["eco"]} {$opening["name"]}" . PHP_EOL;

// write eco tag back to different pgn file
$pgn->setTag("eco", $opening["eco"]);
$file_content = $pgn->stringify();

file_put_contents("chess-game.pgn", $file_content);
