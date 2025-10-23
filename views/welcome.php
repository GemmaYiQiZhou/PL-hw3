<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Welcome Page</title>
</head>

<body>
    <h1>Welcome</h1>
    <form method="post" action="index.php?command=auth">
        <label>Name: <input name="name" required></label><br>
        <label>Email: <input type="email" name="email" required></label><br>
        <label>Password: <input type="password" name="password" required></label><br>
        <button type="submit">Enter</button>
    </form>
</body>

</html>