<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../db.php';

fwrite(STDOUT, "Admin email: ");
$email = trim((string) fgets(STDIN));
fwrite(STDOUT, "Admin password (input is visible): ");
$password = trim((string) fgets(STDIN));

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    fwrite(STDERR, "Use a valid email address and a password with at least 12 characters.\n");
    exit(1);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$statement = mysqli_prepare($conn, 'INSERT INTO admin_users (email, password_hash) VALUES (?, ?)');

try {
    mysqli_stmt_bind_param($statement, 'ss', $email, $passwordHash);
    mysqli_stmt_execute($statement);
    fwrite(STDOUT, "Admin account created.\n");
} catch (mysqli_sql_exception $exception) {
    fwrite(STDERR, "Admin account could not be created. The email may already be registered.\n");
    exit(1);
} finally {
    mysqli_stmt_close($statement);
}