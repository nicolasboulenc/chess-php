<?php

class Chess {

public $Chessboard;

// PGN Tags
public $Event;
public $Site;
public $Date;
public $Round;
public $White;
public $Black;
public $Result;

// PGN related variables.
public $Moves;
public $Move_Index;

// FEN related variables.
private $Active_Colour;
private $Castling_Availability;
private $En_Passant;
private $Halfmove_Clock;
private $Fullmove_Number;

// ECO tree
private $Eco;

function __construct() {
	$this->Chessboard = Chess::chessboard_create();
}

public function pgn_load($pgn) {
	// Init PGN variables
	$this->Event = "";
	$this->Site = "";
	$this->Date = "";
	$this->Round = "";
	$this->White = "";
	$this->Black = "";
	$this->Result = "";
	$this->Moves = [];
	$this->Move_Index = 0;

	// Init FEN state variables
	$this->Active_Colour = "w";
	$this->Halfmove_Clock = 0;
	$this->Fullmove_Number = 0;

	$pgn = str_replace("\r\n", "\n", $pgn);

	$move = Chess::move_create();

	$beg = 0;   // for debug purpose
	$end = 0;   // for debug purpose

	$game_end_index = 0;

	$char_index = 0;
	$char_count = strlen($pgn);

	while($char_index < $char_count) {

		// check for end of game
		if($game_end_index !== 0 && $char_index === $game_end_index) {
			// do end of game things
// echo "reached end of game." . PHP_EOL;
			$char_index += strlen($this->Result);
			$this->Moves[] = $move;

			// reset variables
			//$game = PGN_Game_Init();
			$game_end_index = 0;
		}
		// tag
		else if($pgn[$char_index] === "[") {

			$pos = strpos($pgn, "]", $char_index);
			// trim square brackets
			$buffer = substr($pgn, $char_index + 1, $pos - $char_index - 2);
			$tokens = explode(" \"", $buffer);
			if($tokens[0] === "Event") {
				$this->Event = $tokens[1];
			}
			else if($tokens[0] === "Site") {
				$this->Site = $tokens[1];
			}
			else if($tokens[0] === "Date") {
				$this->Date = $tokens[1];
			}
			else if($tokens[0] === "Round") {
				$this->Round = $tokens[1];
			}
			else if($tokens[0] === "White") {
				$this->White = $tokens[1];
			}
			else if($tokens[0] === "Black") {
				$this->Black = $tokens[1];
			}
			else if($tokens[0] === "Result") {
				$this->Result = $tokens[1];
				$offset = $char_index + strlen("[Result \"\"]") + strlen($this->Result);
				$game_end_index = strpos($pgn, $this->Result, $offset);
			}
			$char_index = $pos + 1;
		}
		// move num?
		else if(is_numeric($pgn[$char_index]) === true) {

			$pos = strpos($pgn, ".", $char_index);

			// ignore black move number in form of #...
			$buffer = substr($pgn, $char_index, $pos - $char_index + 1);
			if($pgn[$pos + 1] === "." && $pgn[$pos + 1] === ".") {
				$char_index = $pos + 3;
				continue;
			}

			$buffer = substr($pgn, $char_index, $pos - $char_index + 1);
			$end = $pos - strlen($buffer);

			if($move["num"] != 0) {
// display line for debug
// echo substr($pgn, $beg, $end - $beg) . PHP_EOL;
// $beg = $end + 1;

				// add move to moves
				$this->Moves[] = $move;
// echo json_encode($move, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;

				// create new move
				$move = Chess::move_create();
			}
			$move["num"] = $buffer;

			$char_index = $pos + 1;
		}
		// variation
		else if($pgn[$char_index] === "(") {
			$pos = strpos($pgn, ")", $char_index);
			$buffer = substr($pgn, $char_index, $pos - $char_index + 1);
			$buffer = str_replace("\n", " ", $buffer);
			$buffer = str_replace("  ", " ", $buffer);

			if($move->black->move === "")
				$move->white->variation = $buffer;
			else
				$move->black->variation = $buffer;

			$char_index = $pos + 1;
		}
		// annotation
		else if($pgn[$char_index] === "{") {
			$pos = strpos($pgn, "}", $char_index);
			$buffer = substr($pgn, $char_index, $pos - $char_index + 1);
			$buffer = str_replace("\n", " ", $buffer);
			$buffer = str_replace("  ", " ", $buffer);

			if($move->black->move === "")
				$move->white->annotation = $buffer;
			else
				$move->black->annotation = $buffer;

			$char_index = $pos + 1;
		}
		// NAG
		else if($pgn[$char_index] === "$") {
			$buffer = "";
			while($pgn[$char_index] !== " " && $pgn[$char_index] !== "\r" && $pgn[$char_index] !== "\n") {
				$buffer .= $pgn[$char_index];
				$char_index++;
			}

			if($move->black->move === "")
				$move->white->nag_codes[] = $buffer;
			else
				$move->black->nag_codes[] = $buffer;
		}
		// move
		else if(	$pgn[$char_index] === "a" ||
					$pgn[$char_index] === "b" ||
					$pgn[$char_index] === "c" ||
					$pgn[$char_index] === "d" ||
					$pgn[$char_index] === "e" ||
					$pgn[$char_index] === "f" ||
					$pgn[$char_index] === "g" ||
					$pgn[$char_index] === "h" ||
					$pgn[$char_index] === "K" ||
					$pgn[$char_index] === "Q" ||
					$pgn[$char_index] === "B" ||
					$pgn[$char_index] === "N" ||
					$pgn[$char_index] === "R" ||
					$pgn[$char_index] === "O"	) {

			$pos = strpos($pgn, " ", $char_index);
			$buffer = substr($pgn, $char_index, $pos - $char_index);

			if($move->white->move === "")
				$move->white->move = $buffer;
			else
				$move->black->move = $buffer;

			$char_index = $pos;
		}
		// white space
		else if($pgn[$char_index] === " " || $pgn[$char_index] === "\n") {
			$char_index++;
		}
		else {
			echo "Error: Unknown token. {$pgn[$char_index]}!" . PHP_EOL;
			return;
		}
	}
}

public function pgn_next() {
	if($this->Move_Index === count($this->Moves))
		return null;

	// get the right move
	$move = $this->Moves[$this->Move_Index]->white->move;
	if($this->Active_Colour === "b")
		$move = $this->Moves[$this->Move_Index]->black->move;

	// execute the move
	$this->Move($move, $this->Active_Colour === "w");
	$res = (object)["move"=>$move, "colour"=>$this->Active_Colour, "num"=>$this->Move_Index + 1];

	// update state
	if($this->Active_Colour === "w") {
		$this->Active_Colour = "b";
	}
	else {
		$this->Active_Colour = "w";
		$this->Move_Index++;
	}

	return $res;
} 

public function eco_load($eco) {

	$eco_array = explode("\n", $eco);

	$eco_index = 0;

	// tidy up array
	while($eco_index < count($eco_array)) {

		if(strlen($eco_array[$eco_index]) === 0) {
			array_splice($eco_array, $eco_index, 1);
		}
		else if($eco_array[$eco_index][0] === "#") {
			array_splice($eco_array, $eco_index, 1);
		}
		else if($eco_array[$eco_index][0] === " ") {
			// append moves to previous line although not first move
			$eco_array[$eco_index - 1] .= $eco_array[$eco_index];
			array_splice($eco_array, $eco_index, 1);
		}
		else {
			$eco_index++;
		}
	}

	// build tree
	$tree = (object)["eco"=>"A00a", "name"=>"Start position", "move"=>"*", "nodes"=>[]];

	$eco_index = 1;
	$eco_count = count($eco_array);
	while($eco_index < $eco_count) {
		
		$this->eco_build($tree, $eco_array[$eco_index]);
		$eco_index++;
	}

	return $tree;
}

private function eco_build(object $tree,  string $line) {

	$pos0 = strpos($line, "\"");
	$eco = trim(substr($line, 0, $pos0));
	$pos1 = strpos($line, "\"", $pos0 + 1);
	$name = substr($line, $pos0 + 1, $pos1 - $pos0 - 1);
	$moves = trim(substr($line, $pos1 + 1));

	$probe = $tree;
	$moves = explode(" ", $moves);
	foreach($moves as $move) {

		if($move === "") continue;

		$pos = strpos($move, ".");
		if($pos !== false) $move = substr($move, $pos + 1);
		if(isset($probe->nodes[$move]) !== true) {

			$probe->nodes[$move] = (object)["eco"=>$eco, "name"=>$name, "move"=>$move, "nodes"=>[]];
		}
		$probe = $probe->nodes[$move];
	}
}

public function eco_detect() {


}

private static function move_create() : object {

	$move = (object)array(	"num"=>0,
							"white"=>null,
							"black"=>null );

	$move->white = (object)array(	"move"=>"",
							"nag_codes"=>[],
							"annotation"=>"",
							"variation"=>""	);

	$move->black = (object)array(	"move"=>"",
							"nag_codes"=>[],
							"annotation"=>"",
							"variation"=>""	);

	return $move;
} 


private static function chessboard_create() : array {
	return array(
		"8" => ["a"=>"r", "b"=>"n", "c"=>"b", "d"=>"q", "e"=>"k", "f"=>"b", "g"=>"n", "h"=>"r"],
		"7" => ["a"=>"p", "b"=>"p", "c"=>"p", "d"=>"p", "e"=>"p", "f"=>"p", "g"=>"p", "h"=>"p"],
		"6" => ["a"=>".", "b"=>".", "c"=>".", "d"=>".", "e"=>".", "f"=>".", "g"=>".", "h"=>"."],
		"5" => ["a"=>".", "b"=>".", "c"=>".", "d"=>".", "e"=>".", "f"=>".", "g"=>".", "h"=>"."],
		"4" => ["a"=>".", "b"=>".", "c"=>".", "d"=>".", "e"=>".", "f"=>".", "g"=>".", "h"=>"."],
		"3" => ["a"=>".", "b"=>".", "c"=>".", "d"=>".", "e"=>".", "f"=>".", "g"=>".", "h"=>"."],
		"2" => ["a"=>"P", "b"=>"P", "c"=>"P", "d"=>"P", "e"=>"P", "f"=>"P", "g"=>"P", "h"=>"P"],
		"1" => ["a"=>"R", "b"=>"N", "c"=>"B", "d"=>"Q", "e"=>"K", "f"=>"B", "g"=>"N", "h"=>"R"]
	);
}


public function move($move, $white_playing=true) {

	// todo: manage castle and promotions
	$is_taking = (strpos($move, "x") === false ? 0 : 1);
	$is_checking = (strpos($move, "+") === false ? 0 : 1) + (strpos($move, "#") === false ? 0 : 1);
	$is_pawn = 0;

	$piece = "";
	if(	$move[0] === "R" || 
		$move[0] === "N" || 
		$move[0] === "B" || 
		$move[0] === "Q" || 
		$move[0] === "K"	) {
		$piece = $move[0];
	}
	else if($move[0] >= "a" && $move[0] <= "h") {
		$piece = "P";
		$is_pawn = 1;
	}
	else if($move[0] == "O") {

		echo "about to castle" . PHP_EOL;
		if($move === "O-O" && $white_playing === true) {
			echo "white castle short" . PHP_EOL;
			$this->Chessboard[1]["e"] = ".";
			$this->Chessboard[1]["g"] = "K";
			$this->Chessboard[1]["h"] = ".";
			$this->Chessboard[1]["f"] = "R";
		}
		else if($move === "O-O-O" && $white_playing === true) {
			$this->Chessboard[1]["e"] = ".";
			$this->Chessboard[1]["c"] = "K";
			$this->Chessboard[1]["a"] = ".";
			$this->Chessboard[1]["d"] = "R";
		}
		else if($move === "O-O" && $white_playing === false) {
			$this->Chessboard[8]["e"] = ".";
			$this->Chessboard[8]["g"] = "k";
			$this->Chessboard[8]["h"] = ".";
			$this->Chessboard[8]["f"] = "r";
		}
		else if($move === "O-O-O" && $white_playing === false) {
			$this->Chessboard[8]["e"] = ".";
			$this->Chessboard[8]["c"] = "k";
			$this->Chessboard[8]["a"] = ".";
			$this->Chessboard[8]["d"] = "r";			
		}

		return;
	}

	if($white_playing !== true) {
		$piece = strtolower($piece);
	}


	$disambiguation = strlen($move) - (3 - $is_pawn + $is_taking + $is_checking);

	$origin = "";
	$destination = substr($move, 1 - $is_pawn + $disambiguation + $is_taking, 2);

	// check disambiguation
	if($disambiguation > 0) {
		$origin = substr($move, 1 - $is_pawn, $disambiguation);
		// check if file, rank or both disambiguation
		if(strlen($origin) === 1) {
			if(is_numeric($origin) === true) {
				// search rank
				$file = "a";
				$rank = $origin;
				$is_valid_move = false;

				while($file < "i" && $is_valid_move === false) {
					if($this->Chessboard[$rank][$file] === $piece) {
						$origin = $file . $rank;
						$is_valid_move = Chessboard_Is_Valid_Move($piece, $origin, $destination, $is_taking === 1);
					}
					$file = chr(ord($file) + 1);
				}
			}
			else {
				// search rank
				$file = $origin;
				$rank = 1;
				$is_valid_move = false;

				while($rank < 9 && $is_valid_move === false) {
					if($this->Chessboard[$rank][$file] === $piece) {
						$origin = $file . $rank;
						$is_valid_move = $this->Move_Is_Valid($piece, $origin, $destination, $is_taking === 1);
					}
					$rank++;
				}
			}
		}
		else {
			// nothing to do we should have the origin as per disambiguation.
		}
	}
	// search the whole board
	else {
		$file = "a";
		$rank = 1;
		$is_valid_move = false;

		while($file < "i" && $is_valid_move === false) {
			$rank = 1;
			while($rank < 9 && $is_valid_move === false) {
				if($this->Chessboard[$rank][$file] === $piece) {
					$origin = $file . $rank;
					$is_valid_move = $this->Move_Is_Valid($piece, $origin, $destination, $is_taking === 1);
				}
				$rank++;
			}
			$file = chr(ord($file) + 1);
		}
	}

	// make the move.
	$this->Chessboard[$origin[1]][$origin[0]] = ".";
	$this->Chessboard[$destination[1]][$destination[0]] = $piece;
}


private function move_is_valid($piece, $origin, $destination, $is_taking=false) : bool {
	// This is only used to find the moving piece, as such does not check:
	// out of board plays, checks, check mates, etc
	// todo: Should manage interposing pieces???

	$file_map = array("a"=>1, "b"=>2, "c"=>3, "d"=>4, "e"=>5, "f"=>6, "g"=>7, "h"=>8);

	$origin_file = $file_map[$origin[0]];
	$origin_rank = intval($origin[1]);

	$destination_file = $file_map[$destination[0]];
	$destination_rank = intval($destination[1]);

	if($piece === "p") {
		return	($destination_file === $origin_file && $is_taking === false && $origin_rank === 7 && $destination_rank === $origin_rank - 2) ||
				($destination_file === $origin_file && $is_taking === false && $destination_rank === $origin_rank - 1) ||
				($destination_file === $origin_file - 1 && $is_taking === true && $destination_rank === $origin_rank - 1) ||
				($destination_file === $origin_file + 1 && $is_taking === true && $destination_rank === $origin_rank - 1);
	}
	else if($piece === "P") {
		return	($destination_file === $origin_file && $is_taking === false && $origin_rank === 2 && $destination_rank === $origin_rank + 2) ||
				($destination_file === $origin_file && $is_taking === false && $destination_rank === $origin_rank + 1) ||
				($destination_file === $origin_file - 1 && $is_taking === true && $destination_rank === $origin_rank + 1) ||
				($destination_file === $origin_file + 1 && $is_taking === true && $destination_rank === $origin_rank + 1);
	}
	else if($piece === "b" || $piece === "B") {
		return	($destination_file - $origin_file === $destination_rank - $origin_rank);
	}
	else if($piece === "r" || $piece === "R") {
		return	($destination_file === $origin_file && $destination_rank !== $origin_rank) || 
				($destination_file !== $origin_file && $destination_rank === $origin_rank);
	}
	else if($piece === "n" || $piece === "N") {
		return	($destination_file === $origin_file + 1 && $destination_rank === $origin_rank + 2) || 
				($destination_file === $origin_file + 2 && $destination_rank === $origin_rank + 1) || 
				($destination_file === $origin_file + 2 && $destination_rank === $origin_rank - 1) || 
				($destination_file === $origin_file + 1 && $destination_rank === $origin_rank - 2) || 
				($destination_file === $origin_file - 1 && $destination_rank === $origin_rank - 2) || 
				($destination_file === $origin_file - 2 && $destination_rank === $origin_rank + 1) || 
				($destination_file === $origin_file - 2 && $destination_rank === $origin_rank - 1) || 
				($destination_file === $origin_file - 1 && $destination_rank === $origin_rank + 2);
	}
	else
		return false;
}


public function ascii() : string {
	//$file = chr(ord($file) + 1);
	$rank = 8;
	$file = "a";

	$ascii = "  +------------------------+" . PHP_EOL;

	while($rank > 0) {

		$file = "a";
		$ascii .= "${rank} |";
		while($file < "i") {
			$ascii .= " {$this->Chessboard[$rank][$file]} ";
			$file = chr(ord($file) + 1);
		}
		$ascii .= "|" . PHP_EOL;
		$rank--;
	}
	$ascii .= "  +------------------------+" . PHP_EOL;
	$ascii .= "    a  b  c  d  e  f  g  h" . PHP_EOL . PHP_EOL;
	return $ascii;
}


public function fen() : string {

	$fen = "";

	$rank = 8;
	while($rank > 0) {
		$file = "a";
		while($file < "i") {
			if($this->Chessboard[$rank][$file] !== ".") {
				$fen .= $this->Chessboard[$rank][$file];
			}
			else {
				$consecutive_blanks = 0;
				while($file < "i" && $this->Chessboard[$rank][$file] === ".") {
					$consecutive_blanks++;
					$file = chr(ord($file) + 1);
				}
				$fen .= $consecutive_blanks;
			}
			$file = chr(ord($file) + 1);
		}
		$fen .= "/";
		$rank--;
	}

	// active colour: w or b
	// castling availability: - = none, K = white king side, Q = white queen side, k = black king side, q = black queen side.
	// en passant
	// halfmove clock
	// fullmove number
	$fen .= " {$this->Active_Colour} {$this->Castling_Availability} {$this->En_Passant} {$this->Halfmove_Clock} {$this->Fullmove_Number}";

	return $fen;
}

}	// end of class


