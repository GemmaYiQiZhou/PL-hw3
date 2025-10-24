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

        $_SESSION['game'] = $_SESSION['game'] ?? [];
        $_SESSION['user'] = $_SESSION['user'] ?? null;
    }


    public function run()
    {
        // Get the command
        $command = $this->input['command'] ?? 'welcome';


        // Gate all pages except welcome/login behind auth
        if (!$_SESSION['user'] && !in_array($command, ['welcome', 'login'], true)) {
            $this->redirect('?command=welcome');
            return;
        }

        switch ($command) {
            case "welcome":
                $this->showWelcome();
                break;

            case 'login':
                $this->login();
                break;

            case "start":
                $this->startGame(true);
                break;

            case "game":
                $this->ensureGameInitialized();
                $this->renderGame();
                break;

            case 'guess':
                $this->checkGuess();
                break;

            case "reshuffle":
                $this->reshuffle();
                break;

            case "gameover":
                $this->showGameOver();
                break;

            default:
                $this->showWelcome();
        }

    }

    private function showWelcome(string $error = '')
    {
        $this->renderView('welcome', [
            'error' => $error
        ]);
    }

    private function login(): void
    {
        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->showWelcome('Please enter a valid email address.');
            return;
        }


        // Look up user by email (case-insensitive)
        $user = Database::fetchOne(
            'SELECT user_id, name, email, password_hash FROM hw3_users WHERE LOWER(email) = LOWER($1) LIMIT 1',
            [$email]
        );


        if ($user) {
            // Existing user → verify password
            if (!password_verify($password, $user['password_hash'])) {
                $this->showWelcome('Incorrect password for that email.');
                return;
            }


            // Success: set session and start fresh game
            $_SESSION['user'] = [
                'id' => (int) $user['user_id'],
                'name' => $user['name'],
                'email' => $user['email'],
            ];


            // Reset any previous game/session-cached dictionaries
            $_SESSION['game'] = [];
            unset($_SESSION['cache_words7'], $_SESSION['cache_bank']);

            $this->startGame(true);
            return;
        }


        // New user → create account with hashed password
        $hash = password_hash($password, PASSWORD_DEFAULT);
        Database::execute(
            'INSERT INTO hw3_users (name, email, password_hash) VALUES ($1, $2, $3)',
            [$fullname, $email, $hash]
        );


        // Fetch the newly created user (primarily to get user_id)
        $created = Database::fetchOne(
            'SELECT user_id, name, email FROM hw3_users WHERE LOWER(email) = LOWER($1) LIMIT 1',
            [$email]
        );


        $_SESSION['user'] = [
            'id' => (int) $created['user_id'],
            'name' => $created['name'],
            'email' => $created['email'],
        ];


        $_SESSION['game'] = [];
        unset($_SESSION['cache_words7'], $_SESSION['cache_bank']);

        $this->startGame(true);
    }

    private function startGame(bool $forceNew = false): void
    {
        $this->renderView('game', [
            'user' => $_SESSION['user'],
            'game' => $_SESSION['game']
        ]);
    }

    private function renderView(string $viewName, array $data = []): void
    {
        // Extracts array keys into variables available to the view
        extract($data);

        // Build path to the view
        $path = __DIR__ . "/view/{$viewName}.php";

        if (!file_exists($path)) {
            die("View not found: $path");
        }

        require $path;
    }

}

