<?php

/**
 * WhatsApp Template Configuration
 *
 * Maps language codes and template categories to Twilio WhatsApp Content Template SIDs.
 *
 * IMPORTANT: All templates use dynamic {{5}} variable for PDF attachment URL.
 * Templates with hardcoded media URLs will cause Error 63021.
 *
 * Template Variables (5 variables per template):
 *   - {{1}} - Customer name
 *   - {{2}} - Entry date/time
 *   - {{3}} - Online guide URL OR PopGuide dynamic link
 *   - {{4}} - Know before you go URL
 *   - {{5}} - PDF attachment URL (dynamically generated presigned URL)
 *
 * To add new templates:
 * 1. Create the template in Twilio Console > Messaging > Content Templates
 * 2. Use twilio/media type with "media": ["{{5}}"] for dynamic PDF
 * 3. Wait for approval (usually 24-48 hours)
 * 4. Copy the SID (starts with HX) and add it here
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Ticket + PDF Templates (5 variables) - WITHOUT Audio Guide
    |--------------------------------------------------------------------------
    |
    | Media templates for ticket delivery with PDF attachment.
    | Uses dynamic {{5}} variable for PDF URL.
    |
    */
    'ticket_pdf' => [
        'en' => 'HXe99a2433d4e53e42ac5dca877eaa8851', // English
        'it' => 'HX7d35de5a55faa43e826a807e687c1216', // Italian
        'es' => 'HXaca15d24845e4e4115dda3d5886f69ba', // Spanish
        'de' => 'HX268847fee98cf54bdd967c68a0be803e', // German
        'fr' => 'HX731940512951f890852567eaa8e2e0cd', // French
        'pt' => 'HXb4dbb32e5d61194832541afe9b2a0b16', // Portuguese
        'ja' => 'HX1eaab455cc7a45ab0d9079bdf4db7e97', // Japanese
        'ko' => 'HX1e748c4e5cf7694fca60f28501cb2524', // Korean
        'el' => 'HX16926a51c9005942b3567a0399fbbd81', // Greek
        'tr' => 'HX2c99778da63646e17b2ee6013c4b9b87', // Turkish
    ],

    /*
    |--------------------------------------------------------------------------
    | Ticket + Audio Guide + PDF Templates (5 variables) - WITH Audio Guide
    |--------------------------------------------------------------------------
    |
    | Media templates for ticket delivery with PDF attachment AND PopGuide link.
    | Uses dynamic {{5}} variable for PDF URL.
    |
    */
    'ticket_audio_pdf' => [
        'en' => 'HXf5b8d9920da797efa1ad6f8233844aa6', // English
        'it' => 'HXa4d47e4cf311042f2fa36b861c0566e4', // Italian
        'es' => 'HXe44f4e61a6bf8faa37426c21b164638f', // Spanish
        'de' => 'HX750bc9155367db86cf9b80c11f3fe9cf', // German
        'fr' => 'HXa6b3a703fcf66a2dd0c7275b857e7246', // French
        'pt' => 'HX4023b025f1dcc29aa17b67c00e27ca1d', // Portuguese
        'ja' => 'HXdb1df50f096850361b3420e52453740f', // Japanese (FIXED - was HXdb1df50f0968503361b3420e52453740f)
        'ko' => 'HXf9c9afdacd5910172982c8458578f90b', // Korean
        'el' => 'HX4fd89046ab200f48789292a5b7ea180d', // Greek
        'tr' => 'HX38ffd19576bd5741507c890582f1242b', // Turkish
    ],

    /*
    |--------------------------------------------------------------------------
    | Default URLs (Static - NOT used for PDF attachments)
    |--------------------------------------------------------------------------
    */
    'urls' => [
        'online_guide' => 'https://uffizi.florencewithlocals.com',
        'know_before_you_go' => 'https://uffizi.florencewithlocals.com/know-before-you-go',
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF URL Settings
    |--------------------------------------------------------------------------
    |
    | PDF URLs are generated dynamically via getTemporaryUrl().
    | For S3: presigned URLs with max 7 days expiry (AWS SigV4 limit)
    | For local: signed URLs via /api/public/attachments/{id}/{signature}
    |
    */
    'pdf_url_expiry_days' => 7,

    /*
    |--------------------------------------------------------------------------
    | Fallback Behavior
    |--------------------------------------------------------------------------
    */
    'fallback_language' => 'en',
];
