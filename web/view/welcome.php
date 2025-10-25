<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Welcome Page</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style/style.css">
</head>

<body>
  <div class="card">
    <h1>Welcome</h1>
    <p>Enter your details to begin.</p>
    <form method="post" action="?command=login">
      <label for="fullname">Full name</label>
      <input id="fullname" name="fullname" type="text" required />

      <label for="email">Email</label>
      <input id="email" name="email" type="email" required />

      <label for="password">Password</label>
      <input id="password" name="password" type="password" required />
      <div class="hint">If this is your first time, we’ll create your account.</div>
      <button type="submit" class="btn btn-primary">Start</button>
    </form>
  </div>

</body>

</html>