class PGN {

	static protected $white = "w";
	static protected $black = "b";
	static protected $valid_tags = ["event", "site", "date", "round", "white", "black", "result", "eco"];

	protected $tags;
	protected $moves;

	public function __constructor() {
		$this->init();
	}

	protected function init() : void {
		$this->tags = [];
		$this->moves = [];
	}

	public function load(string &$pgn) : void {

		$this->init();
		$this->parse_tags($pgn);
		$this->parse_moves($pgn);
	}

	public function stringify(array $options=[]) : string {

		if(count($options) === 0) {
			$options = ["nag_codes"=>true, "annotations"=>true, "variations"=>true];
		}

		return $this->stringify_tags() . PHP_EOL . $this->stringify_moves($options) . " " . $this->get_tag("result");
	}

	protected function parse_tags(string &$pgn) : void {

		$lines = explode("[", $pgn);

		foreach($lines as $line) {

			if($line === "") continue;

			$line = explode("]", $line);
			$line = $line[0];

			list($tag, $val) = explode(" \"", $line);
			$tag = strtolower($tag);
			$val = substr($val, 0, -1);

			$this->tags[$tag] = $val;
		}
	}

	public function stringify_tags() : string {

		$string = "";
		foreach($this->tags as $tag=>$val) {
			$tag = ucwords($tag);
			$string .= "[{$tag} \"{$val}\"]" . PHP_EOL;
		}
		return $string;
	}

