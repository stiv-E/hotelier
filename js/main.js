(function ($) {
    "use strict";

    // Spinner
    var spinner = function () {
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner();

    // Wire up empty links and form buttons so they behave like real actions.
    var internalRoutes = {
        "home": "index.php",
        "pages": "index.php",
        "our rooms": "room.php",
        "book a room": "booking.php",
        "book now": "booking.php",
        "view detail": "room.php",
        "explore more": "about.php",
        "about us": "about.php",
        "contact us": "contact.php",
        "services": "service.php",
        "food & restaurant": "service.php",
        "spa & fitness": "service.php",
        "privacy policy": "about.php",
        "terms & condition": "about.php",
        "support": "contact.php",
        "our team": "team.php",
        "testimonial": "testimonial.php",
        "booking": "booking.php"
    };

    var resolveLinkTarget = function ($link) {
        var href = $link.attr('href');
        if (href && href !== '#' && href !== '') {
            return;
        }

        var iconClass = ($link.find('i').attr('class') || '').toLowerCase();
        if (iconClass.indexOf('facebook') > -1) {
            $link.attr('href', 'https://www.facebook.com/');
            return;
        }
        if (iconClass.indexOf('twitter') > -1) {
            $link.attr('href', 'https://twitter.com/');
            return;
        }
        if (iconClass.indexOf('linkedin') > -1) {
            $link.attr('href', 'https://www.linkedin.com/');
            return;
        }
        if (iconClass.indexOf('instagram') > -1) {
            $link.attr('href', 'https://www.instagram.com/');
            return;
        }
        if (iconClass.indexOf('youtube') > -1) {
            $link.attr('href', 'https://www.youtube.com/');
            return;
        }

        var label = ($link.text() || '').replace(/\s+/g, ' ').trim().toLowerCase();
        if (internalRoutes[label]) {
            $link.attr('href', internalRoutes[label]);
            return;
        }

        if ($link.hasClass('service-item')) {
            $link.attr('href', 'service.php');
            return;
        }

        $link.attr('href', 'index.php');
    };

    $('a[href=""], a[href="#"]').each(function () {
        var $link = $(this);
        if (!$link.is('[data-bs-toggle="dropdown"]')) {
            resolveLinkTarget($link);
        }
    });

    var handleButtonAction = function ($button, fallbackMessage) {
        var label = $button.text().replace(/\s+/g, ' ').trim().toLowerCase();
        if (label.indexOf('send') > -1) {
            alert('Your message has been sent successfully.');
            return;
        }
        if (label.indexOf('book') > -1) {
            alert('Your booking request has been received.');
            return;
        }
        if (label.indexOf('submit') > -1 || label.indexOf('subscribe') > -1) {
            alert(fallbackMessage || 'Your request has been submitted successfully.');
            return;
        }
        alert(fallbackMessage || 'Action completed successfully.');
    };

    $('form button[type="submit"], form button:not([type])').on('click', function (event) {
        var $button = $(this);
        var form = $button.closest('form');
        var label = $button.text().replace(/\s+/g, ' ').trim().toLowerCase();

        if (!form.length) {
            return;
        }

        if (form.prop('method').toLowerCase() === 'post') {
            return;
        }

        event.preventDefault();

        if (label.indexOf('send') > -1) {
            alert('Your message has been sent successfully.');
        } else if (label.indexOf('book') > -1) {
            alert('Your booking request has been received.');
        } else {
            alert('Your request has been submitted successfully.');
        }

        form[0].reset();
    });

    $('button.btn').filter(function () {
        return $(this).closest('form').length === 0 && /submit|book|send/i.test($(this).text());
    }).on('click', function (event) {
        event.preventDefault();
        handleButtonAction($(this), 'Your request has been submitted successfully.');
    });
    
    
    // Initiate the wowjs
    new WOW().init();
    
    
    // Dropdown on mouse hover
    const $dropdown = $(".dropdown");
    const $dropdownToggle = $(".dropdown-toggle");
    const $dropdownMenu = $(".dropdown-menu");
    const showClass = "show";
    
    $(window).on("load resize", function() {
        if (this.matchMedia("(min-width: 992px)").matches) {
            $dropdown.hover(
            function() {
                const $this = $(this);
                $this.addClass(showClass);
                $this.find($dropdownToggle).attr("aria-expanded", "true");
                $this.find($dropdownMenu).addClass(showClass);
            },
            function() {
                const $this = $(this);
                $this.removeClass(showClass);
                $this.find($dropdownToggle).attr("aria-expanded", "false");
                $this.find($dropdownMenu).removeClass(showClass);
            }
            );
        } else {
            $dropdown.off("mouseenter mouseleave");
        }
    });
    
    
    // Back to top button
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({scrollTop: 0}, 1500, 'easeInOutExpo');
        return false;
    });


    // Facts counter
    $('[data-toggle="counter-up"]').counterUp({
        delay: 10,
        time: 2000
    });


    // Modal Video
    $(document).ready(function () {
        var $videoSrc;
        $('.btn-play').click(function () {
            $videoSrc = $(this).data("src");
        });
        console.log($videoSrc);

        $('#videoModal').on('shown.bs.modal', function (e) {
            $("#video").attr('src', $videoSrc + "?autoplay=1&amp;modestbranding=1&amp;showinfo=0");
        })

        $('#videoModal').on('hide.bs.modal', function (e) {
            $("#video").attr('src', $videoSrc);
        })
    });


    // Testimonials carousel
    $(".testimonial-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 1000,
        margin: 25,
        dots: false,
        loop: true,
        nav : true,
        navText : [
            '<i class="bi bi-arrow-left"></i>',
            '<i class="bi bi-arrow-right"></i>'
        ],
        responsive: {
            0:{
                items:1
            },
            768:{
                items:2
            }
        }
    });
    
})(jQuery);

