<?php 

require("../pgn.php");
require("../eco.php");
use Chess\PGN;
use Chess\ECO;

// load pgn file
$file_content = file_get_contents("chess-game+annotations.pgn");
$pgn = new PGN();
$pgn->load($file_content);

// load eco file
$eco_content = file_get_contents("scid.eco");
$eco = new ECO();
$eco->load($eco_content);

// get moves
$moves = $pgn->stringifyMoves();

$opening = $eco->identifyString($moves);
echo "{$opening["eco"]} {$opening["name"]}" . PHP_EOL;

$opening = $eco->identifyTraversable($pgn);
echo "{$opening["eco"]} {$opening["name"]}" . PHP_EOL;
