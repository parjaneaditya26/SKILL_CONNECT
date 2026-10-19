<?php
// Central session + safety bootstrap. Every page loads this instead of calling session_start() directly.

if (!function_exists('app_is_local')) {
  function app_is_local() {
    $host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    return in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true);
  }
}

if (!function_exists('app_is_https')) {
  function app_is_https() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
  }
}

// Base URL used inside emails (reset links etc.).
// Set APP_URL=https://yourdomain.com/yourfolder in .env so a forged Host header can never end up in an email.
if (!function_exists('app_base_url')) {
  function app_base_url() {
    $envFile = __DIR__ . '/.env';
    if (is_readable($envFile)) {
      $env = parse_ini_file($envFile);
      if (is_array($env) && !empty($env['APP_URL'])) {
        return rtrim(trim($env['APP_URL']), '/');
      }
    }
    $scheme = app_is_https() ? 'https' : 'http';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'] ?? '')), '/');
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir;
  }
}

// Accepts the token from a form field (csrf_token) or from the X-CSRF-Token header (used by script.js).
if (!function_exists('csrf_verify_request')) {
  function csrf_verify_request() {
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return !empty($_SESSION['csrf_token']) && is_string($sent) && $sent !== ''
      && hash_equals($_SESSION['csrf_token'], $sent);
  }
}

if (session_status() === PHP_SESSION_NONE) {
  if (!app_is_local()) {
    ini_set('display_errors', '0');   // never show PHP errors to visitors on the live site
    ini_set('log_errors', '1');
  }
  ini_set('session.use_strict_mode', '1');
  ini_set('session.use_only_cookies', '1');
  session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => app_is_https(),
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}
