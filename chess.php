<?php

declare(strict_types=1);

namespace Chess;

class Chess
{
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

    public function __construct()
    {
        $this->Chessboard = Chess::chessboard_create();
    }

    public function pgn_load($pgn)
    {
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

        $beg = 0; // for debug purpose
        $end = 0; // for debug purpose

        $game_end_index = 0;

        $char_index = 0;
        $char_count = strlen($pgn);

        while ($char_index < $char_count) {
            // check for end of game
            if ($game_end_index !== 0 && $char_index === $game_end_index) {
                // do end of game things
                // echo "reached end of game." . PHP_EOL;
                $char_index += strlen($this->Result);
                $this->Moves[] = $move;

                // reset variables
                //$game = PGN_Game_Init();
                $game_end_index = 0;
            } elseif ($pgn[$char_index] === "[") {
                // tag
                $pos = strpos($pgn, "]", $char_index);
                // trim square brackets
                $buffer = substr($pgn, $char_index + 1, $pos - $char_index - 2);
                $tokens = explode(" \"", $buffer);
                if ($tokens[0] === "Event") {
                    $this->Event = $tokens[1];
                } elseif ($tokens[0] === "Site") {
                    $this->Site = $tokens[1];
                } elseif ($tokens[0] === "Date") {
                    $this->Date = $tokens[1];
                } elseif ($tokens[0] === "Round") {
                    $this->Round = $tokens[1];
                } elseif ($tokens[0] === "White") {
                    $this->White = $tokens[1];
                } elseif ($tokens[0] === "Black") {
                    $this->Black = $tokens[1];
                } elseif ($tokens[0] === "Result") {
                    $this->Result = $tokens[1];
                    $offset = $char_index + strlen("[Result \"\"]") + strlen($this->Result);
                    $game_end_index = strpos($pgn, $this->Result, $offset);
                }
                $char_index = $pos + 1;
            } elseif (is_numeric($pgn[$char_index]) === true) {
                // move num?
                $pos = strpos($pgn, ".", $char_index);

                // ignore black move number in form of #...
                $buffer = substr($pgn, $char_index, $pos - $char_index + 1);
                if ($pgn[$pos + 1] === "." && $pgn[$pos + 1] === ".") {
                    $char_index = $pos + 3;
                    continue;
                }

                $buffer = substr($pgn, $char_index, $pos - $char_index + 1);
                $end = $pos - strlen($buffer);

                if ($move["num"] != 0) {
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
            } elseif ($pgn[$char_index] === "(") {
                // variation
                $pos = strpos($pgn, ")", $char_index);
                $buffer = substr($pgn, $char_index, $pos - $char_index + 1);
                $buffer = str_replace("\n", " ", $buffer);
                $buffer = str_replace("  ", " ", $buffer);

                if ($move->black->move === "") {
                    $move->white->variation = $buffer;
                } else {
                    $move->black->variation = $buffer;
                }

                $char_index = $pos + 1;
            } elseif ($pgn[$char_index] === "{") {
                // annotation
                $pos = strpos($pgn, "}", $char_index);
                $buffer = substr($pgn, $char_index, $pos - $char_index + 1);
                $buffer = str_replace("\n", " ", $buffer);
                $buffer = str_replace("  ", " ", $buffer);

                if ($move->black->move === "") {
                    $move->white->annotation = $buffer;
                } else {
                    $move->black->annotation = $buffer;
                }

                $char_index = $pos + 1;
            } elseif ($pgn[$char_index] === "$") {
                // NAG
                $buffer = "";
                while ($pgn[$char_index] !== " " && $pgn[$char_index] !== "\r" && $pgn[$char_index] !== "\n") {
                    $buffer .= $pgn[$char_index];
                    $char_index++;
                }

                if ($move->black->move === "") {
                    $move->white->nag_codes[] = $buffer;
                } else {
                    $move->black->nag_codes[] = $buffer;
                }
            } elseif ($pgn[$char_index] === "a" ||
                // move
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
                $pgn[$char_index] === "O") {
                $pos = strpos($pgn, " ", $char_index);
                $buffer = substr($pgn, $char_index, $pos - $char_index);

                if ($move->white->move === "") {
                    $move->white->move = $buffer;
                } else {
                    $move->black->move = $buffer;
                }

                $char_index = $pos;
            } elseif ($pgn[$char_index] === " " || $pgn[$char_index] === "\n") {
                // white space
                $char_index++;
            } else {
                echo "Error: Unknown token. {$pgn[$char_index]}!" . PHP_EOL;
                return;
            }
        }
    }

    public function pgn_next()
    {
        if ($this->Move_Index === count($this->Moves)) {
            return null;
        }

        // get the right move
        $move = $this->Moves[$this->Move_Index]->white->move;
        if ($this->Active_Colour === "b") {
            $move = $this->Moves[$this->Move_Index]->black->move;
        }

        // execute the move
        $this->Move($move, $this->Active_Colour === "w");
        $res = (object) ["move" => $move, "colour" => $this->Active_Colour, "num" => $this->Move_Index + 1];

        // update state
        if ($this->Active_Colour === "w") {
            $this->Active_Colour = "b";
        } else {
            $this->Active_Colour = "w";
            $this->Move_Index++;
        }

        return $res;
    }

    public function eco_load($eco)
    {
        $eco_array = explode("\n", $eco);

        $eco_index = 0;

        // tidy up array
        while ($eco_index < count($eco_array)) {
            if (strlen($eco_array[$eco_index]) === 0) {
                array_splice($eco_array, $eco_index, 1);
            } elseif ($eco_array[$eco_index][0] === "#") {
                array_splice($eco_array, $eco_index, 1);
            } elseif ($eco_array[$eco_index][0] === " ") {
                // append moves to previous line although not first move
                $eco_array[$eco_index - 1] .= $eco_array[$eco_index];
                array_splice($eco_array, $eco_index, 1);
            } else {
                $eco_index++;
            }
        }

        // build tree
        $tree = (object) ["eco" => "A00a", "name" => "Start position", "move" => "*", "nodes" => []];

        $eco_index = 1;
        $eco_count = count($eco_array);
        while ($eco_index < $eco_count) {
            $this->eco_build($tree, $eco_array[$eco_index]);
            $eco_index++;
        }

        return $tree;
    }

    private function eco_build(object $tree, string $line)
    {
        $pos0 = strpos($line, "\"");
        $eco = trim(substr($line, 0, $pos0));
        $pos1 = strpos($line, "\"", $pos0 + 1);
        $name = substr($line, $pos0 + 1, $pos1 - $pos0 - 1);
        $moves = trim(substr($line, $pos1 + 1));

        $probe = $tree;
        $moves = explode(" ", $moves);
        foreach ($moves as $move) {
            if ($move === "") {
                continue;
            }

            $pos = strpos($move, ".");
            if ($pos !== false) {
                $move = substr($move, $pos + 1);
            }

            if (isset($probe->nodes[$move]) !== true) {
                $probe->nodes[$move] = (object) ["eco" => $eco, "name" => $name, "move" => $move, "nodes" => []];
            }
            $probe = $probe->nodes[$move];
        }
    }

    public function eco_detect()
    {
    }

    private static function move_create(): object
    {
        $move = (object) array("num" => 0,
            "white" => null,
            "black" => null);

        $move->white = (object) array("move" => "",
            "nag_codes" => [],
            "annotation" => "",
            "variation" => "");

        $move->black = (object) array("move" => "",
            "nag_codes" => [],
            "annotation" => "",
            "variation" => "");

        return $move;
    }

    private static function chessboard_create(): array
    {
        return array(
            "8" => ["a" => "r", "b" => "n", "c" => "b", "d" => "q", "e" => "k", "f" => "b", "g" => "n", "h" => "r"],
            "7" => ["a" => "p", "b" => "p", "c" => "p", "d" => "p", "e" => "p", "f" => "p", "g" => "p", "h" => "p"],
            "6" => ["a" => ".", "b" => ".", "c" => ".", "d" => ".", "e" => ".", "f" => ".", "g" => ".", "h" => "."],
            "5" => ["a" => ".", "b" => ".", "c" => ".", "d" => ".", "e" => ".", "f" => ".", "g" => ".", "h" => "."],
            "4" => ["a" => ".", "b" => ".", "c" => ".", "d" => ".", "e" => ".", "f" => ".", "g" => ".", "h" => "."],
            "3" => ["a" => ".", "b" => ".", "c" => ".", "d" => ".", "e" => ".", "f" => ".", "g" => ".", "h" => "."],
            "2" => ["a" => "P", "b" => "P", "c" => "P", "d" => "P", "e" => "P", "f" => "P", "g" => "P", "h" => "P"],
            "1" => ["a" => "R", "b" => "N", "c" => "B", "d" => "Q", "e" => "K", "f" => "B", "g" => "N", "h" => "R"],
        );
    }

    public function move($move, $white_playing = true)
    {

        // todo: manage castle and promotions
        $is_taking = (strpos($move, "x") === false ? 0 : 1);
        $is_checking = (strpos($move, "+") === false ? 0 : 1) + (strpos($move, "#") === false ? 0 : 1);
        $is_pawn = 0;

        $piece = "";
        if ($move[0] === "R" ||
            $move[0] === "N" ||
            $move[0] === "B" ||
            $move[0] === "Q" ||
            $move[0] === "K") {
            $piece = $move[0];
        } elseif ($move[0] >= "a" && $move[0] <= "h") {
            $piece = "P";
            $is_pawn = 1;
        } elseif ($move[0] == "O") {
            echo "about to castle" . PHP_EOL;
            if ($move === "O-O" && $white_playing === true) {
                echo "white castle short" . PHP_EOL;
                $this->Chessboard[1]["e"] = ".";
                $this->Chessboard[1]["g"] = "K";
                $this->Chessboard[1]["h"] = ".";
                $this->Chessboard[1]["f"] = "R";
            } elseif ($move === "O-O-O" && $white_playing === true) {
                $this->Chessboard[1]["e"] = ".";
                $this->Chessboard[1]["c"] = "K";
                $this->Chessboard[1]["a"] = ".";
                $this->Chessboard[1]["d"] = "R";
            } elseif ($move === "O-O" && $white_playing === false) {
                $this->Chessboard[8]["e"] = ".";
                $this->Chessboard[8]["g"] = "k";
                $this->Chessboard[8]["h"] = ".";
                $this->Chessboard[8]["f"] = "r";
            } elseif ($move === "O-O-O" && $white_playing === false) {
                $this->Chessboard[8]["e"] = ".";
                $this->Chessboard[8]["c"] = "k";
                $this->Chessboard[8]["a"] = ".";
                $this->Chessboard[8]["d"] = "r";
            }

            return;
        }

        if ($white_playing !== true) {
            $piece = strtolower($piece);
        }

        $disambiguation = strlen($move) - (3 - $is_pawn + $is_taking + $is_checking);

        $origin = "";
        $destination = substr($move, 1 - $is_pawn + $disambiguation + $is_taking, 2);

        // check disambiguation
        if ($disambiguation > 0) {
            $origin = substr($move, 1 - $is_pawn, $disambiguation);
            // check if file, rank or both disambiguation
            if (strlen($origin) === 1) {
                if (is_numeric($origin) === true) {
                    // search rank
                    $file = "a";
                    $rank = $origin;
                    $is_valid_move = false;

                    while ($file < "i" && $is_valid_move === false) {
                        if ($this->Chessboard[$rank][$file] === $piece) {
                            $origin = $file . $rank;
                            $is_valid_move = Chessboard_Is_Valid_Move($piece, $origin, $destination, $is_taking === 1);
                        }
                        $file = chr(ord($file) + 1);
                    }
                } else {
                    // search rank
                    $file = $origin;
                    $rank = 1;
                    $is_valid_move = false;

                    while ($rank < 9 && $is_valid_move === false) {
                        if ($this->Chessboard[$rank][$file] === $piece) {
                            $origin = $file . $rank;
                            $is_valid_move = $this->Move_Is_Valid($piece, $origin, $destination, $is_taking === 1);
                        }
                        $rank++;
                    }
                }
            } else {
                // nothing to do we should have the origin as per disambiguation.
            }
        } else {
            // search the whole board
            $file = "a";
            $rank = 1;
            $is_valid_move = false;

            while ($file < "i" && $is_valid_move === false) {
                $rank = 1;
                while ($rank < 9 && $is_valid_move === false) {
                    if ($this->Chessboard[$rank][$file] === $piece) {
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

    private function move_is_valid($piece, $origin, $destination, $is_taking = false): bool
    {
        // This is only used to find the moving piece, as such does not check:
        // out of board plays, checks, check mates, etc
        // todo: Should manage interposing pieces???

        $file_map = array("a" => 1, "b" => 2, "c" => 3, "d" => 4, "e" => 5, "f" => 6, "g" => 7, "h" => 8);

        $orig_file = $file_map[$origin[0]];
        $orig_rank = intval($origin[1]);

        $dest_file = $file_map[$destination[0]];
        $dest_rank = intval($destination[1]);

        if ($piece === "p") {
            return ($dest_file === $orig_file && $is_taking === false && $orig_rank === 7 && $dest_rank === $orig_rank - 2) ||
                ($dest_file === $orig_file && $is_taking === false && $dest_rank === $orig_rank - 1) ||
                ($dest_file === $orig_file - 1 && $is_taking === true && $dest_rank === $orig_rank - 1) ||
                ($dest_file === $orig_file + 1 && $is_taking === true && $dest_rank === $orig_rank - 1);
        } elseif ($piece === "P") {
            return ($dest_file === $orig_file && $is_taking === false && $orig_rank === 2 && $dest_rank === $orig_rank + 2) ||
                ($dest_file === $orig_file && $is_taking === false && $dest_rank === $orig_rank + 1) ||
                ($dest_file === $orig_file - 1 && $is_taking === true && $dest_rank === $orig_rank + 1) ||
                ($dest_file === $orig_file + 1 && $is_taking === true && $dest_rank === $orig_rank + 1);
        } elseif ($piece === "b" || $piece === "B") {
            return ($dest_file - $orig_file === $dest_rank - $orig_rank);
        } elseif ($piece === "r" || $piece === "R") {
            return ($dest_file === $orig_file && $dest_rank !== $orig_rank) ||
                ($dest_file !== $orig_file && $dest_rank === $orig_rank);
        } elseif ($piece === "n" || $piece === "N") {
            return ($dest_file === $orig_file + 1 && $dest_rank === $orig_rank + 2) ||
                ($dest_file === $orig_file + 2 && $dest_rank === $orig_rank + 1) ||
                ($dest_file === $orig_file + 2 && $dest_rank === $orig_rank - 1) ||
                ($dest_file === $orig_file + 1 && $dest_rank === $orig_rank - 2) ||
                ($dest_file === $orig_file - 1 && $dest_rank === $orig_rank - 2) ||
                ($dest_file === $orig_file - 2 && $dest_rank === $orig_rank + 1) ||
                ($dest_file === $orig_file - 2 && $dest_rank === $orig_rank - 1) ||
                ($dest_file === $orig_file - 1 && $dest_rank === $orig_rank + 2);
        } else {
            return false;
        }
    }

    public function ascii(): string
    {
        //$file = chr(ord($file) + 1);
        $rank = 8;
        $file = "a";

        $ascii = "  +------------------------+" . PHP_EOL;

        while ($rank > 0) {
            $file = "a";
            $ascii .= "${rank} |";
            while ($file < "i") {
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

    public function fen(): string
    {
        $fen = "";

        $rank = 8;
        while ($rank > 0) {
            $file = "a";
            while ($file < "i") {
                if ($this->Chessboard[$rank][$file] !== ".") {
                    $fen .= $this->Chessboard[$rank][$file];
                } else {
                    $consecutive_blanks = 0;
                    while ($file < "i" && $this->Chessboard[$rank][$file] === ".") {
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
}

?>