<h2>Game Over</h2>
<p>Final Score: <?= $_SESSION["score"] ?></p>
<p>Words: <?= implode(", ", $_SESSION["guesses"]) ?></p>
<a href="index.php?command=welcome">Play Again</a>
<p>Score: <?= htmlspecialchars($game['score'] ?? 0) ?></p>
<p>Valid guesses: <?= implode(', ', $game['valid'] ?? []) ?></p>
<p>Invalid guesses: <?= implode(', ', $game['invalid'] ?? []) ?></p>
<a href="index.php?command=start">Play Again</a>
<a href="index.php?command=welcome">Exit</a>
