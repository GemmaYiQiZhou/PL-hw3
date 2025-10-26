<?php
session_start();
class GameController
{
    private $db;
    private $errorMessage = "";
    private array $input;
    public function __construct(array $input)
    {
        $this->input = $input;
        $_SESSION['user'] = $_SESSION['user'] ?? null;
        $_SESSION['game'] = $_SESSION['game'] ?? [];
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

            case 'game':
                // If someone hits ?command=game directly
                if (empty($_SESSION['game']['target'])) {
                    $this->startGame(true);
                } else {
                    $this->renderView('game', [
                        'user' => $_SESSION['user'],
                        'game' => $_SESSION['game']
                    ]);
                }
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

    private function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
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
        $userId = $_SESSION['user']['id'];
        $target = $this->pickTargetWord($userId);

        // insert into hw3_words if not already
        Database::execute("INSERT INTO hw3_words (word) VALUES ($1) ON CONFLICT DO NOTHING", [$target]);

        // retrieve word_id
        $wordRow = Database::fetchOne("SELECT word_id FROM hw3_words WHERE word = $1", [$target]);

        // create new game record
        Database::execute(
            "INSERT INTO hw3_games (user_id, target_word_id) VALUES ($1, $2)",
            [$userId, $wordRow['word_id']]
        );

        $_SESSION['game'] = [
            'target' => $target,
            'letters' => str_shuffle($target),
            'score' => 0,
            'guesses' => []
        ];

        $this->renderView('game', ['user' => $_SESSION['user'], 'game' => $_SESSION['game']]);
    }

    private function renderView(string $viewName, array $data = []): void
    {
        extract($data);

        // For your Docker layout, views live inside /opt/src/view/
        $path = dirname(__DIR__) . "/view/{$viewName}.php";


        if (!file_exists($path)) {
            die("View not found: {$viewName} (looked in {$path})");
        }

        require $path;

    }

    private function loadWordBank(): array
    {
        $path = __DIR__ . "/data/word_bank.json";
        $json = file_get_contents($path);
        return json_decode($json, true);
    }

    private function loadSevenLetterWords(): array
    {
        $path = __DIR__ . "/data/words7.txt";
        return file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    private function pickTargetWord(int $userId): string
    {
        $all = $this->loadSevenLetterWords();
        $played = Database::fetchAll(
            "SELECT w.word FROM hw3_games g JOIN hw3_words w ON g.target_word_id = w.word_id WHERE g.user_id = $1",
            [$userId]
        );
        $playedSet = array_column($played, "word");
        $candidates = array_diff($all, $playedSet);
        return strtoupper($candidates[array_rand($candidates)]);
    }

    private function checkGuess(): void
    {
        $guess = strtoupper(trim($_POST['guess'] ?? ''));
        $target = $_SESSION['game']['target'];
        $letters = str_split($target);
        $validWords = $this->loadWordBank();

        if ($guess === '') {
            $_SESSION['message'] = "Please enter a word.";
            $this->renderView('game', ['user' => $_SESSION['user'], 'game' => $_SESSION['game']]);
            return;
        }

        //validate letters
        foreach (str_split($guess) as $char) {
            $pos = array_search($char, $letters);
            if ($pos === false) {
                $_SESSION['game']['invalid'][] = $guess;
                $_SESSION['message'] = "Used invalid letters.";
                $this->renderView('game', ['user' => $_SESSION['user'], 'game' => $_SESSION['game']]);
                return;
            }
            unset($letters[$pos]);
        }

        //validate dictionary
        $isValid = in_array(strtolower($guess), array_map('strtolower', $validWords));

        if (!$isValid) {
            $_SESSION['game']['invalid'][] = $guess;
            $_SESSION['message'] = "Not a valid word.";
        } else {
            $_SESSION['game']['valid'][] = $guess;
            $points = match (strlen($guess)) {
                1 => 1, 2 => 2, 3 => 4, 4 => 8, 5 => 15, 6 => 30, default => 0,
            };
            $_SESSION['game']['score'] += $points;

            //save guess in DB
            $gameId = Database::fetchOne(
                "SELECT game_id FROM hw3_games WHERE user_id=$1 ORDER BY started_at DESC LIMIT 1",
                [$_SESSION['user']['id']]
            )['game_id'];

            Database::execute(
                "INSERT INTO hw3_guesses (game_id, guess, is_valid, is_target, points)
                VALUES ($1, $2, $3, $4, $5)",
                [
                    $gameId,
                    $guess,
                    $isValid,
                    ($guess === $target),
                    $points
                ]
            );

            // If 7-letter target guessed, end game
            if ($guess === $target) {
                Database::execute(
                    "UPDATE hw3_games SET ended_at=NOW(), won=TRUE, score=$1 WHERE game_id=$2",
                    [$_SESSION['game']['score'], $gameId]
                );
                header("Location: ?command=gameover");
                return;
            }
        }

        $this->renderView('game', ['user' => $_SESSION['user'], 'game' => $_SESSION['game']]);
    }

    private function reshuffle(): void
    {
        $_SESSION['game']['letters'] = str_shuffle($_SESSION['game']['target']);
        $this->renderView('game', ['user' => $_SESSION['user'], 'game' => $_SESSION['game']]);
    }

    private function showGameOver(): void
    {
        $stats = Database::fetchOne(
            "SELECT * FROM hw3_user_stats WHERE user_id=$1",
            [$_SESSION['user']['id']]
        );

        $this->renderView('over', [
            'user' => $_SESSION['user'],
            'stats' => $stats,
            'game' => $_SESSION['game']
        ]);

        session_destroy();
    }
}

