<?php
// index.php, (no database version yet)

session_start();
require_once "views/game.php";

$controller = new game();
$command = $_GET["command"] ?? "welcome";

switch ($command) {
  case "welcome":
    $controller->showWelcome();
    break;

  case "start":
    $controller->startGame();
    break;

  case "guess":
    $controller->checkGuess();
    break;

  case "reshuffle":
    $controller->reshuffle();
    break;

  case "gameover":
    $controller->showGameOver();
    break;

  default:
    $controller->showWelcome();
}
?>