<?php
declare(strict_types=1);
namespace nicolasboulenc\Chess;

class UCI
{
    protected $proc;
    protected $engineStdIn;
    protected $engineStdOut;
    protected $engineStdErr;

    protected $queue;

    protected $idName;
    protected $idAuthor;
    protected $result;
    protected $options;

    public function __constructor()
    {
        // does not initialise variables?!
    }

    public function __destruct()
    {
        $this->deinit();
    }

    public function _uci(): void
    {
        $success = fwrite($this->engineStdIn, "uci\n");
        if ($success === false) {
            throw new RuntimeException("Unable to send command! (Connection lost?)");
        }

        $this->queue->enqueue("uciok");
    }

    public function _isready(): void
    {
        $success = fwrite($this->engineStdIn, "isready\n");
        if ($success === false) {
            throw new RuntimeException("Unable to send command! (Connection lost?)");
        }

        $this->queue->enqueue("readyok");
    }

    public function _setoption(string $name, string $value = ""): void
    {
        $command = "setoption name {$name}";
        if ($value !== "") {
            $command .= " value {$value}";
        }
        $command .= "\n";
        $success = fwrite($this->engineStdIn, $command);
        if ($success === false) {
            throw new RuntimeException("Unable to send command! (Connection lost?)");
        }
    }

    public function _register(string $name, string $code = ""): void
    {
        $command = "register {$name}";
        if ($code !== "") {
            $command .= " code {$code}";
        }
        $command .= "\n";
        $success = fwrite($this->engineStdIn, $command);
        if ($success === false) {
            throw new RuntimeException("Unable to send command! (Connection lost?)");
        }
    }

    public function _newgame(): void
    {
        $success = fwrite($this->engineStdIn, "newgame\n");
        if ($success === false) {
            throw new RuntimeException("Unable to send command! (Connection lost?)");
        }
    }

    public function _position(string $position, string $moves = ""): void
    {

        // to do: implement moves

        $command = "position ";
        if ($position === "startpos") {
            $command .= "startpos";
        } else {
            $command .= "fen {$position}";
        }
        $command .= "\n";
        $success = fwrite($this->engineStdIn, $command);
        if ($success === false) {
            throw new RuntimeException("Unable to send command! (Connection lost?)");
        }
    }

    public function _go(array $options): void
    {
        $command = "go";
        if (isset($options["movetime"]) === true) {
            $move_time = $options["movetime"] * 1000;
            $command .= " movetime {$move_time}";
        }
        $command .= "\n";
        $success = fwrite($this->engineStdIn, $command);
        if ($success === false) {
            throw new RuntimeException("Unable to send command! (Connection lost?)");
        }

        $this->queue->enqueue("bestmove");
    }

    public function _stop(): void
    {
        $success = fwrite($this->engineStdIn, "stop\n");
        if ($success === false) {
            throw new RuntimeException("Unable to send command! (Connection lost?)");
        }
    }

    public function _ponderhit(): void
    {
        $success = fwrite($this->engineStdIn, "ponderhit\n");
        if ($success === false) {
            throw new RuntimeException("Unable to send command! (Connection lost?)");
        }
    }

    public function _quit(): void
    {
        $success = fwrite($this->engineStdIn, "quit\n");
        if ($success === false) {
            throw new RuntimeException("Unable to send command! (Connection lost?)");
        }
    }

    public function getIdName(): ?string
    {
        return $this->idName;
    }

