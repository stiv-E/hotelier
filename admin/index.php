<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/account_auth.php';

startAccountSession();
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$adminStatement = mysqli_prepare($conn, 'SELECT email FROM admin_users WHERE id = ? AND is_active = 1 LIMIT 1');
mysqli_stmt_bind_param($adminStatement, 'i', $_SESSION['admin_id']);
mysqli_stmt_execute($adminStatement);
$admin = mysqli_fetch_assoc(mysqli_stmt_get_result($adminStatement));
mysqli_stmt_close($adminStatement);
if (!$admin) {
    unset($_SESSION['admin_id']);
    header('Location: login.php');
    exit;
}

$managementMessage = '';
$managementMessageType = 'success';
$allowedStatuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyAccountCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Your session expired. Reload the page and try again.');
    }

    if (($_POST['action'] ?? '') === 'customer') {
        $customerID = filter_var($_POST['customer_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($customerID === false || $firstName === '' || $lastName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '') {
            $managementMessage = 'Check the customer details and try again.';
            $managementMessageType = 'danger';
        } else {
            $statement = mysqli_prepare($conn, 'UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?');
            try {
                mysqli_stmt_bind_param($statement, 'ssssi', $firstName, $lastName, $email, $phone, $customerID);
                mysqli_stmt_execute($statement);
                $managementMessage = 'Customer details updated.';
            } catch (mysqli_sql_exception $exception) {
                $managementMessage = 'Customer details could not be saved. The email may already be in use.';
                $managementMessageType = 'danger';
            }
            mysqli_stmt_close($statement);
        }
    } elseif (($_POST['action'] ?? '') === 'booking') {
        $bookingID = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $status = $_POST['status'] ?? '';

        if ($bookingID === false || !in_array($status, $allowedStatuses, true)) {
            $managementMessage = 'Choose a valid booking and status.';
            $managementMessageType = 'danger';
        } else {
            $statement = mysqli_prepare($conn, 'UPDATE bookings SET status = ? WHERE id = ?');
            mysqli_stmt_bind_param($statement, 'si', $status, $bookingID);
            mysqli_stmt_execute($statement);
            mysqli_stmt_close($statement);
            $managementMessage = 'Booking status updated.';
        }
    }
}

$customers = [];
$customerResult = mysqli_query($conn, 'SELECT id, customer_code, first_name, last_name, email, phone FROM customers ORDER BY id DESC');
while ($customer = mysqli_fetch_assoc($customerResult)) {
    $customers[] = $customer;
}

$bookings = [];
$bookingResult = mysqli_query(
    $conn,
    'SELECT b.id, b.check_in, b.check_out, b.total_price, b.status, c.first_name, c.last_name, c.email, r.room_number, r.room_type '
    . 'FROM bookings b INNER JOIN customers c ON c.id = b.customer_id LEFT JOIN rooms r ON r.id = b.room_id ORDER BY b.check_in DESC, b.id DESC'
);
while ($booking = mysqli_fetch_assoc($bookingResult)) {
    $bookings[] = $booking;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Management | Hotelier</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid bg-dark px-0">
        <nav class="navbar navbar-expand-lg bg-dark navbar-dark px-4 py-3">
            <a href="index.php" class="navbar-brand"><h1 class="m-0 text-primary text-uppercase">Hotelier</h1></a>
            <div class="ms-auto d-flex align-items-center gap-3"><span class="text-light d-none d-sm-inline"><?php echo escapeHtml($admin['email']); ?></span><a href="logout.php" class="btn btn-outline-light">Sign out</a></div>
        </nav>
    </div>
    <main class="container-fluid px-3 px-lg-5 py-5">
        <p class="section-title text-primary text-uppercase">Staff workspace</p>
        <h1 class="mb-4">Guest and booking management</h1>
        <?php if ($managementMessage !== ''): ?><div class="alert alert-<?php echo escapeHtml($managementMessageType); ?>" role="status"><?php echo escapeHtml($managementMessage); ?></div><?php endif; ?>
        <section class="mb-5" aria-labelledby="bookings-heading">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3"><div><h2 id="bookings-heading" class="h3 mb-1">Bookings</h2><p class="text-muted mb-0"><?php echo count($bookings); ?> booking records</p></div></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Guest</th><th>Stay</th><th>Room</th><th>Total</th><th>Status</th><th>Update</th></tr></thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo escapeHtml($booking['first_name'] . ' ' . $booking['last_name']); ?><br><span class="text-muted"><?php echo escapeHtml($booking['email']); ?></span></td>
                                <td><?php echo escapeHtml($booking['check_in']); ?><br><span class="text-muted">to <?php echo escapeHtml($booking['check_out']); ?></span></td>
                                <td><?php echo escapeHtml($booking['room_type'] ?: 'Room'); ?><?php if ($booking['room_number'] !== null): ?><br><span class="text-muted">#<?php echo escapeHtml($booking['room_number']); ?></span><?php endif; ?></td>
                                <td><?php echo escapeHtml(number_format((float) $booking['total_price'], 2)); ?></td>
                                <td><?php echo escapeHtml(ucwords(str_replace('_', ' ', $booking['status']))); ?></td>
                                <td><form method="post" action="index.php" class="d-flex gap-2"><input type="hidden" name="csrf_token" value="<?php echo escapeHtml(accountCsrfToken()); ?>"><input type="hidden" name="action" value="booking"><input type="hidden" name="booking_id" value="<?php echo (int) $booking['id']; ?>"><select class="form-select form-select-sm" name="status" aria-label="Booking status"><?php foreach ($allowedStatuses as $status): ?><option value="<?php echo escapeHtml($status); ?>" <?php echo $booking['status'] === $status ? 'selected' : ''; ?>><?php echo escapeHtml(ucwords(str_replace('_', ' ', $status))); ?></option><?php endforeach; ?></select><button class="btn btn-sm btn-primary" type="submit">Save</button></form></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <section aria-labelledby="customers-heading">
            <h2 id="customers-heading" class="h3 mb-3">Customer records <span class="text-muted fw-normal">(<?php echo count($customers); ?>)</span></h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Code</th><th>Name</th><th>Email</th><th>Phone</th><th>Save</th></tr></thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <?php $customerFormID = 'customer-edit-' . (int) $customer['id']; ?>
                            <tr>
                                <td><?php echo escapeHtml($customer['customer_code']); ?></td>
                                <td><div class="d-flex gap-2"><input form="<?php echo $customerFormID; ?>" class="form-control form-control-sm" name="first_name" aria-label="First name" value="<?php echo escapeHtml($customer['first_name']); ?>" required><input form="<?php echo $customerFormID; ?>" class="form-control form-control-sm" name="last_name" aria-label="Last name" value="<?php echo escapeHtml($customer['last_name']); ?>" required></div></td>
                                <td><input form="<?php echo $customerFormID; ?>" class="form-control form-control-sm" name="email" type="email" aria-label="Email" value="<?php echo escapeHtml($customer['email']); ?>" required></td>
                                <td><input form="<?php echo $customerFormID; ?>" class="form-control form-control-sm" name="phone" type="tel" aria-label="Phone" value="<?php echo escapeHtml($customer['phone']); ?>" required></td>
                                <td><form id="<?php echo $customerFormID; ?>" method="post" action="index.php"><input type="hidden" name="csrf_token" value="<?php echo escapeHtml(accountCsrfToken()); ?>"><input type="hidden" name="action" value="customer"><input type="hidden" name="customer_id" value="<?php echo (int) $customer['id']; ?>"><button class="btn btn-sm btn-outline-primary" type="submit">Save</button></form></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>