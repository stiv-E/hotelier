<?php
require_once "db.php";

$bookingMessage = '';
$bookingSuccess = false;
$customerCodeValue = '';
$emailValue = '';
$checkInValue = '';
$checkOutValue = '';
$adultsValue = '1';
$childrenValue = '0';
$roomIDValue = '';
$specialRequestValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerCodeValue = strtoupper(trim($_POST['customer_code'] ?? ''));
    $emailValue = trim($_POST['email'] ?? '');
    $checkInValue = trim($_POST['check_in'] ?? '');
    $checkOutValue = trim($_POST['check_out'] ?? '');
    $adultsValue = trim($_POST['adults'] ?? '1');
    $childrenValue = trim($_POST['children'] ?? '0');
    $roomIDValue = trim($_POST['room_id'] ?? '');
    $specialRequestValue = trim($_POST['special_request'] ?? '');

    $checkInDate = DateTime::createFromFormat('!Y-m-d', $checkInValue);
    $checkOutDate = DateTime::createFromFormat('!Y-m-d', $checkOutValue);
    $validDates = $checkInDate && $checkOutDate
        && $checkInDate->format('Y-m-d') === $checkInValue
        && $checkOutDate->format('Y-m-d') === $checkOutValue
        && $checkInDate >= new DateTime('today')
        && $checkOutDate > $checkInDate;
    $validatedAdults = filter_var($adultsValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10]]);
    $validatedChildren = filter_var($childrenValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 10]]);
    $validatedRoomID = filter_var($roomIDValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $specialRequestWords = preg_split('/\s+/u', $specialRequestValue, -1, PREG_SPLIT_NO_EMPTY);
    $specialRequestWordCount = $specialRequestWords === false ? 0 : count($specialRequestWords);

    if (!preg_match('/^[A-Z0-9]{8}$/', $customerCodeValue)
        || !filter_var($emailValue, FILTER_VALIDATE_EMAIL)
        || !$validDates
        || $validatedAdults === false
        || $validatedChildren === false
        || $validatedRoomID === false
        || $specialRequestWords === false
        || $specialRequestWordCount > 120) {
        $bookingMessage = 'Check your customer details, dates, guest counts, room selection, and keep any special request to 120 words or fewer.';
    } else {
        $adultsValue = $validatedAdults;
        $childrenValue = $validatedChildren;
        $roomIDValue = $validatedRoomID;

        try {
            mysqli_begin_transaction($conn);

            $customerStatement = mysqli_prepare($conn, 'SELECT id FROM customers WHERE customer_code = ? AND email = ? LIMIT 1');
            mysqli_stmt_bind_param($customerStatement, 'ss', $customerCodeValue, $emailValue);
            mysqli_stmt_execute($customerStatement);
            $customerResult = mysqli_stmt_get_result($customerStatement);
            $customer = mysqli_fetch_assoc($customerResult);
            mysqli_stmt_close($customerStatement);

            if (!$customer) {
                mysqli_rollback($conn);
                $bookingMessage = 'We could not find an account matching that customer code and email. Register first or check your details.';
            } else {
                $roomStatement = mysqli_prepare($conn, 'SELECT price_per_night, capacity, status FROM rooms WHERE id = ? FOR UPDATE');
                mysqli_stmt_bind_param($roomStatement, 'i', $roomIDValue);
                mysqli_stmt_execute($roomStatement);
                $roomResult = mysqli_stmt_get_result($roomStatement);
                $room = mysqli_fetch_assoc($roomResult);
                mysqli_stmt_close($roomStatement);

                if (!$room || $room['status'] !== 'available') {
                    mysqli_rollback($conn);
                    $bookingMessage = 'That room is no longer available. Please choose another room.';
                } elseif ((int) $adultsValue + (int) $childrenValue > (int) $room['capacity']) {
                    mysqli_rollback($conn);
                    $bookingMessage = 'The selected room does not have enough capacity for your guests.';
                } else {
                    $availabilityStatement = mysqli_prepare(
                        $conn,
                        "SELECT COUNT(*) AS conflicts FROM bookings WHERE room_id = ? AND status IN ('pending', 'confirmed', 'checked_in') AND check_in < ? AND check_out > ?"
                    );
                    mysqli_stmt_bind_param($availabilityStatement, 'iss', $roomIDValue, $checkOutValue, $checkInValue);
                    mysqli_stmt_execute($availabilityStatement);
                    $availabilityResult = mysqli_stmt_get_result($availabilityStatement);
                    $availability = mysqli_fetch_assoc($availabilityResult);
                    mysqli_stmt_close($availabilityStatement);

                    if ((int) $availability['conflicts'] > 0) {
                        mysqli_rollback($conn);
                        $bookingMessage = 'That room is already booked for some of those dates. Please choose another room or date range.';
                    } else {
                        $numberOfNights = (int) $checkInDate->diff($checkOutDate)->days;
                        $totalPrice = (float) $room['price_per_night'] * $numberOfNights;
                        $insertStatement = mysqli_prepare(
                            $conn,
                            'INSERT INTO bookings (customer_id, room_id, check_in, check_out, adults, children, total_price, special_request) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                        );
                        $customerID = (int) $customer['id'];
                        $specialRequest = $specialRequestValue === '' ? null : $specialRequestValue;
                        mysqli_stmt_bind_param(
                            $insertStatement,
                            'iissiids',
                            $customerID,
                            $roomIDValue,
                            $checkInValue,
                            $checkOutValue,
                            $adultsValue,
                            $childrenValue,
                            $totalPrice,
                            $specialRequest
                        );
                        mysqli_stmt_execute($insertStatement);
                        mysqli_stmt_close($insertStatement);
                        mysqli_commit($conn);

                        $bookingSuccess = true;
                        $bookingMessage = 'Your booking request has been received. Its status is pending confirmation.';
                        $customerCodeValue = '';
                        $emailValue = '';
                        $checkInValue = '';
                        $checkOutValue = '';
                        $adultsValue = '1';
                        $childrenValue = '0';
                        $roomIDValue = '';
                        $specialRequestValue = '';
                    }
                }
            }
        } catch (mysqli_sql_exception $exception) {
            mysqli_rollback($conn);
            error_log('Booking submission failed: ' . $exception->getMessage());
            $bookingMessage = 'We could not save your booking right now. Please try again.';
        }
    }
}

$availableRooms = [];
$roomResult = mysqli_query($conn, "SELECT id, room_number, room_type, price_per_night, capacity FROM rooms WHERE status = 'available' ORDER BY room_number");
while ($room = mysqli_fetch_assoc($roomResult)) {
    $availableRooms[] = $room;
}

require_once "includes/header.php";
require_once "includes/navbar.php";

?>

<body>
    <div class="container-xxl bg-white p-0">
        <!-- Spinner Start -->
        <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <!-- Spinner End -->

        <!-- Header Start -->
      
        <!-- Header End -->


        <!-- Page Header Start -->
        <div class="container-fluid page-header mb-5 p-0" style="background-image: url(img/carousel-1.jpg);">
            <div class="container-fluid page-header-inner py-5">
                <div class="container text-center pb-5">
                    <h1 class="display-3 text-white mb-3 animated slideInDown">Booking</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center text-uppercase">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Pages</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">Booking</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
        <!-- Page Header End -->


        <!-- Booking Start -->
        <div class="container-fluid booking pb-5 wow fadeIn" data-wow-delay="0.1s">
            <div class="container">
                <div class="bg-white shadow" style="padding: 35px;">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div>
                            <h5 class="mb-1">Ready to plan your stay?</h5>
                            <p class="mb-0">Choose your room, dates, and guests to send a booking request.</p>
                        </div>
                        <a href="#booking-form" class="btn btn-primary py-3 px-4">Start booking<i class="fa fa-arrow-right ms-3"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Booking End -->


        <!-- Booking Start -->
        <div class="container-xxl py-5">
            <div class="container">
                <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                    <h6 class="section-title text-center text-primary text-uppercase">Room Booking</h6>
                    <h1 class="mb-5">Book A <span class="text-primary text-uppercase">Luxury Room</span></h1>
                </div>
                <div class="row g-5">
                    <div class="col-lg-6">
                        <div class="row g-3">
                            <div class="col-6 text-end">
                                <img class="img-fluid rounded w-75 wow zoomIn" data-wow-delay="0.1s" src="img/about-1.jpg" style="margin-top: 25%;">
                            </div>
                            <div class="col-6 text-start">
                                <img class="img-fluid rounded w-100 wow zoomIn" data-wow-delay="0.3s" src="img/about-2.jpg">
                            </div>
                            <div class="col-6 text-end">
                                <img class="img-fluid rounded w-50 wow zoomIn" data-wow-delay="0.5s" src="img/about-3.jpg">
                            </div>
                            <div class="col-6 text-start">
                                <img class="img-fluid rounded w-75 wow zoomIn" data-wow-delay="0.7s" src="img/about-4.jpg">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="wow fadeInUp" data-wow-delay="0.2s">
                            <?php if ($bookingMessage !== ''): ?>
                                <div class="alert <?php echo $bookingSuccess ? 'alert-success' : 'alert-danger'; ?>" role="status">
                                    <?php echo htmlspecialchars($bookingMessage, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>
                            <form id="booking-form" method="POST" action="booking.php">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="customer_code" name="customer_code" placeholder="Customer code" value="<?php echo htmlspecialchars($customerCodeValue, ENT_QUOTES, 'UTF-8'); ?>" pattern="[A-Za-z0-9]{8}" maxlength="8" required autocomplete="off">
                                            <label for="customer_code">Customer code from registration</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="email" class="form-control" id="email" name="email" placeholder="Your Email" value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="email">
                                            <label for="email">Registered email address</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="date" class="form-control" id="check_in" name="check_in" value="<?php echo htmlspecialchars($checkInValue, ENT_QUOTES, 'UTF-8'); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                                            <label for="check_in">Check In</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="date" class="form-control" id="check_out" name="check_out" value="<?php echo htmlspecialchars($checkOutValue, ENT_QUOTES, 'UTF-8'); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                                            <label for="check_out">Check Out</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select class="form-select" name="adults" id="select1" required>
                                                <?php for ($adultCount = 1; $adultCount <= 10; $adultCount++): ?>
                                                    <option value="<?php echo $adultCount; ?>" <?php echo (string) $adultCount === $adultsValue ? 'selected' : ''; ?>><?php echo $adultCount; ?> <?php echo $adultCount === 1 ? 'adult' : 'adults'; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                            <label for="select1">Adults</label>
                                          </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select class="form-select" name="children" id="select2" required>
                                                <?php for ($childCount = 0; $childCount <= 10; $childCount++): ?>
                                                    <option value="<?php echo $childCount; ?>" <?php echo (string) $childCount === $childrenValue ? 'selected' : ''; ?>><?php echo $childCount === 0 ? 'No children' : $childCount . ' ' . ($childCount === 1 ? 'child' : 'children'); ?></option>
                                                <?php endfor; ?>
                                            </select>
                                            <label for="select2">Children</label>
                                          </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <select class="form-select" name="room_id" id="select3" required>
                                                <option value="">Select an available room</option>
                                                <?php foreach ($availableRooms as $room): ?>
                                                    <option value="<?php echo (int) $room['id']; ?>" data-price="<?php echo htmlspecialchars($room['price_per_night'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) $room['id'] === $roomIDValue ? 'selected' : ''; ?>>
                                                        Room <?php echo htmlspecialchars($room['room_number'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($room['room_type'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($room['price_per_night'], ENT_QUOTES, 'UTF-8'); ?> per night · up to <?php echo (int) $room['capacity']; ?> guests
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label for="select3">Available room</label>
                                          </div>
                                    </div>
                                    <div class="col-12">
                                        <p class="mb-0">Estimated total: <strong id="booking-estimate">Select a room and dates</strong></p>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <textarea class="form-control" id="special_request" name="special_request" placeholder="Special request" style="height: 100px"><?php echo htmlspecialchars($specialRequestValue, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                            <label for="special_request">Anything we should know before your stay?</label>
                                        </div>
                                        <small class="text-muted"><span id="special-request-word-count">0</span>/120 words</small>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-primary w-100 py-3" type="submit">Book Now</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Booking End -->


        <!-- Newsletter Start -->
        <div class="container newsletter mt-5 wow fadeIn" data-wow-delay="0.1s">
            <div class="row justify-content-center">
                <div class="col-lg-10 border rounded p-1">
                    <div class="border rounded text-center p-1">
                        <div class="bg-white rounded text-center p-5">
                            <h4 class="mb-4">Subscribe Our <span class="text-primary text-uppercase">Newsletter</span></h4>
                            <div class="position-relative mx-auto" style="max-width: 400px;">
                                <input class="form-control w-100 py-3 ps-4 pe-5" type="text" placeholder="Enter your email">
                                <button type="button" class="btn btn-primary py-2 px-3 position-absolute top-0 end-0 mt-2 me-2">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Newsletter Start -->
        

        <!-- Footer Start -->
        <div class="container-fluid bg-dark text-light footer wow fadeIn" data-wow-delay="0.1s">
            <div class="container pb-5">
                <div class="row g-5">
                    <div class="col-md-6 col-lg-4">
                        <div class="bg-primary rounded p-4">
                            <a href="index.php"><h1 class="text-white text-uppercase mb-3">Hotelier</h1></a>
                            <p class="text-white mb-0">
                                Experience comfortable rooms, thoughtful service, and a warm welcome every time you stay with us.
							</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <h6 class="section-title text-start text-primary text-uppercase mb-4">Contact</h6>
                        <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>10800, Mombasa, Kenya</p>
                        <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>0712345678</p>
                        <p class="mb-2"><i class="fa fa-envelope me-3"></i>info@example.com</p>
                        <div class="d-flex pt-2">
                            <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-twitter"></i></a>
                            <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-facebook-f"></i></a>
                            <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-youtube"></i></a>
                            <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-12">
                        <div class="row gy-5 g-4">
                            <div class="col-md-6">
                                <h6 class="section-title text-start text-primary text-uppercase mb-4">Company</h6>
                                <a class="btn btn-link" href="">About Us</a>
                                <a class="btn btn-link" href="">Contact Us</a>
                                <a class="btn btn-link" href="">Privacy Policy</a>
                                <a class="btn btn-link" href="">Terms & Condition</a>
                                <a class="btn btn-link" href="">Support</a>
                            </div>
                            <div class="col-md-6">
                                <h6 class="section-title text-start text-primary text-uppercase mb-4">Services</h6>
                                <a class="btn btn-link" href="">Food & Restaurant</a>
                                <a class="btn btn-link" href="">Spa & Fitness</a>
                                <a class="btn btn-link" href="">Sports & Gaming</a>
                                <a class="btn btn-link" href="">Event & Party</a>
                                <a class="btn btn-link" href="">GYM & Yoga</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container">
                <div class="copyright">
                    <div class="row">
                        <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                            &copy; <a class="border-bottom" href="#">Your Site Name</a>, All Right Reserved. 
							
							<!--/*** This template is free as long as you keep the footer author’s credit link/attribution link/backlink. If you'd like to use the template without the footer author’s credit link/attribution link/backlink, you can purchase the Credit Removal License from "https://htmlcodex.com/credit-removal". Thank you for your support. ***/-->
							Designed By <a class="border-bottom" href="https://htmlcodex.com">HTML Codex</a>
                        </div>
                        <div class="col-md-6 text-center text-md-end">
                            <div class="footer-menu">
                                <a href="">Home</a>
                                <a href="">Cookies</a>
                                <a href="">Help</a>
                                <a href="">FQAs</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Footer End -->


        <!-- Back to Top -->
        <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/counterup/counterup.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>
    <script>
        const bookingForm = document.getElementById('booking-form');
        const checkInInput = document.getElementById('check_in');
        const checkOutInput = document.getElementById('check_out');
        const roomInput = document.getElementById('select3');
        const estimateOutput = document.getElementById('booking-estimate');
        const specialRequestInput = document.getElementById('special_request');
        const specialRequestWordCount = document.getElementById('special-request-word-count');

        function updateBookingEstimate() {
            const selectedRoom = roomInput.options[roomInput.selectedIndex];
            const checkIn = new Date(checkInInput.value + 'T00:00:00');
            const checkOut = new Date(checkOutInput.value + 'T00:00:00');
            const nights = Math.round((checkOut - checkIn) / 86400000);
            const nightlyPrice = Number(selectedRoom.dataset.price);

            estimateOutput.textContent = nights > 0 && nightlyPrice > 0
                ? (nightlyPrice * nights).toFixed(2) + ' for ' + nights + (nights === 1 ? ' night' : ' nights')
                : 'Select a room and valid dates';
        }

        [checkInInput, checkOutInput, roomInput].forEach(function (input) {
            input.addEventListener('change', updateBookingEstimate);
        });

        specialRequestInput.addEventListener('input', function () {
            const words = specialRequestInput.value.trim().split(/\s+/).filter(Boolean);
            specialRequestWordCount.textContent = words.length;
            specialRequestWordCount.classList.toggle('text-danger', words.length > 120);
        });

        checkInInput.addEventListener('change', function () {
            checkOutInput.min = checkInInput.value || checkOutInput.min;
        });

        updateBookingEstimate();
    </script>

    <!-- Template Javascript -->
    <script src="js/main.js?v=20261004"></script>
</body>

</html>