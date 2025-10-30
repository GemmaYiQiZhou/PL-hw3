
<?php
// Homework 3 - Anagrams Game
// Published at: https://cs4640.cs.virginia.edu/kus8en/hw3 and https://cs4640.cs.virginia.edu/gsm3ck/hw3
$game = $game ?? ['score' => 0, 'guesses' => [], 'valid' => [], 'invalid' => []];
?>

<h2>Game Over</h2>

<p><strong>Final Score:</strong> <?= htmlspecialchars($game['score'] ?? 0) ?></p>

<h3>Guessed Words:</h3>
<?php if (!empty($game['guesses'])): ?>
  <ul>
    <?php foreach ($game['guesses'] as $g): ?>
      <?php $isValid = in_array($g, $game['valid'] ?? []); ?>
      <li style="color: <?= $isValid ? 'green' : 'red' ?>;">
        <?= htmlspecialchars($g) ?>
      </li>
    <?php endforeach; ?>
  </ul>
<?php else: ?>
  <p>No words guessed this round.</p>
<?php endif; ?>

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

<br>
<a href="index.php?command=start">Play Again</a>
<a href="index.php?command=welcome">Exit</a>
