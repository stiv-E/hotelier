<?php
require_once "db.php";

function generateCustomerID($length = 8)
{
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $id = '';

    for ($index = 0; $index < $length; $index++) {
        $id .= $characters[random_int(0, strlen($characters) - 1)];
    }

    return $id;
}

$registrationMessage = '';
$registrationSuccess = false;
$customerName = '';
$customerEmail = '';
$customerPhone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim($_POST['name'] ?? '');
    $customerEmail = trim($_POST['email'] ?? '');
    $customerPhone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $nameParts = preg_split('/\s+/', $customerName, 2);
    $firstName = $nameParts[0] ?? '';
    $lastName = $nameParts[1] ?? '';

    if ($firstName === '' || $lastName === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL) || $customerPhone === '' || strlen($password) < 8) {
        $registrationMessage = 'Please enter your first and last name, valid contact details, and a password with at least 8 characters.';
    } else {
        $customerID = generateCustomerID();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $query = 'INSERT INTO customers (customer_code, first_name, last_name, email, phone, password_hash) VALUES (?, ?, ?, ?, ?, ?)';
        $statement = mysqli_prepare($conn, $query);

        if ($statement) {
            mysqli_stmt_bind_param($statement, 'ssssss', $customerID, $firstName, $lastName, $customerEmail, $customerPhone, $hashedPassword);
            $registrationSuccess = mysqli_stmt_execute($statement);
            $registrationMessage = $registrationSuccess
                ? 'Your account is ready. Keep your customer ID for future bookings.'
                : 'We could not create your account with those details. The email may already be registered.';
            mysqli_stmt_close($statement);
        } else {
            $registrationMessage = 'We could not process your registration right now. Please try again shortly.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Create Your Account | Hotelier</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
    <div class="container-xxl bg-white p-0">
        <div class="container-fluid bg-dark px-0">
            <div class="row gx-0">
                <div class="col-lg-3 bg-dark d-none d-lg-block">
                    <a href="index.php" class="navbar-brand w-100 h-100 m-0 p-0 d-flex align-items-center justify-content-center">
                        <h1 class="m-0 text-primary text-uppercase">Hotelier</h1>
                    </a>
                </div>
                <div class="col-lg-9">
                    <nav class="navbar navbar-expand-lg bg-dark navbar-dark p-3 p-lg-0">
                        <a href="index.php" class="navbar-brand d-block d-lg-none">
                            <h1 class="m-0 text-primary text-uppercase">Hotelier</h1>
                        </a>
                        <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                            <div class="navbar-nav mr-auto py-0">
                                <a href="index.php" class="nav-item nav-link">Home</a>
                                <a href="service.php" class="nav-item nav-link">Services</a>
                                <a href="room.php" class="nav-item nav-link">Rooms</a>
                                <a href="booking.php" class="nav-item nav-link">Booking</a>
                                <a href="login.php" class="nav-item nav-link">My Account</a>
                                <a href="registration.php" class="nav-item nav-link active" aria-current="page">Register</a>
                            </div>
                            <a href="booking.php" class="btn btn-primary rounded-0 py-4 px-md-5 d-none d-lg-block">Book A Room<i class="fa fa-arrow-right ms-3"></i></a>
                        </div>
                    </nav>
                </div>
            </div>
        </div>

        <div class="container-fluid page-header mb-5 p-0" style="background-image: url(img/carousel-1.jpg);">
            <div class="container-fluid page-header-inner py-5">
                <div class="container text-center pb-5">
                    <h1 class="display-3 text-white mb-3 animated slideInDown">Create Your Account</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center text-uppercase">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="booking.php">Booking</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">Register</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        <main class="container-xxl py-5">
            <div class="container">
                <div class="row g-5 justify-content-center align-items-center">
                    <div class="col-lg-5">
                        <div class="text-center text-lg-start wow fadeInUp" data-wow-delay="0.1s">
                            <h6 class="section-title text-primary text-uppercase">A Better Stay Starts Here</h6>
                            <h2 class="mb-4">Join us at <span class="text-primary text-uppercase">Hotelier</span></h2>
                            <p class="mb-4">Create an account to make arranging your stay simple. Once registered, continue to room booking and keep your customer ID for future visits.</p>
                            <a class="btn btn-outline-primary py-3 px-4" href="booking.php">Continue to booking<i class="fa fa-arrow-right ms-3"></i></a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="bg-light p-4 p-md-5 wow fadeInUp" data-wow-delay="0.2s">
                            <h3 class="mb-4">Guest registration</h3>
                            <?php if ($registrationMessage !== ''): ?>
                                <div class="alert <?php echo $registrationSuccess ? 'alert-success' : 'alert-danger'; ?>" role="status">
                                    <?php echo htmlspecialchars($registrationMessage, ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ($registrationSuccess): ?>
                                        <div class="mt-2 fw-bold">Customer code: <?php echo htmlspecialchars($customerID, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <form method="POST" action="registration.php">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input class="form-control" type="text" id="name" name="name" placeholder="Your Name" value="<?php echo htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="name">
                                            <label for="name">Full name (first and last)</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input class="form-control" type="email" id="email" name="email" placeholder="Your Email" value="<?php echo htmlspecialchars($customerEmail, ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="email">
                                            <label for="email">Email address</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input class="form-control" type="tel" id="phone" name="phone" placeholder="Your Phone" value="<?php echo htmlspecialchars($customerPhone, ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="tel">
                                            <label for="phone">Phone number</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input class="form-control" type="password" id="password" name="password" placeholder="Password" minlength="8" required autocomplete="new-password">
                                            <label for="password">Password (at least 8 characters)</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-primary w-100 py-3" type="submit">Create account</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <div class="container-fluid bg-dark text-light py-4">
            <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <span>&copy; Hotelier. We look forward to welcoming you.</span>
                <a href="booking.php" class="text-primary">Plan your stay</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script>new WOW().init();</script>
</body>

</html>