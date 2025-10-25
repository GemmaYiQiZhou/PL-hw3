<h2>Game Over</h2>
<p>Final Score: <?= $_SESSION["score"] ?></p>
<p>Words: <?= implode(", ", $_SESSION["guesses"]) ?></p>
<a href="index.php?command=welcome">Play Again</a>
