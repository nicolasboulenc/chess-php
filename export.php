<?php
date_default_timezone_set("Europe/London");

define("MYSQL_ADDRESS", "192.168.0.3");
define("MYSQL_USERNAME", "root");
define("MYSQL_PASSWORD", "nicol@s0112");
define("MYSQL_DATABASE", "chess");

$mysqli = new mysqli(MYSQL_ADDRESS, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DATABASE);
if ($mysqli->connect_error) 
{
	$message = "Error: Unable to connect to database - {$mysqli->connect_errno} {$mysqli->connect_error} !";
	echo "<div class=\"alert alert-danger\" role=\"alert\">{$message}</div>";
	exit();
}

$sql = "SELECT * FROM games ORDER BY `id` DESC";
$res = $mysqli->query($sql);
if ($res === false)
{
	$message = "Error: Unable to list games - {$mysqli->errno} {$mysqli->error} !";
	echo "<div class=\"alert alert-danger\" role=\"alert\">{$message}</div>";
	exit();
}

//$games = "";
while(($row = $res->fetch_object()) !== null) 
{
	$games = "";
	$date = str_replace("-", ".", $row->date);
	$game = "[Event \"?\"]
[Site \"?\"]
[Date \"{$date}\"]
[Round \"?\"]
[White \"{$row->white}\"]
[Black \"{$row->black}\"]
[Result \"{$row->result}\"]

{$row->pgn}";

	file_put_contents("{$row->date} {$row->id}.pgn", $game);
	//$games .= $game . PHP_EOL . PHP_EOL;
}

echo "<pre>{$games}</pre>";

?>