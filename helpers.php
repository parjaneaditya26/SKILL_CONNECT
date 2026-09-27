<?php
function timeAgo($datetime) {
  $timestamp = strtotime($datetime);
  $diff = time() - $timestamp;

  if ($diff < 60) { return "just now"; }
  if ($diff < 3600) { $m = floor($diff / 60); return $m . ($m == 1 ? " minute ago" : " minutes ago"); }
  if ($diff < 86400) { $h = floor($diff / 3600); return $h . ($h == 1 ? " hour ago" : " hours ago"); }
  if ($diff < 604800) { $d = floor($diff / 86400); return $d . ($d == 1 ? " day ago" : " days ago"); }
  return date("M j, Y", $timestamp);
}

function csrf_token() {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

function csrf_field() {
  return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_verify($token) {
  return isset($_SESSION['csrf_token']) && !empty($token) && hash_equals($_SESSION['csrf_token'], $token);
}
?>