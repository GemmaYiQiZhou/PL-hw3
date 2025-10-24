<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Game</title>
    <link rel="stylesheet" href="style/style.css" />
</head>

<body>
    <div class="card">
        <h1>Anagrams Game</h1>

        <p><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Score:</strong> <?= (int) ($game['score'] ?? 0) ?></p>

        <hr>

        <p><a href="?command=logout">Log out</a></p>
    </div>
</body>

</html>