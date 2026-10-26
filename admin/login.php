<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/account_auth.php';

startAccountSession();

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$loginMessage = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailValue = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $statement = mysqli_prepare($conn, 'SELECT id, email, password_hash FROM admin_users WHERE email = ? AND is_active = 1 LIMIT 1');

    if ($statement) {
        mysqli_stmt_bind_param($statement, 's', $emailValue);
        mysqli_stmt_execute($statement);
        $result = mysqli_stmt_get_result($statement);
        $admin = mysqli_fetch_assoc($result);
        mysqli_stmt_close($statement);

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            header('Location: index.php');
            exit;
        }
    }

    $loginMessage = 'The email or password was not recognized.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Staff Login | Hotelier</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <main class="container py-5" style="max-width: 680px;">
        <p class="section-title text-primary text-uppercase">Hotelier management</p>
        <h1 class="mb-4">Staff sign in</h1>
        <?php if ($loginMessage !== ''): ?><div class="alert alert-danger" role="alert"><?php echo escapeHtml($loginMessage); ?></div><?php endif; ?>
        <form method="post" action="login.php" class="bg-light p-4 p-md-5">
            <div class="mb-3"><label class="form-label" for="email">Admin email</label><input class="form-control" id="email" name="email" type="email" value="<?php echo escapeHtml($emailValue); ?>" autocomplete="username" required></div>
            <div class="mb-4"><label class="form-label" for="password">Password</label><input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required></div>
            <button class="btn btn-primary w-100 py-3" type="submit">Sign in</button>
            <a class="d-block text-center mt-3" href="../index.php">Return to website</a>
        </form>
    </main>
</body>
</html>