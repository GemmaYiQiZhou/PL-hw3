<?php
// Homework 3 - Anagrams Game
// Published at: https://cs4640.cs.virginia.edu/kus8en/hw3 and https://cs4640.cs.virginia.edu/gsm3ck/hw3
$game = $_SESSION['game'] ?? [
  'target' => '',
  'letters' => '',
  'score' => 0,
  'guesses' => []
];
?>

<h2>Welcome, <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Player') ?></h2>
<p>Target letters: <?= implode(" ", str_split($game['letters'] ?? '')) ?></p>
<p>Score: <?= $game['score'] ?? 0 ?></p>

<form action="index.php?command=guess" method="post">
  <input type="text" name="guess" required>
  <button type="submit">Guess</button>
</form>

<?php if (!empty($_SESSION['message'])): ?>
  <p><?= htmlspecialchars($_SESSION['message']) ?></p>
<?php endif; ?>

<h3>Guessed Words:</h3>
<ul>
  <?php foreach (($game['guesses'] ?? []) as $g): ?>
    <li><?= htmlspecialchars($g) ?></li>
  <?php endforeach; ?>
</ul>

<a href="index.php?command=reshuffle">Reshuffle</a>

<a href="index.php?command=gameover">Quit</a>

<?php
$stats = Database::fetchOne(
    "SELECT
        COUNT(*) AS games_played,
        ROUND(100.0 * SUM(CASE WHEN won THEN 1 ELSE 0 END) / COUNT(*), 2) AS percent_won,
        MAX(score) AS highest_score,
        ROUND(AVG(score), 2) AS average_score
     FROM hw3_games
     WHERE user_id = $1",
    [$_SESSION['user']['id']]
);
?>

<h3>Player Statistics</h3>
<ul>
  <li>Games Played: <?= $stats['games_played'] ?? 0 ?></li>
  <li>Games Won: <?= $stats['games_won'] ?? 0 ?></li>
  <li>Win Percentage: <?= isset($stats['games_played'], $stats['games_won']) && $stats['games_played'] > 0
        ? round(100 * ($stats['games_won'] / $stats['games_played']), 2)
        : 0 ?>%
  </li>
  <li>Highest Score: <?= $stats['highest_score'] ?? 0 ?></li>
  <li>Average Score: <?= round($stats['average_score'] ?? 0, 2) ?></li>
</ul>