	public function get_tag(string $tag) : string {

		$val = "";
		if(isset($this->tags[$tag]) === true) {
			$val = $this->tags[$tag];
		}
		return $val;
	}

	public function set_tag(string $tag, $val) : void {

		$tag = strtolower($tag);
		$is_valid = in_array($tag, PGN::$valid_tags);
		if($is_valid === true) {
			$this->tags[$tag] = $val;
		}
	}

	protected function parse_moves(string &$pgn) : void {

		$move = PGN::create_move();
		$move["color"] = PGN::$white;
	
		$char_index = strpos($pgn, "1.");
		$char_count = strpos($pgn, $this->get_tag("result"), $char_index);
	
		$continue = true;

		while($char_index <= $char_count && $continue === true) {

			// end of the file
			if($char_index === $char_count) {
				$this->moves[] = $move;
				$continue = false;
			}
			// move num
			else if(is_numeric($pgn[$char_index]) === true) {
				
				// num can be formated as 12. for white or 32... for black
				$pos = $char_index;
				while(is_numeric($pgn[$pos]) === true || $pgn[$pos] === ".") {
					$pos++;
				}
				$pos--;
	
				$buffer = substr($pgn, $char_index, $pos - $char_index + 1);
				$buffer = str_replace(".", "", $buffer);

				if($move["num"] !== 0) {

					$this->moves[] = $move;

					$color = PGN::$white;
					if($move["color"] === PGN::$white) {
						$color = PGN::$black;
					}
					$move = PGN::create_move(["color"=>$color]);
				}
				$move["num"] = intval($buffer);
				$char_index = $pos + 1;
			}
			// variation
			else if($pgn[$char_index] === "(") {
				$pos = strpos($pgn, ")", $char_index);
				$buffer = substr($pgn, $char_index, $pos - $char_index + 1);
				$buffer = str_replace("\n", " ", $buffer);
				$buffer = str_replace("  ", " ", $buffer);
	
				$move["variation"] = $buffer;
				$char_index = $pos + 1;
			}
			// annotation
			else if($pgn[$char_index] === "{") {
				$pos = strpos($pgn, "}", $char_index);
				$buffer = substr($pgn, $char_index, $pos - $char_index + 1);
				$buffer = str_replace("\n", " ", $buffer);
				$buffer = str_replace("  ", " ", $buffer);
	
				$move["annotation"] = $buffer;
				$char_index = $pos + 1;
			}
			// NAG
			else if($pgn[$char_index] === "$") {
				$buffer = "";
				while($pgn[$char_index] !== " " && $pgn[$char_index] !== "\r" && $pgn[$char_index] !== "\n") {
					$buffer .= $pgn[$char_index];
					$char_index++;
				}
	
				$move["nag_codes"][] = $buffer;
			}
			// move
			else if(	$pgn[$char_index] === "a" ||
						$pgn[$char_index] === "b" ||
						$pgn[$char_index] === "c" ||
						$pgn[$char_index] === "d" ||
						$pgn[$char_index] === "e" ||
						$pgn[$char_index] === "f" ||
						$pgn[$char_index] === "g" ||
						$pgn[$char_index] === "h" ||
						$pgn[$char_index] === "K" ||
						$pgn[$char_index] === "Q" ||
						$pgn[$char_index] === "B" ||
						$pgn[$char_index] === "N" ||
						$pgn[$char_index] === "R" ||
						$pgn[$char_index] === "O"	) {
				
				// new black move
				if($move["move"] !== "") {

					$this->moves[] = $move;
					$num = $move["num"];

					$move = PGN::create_move(["num"=>$num, "color"=>PGN::$black]);
				}

				$pos = strpos($pgn, " ", $char_index);
				$buffer = substr($pgn, $char_index, $pos - $char_index);
	
				$move["move"] = $buffer;
	
				$char_index = $pos + 1;
			}
			// white space
			else if($pgn[$char_index] === " " || $pgn[$char_index] === "\n" || $pgn[$char_index] === "\r") {
				$char_index++;
			}
			else {
				echo "Error: Unknown token. {$pgn[$char_index]}!" . PHP_EOL;
				$continue = false;
			}
		}
	}

