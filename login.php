<?php
require_once 'db.php';
require_once 'includes/account_auth.php';

startAccountSession();

if (!empty($_SESSION['customer_id'])) {
    header('Location: account.php');
    exit;
}

$loginMessage = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailValue = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $statement = mysqli_prepare($conn, 'SELECT id, first_name, last_name, email, password_hash FROM customers WHERE email = ? LIMIT 1');

    if ($statement) {
        mysqli_stmt_bind_param($statement, 's', $emailValue);
        mysqli_stmt_execute($statement);
        $result = mysqli_stmt_get_result($statement);
        $customer = mysqli_fetch_assoc($result);
        mysqli_stmt_close($statement);

        if ($customer && password_verify($password, $customer['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['customer_id'] = (int) $customer['id'];
            header('Location: account.php');
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
    <title>Customer Login | Hotelier</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-xxl bg-white p-0">
        <div class="container-fluid bg-dark px-0">
            <nav class="navbar navbar-expand-lg bg-dark navbar-dark px-4 py-3">
                <a href="index.php" class="navbar-brand"><h1 class="m-0 text-primary text-uppercase">Hotelier</h1></a>
                <a href="index.php" class="btn btn-outline-light ms-auto">Back to site</a>
            </nav>
        </div>
        <main class="container py-5" style="max-width: 720px;">
            <h1 class="mb-2">Your Hotelier account</h1>
            <p class="text-muted mb-4">Sign in to view your profile and stay history.</p>
            <?php if ($loginMessage !== ''): ?>
                <div class="alert alert-danger" role="alert"><?php echo escapeHtml($loginMessage); ?></div>
            <?php endif; ?>
            <form method="post" action="login.php" class="bg-light p-4 p-md-5">
                <div class="mb-3">
                    <label class="form-label" for="email">Email address</label>
                    <input class="form-control" id="email" name="email" type="email" value="<?php echo escapeHtml($emailValue); ?>" autocomplete="email" required>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <button class="btn btn-primary w-100 py-3" type="submit">Sign in</button>
                <p class="mb-0 mt-3 text-center">New here? <a href="registration.php">Create an account</a></p>
            </form>
        </main>
    </div>
</body>
</html>