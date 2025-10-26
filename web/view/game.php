<?php
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