	public function stringify_moves(array $options=[]) : string {

		$move_text = "";
		foreach($this->moves as $move) {

			if($move["color"] === PGN::$white) {
				$move_text .= "{$move["num"]}.";
			}

			$move_text .= "{$move["move"]} ";

			if(isset($options["nag_codes"]) === true && $options["nag_codes"] === true) {
				$nag_codes = $move["nag_codes"];
				foreach($nag_codes as $nag_code) {
					$move_text .= "{$nag_code} ";
				}
			}
			if(isset($options["annotations"]) === true && $options["annotations"] === true) {
				$move_text .= "{$move["annotation"]} ";
			}
			if(isset($options["variations"]) === true && $options["variations"] === true) {
				$move_text .= "{$move["variation"]} ";
			}
		}

		return $move_text;
	}

	static protected function create_move(array $options=[]) : array {

		$num = (isset($options["num"]) === true ? $options["num"] : 0);
		$color = (isset($options["color"]) === true ? $options["color"] : "");
		$move = (isset($options["move"]) === true ? $options["move"] : "");
		return ["num"=>$num, "color"=>$color, "move"=>$move, "nag_codes"=>[], "annotation"=>"", "variation"=>""];
	}
}


class ECO {

	protected $tree;

	public function __constructor() {
		$this->init();
	}

