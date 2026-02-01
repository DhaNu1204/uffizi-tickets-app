<?php

/**
 * WhatsApp Template Configuration
 *
 * Maps language codes and template categories to Twilio WhatsApp Template SIDs.
 *
 * NEW: Media Templates with PDF Attachment Support (5 variables)
 *
 * Template Categories:
 * - ticket_pdf: Ticket delivery WITH PDF attachment (no audio guide)
 * - ticket_audio_pdf: Ticket + Audio Guide WITH PDF attachment
 *
 * Template Variables (5 variables per template):
 *   - {{1}} - Customer name
 *   - {{2}} - Entry date/time
 *   - {{3}} - Online guide URL OR PopGuide dynamic link
 *   - {{4}} - Know before you go URL
 *   - {{5}} - PDF attachment URL (S3 pre-signed)
 *
 * To add new templates:
 * 1. Create the template in Twilio Console > Messaging > Content Templates
 * 2. Use twilio/media type with document support
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
    | Variables: customer_name, entry_datetime, online_guide_url, know_before_you_go_url, pdf_url
    |
    */
    'ticket_pdf' => [
        'en' => 'HX50c5e100ce4cff2beaa057009519b8b3', // English
        'it' => 'HX872e1d78862eb97fef19aabccee263d8', // Italian
        'es' => 'HX3ee1bc0213734477615f55b67b2b45b0', // Spanish
        'de' => 'HX146a3dfb60ec1c3be7d42eb3290e8717', // German
        'fr' => 'HXa17ba29e85eb203a43217bd71016aa9f', // French
        'pt' => 'HXfe93a29b5c5e3270a6c8bcb58871f11e', // Portuguese
        'ja' => 'HX33465712878170c6eb099be38e61649d', // Japanese
        'ko' => 'HX949fc8e553c3a058aafd4d1fe40ed115', // Korean
        'el' => 'HX96de0b3000ac39c91e129718b45cfe91', // Greek
        'tr' => 'HX6d0291b3b7448548947ad45a5eac4c1a', // Turkish
    ],

    /*
    |--------------------------------------------------------------------------
    | Ticket + Audio Guide + PDF Templates (5 variables) - WITH Audio Guide
    |--------------------------------------------------------------------------
    |
    | Media templates for ticket delivery with PDF attachment AND PopGuide link.
    | Variables: customer_name, entry_datetime, popguide_dynamic_link, know_before_you_go_url, pdf_url
    |
    */
    'ticket_audio_pdf' => [
        'en' => 'HXf89e9f799de533ef61a58b1c78979a6c', // English
        'it' => 'HX96970f78810c1f3470d790b8bdf46aaf', // Italian
        'es' => 'HXf517172c0d730a3fba87165b75f6e848', // Spanish
        'de' => 'HXed77ab79d1e10decd9625ebe2ecaebac', // German
        'fr' => 'HX388e5dde667434358daa6dc22c6c8469', // French
        'pt' => 'HX5aa16df8dee24ab2015db38e0c5b6ded', // Portuguese
        'ja' => 'HXdd62d4f98f9886fba026120920ba362b', // Japanese
        'ko' => 'HX864751c0a0cbc34010d60995cb9ccd69', // Korean
        'el' => 'HX9072a0440c4f1ffaf0fdf4787df30ac5', // Greek
        'tr' => 'HX992ce5239bf554785005a787a0f29df2', // Turkish
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy Templates (4 variables) - Text Only, No PDF
    |--------------------------------------------------------------------------
    |
    | Kept for backwards compatibility. Use ticket_pdf and ticket_audio_pdf instead.
    |
    */
    'ticket_only' => [
        'en' => 'HX903d5ba5ab918c0a41f0a0613054adc9',
        'it' => 'HX2cafa5bc1638c752068bf00337053e41',
        'es' => 'HX988cc9aa1ee651995af8dce063090ec6',
        'de' => 'HX229c90f27b1d67de43aea14cf488a096',
        'fr' => 'HXa72416c623ca61249b2884c34513db4e',
        'pt' => 'HX9feb703a1f16c64c9dfa728ba581774b',
        'ja' => 'HX0c7c5aa6f2aba9e5a3d13fab995be8b3',
        'ko' => 'HX6bbf78e995af1befeda1e2b75d0f7ce4',
        'el' => 'HX267374e3b7f0c88af864e375bf9398bf',
        'tr' => 'HX40a4a3a8cd873302a670edd736109df5',
    ],

    'ticket_with_audio' => [
        'en' => 'HXfcf3f39931f3399ffb6667295fa94afc',
        'it' => 'HX6c41442e3d4248fc20aae6f6f703d42d',
        'es' => 'HX237b81b830cb65cdb3dfe3a39654bdff',
        'de' => 'HX68e5c508e0e1afe6138f22ab5d3623de',
        'fr' => 'HXd3979b260b6809a7f0b2b23dfe790e53',
        'pt' => 'HX004c38cd334f1c70496f14cded00265d',
        'ja' => 'HXae0e21e13999f699d06dbb185b2df3ea',
        'ko' => 'HX52f0c3ccf448561c51f15332d8ac9134',
        'el' => 'HX41ef2aada9ab5a020e4243dd997f6fb3',
        'tr' => 'HX28976f52c12b6f0d2964403231e8cf8b',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default URLs
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
    | PDF URLs must remain valid long enough for customers to access tickets.
    | NOTE: AWS S3 SigV4 pre-signed URLs have a MAXIMUM of 7 days.
    | For longer access, customers can request a new link via WhatsApp.
    |
    */
    'pdf_url_expiry_days' => 7, // Maximum allowed by AWS S3 SigV4

    /*
    |--------------------------------------------------------------------------
    | Fallback Behavior
    |--------------------------------------------------------------------------
    */
    'fallback_language' => 'en',
];
