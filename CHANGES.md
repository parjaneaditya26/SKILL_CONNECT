# Skill Connect: security patch notes

## Files REMOVED (delete these from your project and from GitHub)
verify_email.php, verify_otp.php, verify_otp_ajax.php, resend_verification.php, admin_seed_demo.php, admin_login.php
(Nothing in the app linked to them. verify_otp.php and verify_otp_ajax.php let people guess OTPs with no limit.)

PowerShell, run inside your project folder:
    Remove-Item verify_email.php, verify_otp.php, verify_otp_ajax.php, resend_verification.php, admin_seed_demo.php, admin_login.php

## Files ADDED
- session_init.php  : safe session cookies, hides PHP errors on the live site, CSRF + URL helpers
- schema.sql        : creates users, requests, ratings, reports, messages (these were never created by the code)
- .env.example      : template for your server .env (includes DB_* and APP_URL)
- PHPMailer/.htaccess : blocks direct web access to the library

## What changed
- SQL injection fixed in notifications.php (it used ?name= directly in a query, no login needed)
- CSRF protection added: admin delete user/request/report, delete_profile, save_rating, chatbot, send/verify signup OTP
- OTP: random_int() instead of rand(); 30s resend cooldown; max 5 codes/hour; max 5 wrong guesses (also in save_profile.php)
- OTP code is shown on screen ONLY on localhost; on the live site, if the email fails, signup is refused instead of leaking the code
- Session cookies: HttpOnly, SameSite=Lax, Secure on https; session id regenerated on login/signup
- Admin password compared with hash_equals()
- Email links use APP_URL from .env (https) instead of the request Host header
- .htaccess blocks mailer_config.php, helpers/db files, *.sql, *.md; no folder listing; basic security headers
- script.js now sends the CSRF token on every POST (cache version bumped to v=4)
