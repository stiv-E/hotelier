<?php
require_once "db.php";

$feedbackMessage = '';
$feedbackSuccess = false;
$feedbackEmail = '';
$feedbackRating = '';
$feedbackText = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'testimonial') {
    $feedbackEmail = trim($_POST['email'] ?? '');
    $feedbackRating = trim($_POST['rating'] ?? '');
    $feedbackText = trim($_POST['message'] ?? '');
    $feedbackWords = preg_split('/\s+/u', $feedbackText, -1, PREG_SPLIT_NO_EMPTY);
    $ratingValue = filter_var($feedbackRating, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);

    if (!filter_var($feedbackEmail, FILTER_VALIDATE_EMAIL)
        || $ratingValue === false
        || $feedbackText === ''
        || $feedbackWords === false
        || count($feedbackWords) > 120) {
        $feedbackMessage = 'Enter a valid registered email, choose a star rating, and keep your comment to 120 words or fewer.';
    } else {
        $customerStatement = mysqli_prepare($conn, 'SELECT id FROM customers WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($customerStatement, 's', $feedbackEmail);
        mysqli_stmt_execute($customerStatement);
        $customerResult = mysqli_stmt_get_result($customerStatement);
        $customer = mysqli_fetch_assoc($customerResult);
        mysqli_stmt_close($customerStatement);

        if (!$customer) {
            $feedbackMessage = 'We could not find a guest account with that email. Use the email you registered with.';
        } else {
            $customerID = (int) $customer['id'];
            $testimonialStatement = mysqli_prepare($conn, 'INSERT INTO testimonials (customer_id, message, rating) VALUES (?, ?, ?)');
            mysqli_stmt_bind_param($testimonialStatement, 'isi', $customerID, $feedbackText, $ratingValue);
            $feedbackSuccess = mysqli_stmt_execute($testimonialStatement);
            mysqli_stmt_close($testimonialStatement);

            if ($feedbackSuccess) {
                $feedbackMessage = 'Thank you for sharing your experience. Your rating and comment have been received.';
                $feedbackEmail = '';
                $feedbackRating = '';
                $feedbackText = '';
            } else {
                $feedbackMessage = 'We could not save your comment right now. Please try again.';
            }
        }
    }
}

$testimonials = [];
$testimonialResult = mysqli_query(
    $conn,
    'SELECT t.id, t.message, t.rating, t.created_at FROM testimonials t ORDER BY t.created_at DESC LIMIT 12'
);
while ($testimonial = mysqli_fetch_assoc($testimonialResult)) {
    $testimonials[] = $testimonial;
}
$testimonialPseudonyms = ['Alex Morgan', 'Sam Kim', 'Jordan Taylor', 'Taylor Nelson', 'Casey Wright', 'Riley Johnson', 'Morgan Adams', 'Jamie Patel', 'Avery Davis', 'Cameron Lee', 'Robin Singh', 'Drew Brown'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Hotelier - Hotel HTML Template</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">  

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <!-- Customized Bootstrap Stylesheet -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="assets/css/style.css?v=20261004" rel="stylesheet">
</head>

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
        <div class="container-fluid bg-dark px-0">
            <div class="row gx-0">
                <div class="col-lg-3 bg-dark d-none d-lg-block">
                    <a href="index.php" class="navbar-brand w-100 h-100 m-0 p-0 d-flex align-items-center justify-content-center">
                        <h1 class="m-0 text-primary text-uppercase">Hotelier</h1>
                    </a>
                </div>
                <div class="col-lg-9">
                    <div class="row gx-0 bg-white d-none d-lg-flex">
                        <div class="col-lg-7 px-5 text-start">
                            <div class="h-100 d-inline-flex align-items-center py-2 me-4">
                                <i class="fa fa-envelope text-primary me-2"></i>
                                <p class="mb-0">info@example.com</p>
                            </div>
                            <div class="h-100 d-inline-flex align-items-center py-2">
                                <i class="fa fa-phone-alt text-primary me-2"></i>
                                <p class="mb-0">0712345678</p>
                            </div>
                        </div>
                        <div class="col-lg-5 px-5 text-end">
                            <div class="d-inline-flex align-items-center py-2">
                                <a class="me-3" href=""><i class="fab fa-facebook-f"></i></a>
                                <a class="me-3" href=""><i class="fab fa-twitter"></i></a>
                                <a class="me-3" href=""><i class="fab fa-linkedin-in"></i></a>
                                <a class="me-3" href=""><i class="fab fa-instagram"></i></a>
                                <a class="" href=""><i class="fab fa-youtube"></i></a>
                            </div>
                        </div>
                    </div>
                    <nav class="navbar navbar-expand-lg bg-dark navbar-dark p-3 p-lg-0">
                        <a href="index.php" class="navbar-brand d-block d-lg-none">
                            <h1 class="m-0 text-primary text-uppercase">Hotelier</h1>
                        </a>
                        <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                            <div class="navbar-nav mr-auto py-0">
                                <a href="index.php" class="nav-item nav-link">Home</a>
                                <a href="about.php" class="nav-item nav-link">About</a>
                                <a href="service.php" class="nav-item nav-link">Services</a>
                                <a href="room.php" class="nav-item nav-link">Rooms</a>
                                <div class="nav-item dropdown">
                                    <a href="#" class="nav-link dropdown-toggle active" data-bs-toggle="dropdown">Pages</a>
                                    <div class="dropdown-menu rounded-0 m-0">
                                        <a href="booking.php" class="dropdown-item">Booking</a>
                                        <a href="registration.php" class="dropdown-item">Guest Registration</a>
                                        <a href="team.php" class="dropdown-item">Our Team</a>
                                        <a href="testimonial.php" class="dropdown-item active">Testimonial</a>
                                    </div>
                                </div>
                                <a href="contact.php" class="nav-item nav-link">Contact</a>
                            </div>
                            <a href="booking.php" class="btn btn-primary rounded-0 py-4 px-md-5 d-none d-lg-block">Book A Room<i class="fa fa-arrow-right ms-3"></i></a>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
        <!-- Header End -->


        <!-- Page Header Start -->
        <div class="container-fluid page-header mb-5 p-0" style="background-image: url(img/carousel-1.jpg);">
            <div class="container-fluid page-header-inner py-5">
                <div class="container text-center pb-5">
                    <h1 class="display-3 text-white mb-3 animated slideInDown">Testimonial</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center text-uppercase">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Pages</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">Testimonial</li>
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
                    <div class="row g-2">
                        <div class="col-md-10">
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <div class="date" id="date1" data-target-input="nearest">
                                        <input type="text" class="form-control datetimepicker-input"
                                            placeholder="Check in" data-target="#date1" data-toggle="datetimepicker" />
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="date" id="date2" data-target-input="nearest">
                                        <input type="text" class="form-control datetimepicker-input" placeholder="Check out" data-target="#date2" data-toggle="datetimepicker"/>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select">
                                        <option selected>Adult</option>
                                        <option value="1">Adult 1</option>
                                        <option value="2">Adult 2</option>
                                        <option value="3">Adult 3</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select">
                                        <option selected>Child</option>
                                        <option value="1">Child 1</option>
                                        <option value="2">Child 2</option>
                                        <option value="3">Child 3</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100">Submit</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Booking End -->


        <!-- Testimonial Start -->
        <div class="container-xxl testimonial mt-5 py-5 bg-dark wow zoomIn" data-wow-delay="0.1s" style="margin-bottom: 90px;">
            <div class="container">
                <div class="owl-carousel testimonial-carousel py-5">
                    <?php foreach ($testimonials as $testimonial): ?>
                        <article class="testimonial-item position-relative bg-white rounded overflow-hidden p-4">
                            <div class="text-primary mb-2" aria-label="<?php echo (int) $testimonial['rating']; ?> out of 5 stars">
                                <?php for ($star = 1; $star <= 5; $star++): ?>
                                    <i class="fa fa-star<?php echo $star <= (int) $testimonial['rating'] ? '' : ' text-muted'; ?>" aria-hidden="true"></i>
                                <?php endfor; ?>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($testimonial['message'], ENT_QUOTES, 'UTF-8')); ?></p>
                            <div class="d-flex align-items-center">
                                <div class="btn-square bg-primary text-white rounded-circle"><i class="fa fa-user" aria-hidden="true"></i></div>
                                <div class="ps-3">
                                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($testimonialPseudonyms[hexdec(substr(hash('sha256', (string) $testimonial['id']), 0, 8)) % count($testimonialPseudonyms)], ENT_QUOTES, 'UTF-8'); ?></h6>
                                    <small>Guest review · pseudonym</small>
                                </div>
                            </div>
                            <i class="fa fa-quote-right fa-3x text-primary position-absolute end-0 bottom-0 me-4 mb-n1" aria-hidden="true"></i>
                        </article>
                    <?php endforeach; ?>
                    <?php if (!$testimonials): ?>
                        <div class="bg-white p-4"><p class="mb-0">Be the first guest to share your experience.</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Testimonial End -->


        <section class="container-xxl py-5" id="share-review">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="bg-light p-4 p-md-5">
                            <div class="mb-4">
                                <h2 class="mb-2">How was your stay?</h2>
                                <p class="mb-0">A quick rating and a few words help other guests choose their stay. It takes one short form.</p>
                            </div>
                            <?php if ($feedbackMessage !== ''): ?>
                                <div class="alert <?php echo $feedbackSuccess ? 'alert-success' : 'alert-danger'; ?>" role="status">
                                    <?php echo htmlspecialchars($feedbackMessage, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>
                            <form method="POST" action="testimonial.php#share-review">
                                <input type="hidden" name="form_type" value="testimonial">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input class="form-control" type="email" id="feedback-email" name="email" placeholder="Your registered email" value="<?php echo htmlspecialchars($feedbackEmail, ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="email">
                                            <label for="feedback-email">Email used for your guest account</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label d-block mb-2" for="feedback-rating">Your rating</label>
                                        <div class="d-flex gap-2" role="radiogroup" aria-label="Choose a rating from one to five stars">
                                            <?php for ($rating = 1; $rating <= 5; $rating++): ?>
                                                <label class="d-inline-flex align-items-center gap-1 text-primary">
                                                    <input type="radio" name="rating" value="<?php echo $rating; ?>" <?php echo (string) $rating === $feedbackRating ? 'checked' : ''; ?> required>
                                                    <span><?php echo $rating; ?><i class="fa fa-star ms-1" aria-hidden="true"></i></span>
                                                </label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <textarea class="form-control" id="feedback-message" name="message" placeholder="Your stay" style="height: 130px" required><?php echo htmlspecialchars($feedbackText, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                            <label for="feedback-message">What stood out about your stay?</label>
                                        </div>
                                        <small class="text-muted"><span id="feedback-word-count">0</span>/120 words</small>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-primary py-3 px-5" type="submit">Share my review</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        

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
        const feedbackMessageInput = document.getElementById('feedback-message');
        const feedbackWordCount = document.getElementById('feedback-word-count');

        feedbackMessageInput.addEventListener('input', function () {
            const words = feedbackMessageInput.value.trim().split(/\s+/).filter(Boolean);
            feedbackWordCount.textContent = words.length;
            feedbackWordCount.classList.toggle('text-danger', words.length > 120);
        });

        feedbackMessageInput.dispatchEvent(new Event('input'));
    </script>

    <!-- Template Javascript -->
    <script src="js/main.js?v=20261004"></script>
</body>

</html>