	protected function init() : void {
		$this->tree = null;
	}

	public function load(string &$eco) : void {

		$this->init();

		$eco_array = explode("\n", $eco);
		$eco_index = 0;
	
		// tidy up array
		while($eco_index < count($eco_array)) {
	
			if(strlen($eco_array[$eco_index]) === 0) {
				array_splice($eco_array, $eco_index, 1);
			}
			else if($eco_array[$eco_index][0] === "#") {
				array_splice($eco_array, $eco_index, 1);
			}
			else if($eco_array[$eco_index][0] === " ") {
				// append moves to previous line although not first move
				$eco_array[$eco_index - 1] .= $eco_array[$eco_index];
				array_splice($eco_array, $eco_index, 1);
			}
			else {
				$eco_index++;
			}
		}
	
		// build tree
		$this->tree = ECO::create_node("A00a", "Start position", "*");
	
		$eco_index = 1;
		$eco_count = count($eco_array);
		while($eco_index < $eco_count) {
			
			$this->build_tree($eco_array[$eco_index]);
			$eco_index++;
		}
	}
	
	protected function build_tree(string $line) : void {
	
		$pos0 = strpos($line, "\"");
		$eco = trim(substr($line, 0, $pos0));
		$pos1 = strpos($line, "\"", $pos0 + 1);
		$name = substr($line, $pos0 + 1, $pos1 - $pos0 - 1);
		$moves = trim(substr($line, $pos1 + 1));
	
		$probe = $this->tree;
		$moves = explode(" ", $moves);
		foreach($moves as $move) {
	
			if($move === "") continue;
	
			$pos = strpos($move, ".");
			if($pos !== false) $move = substr($move, $pos + 1);
			if(isset($probe->nodes[$move]) !== true) {

				$probe->nodes[$move] = ECO::create_node($eco, $name, $move);
			}
			$probe = $probe->nodes[$move];
		}
	}

