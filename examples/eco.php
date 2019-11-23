<?php

require("../chess.php");

// load pgn file
$file_content = file_get_contents("chess-game+annotations.pgn");
$pgn = new PGN();
$pgn->load($file_content);

// get moves
$moves = $pgn->stringify_moves();

// load eco file
$eco_content = file_get_contents("scid.eco");
$eco = new ECO();
$eco->load($eco_content);

// identify opening
$opening = $eco->identify($moves);
echo "Opening code: {$opening["eco"]}" . PHP_EOL;
echo "Opening name: {$opening["name"]}" . PHP_EOL;

// write eco tag back to different pgn file
$pgn->set_tag("eco", $opening["eco"]);
$pgn_content = $pgn->stringify();

file_put_contents("chess-game.pgn", $pgn_content);

?>