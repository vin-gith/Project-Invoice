# Invoicely

Invoicely is a web app for small businesses to create invoices, calculate totals automatically,
track payments, and send or print invoices as PDF. Every account has its own invoices, business
details, and invoice design.

The interface is in Indonesian. It is built with Laravel 12 and has no frontend framework:
pages are Blade templates, and the styles and scripts are plain CSS and JavaScript in `public/`.

## Features

| Area | What it does |
|---|---|
| Accounts | Register, log in, log out. After 5 wrong passwords within 10 minutes, login is locked for 1 minute. |
| Password reset | A 6-digit code is sent by email. The code is valid for 10 minutes, is cancelled after 5 wrong tries, and can be resent once per minute. |
| Invoices | Create, view, edit, and delete invoices. Search with suggestions, and filter by paid or unpaid. |
| Invoice numbers | Generated automatically as `INV-DD-MM-YYYY-0001`. The sequence keeps counting per account and never repeats. |
| Totals | Subtotal, tax, and total are previewed while typing and recalculated on the server when saved. |
| Payment status | Paid, unpaid, or overdue (unpaid past its due date). |
| Duplicate | Copy an invoice into a new one: same customer and items, new number, today's date, same payment term. |
| Send via WhatsApp | Opens WhatsApp with a ready-made message: invoice number, total, due date, bank details, and a link to the PDF. The wording changes for new, overdue, and paid invoices. |
| PDF | Download or print. Three templates (Modern, Klasik, Minimalist), five fonts, six accent colours, logo, faded logo watermark, bank details, footer note, and the total written out in words (*terbilang*). |
| Template preview | Try a template on a real or sample invoice before saving it. |
| Business details | Name, address, email, and phone used on every new invoice. |
| Reports | Income this month compared with last month, unpaid total, overdue total, invoices due in the next 7 days, a monthly income chart (6 or 12 months), top customers, and a list of invoices to follow up. |
| Look and feel | "Liquid glass" theme with light and dark mode, page transitions, and a landing page with an intro animation. All animation stops when the device asks for reduced motion. |

## Requirements

- PHP 8.2 or newer, with these extensions: `gd`, `mbstring`, `fileinfo`, and `pdo_mysql` (or `pdo_sqlite`)
- Composer
- MySQL or MariaDB (XAMPP works). SQLite also works.
- Node.js is **not** needed. The Vite files in the repository are unused leftovers from the Laravel skeleton.

`gd` is needed to place PNG logos in PDFs and to make the logo watermark. Without it, PDFs are still
created, but without PNG logos. OPcache is strongly recommended: without it, every request to
`php artisan serve` takes about 1 to 5 seconds instead of about 0.1 to 0.2 seconds.

To enable both in XAMPP, remove the leading `;` from these lines in `C:\xampp\php\php.ini`, then
restart the server:

```ini
extension=gd
zend_extension=opcache
```

## Getting started

```bash
# 1. Install the PHP packages
composer install

# 2. Create the environment file and the app key
cp .env.example .env
php artisan key:generate

# 3. Create an empty MySQL database called "Invoicely" (see the DB_ settings in .env),
#    then create the tables
php artisan migrate

# 4. Start the app
php artisan serve
```

Open http://127.0.0.1:8000 and register an account.

To fill the first account with sample invoices for a demo, register first and then run:

```bash
php artisan db:seed --class=InvoiceSeeder
```

Do not use `composer setup` or `composer dev`. They come from the Laravel skeleton and also run npm,
which this project does not use.

## Configuration

| Setting | Where | Notes |
|---|---|---|
| Database | `.env` (`DB_...`) | Defaults to MySQL database `Invoicely` on `127.0.0.1`. Sessions and cache are stored in the database too. |
| Time zone | `config/app.php` | Set to `Asia/Jakarta`. |
| Fallback business details | `config/invoicely.php` or `.env` (`INVOICELY_COMPANY`, `INVOICELY_ADDRESS`, `INVOICELY_EMAIL`, `INVOICELY_PHONE`) | Used until an account fills in its own business details. |
| Email | `.env` (`MAIL_...`) | Needed for password reset codes. For testing without real email, set `MAIL_MAILER=log`: the code then appears in `storage/logs/laravel.log`. |
| Email domain check | `config/invoicely.php` | Registration checks that the email domain exists, which needs internet. For an offline demo, add `'check_email_dns' => false,`. |
| Trusted proxies | `bootstrap/app.php` | Only `127.0.0.1` and `::1` are trusted. Add the proxy's IP address if the app runs behind Nginx, Cloudflare, or a load balancer. |

## Sharing the app with others

The app can be opened by others through a tunnel while your computer and `php artisan serve` keep
running. Examples are VS Code port forwarding (a `*.devtunnels.ms` link), ngrok, and Cloudflare
Tunnel. The link stops working when your computer sleeps or the server stops. For a link that always
works, deploy the app to a hosting provider or a server.

