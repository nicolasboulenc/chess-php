<?php

require("../pgn.php");
use Chess\PGN;

$file_content = file_get_contents("chess-game+annotations.pgn");
$pgn = new PGN();
$pgn->load($file_content);

$white = $pgn->getTag("white");
$black = $pgn->getTag("black");
echo "{$white} vs. {$black}" . PHP_EOL;

foreach($pgn as $move) {
    echo $move["move"] . PHP_EOL;
}

?>