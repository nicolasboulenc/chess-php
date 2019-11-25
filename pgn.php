<?php

declare(strict_types=1);
namespace Chess;

class PGN
{
    private const WHITE = "w";
    private const BLACK = "b";
    private const VALID_TAGS = ["event", "site", "date", "round", "white", "black", "result", "eco"];

    private $tags;
    private $moves;

    public function __constructor()
    {
        $this->init();
    }

    private function init(): void
    {
        $this->tags = [];
        $this->moves = [];
        $this->moveIndex = 0;
    }

    public function load(string &$pgn): void
    {
        $this->init();
        $this->parseTags($pgn);
        $this->parseMoves($pgn);
    }

    public function stringify(?array $options = null): string
    {
        if ($options === null) {
            $options = ["nag_codes" => true, "annotations" => true, "variations" => true];
        }

        return $this->getTagText() . PHP_EOL . $this->getMoveText($options) . " " . $this->getTag("result");
    }

    private function parseTags(string &$pgn): void
    {
        $lines = explode("[", $pgn);

        foreach ($lines as $line) {
            if ($line === "") {
                continue;
            }

            $line = explode("]", $line);
            $line = $line[0];

            list($tag, $val) = explode(" \"", $line);
            $tag = strtolower($tag);
            $val = substr($val, 0, -1);

            $this->tags[$tag] = $val;
        }
    }

    private function getTagText(): string
    {
        $string = "";
        foreach ($this->tags as $tag => $val) {
            $tag = ucwords($tag);
            $string .= "[{$tag} \"{$val}\"]" . PHP_EOL;
        }
        return $string;
    }

    public function getTag(string $tag): ?string
    {
        $val = null;
        if (isset($this->tags[$tag]) === true) {
            $val = $this->tags[$tag];
        }
        return $val;
    }

    public function setTag(string $tag, $val): void
    {
        $tag = strtolower($tag);
        $is_valid = in_array($tag, PGN::VALID_TAGS);
        if ($is_valid === true) {
            $this->tags[$tag] = $val;
        }
    }

    private function parseMoves(string &$pgn): void
    {
        $move = PGN::createMove();
        $move["color"] = PGN::WHITE;

        $char_index = strpos($pgn, "1.");
        $char_count = strpos($pgn, $this->getTag("result"), $char_index);

        $continue = true;

        while ($char_index <= $char_count && $continue === true) {
            if ($char_index === $char_count) {
                // end of the file
                $this->moves[] = $move;
                $continue = false;
            } elseif (is_numeric($pgn[$char_index]) === true) {
                // move num
                // num can be formated as 12. for white or 32... for black
                $pos = $char_index;
                while (is_numeric($pgn[$pos]) === true || $pgn[$pos] === ".") {
                    $pos++;
                }
                $pos--;

                $buffer = substr($pgn, $char_index, $pos - $char_index + 1);
                $buffer = str_replace(".", "", $buffer);

                if ($move["num"] !== 0) {
                    $this->moves[] = $move;

                    $color = PGN::WHITE;
                    if ($move["color"] === PGN::WHITE) {
                        $color = PGN::BLACK;
                    }
                    $move = PGN::createMove(0, $color);
                }
                $move["num"] = intval($buffer);
                $char_index = $pos + 1;
            } elseif ($pgn[$char_index] === "(") {
                // variation
                $pos = strpos($pgn, ")", $char_index);
                $buffer = substr($pgn, $char_index, $pos - $char_index + 1);
                $buffer = str_replace("\n", " ", $buffer);
                $buffer = str_replace("  ", " ", $buffer);

                $move["variation"] = $buffer;
                $char_index = $pos + 1;
            } elseif ($pgn[$char_index] === "{") {
                // annotation
                $pos = strpos($pgn, "}", $char_index);
                $buffer = substr($pgn, $char_index, $pos - $char_index + 1);
                $buffer = str_replace("\n", " ", $buffer);
                $buffer = str_replace("  ", " ", $buffer);

                $move["annotation"] = $buffer;
                $char_index = $pos + 1;
            } elseif ($pgn[$char_index] === "$") {
                // NAG
                $buffer = "";
                while ($pgn[$char_index] !== " " && $pgn[$char_index] !== "\r" && $pgn[$char_index] !== "\n") {
                    $buffer .= $pgn[$char_index];
                    $char_index++;
                }

                $move["nag_codes"][] = $buffer;
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
                // new black move
                if ($move["san"] !== "") {
                    $this->moves[] = $move;
                    $num = $move["num"];

                    $move = PGN::createMove($num, PGN::BLACK);
                }

                $pos = strpos($pgn, " ", $char_index);
                $buffer = substr($pgn, $char_index, $pos - $char_index);

                $move["san"] = $buffer;

                $char_index = $pos + 1;
            } elseif ($pgn[$char_index] === " " || $pgn[$char_index] === "\n" || $pgn[$char_index] === "\r") {
                // white space
                $char_index++;
            } else {
                echo "Error: Unknown token. {$pgn[$char_index]}!" . PHP_EOL;
                $continue = false;
            }
        }
    }

    public function getMoveText(array $options = []): string
    {
        $move_text = "";
        foreach ($this->moves as $move) {
            if ($move["color"] === PGN::WHITE) {
                $move_text .= "{$move["num"]}.";
            } else {
                $move_text .= " ";
            }

            $move_text .= "{$move["san"]}";

            if (isset($options["nag_codes"]) === true && $options["nag_codes"] === true && count($move["nag_codes"]) > 0) {
                $nag_codes = $move["nag_codes"];
                foreach ($nag_codes as $nag_code) {
                    $move_text .= " {$nag_code}";
                }
            }
            if (isset($options["annotations"]) === true && $options["annotations"] === true && $move["annotation"] !== "") {
                $move_text .= " {$move["annotation"]}";
            }
            if (isset($options["variations"]) === true && $options["variations"] === true && $move["variation"] !== "") {
                $move_text .= " {$move["variation"]}";
            }

            if ($move["color"] === PGN::BLACK) {
                $move_text .= " ";
            }
        }

        return $move_text;
    }

    public function getSANs(): iterable
    {
        $SANs = [];
        foreach ($this->moves as $move) {
            $SANs[] = $move["san"];
        }
        return $SANs;
    }

    public function getMoves(): iterable
    {
        return $this->moves;
    }

    private static function createMove(int $num = 0, string $color = "", string $move = ""): array
    {
        return [    "num"=>$num,
                    "color"=>$color,
                    "san"=>$move,
                    "nag_codes"=>[],
                    "annotation"=>"",
                    "variation"=>"" ];
    }
}
