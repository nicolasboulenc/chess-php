<?php
declare(strict_types=1);
namespace Chess;

class PGN
{
    protected const WHITE = "w";
    protected const BLACK = "b";
    protected const VALID_TAGS = ["event", "site", "date", "round", "white", "black", "result", "eco"];

    protected $tags;
    protected $moves;

    public function __constructor()
    {
        $this->init();
    }

    protected function init(): void
    {
        $this->tags = [];
        $this->moves = [];
    }

    public function load(string &$pgn): void
    {
        $this->init();
        $this->parseTags($pgn);
        $this->parseMoves($pgn);
    }

    public function stringify(array $options = []): string
    {
        if (count($options) === 0) {
            $options = ["nag_codes" => true, "annotations" => true, "variations" => true];
        }

        return $this->stringifyTags() . PHP_EOL . $this->stringifyMoves($options) . " " . $this->getTag("result");
    }

    protected function parseTags(string &$pgn): void
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

    public function stringifyTags(): string
    {
        $string = "";
        foreach ($this->tags as $tag => $val) {
            $tag = ucwords($tag);
            $string .= "[{$tag} \"{$val}\"]" . PHP_EOL;
        }
        return $string;
    }

    public function getTag(string $tag): string
    {
        $val = "";
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

    protected function parseMoves(string &$pgn): void
    {
        $move = PGN::createMove();
        $move["color"] = PGN::WHITE;

        $char_index = strpos($pgn, "1.");
        $char_count = strpos($pgn, $this->getTag("result"), $char_index);

        $continue = true;

        while ($char_index <= $char_count && $continue === true) {
            // end of the file
            if ($char_index === $char_count) {
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
                    $move = PGN::createMove(["color" => $color]);
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
                if ($move["move"] !== "") {
                    $this->moves[] = $move;
                    $num = $move["num"];

                    $move = PGN::createMove(["num" => $num, "color" => PGN::BLACK]);
                }

                $pos = strpos($pgn, " ", $char_index);
                $buffer = substr($pgn, $char_index, $pos - $char_index);

                $move["move"] = $buffer;

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

    public function stringifyMoves(array $options = []): string
    {
        $move_text = "";
        foreach ($this->moves as $move) {
            if ($move["color"] === PGN::WHITE) {
                $move_text .= "{$move["num"]}.";
            }

            $move_text .= "{$move["move"]} ";

            if (isset($options["nag_codes"]) === true && $options["nag_codes"] === true) {
                $nag_codes = $move["nag_codes"];
                foreach ($nag_codes as $nag_code) {
                    $move_text .= "{$nag_code} ";
                }
            }
            if (isset($options["annotations"]) === true && $options["annotations"] === true) {
                $move_text .= "{$move["annotation"]} ";
            }
            if (isset($options["variations"]) === true && $options["variations"] === true) {
                $move_text .= "{$move["variation"]} ";
            }
        }

        return $move_text;
    }

    protected static function createMove(array $options = []): array
    {
        $num = (isset($options["num"]) === true ? $options["num"] : 0);
        $color = (isset($options["color"]) === true ? $options["color"] : "");
        $move = (isset($options["move"]) === true ? $options["move"] : "");
        return ["num" => $num, "color" => $color, "move" => $move, "nag_codes" => [], "annotation" => "", "variation" => ""];
    }
}
