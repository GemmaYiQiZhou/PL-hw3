<?php

class GameController
{
    private $db;
    private $errorMessage = "";
    public function __construct($input)
    {
        // Start the session! (new!)
        session_start();

        $this->input = $input;
    }

    /**
     * Run the server
     * 
     * Given the input (usually $_GET), then it will determine
     * which command to execute based on the given "command"
     * parameter.  Default is the welcome page.
     */
    public function run()
    {
        // Get the command
        $command = "welcome";
        if (
            isset($this->input["command"]) && (
                $this->input["command"] == "login" || isset($_SESSION["name"]))
        )
            $command = $this->input["command"];

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

    }

    public function login()
    {
        if (
            isset($_POST["fullname"]) && isset($_POST["email"]) &&
            !empty($_POST["fullname"]) && !empty($_POST["email"])
        ) {
            // TODO: check that email looks right!
            $_SESSION["name"] = $_POST["fullname"];
            $_SESSION["email"] = $_POST["email"];
            $_SESSION["score"] = 0;

            header("Location: ?command=question");
            return;
        }

        $this->showWelcome("Name or email missing");
    }


    public function logout()
    {
        // Destroy the session
        session_destroy();

        // Start a new session.  Why?  We want to show the next question.
        session_start();
        $_SESSION["score"] = 0;
    }
    
?>