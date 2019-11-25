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

// get moves
$moves = $pgn->getSANs();

// identify opening
$opening = $eco->identify($moves);
echo "{$opening["eco"]} {$opening["name"]}" . PHP_EOL;
