# chess-php

This implementation is a set of 4 classes: ECO, PGN, UCI, Chess.
Created to support automated analysis of your pgn files.

![](https://github.com/nicolasboulenc/chess-php/workflows/chess-composer/badge.svg)


## Installation

Use composer with `composer require ???/???`   
or put in your composer.json  
```
"require": {
	"???/???": "^1.0"
}
```


## PGN Example Code
The code below loads a PGN file, get tags values and move list.

```php
<?php

require("../pgn.php");
require("../eco.php");

// load pgn file
$file_content = file_get_contents("chess-game+annotations.pgn");
$pgn = new Chess\PGN();
$pgn->load($file_content);

// display players
$white = $pgn->getTag("white");
$black = $pgn->getTag("black");
echo "{$white} vs. {$black}" . PHP_EOL;

// get moves
$moves = $pgn->getSANs();

```

## ECO Example Code
The code below loads a PGN file and uses the ECO class to identify the opening.

```php
<?php

require("../pgn.php");
require("../eco.php");

// load pgn file
$file_content = file_get_contents("chess-game+annotations.pgn");
$pgn = new Chess\PGN();
$pgn->load($file_content);

// load eco file
$eco_content = file_get_contents("scid.eco");
$eco = new Chess\ECO();
$eco->load($eco_content);

// get moves
$moves = $pgn->getSANs();

// identify opening
$opening = $eco->identify($moves);
echo "{$opening["eco"]} {$opening["name"]}" . PHP_EOL;

```
