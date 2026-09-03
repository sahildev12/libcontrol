<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

$configPath = __DIR__ . '/mail-config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Mail is not configured.']);
    exit;
}

$config = require $configPath;

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
if (!is_array($data)) {
    $data = $_POST;
}

$name      = trim((string) ($data['name'] ?? ''));
$phone     = trim((string) ($data['phone'] ?? ''));
$institute = trim((string) ($data['institute'] ?? ''));
$city      = trim((string) ($data['city'] ?? ''));
$message   = trim((string) ($data['message'] ?? ''));

if ($name === '' || $phone === '' || $institute === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Please fill name, phone and library / institute name.']);
    exit;
}

if (mb_strlen($name) > 120 || mb_strlen($phone) > 40 || mb_strlen($institute) > 180) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'One or more fields are too long.']);
    exit;
}

if (mb_strlen($city) > 100 || mb_strlen($message) > 2000) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'City or message is too long.']);
    exit;
}

$phpmailerBase = __DIR__ . '/../kims_pro/lib/PHPMailer';
if (!is_file($phpmailerBase . '/PHPMailer.php')) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Mail library is not available.']);
    exit;
}

require_once $phpmailerBase . '/Exception.php';
require_once $phpmailerBase . '/PHPMailer.php';
require_once $phpmailerBase . '/SMTP.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$submittedAt = date('d M Y, h:i A');
$safeName = h($name);
$safePhone = h($phone);
$safeInstitute = h($institute);
$safeCity = $city !== '' ? h($city) : '—';
$safeMessage = $message !== '' ? nl2br(h($message)) : '—';
$digits = preg_replace('/\D+/', '', $phone) ?: '';
if (str_starts_with($digits, '91') && strlen($digits) >= 12) {
    $waDigits = $digits;
} elseif (strlen($digits) === 10) {
    $waDigits = '91' . $digits;
} else {
    $waDigits = $digits;
}
$waLink = 'https://wa.me/' . $waDigits;

$htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>New LibControl Demo Request</title>
</head>
<body style="margin:0;padding:0;background:#eef1f6;font-family:Arial,Helvetica,sans-serif;color:#001131;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef1f6;padding:28px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 12px 36px rgba(0,17,49,0.12);">
          <tr>
            <td style="background:#001131;padding:28px 32px;border-left:6px solid #ffc800;">
              <p style="margin:0 0 6px;font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:#ffc800;font-weight:700;">LibControl · Demo Request</p>
              <h1 style="margin:0;font-size:24px;line-height:1.3;color:#ffffff;font-weight:700;">New demo enquiry received</h1>
              <p style="margin:10px 0 0;font-size:14px;color:rgba(255,255,255,0.78);">Submitted on {$submittedAt}</p>
            </td>
          </tr>
          <tr>
            <td style="padding:28px 32px 8px;">
              <p style="margin:0 0 18px;font-size:15px;line-height:1.6;color:#3d4a63;">
                Someone requested a live walkthrough of LibControl — library &amp; study seat management by Phenomit.
              </p>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                <tr>
                  <td style="padding:14px 16px;background:#f5f7fa;border-radius:10px 10px 0 0;border-bottom:1px solid #d9dee8;">
                    <p style="margin:0 0 4px;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#6b778c;font-weight:700;">Full name</p>
                    <p style="margin:0;font-size:16px;font-weight:700;color:#001131;">{$safeName}</p>
                  </td>
                </tr>
                <tr>
                  <td style="padding:14px 16px;background:#ffffff;border-bottom:1px solid #d9dee8;">
                    <p style="margin:0 0 4px;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#6b778c;font-weight:700;">WhatsApp / Phone</p>
                    <p style="margin:0;font-size:16px;font-weight:700;color:#001131;">
                      <a href="{$waLink}" style="color:#001131;text-decoration:none;">{$safePhone}</a>
                    </p>
                  </td>
                </tr>
                <tr>
                  <td style="padding:14px 16px;background:#f5f7fa;border-bottom:1px solid #d9dee8;">
                    <p style="margin:0 0 4px;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#6b778c;font-weight:700;">Library / Institute</p>
                    <p style="margin:0;font-size:16px;font-weight:700;color:#001131;">{$safeInstitute}</p>
                  </td>
                </tr>
                <tr>
                  <td style="padding:14px 16px;background:#ffffff;border-bottom:1px solid #d9dee8;">
                    <p style="margin:0 0 4px;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#6b778c;font-weight:700;">City</p>
                    <p style="margin:0;font-size:16px;font-weight:700;color:#001131;">{$safeCity}</p>
                  </td>
                </tr>
                <tr>
                  <td style="padding:14px 16px;background:#f5f7fa;border-radius:0 0 10px 10px;">
                    <p style="margin:0 0 4px;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#6b778c;font-weight:700;">Message</p>
                    <p style="margin:0;font-size:15px;line-height:1.6;color:#001131;">{$safeMessage}</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:8px 32px 32px;" align="left">
              <a href="{$waLink}" style="display:inline-block;background:#ffc800;color:#001131;text-decoration:none;font-weight:700;font-size:13px;letter-spacing:0.06em;text-transform:uppercase;padding:12px 22px;border-radius:4px;">
                Chat on WhatsApp
              </a>
            </td>
          </tr>
          <tr>
            <td style="background:#001131;padding:18px 32px;border-top:3px solid #ffc800;">
              <p style="margin:0;font-size:12px;line-height:1.5;color:rgba(255,255,255,0.72);">
                LibControl is a product by Phenomit.com · Sent from the LibControl demo form
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

$plainBody = "New LibControl Demo Request\n"
    . "Submitted: {$submittedAt}\n\n"
    . "Name: {$name}\n"
    . "Phone: {$phone}\n"
    . "Library / Institute: {$institute}\n"
    . "City: " . ($city !== '' ? $city : '—') . "\n"
    . "Message: " . ($message !== '' ? $message : '—') . "\n";

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = (string) $config['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = (string) $config['username'];
    $mail->Password   = (string) $config['password'];
    $mail->SMTPSecure = ((string) ($config['encryption'] ?? 'ssl')) === 'tls'
        ? PHPMailer::ENCRYPTION_STARTTLS
        : PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = (int) $config['port'];
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom((string) $config['from_email'], (string) $config['from_name']);
    $mail->addAddress((string) $config['to_email'], (string) ($config['to_name'] ?? ''));
    $mail->addReplyTo((string) $config['from_email'], $name);

    $mail->isHTML(true);
    $mail->Subject = 'LibControl Demo Request — ' . $institute;
    $mail->Body    = $htmlBody;
    $mail->AltBody = $plainBody;

    $mail->send();

    echo json_encode(['ok' => true, 'message' => 'Demo request sent successfully.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Could not send email. Please try WhatsApp or call us.',
    ]);
}