Things already handled for tunnels:

- Every response sends a `Content-Length` header (`app/Providers/AppServiceProvider.php`). Without
  it, pages opened through VS Code port forwarding kept loading forever, and the PDF preview timed out.
- Links and assets use `https` behind the tunnel, because proxy headers from `127.0.0.1` are trusted.
- The PDF link in WhatsApp messages uses the address you are currently using. If you open the app
  as `127.0.0.1`, customers cannot open that link, and the invoice page shows a warning. Send from
  the tunnel link instead.

`php artisan serve` handles one request at a time on Windows, so it is fine for a demo but not for
real traffic.

## Running the tests

```bash
php artisan test
```

The tests use an in-memory SQLite database, so they never touch your real data. They cover:

- registration, login, the login lock, and a spoofed `X-Forwarded-For` header
- invoice totals, numbering per account, and access to other accounts' invoices (must return 404)
- PDF download, print, and preview, including transparent and large logos
- WhatsApp links, the signed public PDF link, duplicating invoices, and the reports page
- expired forms (419) and the `Content-Length` header

## Project structure

```
app/
  Console/Commands/RenumberInvoices.php    php artisan invoices:renumber
  Http/Controllers/
    AuthController.php                     register, login (with lock), logout
    PasswordResetController.php            6-digit reset code by email
    InvoiceController.php                  invoices, PDF, WhatsApp, duplicate, public PDF link
    BusinessProfileController.php          business details page
    TemplateInvoiceController.php          invoice design and PDF preview
    ReportController.php                   reports page and its chart
  Models/                                  User, Invoice, InvoiceItem, BusinessProfile
  Support/
    InvoicePdf.php                         builds every PDF (download, print, preview, public link)
    LogoImage.php                          logo for PDFs: shrinking and watermark
    WhatsApp.php                           WhatsApp message and signed PDF link
    Phone.php, Terbilang.php               phone formatting, amounts in Indonesian words
bootstrap/app.php                          trusted proxies, friendly page for expired forms (419)
routes/web.php                             all routes
resources/views/                           Blade pages (invoices/pdf-templates holds the 3 PDF designs)
public/css/                                app.css (base), glass.css (theme), template.css, report.css
public/js/                                 app.js (theme, menu, forms, search), template.js (preview)
tests/Feature/                             automated tests
```

## How it works

- **One account, one list.** Invoices and business details belong to a user (`user_id`). Opening
  another account's invoice returns 404, as if it did not exist, so its existence is not revealed.
- **Invoice numbers.** The next number is the highest sequence number of that account plus one,
  taken inside a database transaction with `lockForUpdate`. The pair `user_id` + `number` is unique,
  so duplicate numbers cannot be saved.
- **Totals are calculated on the server.** The browser only shows a preview. Saved amounts always
  come from validated items, so they cannot be changed with the browser's developer tools.
- **Money is stored as whole rupiah (integers)**, not floats, to avoid rounding errors.
- **Each invoice keeps its own copy** of the seller details and logo. Changing the business details
  later does not change invoices that already exist.
- **Public PDF links are signed.** The link in a WhatsApp message works without logging in, expires
  after 30 days, and stops working if anyone changes the invoice number in the address.
- **Small PDFs.** Only the characters actually used are embedded from the font, and logos are
  shrunk to at most 400 px. A typical PDF is about 30 to 250 KB instead of 1 to 2 MB.
- **Passwords** must be 8 to 72 characters with a letter, a number, and a symbol. They are stored
  as bcrypt hashes.
- **Expired forms.** If a form was left open too long, the page returns to the form with a message
  and keeps what was typed (except passwords), instead of showing a "419 Page Expired" error.
- **Theme.** All colours come from CSS variables. The `data-theme` attribute on `<html>` switches
  between the light and dark palettes, and the choice is saved in `localStorage`. The landing page
  always opens in light mode.

## Troubleshooting

| Problem | Cause and fix |
|---|---|
| Logo missing from PDFs, or "The PHP GD extension is required" in the log | `gd` is not enabled. See [Requirements](#requirements). |
| Every page is slow (1 to 5 seconds) | OPcache is not enabled. See [Requirements](#requirements). |
| "Pembuatan PDF terlalu lama" in the template preview | Check the two rows above. Also, the dev server handles one request at a time, so wait for other people's previews to finish, then click *Perbarui*. |
| "Halaman sudah kedaluwarsa" after submitting a form | The form was open too long. Submit it again. |
| Customers cannot open the PDF link from WhatsApp | The message was sent while using `127.0.0.1`. Open the app through the public tunnel link and send again. |
| Old invoice numbers are out of order | Run `php artisan invoices:renumber`. It shows the changes first and asks before saving. |

## License

MIT. See [LICENSE](LICENSE).