	static protected function create_node(string $eco="", string $name="", string $move="") : object {
		return (object)["eco"=>$eco, "name"=>$name, "move"=>$move, "nodes"=>[]];
	}

	public function identify(string $moves) : ?array {
	
		$moves = explode(" ", $moves);
		$probe = $this->tree;
		$opening = ["eco"=>"", "name"=>""];
		
		$move_index = 0;
		$move_count = count($moves);
		$continue = true;

		while($move_index < $move_count && $continue === true) {

			$move = $moves[$move_index];
			$pos = strpos($move, ".");
			if($pos !== false) $move = substr($move, $pos + 1);

			if(isset($probe->nodes[$move]) === true) {
				$probe = $probe->nodes[$move];
				$opening["eco"] = $probe->eco;
				$opening["name"] = $probe->name;
			}
			else {
				$continue = false;
			}

			$move_index++;
		}
		return $opening;
	}
}


class UCI {

	protected $proc;
	protected $engine_stdin;
	protected $engine_stdout;
	protected $engine_stderr;

	protected $queue;

	protected $id_name;
	protected $id_author;
	protected $result;
	protected $options;

	public function __constructor() {
		// does not initialise variables?!
	}

	public function __destruct() {
		$this->deinit();
	}


	function _uci() : void {

		$success = fwrite($this->engine_stdin, "uci\n");
		if($success === false) throw new RuntimeException("Unable to send command! (Connection lost?)");
		$this->queue->enqueue("uciok");
	}
	
