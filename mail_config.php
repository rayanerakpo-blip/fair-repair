<?php
// ====================================================
// Configuration SMTP - Fair'repair
// ====================================================
// Par défaut : Mailpit (serveur SMTP local pour dev).
// En production, définir les variables d'environnement MAIL_*.

define('MAIL_HOST', getenv('MAIL_HOST') ?: '127.0.0.1');
define('MAIL_PORT', (int) (getenv('MAIL_PORT') ?: 1025));
define('MAIL_USER', getenv('MAIL_USER') ?: '');
define('MAIL_PASS', getenv('MAIL_PASS') ?: '');
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'noreply@fairrepair.fr');
define('MAIL_FROM_NAME', "Fair'repair");
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: '');
