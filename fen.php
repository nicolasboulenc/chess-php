<?php
declare(strict_types=1);
namespace Chess;

class FEN
{
    // FEN related variables.
    private $Active_Colour;
    private $Castling_Availability;
    private $En_Passant;
    private $Halfmove_Clock;
    private $Fullmove_Number;

    public function __constructor()
    {
        $this->init();
    }

    protected function init(): void
    {
    }

    public function stringify(): string
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
