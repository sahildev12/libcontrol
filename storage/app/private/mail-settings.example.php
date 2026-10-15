<?php

/**
 * Copy to mail-settings.php on the server (not in git).
 * Overrides Laravel mail config so SMTP works without editing .env on shared hosting.
 */
return [
    'mail.default' => 'smtp',
    'mail.mailers.smtp.scheme' => 'smtps',
    'mail.mailers.smtp.host' => 'smtp.hostinger.com',
    'mail.mailers.smtp.port' => 465,
    'mail.mailers.smtp.username' => 'support@libcontrol.in',
    'mail.mailers.smtp.password' => 'your-mailbox-password',
    'mail.from.address' => 'support@libcontrol.in',
    'mail.from.name' => 'LibControl',
];
