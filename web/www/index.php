<?php

// DEBUGGING ONLY! Show all errors.
error_reporting(E_ALL);
ini_set("display_errors", 1);

// Class autoloading by name.
spl_autoload_register(function ($classname) {
  include "$classname.php";
});

$game = new GameController($_GET);

// Run the controller
$game->run();

