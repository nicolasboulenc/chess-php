<?php

require("../chess.php");

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