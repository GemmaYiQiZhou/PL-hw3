<?php

// DEBUGGING ONLY! Show all errors.
error_reporting(E_ALL);
ini_set("display_errors", 1);

// Class autoloading by name.
spl_autoload_register(function ($classname) {
  $paths = [
    '/opt/src/' . $classname . '.php',
    __DIR__ . '/' . $classname . '.php',
  ];
  foreach ($paths as $file) {
    if (file_exists($file)) {
      require_once $file;
      return;
    }
  }
  throw new Exception("Class file for '$classname' not found.");
});

$game = new GameController($_GET);

// Run the controller
$game->run();