	function _isready() : void {

		$success = fwrite($this->engine_stdin, "isready\n");
		if($success === false) throw new RuntimeException("Unable to send command! (Connection lost?)");
		$this->queue->enqueue("readyok");
	}
	
	function _option(string $name, string $value="") : void {

		$command = "setoption name {$name}";
		if($value !== "") {
			$command .= " value {$value}";
		}
		$command .= "\n";
		$success = fwrite($this->engine_stdin, $command);
		if($success === false) throw new RuntimeException("Unable to send command! (Connection lost?)");
	}

	function _register(string $name, string $code="") : void {
		$command = "register {$name}";
		if($code !== "") {
			$command .= " code {$code}";
		}
		$command .= "\n";
		$success = fwrite($this->engine_stdin, $command);
		if($success === false) throw new RuntimeException("Unable to send command! (Connection lost?)");
	}

	function _newgame() : void {

		$success = fwrite($this->engine_stdin, "newgame\n");
		if($success === false) throw new RuntimeException("Unable to send command! (Connection lost?)");
	}

	function _position(string $position, string $moves="") : void {

		// to do: implement moves

		$command = "position ";
		if($position === "startpos") {
			$command .= "startpos";
		}
		else {
			$command .= "fen {$position}";
		}
		$command .= "\n";
		$success = fwrite($this->engine_stdin, $command);
		if($success === false) throw new RuntimeException("Unable to send command! (Connection lost?)");
	}
	
	function _go(array $options) : void {

		$command = "go";
		if(isset($options["movetime"]) === true) {
			$move_time = $options["movetime"] * 1000;
			$command .= " movetime {$move_time}";
		}
		$command .= "\n";
		$success = fwrite($this->engine_stdin, $command);
		if($success === false) throw new RuntimeException("Unable to send command! (Connection lost?)");
		$this->queue->enqueue("bestmove");
	}

	function _stop() : void {

		$success = fwrite($this->engine_stdin, "stop\n");
		if($success === false) throw new RuntimeException("Unable to send command! (Connection lost?)");
	}

	function _ponderhit() : void {

		$success = fwrite($this->engine_stdin, "ponderhit\n");
		if($success === false) throw new RuntimeException("Unable to send command! (Connection lost?)");
	}

	function _quit() : void {

		$success = fwrite($this->engine_stdin, "quit\n");
		if($success === false) throw new RuntimeException("Unable to send command! (Connection lost?)");
	}

	function get_id_name() : ?string {
		return $this->id_name;
	}

	function get_id_author() : ?string {
		return $this->id_author;
	}

	function set_timeout(int $timeout) : void {
		$this->timeout = $timeout;
	}

	public function init(string $engine_path) : void {

		$cwd = pathinfo($engine_path, PATHINFO_DIRNAME);
		$cmd = $engine_path;

		$descriptor = [0=>["pipe", "r"], 1=>["pipe", "w"], 2=>["pipe", "w"]];
		$this->proc = proc_open($cmd, $descriptor, $pipes, $cwd, null, ["bypass_shell"=>true]);
		if($this->proc === false) {
			throw new RuntimeException("Unable to connect to the engine! (Incorrect engine_path?)");
		}
		$this->engine_stdin = $pipes[0];
		$this->engine_stdout = $pipes[1];

		$this->queue = new SplQueue();
	}

	public function deinit() : void {
		
		$this->_quit();
		if(is_resource($this->engine_stdin) === true) fclose($this->engine_stdin);
		if(is_resource($this->engine_stdout) === true) fclose($this->engine_stdout);
		if(is_resource($this->engine_stderr) === true) fclose($this->engine_stderr);
		if(is_resource($this->proc) === true) proc_close($this->proc);
	}

