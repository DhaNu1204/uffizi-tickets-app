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
        'en' => 'HX24c902699375b1d981a148c9e6981a75', // English (ticket_pdf_delivery_en)
        'it' => 'HX304bb8b0ea10ea9682bd5364bd6167b5', // Italian (ticket_pdf_delivery_it)
        'es' => 'HXafed6d0ace699a0e4c53ed1b4226e144', // Spanish (ticket_pdf_delivery_es)
        'de' => 'HX4217737652d955ed3300aed1ade24caa', // German (ticket_pdf_delivery_de)
        'fr' => 'HXc328823b4bea6943a3067978af2ee4cb', // French (ticket_pdf_delivery_fr)
        'pt' => 'HX8d05d1e89e33345d74f95709853ddafb', // Portuguese (ticket_pdf_delivery_pt)
        'ja' => 'HX813c83ee7f305359a09971b899073aec', // Japanese (ticket_pdf_delivery_ja)
        'ko' => 'HX06a60a7665880a6495aa72ec3bf1cd33', // Korean (ticket_pdf_delivery_ko)
        'el' => 'HXe0a7d51965b5cafefcd80e9e852f37e5', // Greek (ticket_pdf_delivery_el)
        'tr' => 'HXdd63672ccee42a7ccb411f7a3cb0b447', // Turkish (ticket_pdf_delivery_tr)
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
        'en' => 'HX31b6aa39d7ce16806d0517ac7cb3016a', // English (ticket_audio_pdf_delivery_en)
        'it' => 'HX1ef7cbcef36de078fc81b2d1ddda0b0d', // Italian (ticket_audio_pdf_delivery_it)
        'es' => 'HX9a056acfa7df0870afad9eb83f131c5a', // Spanish (ticket_audio_pdf_delivery_es)
        'de' => 'HX852cd41df6a7b45acebbce69fa740c24', // German (ticket_audio_pdf_delivery_de)
        'fr' => 'HXb8e1539777cf694c9fda02de48b29d04', // French (ticket_audio_pdf_delivery_fr)
        'pt' => 'HX4d0de9fa22d8f8cfead71cced5a4a758', // Portuguese (ticket_audio_pdf_delivery_pt)
        'ja' => 'HXeef172f181c5afacfd93eff101423c5c', // Japanese (ticket_audio_pdf_delivery_ja)
        'ko' => 'HXfdc6b4cd60a06e63f589960864e72eef', // Korean (ticket_audio_pdf_delivery_ko)
        'el' => 'HX85322be8f9fbc5ccafcda80e3e90cb09', // Greek (ticket_audio_pdf_delivery_el)
        'tr' => 'HXf6013f84705a1d5b5ea4baed02f056b3', // Turkish (ticket_audio_pdf_delivery_tr)
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
