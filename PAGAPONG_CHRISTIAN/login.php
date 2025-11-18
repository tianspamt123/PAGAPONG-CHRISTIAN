<?php
session_start();

$error = '';
$defaultEmail = 'defaultadmin@admin.com';
$defaultPassword = '123456';

// Simple default-only authentication (no DB)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === $defaultEmail && $password === $defaultPassword) {
        $_SESSION['user'] = $email;
        header('Location: adminIndex.php');
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Web Page</title>
    <link rel="stylesheet" href="style.css">
    
</head>
<body>
<div class=container>
    <h1>Log in</h1>
    <p>Please type your email and password</p>
    <?php if (!empty($error)) : ?>
        <p style="color: red;"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></p>
    <?php endif; ?>
    <form id="loginForm" method="post" action="login.php">

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit">LOG IN</button>
        <script src="log.js"></script>
</div>
</body>
</html>
