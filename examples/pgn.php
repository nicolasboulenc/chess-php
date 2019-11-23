<?php

require("../chess.php");

$file_content = file_get_contents("chess-game+annotations.pgn");
$pgn = new PGN();
$pgn->load($file_content);

$white = $pgn->get_tag("white");
$black = $pgn->get_tag("black");
echo "{$white} vs. {$black}" . PHP_EOL;

?>