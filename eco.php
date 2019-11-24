<?php
declare(strict_types=1);
namespace Chess;

class ECO
{
    protected $tree;

    public function __constructor()
    {
        $this->init();
    }

    protected function init(): void
    {
        $this->tree = null;
    }

    public function load(string &$eco): void
    {
        $this->init();

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
        $this->tree = ECO::createNode("A00a", "Start position", "*");

        $eco_index = 1;
        $eco_count = count($eco_array);
        while ($eco_index < $eco_count) {
            $this->buildTree($eco_array[$eco_index]);
            $eco_index++;
        }
    }

    protected function buildTree(string $line): void
    {
        $pos0 = strpos($line, "\"");
        $eco = trim(substr($line, 0, $pos0));
        $pos1 = strpos($line, "\"", $pos0 + 1);
        $name = substr($line, $pos0 + 1, $pos1 - $pos0 - 1);
        $moves = trim(substr($line, $pos1 + 1));

        $probe = $this->tree;
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
                $probe->nodes[$move] = ECO::createNode($eco, $name, $move);
            }
            $probe = $probe->nodes[$move];
        }
    }

    protected static function createNode(string $eco = "", string $name = "", string $move = ""): object
    {
        return (object) ["eco" => $eco, "name" => $name, "move" => $move, "nodes" => []];
    }

    public function identify(string $moves): ?array
    {
        $moves = explode(" ", $moves);
        $probe = $this->tree;
        $opening = ["eco" => "", "name" => ""];

        $move_index = 0;
        $move_count = count($moves);
        $continue = true;

        while ($move_index < $move_count && $continue === true) {
            $move = $moves[$move_index];
            $pos = strpos($move, ".");
            if ($pos !== false) {
                $move = substr($move, $pos + 1);
            }

            if (isset($probe->nodes[$move]) === true) {
                $probe = $probe->nodes[$move];
                $opening["eco"] = $probe->eco;
                $opening["name"] = $probe->name;
            } else {
                $continue = false;
            }

            $move_index++;
        }
        return $opening;
    }
}
