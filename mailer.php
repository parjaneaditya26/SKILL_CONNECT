<?php
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';
require_once 'mailer_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($toEmail, $toName, $subject, $body) {
  if (!$toEmail) { return false; }
  $mail = new PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_APP_PASSWORD;
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
    $mail->addAddress($toEmail, $toName);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $body;

    $mail->send();
    return true;
  } catch (Exception $e) {
    return false;
  }
}

function sendEmailBcc($emails, $subject, $body) {
  $emails = array_values(array_filter(array_unique($emails)));
  if (empty($emails)) { return 0; }
  $sent = 0;
  foreach (array_chunk($emails, 50) as $chunk) {
    $mail = new PHPMailer(true);
    try {
      $mail->isSMTP();
      $mail->Host = 'smtp.gmail.com';
      $mail->SMTPAuth = true;
      $mail->Username = MAIL_USERNAME;
      $mail->Password = MAIL_APP_PASSWORD;
      $mail->SMTPSecure = 'tls';
      $mail->Port = 587;
      $mail->Timeout = 20;

      $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
      $mail->addAddress(MAIL_USERNAME, MAIL_FROM_NAME);
      foreach ($chunk as $addr) { $mail->addBCC($addr); }

      $mail->isHTML(true);
      $mail->Subject = $subject;
      $mail->Body = $body;

      $mail->send();
      $sent += count($chunk);
    } catch (Exception $e) {
    }
  }
  return $sent;
}
?>
