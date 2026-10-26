# Hotelier

Hotelier is a PHP and MySQL hotel website with room listings, guest accounts, reservation requests, testimonials, and a staff management area. It combines a hotel-themed website template with PHP pages that read and update hotel data in a MySQL or MariaDB database.

## Features

- Browse hotel information, services, rooms, the team, and guest testimonials.
- Register and sign in as a guest, update contact details, and view your bookings.
- Request a room booking with guest counts, dates, and optional special requests.
- Submit a testimonial using a registered guest email address.
- Sign in to the staff area to manage guest details and booking statuses.

## Requirements

- PHP 8.2 or later with the `mysqli` extension.
- MySQL or MariaDB.
- Apache or another web server configured to serve PHP.
- XAMPP is a convenient local development option on Windows.

## Local setup

1. Put the project directory in your web server's document root. With XAMPP, that is usually `C:\xampp\htdocs\hotelier`.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Create a MySQL/MariaDB database and configure the connection values in [`db.php`](./db.php). The current defaults are `localhost`, user `root`, an empty password, and database `hotelier_db`; change them to match your local setup.
4. **Set up the Hotelier database tables before opening the site.** The PHP pages expect `customers`, `rooms`, `bookings`, `admin_users`, and `testimonials` tables.
5. Open [http://localhost/hotelier/](http://localhost/hotelier/) in your browser.

### Database setup warning

There is currently no Hotelier database schema or seed data in this repository. Obtain or create the matching schema for the tables listed above before proceeding.

## Guest and staff access

- Guests can register from the **Guest Registration** page. The customer code shown after registration is needed when making a booking.
- Guest sign-in and account pages are available through **My Account**.
- Staff sign-in is at `http://localhost/hotelier/admin/login.php`.
- To create a staff account, run `php admin/create_admin.php` from the project directory in a private terminal. The script prompts for an email and a password of at least 12 characters. **Password input is visible while typing.** Do not run it where other people can see the terminal.

No sample guest or staff credentials are included.

## Main pages and files

| Path | Purpose |
| --- | --- |
| `index.php` | Home page |
| `room.php`, `service.php`, `about.php`, `team.php` | Hotel information and listings |
| `registration.php`, `login.php`, `account.php`, `logout.php` | Guest account flows |
| `booking.php` | Room booking requests |
| `testimonial.php`, `contact.php` | Guest feedback and contact page |
| `admin/` | Staff sign-in and booking/customer management |
| `includes/` | Shared page layout and account authentication helpers |
| `assets/`, `img/`, `lib/`, `js/`, `scss/` | Styles, images, scripts, and front-end libraries |

## Notes

- The site uses locally stored Bootstrap styles and front-end libraries, and loads some fonts and icons from third-party CDNs.
- Template licensing and attribution terms are provided in [`LICENSE.txt`](./LICENSE.txt). Retain any required HTML Codex attribution.
- There is no Composer or npm manifest and no automated test suite is currently included.