<?php

/**
 * WhatsApp Template Configuration
 *
 * Maps language codes and template categories to Twilio WhatsApp Template SIDs.
 *
 * Template Categories:
 * - ticket_only: Templates for standard ticket delivery (no audio guide)
 * - ticket_with_audio: Templates that include audio guide credentials
 *
 * To add new templates:
 * 1. Create the template in Twilio Console > Messaging > Content Templates
 * 2. Wait for approval (usually 24-48 hours)
 * 3. Copy the SID (starts with HX) and add it here
 *
 * Template Variables (4 variables per template):
 * ticket_only:
 *   - {{1}} - Customer name
 *   - {{2}} - Entry date/time
 *   - {{3}} - Online guide URL
 *   - {{4}} - Know before you go URL
 *
 * ticket_with_audio:
 *   - {{1}} - Customer name
 *   - {{2}} - Entry date/time
 *   - {{3}} - PopGuide dynamic link
 *   - {{4}} - Know before you go URL
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Ticket Only Templates (4 variables)
    |--------------------------------------------------------------------------
    |
    | Templates for standard ticket delivery without audio guide.
    | Variables: customer_name, entry_datetime, online_guide_url, know_before_you_go_url
    |
    */
    'ticket_only' => [
        'en' => 'HX903d5ba5ab918c0a41f0a0613054adc9', // English
        'it' => 'HX2cafa5bc1638c752068bf00337053e41', // Italian
        'es' => 'HX988cc9aa1ee651995af8dce063090ec6', // Spanish
        'de' => 'HX229c90f27b1d67de43aea14cf488a096', // German
        'fr' => 'HXa72416c623ca61249b2884c34513db4e', // French
        'pt' => 'HX9feb703a1f16c64c9dfa728ba581774b', // Portuguese
        'ja' => 'HX0c7c5aa6f2aba9e5a3d13fab995be8b3', // Japanese
        'ko' => 'HX6bbf78e995af1befeda1e2b75d0f7ce4', // Korean
        'el' => 'HX267374e3b7f0c88af864e375bf9398bf', // Greek
        'tr' => 'HX40a4a3a8cd873302a670edd736109df5', // Turkish
    ],

    /*
    |--------------------------------------------------------------------------
    | Ticket + Audio Guide Templates (4 variables)
    |--------------------------------------------------------------------------
    |
    | Templates for ticket delivery WITH PopGuide audio guide link.
    | Variables: customer_name, entry_datetime, popguide_dynamic_link, know_before_you_go_url
    |
    */
    'ticket_with_audio' => [
        'en' => 'HXfcf3f39931f3399ffb6667295fa94afc', // English
        'it' => 'HX6c41442e3d4248fc20aae6f6f703d42d', // Italian
        'es' => 'HX237b81b830cb65cdb3dfe3a39654bdff', // Spanish
        'de' => 'HX68e5c508e0e1afe6138f22ab5d3623de', // German
        'fr' => 'HXd3979b260b6809a7f0b2b23dfe790e53', // French
        'pt' => 'HX004c38cd334f1c70496f14cded00265d', // Portuguese
        'ja' => 'HXae0e21e13999f699d06dbb185b2df3ea', // Japanese
        'ko' => 'HX52f0c3ccf448561c51f15332d8ac9134', // Korean
        'el' => 'HX41ef2aada9ab5a020e4243dd997f6fb3', // Greek
        'tr' => 'HX28976f52c12b6f0d2964403231e8cf8b', // Turkish
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Variables Reference
    |--------------------------------------------------------------------------
    |
    | Maps template placeholders to booking data fields.
    |
    */
    'variables' => [
        'ticket_only' => [
            '{{1}}' => 'customer_name',
            '{{2}}' => 'entry_datetime',
            '{{3}}' => 'online_guide_url',
            '{{4}}' => 'know_before_you_go_url',
        ],
        'ticket_with_audio' => [
            '{{1}}' => 'customer_name',
            '{{2}}' => 'entry_datetime',
            '{{3}}' => 'popguide_dynamic_link',
            '{{4}}' => 'know_before_you_go_url',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default URLs
    |--------------------------------------------------------------------------
    |
    | Static URLs used in templates when booking-specific URLs are not available.
    |
    */
    'urls' => [
        'online_guide' => 'https://uffizi.florencewithlocals.com',
        'know_before_you_go' => 'https://florencewithlocals.com/uffizi-know-before-you-go',
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Behavior
    |--------------------------------------------------------------------------
    |
    | When a specific language template is not available:
    | 1. If ticket_with_audio template is null, fall back to ticket_only
    | 2. If language is not found, fall back to English ('en')
    |
    */
    'fallback_language' => 'en',
];
