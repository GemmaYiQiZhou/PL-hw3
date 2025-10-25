<h2>Welcome, <?= htmlspecialchars($_SESSION["name"] ?? "") ?></h2>
<p>Target letters: <?= implode(" ", str_split($_SESSION["target"])) ?></p>
<p>Score: <?= $_SESSION["score"] ?></p>

<form action="index.php?command=guess" method="post">
  <input type="text" name="word" required>
  <button type="submit">Guess</button>
</form>

<?php if (isset($_SESSION["message"])) echo "<p>{$_SESSION["message"]}</p>"; ?>

<h3>Guessed Words:</h3>
<ul>
<?php foreach ($_SESSION["guesses"] as $g) echo "<li>$g</li>"; ?>
</ul>

<a href="index.php?command=over">Quit</a>
