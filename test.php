<?php

require("chess.php");

// test PGN / ECO
// $pgn_content = file_get_contents("chess+annotations.pgn");
// $pgn = new PGN();
// $pgn->load($pgn_content);
// $moves = $pgn->stringify_moves();
// echo $moves . PHP_EOL;

// $eco_content = file_get_contents("scid.eco");
// $eco = new ECO();
// $eco->load($eco_content);
// $opening = $eco->identify($moves);
// echo $opening["eco"] . " " . $opening["name"];

// $pgn->set_tag("eco", $opening["eco"]);
// $pgn_content = $pgn->stringify();

// file_put_contents("test.pgn", $pgn_content);


// test UCI
// $engine_path = "C:/Users/nicol/Documents/Scid-4.7.0/bin/engines/stockfish.exe";

// $uci = new UCI();
// $uci->init($engine_path);

// $uci->send(UCI::$uci);
// $uci->send(UCI::$ucinewgame);
// $uci->send(UCI::$isready);
// while($uci->is_synched() !== true) {
// 	$uci->wait();
// }

// $uci->send(UCI::$position, ["fen"=>"4kb1r/p2rqppp/5n2/1B2p1B1/4P3/1Q6/PPP2PPP/2K4R w k - 0 14"]);
// $uci->send(UCI::$go, ["movetime"=>5000]);
// while($uci->is_synched() !== true) {
// 	$uci->wait();
// }

// $uci->send(UCI::$ucinewgame);
// $uci->send(UCI::$isready);
// while($uci->is_synched() !== true) {
// 	$uci->wait();
// }
// $uci->send(UCI::$position, ["fen"=>"4kb1r/p2rqppp/5n2/1B2p1B1/4P3/1Q6/PPP2PPP/2K4R w k - 0 14", "moves"=>"h1d1"]);
// $uci->send(UCI::$go, ["movetime"=>15000]);
// while($uci->is_synched() !== true) {
// 	$uci->wait();
// }

// $uci->send(UCI::$quit);

// test UCI
$engine_path = "C:/Users/nicol/Documents/Scid-4.7.0/bin/engines/stockfish.exe";

$uci = new UCI();
$uci->init($engine_path);

$uci->send(UCI::$uci);
while($uci->is_synched() !== true) {
	$uci->wait();
}

echo $uci->get_id_name() . PHP_EOL;
echo $uci->get_id_author() . PHP_EOL;

$option = $uci->get_option("Threads");
if($option !== null) {
	$uci->send(UCI::$setoption, ["name"=>"Threads", "value"=>4]);
	while($uci->is_synched() !== true) {
		$uci->wait();
	}
}

$uci->send(UCI::$quit);

?>