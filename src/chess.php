<?php

declare(strict_types=1);
namespace nicolasboulenc\Chess;

class Chess
{
    public $chessboard;
    public $pgn;
    public $fen;

    public function __construct()
    {
        $this->chessboard = null;
        $this->pgn = null;
        $this->fen = null;
    }

    public function init()
    {
        $this->chessboard = array(
            "8" => ["a" => "r", "b" => "n", "c" => "b", "d" => "q", "e" => "k", "f" => "b", "g" => "n", "h" => "r"],
            "7" => ["a" => "p", "b" => "p", "c" => "p", "d" => "p", "e" => "p", "f" => "p", "g" => "p", "h" => "p"],
            "6" => ["a" => ".", "b" => ".", "c" => ".", "d" => ".", "e" => ".", "f" => ".", "g" => ".", "h" => "."],
            "5" => ["a" => ".", "b" => ".", "c" => ".", "d" => ".", "e" => ".", "f" => ".", "g" => ".", "h" => "."],
            "4" => ["a" => ".", "b" => ".", "c" => ".", "d" => ".", "e" => ".", "f" => ".", "g" => ".", "h" => "."],
            "3" => ["a" => ".", "b" => ".", "c" => ".", "d" => ".", "e" => ".", "f" => ".", "g" => ".", "h" => "."],
            "2" => ["a" => "P", "b" => "P", "c" => "P", "d" => "P", "e" => "P", "f" => "P", "g" => "P", "h" => "P"],
            "1" => ["a" => "R", "b" => "N", "c" => "B", "d" => "Q", "e" => "K", "f" => "B", "g" => "N", "h" => "R"],
        );

        $this->fen = (object)["activeColor"=>"", "castlingAvailability"=>true, "enPassant"=>false, "halfMoveClock"=>0, "fullMoveNumber"=>0];
    }

    public function next()
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

    private static function createMove(): object
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

    private function moveIsValid($piece, $origin, $destination, $is_taking = false): bool
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