    public function getIdAuthor(): ?string
    {
        return $this->idAuthor;
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    public function init(string $engine_path): void
    {
        $cwd = pathinfo($engine_path, PATHINFO_DIRNAME);
        $cmd = $engine_path;

        $descriptor = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
        $this->proc = proc_open($cmd, $descriptor, $pipes, $cwd, null, ["bypass_shell" => true]);
        if ($this->proc === false) {
            throw new RuntimeException("Unable to connect to the engine! (Incorrect engine_path?)");
        }
        $this->engineStdIn = $pipes[0];
        $this->engineStdOut = $pipes[1];

        $this->queue = new \SplQueue();
    }

    public function deinit(): void
    {
        $this->_quit();
        if (is_resource($this->engineStdIn) === true) {
            fclose($this->engineStdIn);
        }

        if (is_resource($this->engineStdOut) === true) {
            fclose($this->engineStdOut);
        }

        if (is_resource($this->engineStdErr) === true) {
            fclose($this->engineStdErr);
        }

        if (is_resource($this->proc) === true) {
            proc_close($this->proc);
        }
    }

    public function sync(int $timeout = 2): bool
    {

        // wait until expected responses queue is empty or timeout

        $timer = time();
        $info_index = 0;
        $info_count = 50;
        $this->queue->rewind();

        $is_empty = ($this->queue->isEmpty() === true);
        if ($is_empty === true) {
            return true;
        }

        $is_waiting = true;
        while ($is_waiting === true) {
            $line = fgets($this->engineStdOut, 512);
            if ($line !== false) {
                $line = trim($line);
                $code = explode(" ", $line)[0];

                if ($code === "id") {
                    $id_cmd = substr($line, 0, 7);
                    if ($id_cmd === "id name") {
                        $this->idName = trim(substr($line, 7));
                    } elseif ($id_cmd === "id auth") {
                        $this->idAuthor = trim(substr($line, 9));
                    }
                } elseif ($code === "option") {
                    $option = UCI::createOption();

                    // parse option name
                    $a = strpos($line, " name ") + strlen(" name ");
                    $b = strpos($line, " type ");
                    if ($a !== false) {
                        $option["name"] = substr($line, $a, $b - $a);
                    }

                    // parse option type
                    $a = $b + strlen(" type ");
                    $b = strpos($line, " default");
                    if ($b !== false) {
                        $option["type"] = substr($line, $a, $b - $a);
                    } else {
                        $option["type"] = substr($line, $a);
                    }

                    // parse option default
                    if ($b !== false) {
                        $a = $b + strlen(" default");
                        if ($option["type"] === "combo") {
                            $b = strpos($line, " var ");
                        } else {
                            $b = strpos($line, " min ");
                        }
                        if ($b !== false) {
                            $option["default"] = trim(substr($line, $a, $b - $a));
                        } else {
                            $option["default"] = trim(substr($line, $a));
                        }

                        if ($option["default"] === "<empty>") {
                            $option["default"] = "";
                        }
                    }

                    if ($b !== false && $option["type"] !== "combo") {
                        // parse min/max
                        $a = $b + strlen(" min ");
                        $b = strpos($line, " max ");
                        if ($b !== false) {
                            $option["min"] = substr($line, $a, $b - $a);
                        } else {
                            $option["min"] = substr($line, $a);
                        }

                        if ($b !== false) {
                            $a = $b + strlen(" max ");
                            $option["max"] = substr($line, $a);
                        }
                    } elseif (($a = strpos($line, " var ")) !== false) {
                        // parse values
                        $option["values"] = substr($line, $a + 5);
                        $option["values"] = explode(" var ", $option["values"]);
                    }

                    $this->options[] = $option;
                } elseif ($code === "uciok") {
                    if ($this->queue->current() === $code) {
                        $this->queue->dequeue();
                        $this->queue->rewind();
                        echo $line . PHP_EOL;
                    }
                } elseif ($code === "readyok") {
                    if ($this->queue->current() === $code) {
                        $this->queue->dequeue();
                        $this->queue->rewind();
                        echo $line . PHP_EOL;
                    }
                } elseif ($code === "info") {
                    if ($info_index++ > $info_count) {
                        echo str_repeat(".", $info_index) . PHP_EOL;
                        $info_index = 0;
                    }
                } elseif ($code === "bestmove") {
                    if ($this->queue->current() === $code) {
                        $result = explode(" ", $line);
                        $this->result = ["bestmove" => $result[1], "ponder" => $result[3]];
                        // echo "found best move" . PHP_EOL;
                        $this->queue->dequeue();
                        $this->queue->rewind();
                        // echo PHP_EOL . $line . PHP_EOL;
                    }
                }
            } else {
                usleep(300000);
            }

            // test queue
            $is_empty = ($this->queue->isEmpty() === true);
            $is_timed_out = (time() - $timer >= $timeout);

            if ($is_empty === true || $is_timed_out === true) {
                $is_waiting = false;
                if ($info_index > 0) {
                    echo str_repeat(".", $info_index) . PHP_EOL;
                }
            }
        }

        return $is_empty;
    }

    public function getResult(): ?array
    {
        return $this->result;
    }

    public function getOption(string $name): ?array
    {
        $found = false;
        $option_index = 0;
        $option_count = count($this->options);

        while ($option_index < $option_count && $found !== true) {
            if ($this->options[$option_index]["name"] === $name) {
                $found = true;
            }
            $option_index++;
        }

        $option = null;
        if ($found === true) {
            $option = $this->options[$option_index - 1];
        }

        return $option;
    }

    protected function createOption(array $options = []): array
    {
        $name = (isset($options["name"]) === true ? $options["name"] : 0);
        $type = (isset($options["type"]) === true ? $options["type"] : "");
        $default = (isset($options["default"]) === true ? $options["default"] : "");
        $min = (isset($options["min"]) === true ? $options["min"] : "");
        $max = (isset($options["max"]) === true ? $options["max"] : "");
        $values = (isset($options["values"]) === true ? $options["values"] : []);
        return ["name" => $name, "type" => $type, "default" => $default, "min" => $min, "max" => $max, "values" => $values];
    }
}
