<?php
session_start();

class GameController
{
    private array $input;

    public function __construct(array $input)
    {
        $this->input = $input;
        $_SESSION['user'] = $_SESSION['user'] ?? null;
        $_SESSION['game'] = $_SESSION['game'] ?? [];
    }

    public function run()
    {
        $command = $this->input['command'] ?? 'welcome';

        if (!$_SESSION['user'] && !in_array($command, ['welcome', 'login'], true)) {
            $this->redirect('?command=welcome');
            return;
        }

        switch ($command) {
            case 'welcome':
                $this->logoutAndShowWelcome();
                break;
            case 'login':
                $this->login();
                break;
            case 'start':
                $this->startGame(true);
                break;
            case 'game':
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
            case 'reshuffle':
                $this->reshuffle();
                break;
            case 'gameover':
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

    private function showWelcome(string $error = ''): void
    {
        $this->renderView('welcome', ['error' => $error]);
    }

    private function logoutAndShowWelcome(): void
    {
        //mark any unfinished games as lost before logout
        if (!empty($_SESSION['user']['id'])) {
            Database::execute(
                "UPDATE hw3_games
                 SET ended_at = NOW(), won = FALSE
                 WHERE user_id = $1 AND ended_at IS NULL",
                [$_SESSION['user']['id']]
            );
            $this->updateUserStats($_SESSION['user']['id']);
        }

        session_unset();
        session_destroy();
        session_start();
        $this->showWelcome();
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

        $user = Database::fetchOne(
            'SELECT user_id, name, email, password_hash FROM hw3_users WHERE LOWER(email)=LOWER($1) LIMIT 1',
            [$email]
        );

        if ($user) {
            if (!password_verify($password, $user['password_hash'])) {
                $this->showWelcome('Incorrect password for that email.');
                return;
            }

            $_SESSION['user'] = [
                'id' => (int)$user['user_id'],
                'name' => $user['name'],
                'email' => $user['email'],
            ];

            $_SESSION['game'] = [];
            unset($_SESSION['cache_words7'], $_SESSION['cache_bank']);
            $this->startGame(true);
            return;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        Database::execute(
            'INSERT INTO hw3_users (name, email, password_hash) VALUES ($1, $2, $3)',
            [$fullname, $email, $hash]
        );

        $created = Database::fetchOne(
            'SELECT user_id, name, email FROM hw3_users WHERE LOWER(email)=LOWER($1) LIMIT 1',
            [$email]
        );

        $_SESSION['user'] = [
            'id' => (int)$created['user_id'],
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

        Database::execute("INSERT INTO hw3_words (word) VALUES ($1) ON CONFLICT DO NOTHING", [$target]);
        $wordRow = Database::fetchOne("SELECT word_id FROM hw3_words WHERE word=$1", [$target]);

        Database::execute(
            "INSERT INTO hw3_games (user_id, target_word_id) VALUES ($1, $2)",
            [$userId, $wordRow['word_id']]
        );

        $_SESSION['game'] = [
            'target' => strtoupper($target),
            'letters' => str_shuffle(strtoupper($target)),
            'score' => 0,
            'valid' => [],
            'invalid' => [],
            'guesses' => []
        ];
        //clear message
        $_SESSION['message'] = ""; 

        $this->renderView('game', ['user' => $_SESSION['user'], 'game' => $_SESSION['game']]);
    }

    private function renderView(string $viewName, array $data = []): void
    {
        extract($data);
        $path = dirname(__DIR__) . "/view/{$viewName}.php";
        if (!file_exists($path)) {
            die("View not found: {$viewName} (looked in {$path})");
        }
        require $path;
    }

    private function loadWordBank(): array
    {
        //change to given 
        $path =  '/var/www/html/homework/word_bank.json';
        //"/data/word_bank.json";
        // /var/www/html/homework/word_bank.json
        if (!file_exists($path)) die("❌ Word bank not found at: {$path}");

        $decoded = json_decode(file_get_contents($path), true);
        if (!is_array($decoded)) die("❌ Invalid word bank structure.");

        $flat = [];
        foreach ($decoded as $group)
            if (is_array($group))
                foreach ($group as $w)
                    if (is_string($w)) $flat[] = strtolower(trim($w));

        return $flat;
    }

    private function loadSevenLetterWords(): array
    {
        //change to given
        $path = '/var/www/html/homework/words7.txt';
        //"/data/words7.txt";
        // /var/www/html/homework/words7.txt
        return file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    private function pickTargetWord(int $userId): string
    {
        $all = $this->loadSevenLetterWords();
        $played = Database::fetchAll(
            "SELECT w.word FROM hw3_games g
             JOIN hw3_words w ON g.target_word_id=w.word_id
             WHERE g.user_id=$1",
            [$userId]
        );
        $playedSet = array_column($played, "word");
        $candidates = array_diff($all, $playedSet);
        return strtoupper($candidates[array_rand($candidates)]);
    }

    private function checkGuess(): void
    {
        $guess = strtoupper(trim($_POST['guess'] ?? ''));
        $target = strtoupper($_SESSION['game']['target']);
        $validWords = $this->loadWordBank();
        $isValid = false;
        
        // reset message each time
        $_SESSION['message'] = ""; 

        if ($guess === '') {
            $_SESSION['message'] = "Please enter a word.";
            $this->renderView('game', ['user' => $_SESSION['user'], 'game' => $_SESSION['game']]);
            return;
        }

        if (in_array($guess, $_SESSION['game']['guesses'] ?? [])) {
            $_SESSION['message'] = "You already guessed {$guess}.";
            $this->renderView('game', ['user' => $_SESSION['user'], 'game' => $_SESSION['game']]);
            return;
        }

        // letter validation
        $letters = array_map('strtoupper', str_split($_SESSION['game']['target']));
        foreach (str_split(strtoupper($guess)) as $char) {
            $pos = array_search($char, $letters, true);
            if ($pos === false) {
                $_SESSION['game']['invalid'][] = $guess;
                $_SESSION['game']['guesses'][] = $guess;
                $_SESSION['message'] = "Used invalid letters.";
                $this->renderView('game', ['user' => $_SESSION['user'], 'game' => $_SESSION['game']]);
                return;
            }
            unset($letters[$pos]);
        }

        // dictionary validation
        $isValid = in_array(strtolower($guess), $validWords, true);

        if (!$isValid) {
            $_SESSION['game']['invalid'][] = $guess;
            $_SESSION['game']['guesses'][] = $guess;
            $_SESSION['message'] = "Not a valid word.";
        } else {
            $_SESSION['game']['valid'][] = $guess;
            $_SESSION['game']['guesses'][] = $guess;
            $points = match (strlen($guess)) {
                1 => 1, 2 => 2, 3 => 4, 4 => 8, 5 => 15, 6 => 30, default => 0,
            };
            $_SESSION['game']['score'] += $points;

            $gameId = Database::fetchOne(
                "SELECT game_id FROM hw3_games WHERE user_id=$1 ORDER BY started_at DESC LIMIT 1",
                [$_SESSION['user']['id']]
            )['game_id'];

            $isValidBool = (bool)$isValid;
            $isTargetBool = ($guess === $target);

            Database::execute(
                "INSERT INTO hw3_guesses (game_id, guess, is_valid, is_target, points)
                 VALUES ($1,$2,$3,$4,$5)",
                [$gameId, $guess, $isValidBool, $isTargetBool, $points]
            );

            if ($guess === $target) {
                Database::execute(
                    "UPDATE hw3_games SET ended_at=NOW(), won=TRUE, score=$1 WHERE game_id=$2",
                    [$_SESSION['game']['score'], $gameId]
                );
                $this->updateUserStats($_SESSION['user']['id']);
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
        // mark unfinished games as lost
        Database::execute(
            "UPDATE hw3_games
            SET ended_at=NOW(), won=FALSE, score=$2
            WHERE user_id=$1 AND ended_at IS NULL",
            [$_SESSION['user']['id'], $_SESSION['game']['score']]
            );

        $this->updateUserStats($_SESSION['user']['id']);

        //$stats = Database::fetchOne(
          //  "SELECT * FROM hw3_user_stats WHERE user_id=$1",
            //[$_SESSION['user']['id']]
        //);
        $stats = Database::fetchOne(
            "SELECT
                COUNT(*) AS games_played,
                SUM(CASE WHEN won THEN 1 ELSE 0 END) AS games_won,
                MAX(score) AS highest_score,
                ROUND(AVG(score), 2) AS average_score
            FROM hw3_games
            WHERE user_id = $1",
            [$_SESSION['user']['id']]
        );

        $this->renderView('over', [
            'game' => $_SESSION['game'],
            'stats' => $stats
        ]);
    }

    private function updateUserStats(int $userId): void
    {
        $stats = Database::fetchOne(
            "SELECT
                COUNT(*) AS games_played,
                SUM(CASE WHEN won THEN 1 ELSE 0 END) AS games_won,
                MAX(score) AS highest_score,
                AVG(score) AS average_score
             FROM hw3_games WHERE user_id=$1",
            [$userId]
        );

        if (!$stats) return;

        Database::execute(
            "INSERT INTO hw3_user_stats (user_id, games_played, games_won, highest_score, average_score)
             VALUES ($1,$2,$3,$4,$5)
             ON CONFLICT (user_id)
             DO UPDATE SET
                games_played=$2,
                games_won=$3,
                highest_score=$4,
                average_score=$5",
            [
                $userId,
                (int)$stats['games_played'],
                (int)$stats['games_won'],
                (int)$stats['highest_score'],
                round((float)$stats['average_score'], 2)
            ]
        );
    }
}
