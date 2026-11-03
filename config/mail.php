<?php
/**
 * =====================================================================================
 * GMAIL SMTP SETTINGS (Fan Hub Plus)
 * =====================================================================================
 * 
 * Aapko kisi .env file ki zaroorat nahi hai! Seedha seedha yahan details enter karein:
 * 
 * 1. 'smtp_user' => Yahan apna Gmail address likhein (e.g. 'apna-email@gmail.com')
 * 2. 'smtp_pass' => Yahan apna 16-character Google App Password likhein
 *                   NOTE: Normal Gmail login password Google allow nahi karta.
 *                   Google Account -> Security -> 2-Step Verification -> App Passwords
 *                   se generate kiya hua 16-digit code yahan paste karein.
 * =====================================================================================
 */

return array(
    // Gmail SMTP Server Settings
    'smtp_host'   => 'smtp.gmail.com',
    'smtp_port'   => 465,                      // 465 for SSL (Recommended), ya 587 for TLS
    'smtp_secure' => 'ssl',                    // 'ssl' ya 'tls'

    // ==============================================================================
    // [STEP 1]: YAHAN APNA GMAIL ADDRESS LIKHEIN:
    // ==============================================================================
    'smtp_user'   => 'azlansaqib04@gmail.com',

    // ==============================================================================
    // [STEP 2]: YAHAN APNA 16-DIGIT GOOGLE APP PASSWORD LIKHEIN:
    // ==============================================================================
    'smtp_pass'   => 'kbbo arie hfod ocju',

    // Sender Details (Email kis naam se jaye)
    'from_name'   => 'Fan Hub Plus Support',
    'from_email'  => 'azlansaqib04@gmail.com',                      // Khali chhorne par 'smtp_user' use hoga

    // Website Base URL for Reset Links (Khali chhorne par auto-detect ho jayega)
    'base_url'    => '',
);
