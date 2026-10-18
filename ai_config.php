<?php
if (!defined('GEMINI_API_KEY')) {
  $key = '';

  $envFile = __DIR__ . '/.env';
  if (is_readable($envFile)) {
    $env = parse_ini_file($envFile);
    if (is_array($env) && !empty($env['GEMINI_API_KEY'])) {
      $key = trim($env['GEMINI_API_KEY']);
    }
  }

  // Fallback to a real server environment variable
  if ($key === '') {
    $fromEnv = getenv('GEMINI_API_KEY');
    if ($fromEnv) { $key = trim($fromEnv); }
  }

  define('GEMINI_API_KEY', $key);
}