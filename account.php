<?php
require_once 'db.php';
require_once 'includes/account_auth.php';

startAccountSession();
requireCustomerAccount();
$customerID = (int) $_SESSION['customer_id'];
$accountMessage = '';
$accountMessageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyAccountCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Your session expired. Reload the page and try again.');
    }

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($firstName === '' || $lastName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '') {
        $accountMessage = 'Enter a first name, last name, valid email address, and phone number.';
        $accountMessageType = 'danger';
    } else {
        $statement = mysqli_prepare($conn, 'UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?');

        try {
            mysqli_stmt_bind_param($statement, 'ssssi', $firstName, $lastName, $email, $phone, $customerID);
            mysqli_stmt_execute($statement);
            $accountMessage = 'Your contact details have been updated.';
        } catch (mysqli_sql_exception $exception) {
            $accountMessage = 'Those details could not be saved. The email may already belong to another account.';
            $accountMessageType = 'danger';
        }
        mysqli_stmt_close($statement);
    }
}

$customerStatement = mysqli_prepare($conn, 'SELECT customer_code, first_name, last_name, email, phone FROM customers WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($customerStatement, 'i', $customerID);
mysqli_stmt_execute($customerStatement);
$customerResult = mysqli_stmt_get_result($customerStatement);
$customer = mysqli_fetch_assoc($customerResult);
mysqli_stmt_close($customerStatement);

if (!$customer) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

$bookingStatement = mysqli_prepare(
    $conn,
    'SELECT b.id, b.check_in, b.check_out, b.adults, b.children, b.total_price, b.status, b.special_request, r.room_number, r.room_type '
    . 'FROM bookings b LEFT JOIN rooms r ON r.id = b.room_id WHERE b.customer_id = ? ORDER BY b.check_in DESC, b.id DESC'
);
mysqli_stmt_bind_param($bookingStatement, 'i', $customerID);
mysqli_stmt_execute($bookingStatement);
$bookingResult = mysqli_stmt_get_result($bookingStatement);
$bookings = [];
while ($booking = mysqli_fetch_assoc($bookingResult)) {
    $bookings[] = $booking;
}
mysqli_stmt_close($bookingStatement);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Account | Hotelier</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-xxl bg-white p-0">
        <div class="container-fluid bg-dark px-0">
            <nav class="navbar navbar-expand-lg bg-dark navbar-dark px-4 py-3">
                <a href="index.php" class="navbar-brand"><h1 class="m-0 text-primary text-uppercase">Hotelier</h1></a>
                <div class="ms-auto d-flex align-items-center gap-3">
                    <a href="index.php" class="text-light">Hotelier home</a>
                    <a href="logout.php" class="btn btn-outline-light">Sign out</a>
                </div>
            </nav>
        </div>
        <main class="container py-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-4">
                <div>
                    <p class="section-title text-primary text-uppercase mb-2">Guest account</p>
                    <h1 class="mb-0">Welcome, <?php echo escapeHtml($customer['first_name']); ?></h1>
                </div>
                <p class="text-muted mb-0">Customer code: <strong><?php echo escapeHtml($customer['customer_code']); ?></strong></p>
            </div>
            <?php if ($accountMessage !== ''): ?>
                <div class="alert alert-<?php echo escapeHtml($accountMessageType); ?>" role="status"><?php echo escapeHtml($accountMessage); ?></div>
            <?php endif; ?>
            <div class="row g-5">
                <section class="col-lg-4" aria-labelledby="profile-heading">
                    <h2 id="profile-heading" class="h3 mb-3">Contact details</h2>
                    <form method="post" action="account.php" class="bg-light p-4">
                        <input type="hidden" name="csrf_token" value="<?php echo escapeHtml(accountCsrfToken()); ?>">
                        <div class="mb-3"><label class="form-label" for="first_name">First name</label><input class="form-control" id="first_name" name="first_name" value="<?php echo escapeHtml($customer['first_name']); ?>" required></div>
                        <div class="mb-3"><label class="form-label" for="last_name">Last name</label><input class="form-control" id="last_name" name="last_name" value="<?php echo escapeHtml($customer['last_name']); ?>" required></div>
                        <div class="mb-3"><label class="form-label" for="email">Email address</label><input class="form-control" id="email" name="email" type="email" value="<?php echo escapeHtml($customer['email']); ?>" required></div>
                        <div class="mb-4"><label class="form-label" for="phone">Phone number</label><input class="form-control" id="phone" name="phone" type="tel" value="<?php echo escapeHtml($customer['phone']); ?>" required></div>
                        <button class="btn btn-primary w-100 py-3" type="submit">Save details</button>
                    </form>
                </section>
                <section class="col-lg-8" aria-labelledby="history-heading">
                    <div class="d-flex justify-content-between align-items-end gap-3 mb-3">
                        <div><p class="section-title text-primary text-uppercase mb-2">Your stays</p><h2 id="history-heading" class="h3 mb-0">Visit history</h2></div>
                        <a class="btn btn-outline-primary" href="booking.php">Book a room</a>
                    </div>
                    <?php if (!$bookings): ?>
                        <div class="border p-4"><p class="mb-2">No stays are linked to your account yet.</p><a href="booking.php">Make your first booking</a></div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead><tr><th>Stay</th><th>Room</th><th>Guests</th><th>Total</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td><?php echo escapeHtml(date('M j, Y', strtotime($booking['check_in']))); ?><br><span class="text-muted">to <?php echo escapeHtml(date('M j, Y', strtotime($booking['check_out']))); ?></span></td>
                                            <td><?php echo escapeHtml($booking['room_type'] ?: 'Room'); ?><?php if ($booking['room_number'] !== null): ?><br><span class="text-muted">Room <?php echo escapeHtml($booking['room_number']); ?></span><?php endif; ?></td>
                                            <td><?php echo (int) $booking['adults']; ?> adults, <?php echo (int) $booking['children']; ?> children</td>
                                            <td><?php echo escapeHtml(number_format((float) $booking['total_price'], 2)); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo escapeHtml(ucwords(str_replace('_', ' ', $booking['status']))); ?></span><?php if (!empty($booking['special_request'])): ?><div class="small text-muted mt-1">Request: <?php echo escapeHtml($booking['special_request']); ?></div><?php endif; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</body>
</html>