	public function sync(int $timeout=2) : bool {

		// wait until expected responses queue is empty or timeout

		$timer = time();
		$info_index = 0;
		$info_count = 50;
		$this->queue->rewind();

		$is_waiting = true;
		while($is_waiting === true) {

			$line = fgets($this->engine_stdout, 512);
			if($line !== false) {
				
				$line = trim($line);
				$code = explode(" ", $line)[0];
				
				if($code === "id") {
					$id_cmd = substr($line, 0, 7);
					if($id_cmd === "id name") {
						$this->id_name = trim(substr($line, 7));
					}
					else if($id_cmd === "id auth") {
						$this->id_author = trim(substr($line, 9));
					}
				}
				else if($code === "option") {

					$option = UCI::create_option();

					// parse option name
					$a = strpos($line, " name ") + strlen(" name ");
					$b = strpos($line, " type ");
					if($a !== false) $option["name"] = substr($line, $a, $b - $a);

					// parse option type
					$a = $b + strlen(" type ");
					$b = strpos($line, " default");
					if($b !== false) $option["type"] = substr($line, $a, $b - $a);
					else $option["type"] = substr($line, $a);

					// parse option default
					if($b !== false) {
						$a = $b + strlen(" default");
						if($option["type"] === "combo") {
							$b = strpos($line, " var ");
						}
						else {
							$b = strpos($line, " min ");	
						}
						if($b !== false) $option["default"] = trim(substr($line, $a, $b - $a));
						else $option["default"] = trim(substr($line, $a));
						if($option["default"] === "<empty>") $option["default"] = "";
					}

					// parse min/max
					if($b !== false && $option["type"] !== "combo") {

						$a = $b + strlen(" min ");
						$b = strpos($line, " max ");
						if($b !== false) $option["min"] = substr($line, $a, $b - $a);
						else $option["min"] = substr($line, $a);

						if($b !== false) {
							$a = $b + strlen(" max ");
							$option["max"] = substr($line, $a);
						}
					}
					// parse values
					else if( ($a = strpos($line, " var ")) !== false ) {
						$option["values"] = substr($line, $a + 5);
						$option["values"] = explode(" var ", $option["values"]);
					}

					$this->options[] = $option;
				}
				else if($code === "uciok") {
					if($this->queue->current() === $code) {
						$this->queue->dequeue();
						$this->queue->rewind();
						echo $line . PHP_EOL;
					}
				}
				else if($code === "readyok") {
					if($this->queue->current() === $code) {
						$this->queue->dequeue();
						$this->queue->rewind();
						echo $line . PHP_EOL;
					}
				}
				else if($code === "info") {
					if($info_index++ > $info_count) {
						echo str_repeat(".", $info_index) . PHP_EOL;
						$info_index = 0;
					}
				}
				else if($code === "bestmove") {
					if($this->queue->current() === $code) {
						
						$result = explode(" ", $line);
						$this->result = ["bestmove"=>$result[1], "ponder"=>$result[3]];
						// echo "found best move" . PHP_EOL;
						$this->queue->dequeue();
						$this->queue->rewind();
						// echo PHP_EOL . $line . PHP_EOL;
					}
				}
			}
			else {
				usleep(300000);
			}

			// test queue
			$is_empty = ($this->queue->isEmpty() === true);
			$is_timed_out = (time() - $timer >= $timeout);

			if($is_empty === true || $is_timed_out === true) {
				$is_waiting = false;
				if($info_index > 0) echo str_repeat(".", $info_index) . PHP_EOL;
			}
		}

		return $is_empty;
	}

	public function get_result() : ?array {
		return $this->result;
	} 

	public function get_option(string $name) : array {

		$found = false;
		$option_index = 0;
		$option_count = count($this->options);

		while($option_index < $option_count && $found !== true) {

			if($this->options[$option_index]["name"] === $name) {
				$found = true;
			}
			$option_index++;
		}

		$option = null;
		if($found === true) {
			$option = $this->options[$option_index - 1];
		}

		return $option;
	}

	protected function create_option(array $options=[]) : array{

		$name = (isset($options["name"]) === true ? $options["name"] : 0);
		$type = (isset($options["type"]) === true ? $options["type"] : "");
		$default = (isset($options["default"]) === true ? $options["default"] : "");
		$min = (isset($options["min"]) === true ? $options["min"] : "");
		$max = (isset($options["max"]) === true ? $options["max"] : "");
		$values = (isset($options["values"]) === true ? $options["values"] : []);
		return ["name"=>$name, "type"=>$type, "default"=>$default, "min"=>$min, "max"=>$max, "values"=>$values];
	}
}


?>