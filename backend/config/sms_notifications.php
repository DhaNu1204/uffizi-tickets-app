<?php

/**
 * SMS Notification Messages
 *
 * Short messages (under 160 characters) to notify customers that their tickets
 * have been sent via email. These are NOT the actual tickets - just a notification.
 *
 * Character limits for 1 SMS segment:
 * - GSM-7 encoding: 160 characters (Latin alphabet, basic punctuation)
 * - UCS-2 encoding: 70 characters (Unicode - Japanese, Korean, Chinese, Greek, etc.)
 *
 * Note: Messages with non-Latin characters will use UCS-2 and may be split into multiple segments.
 */

return [
    'ticket_email_notification' => [
        // Latin alphabet languages (160 char limit, GSM-7)
        'en' => 'Your Uffizi Gallery tickets have been sent to your email. Please check your inbox. - Florence with Locals',  // 104 chars
        'it' => 'I biglietti Uffizi sono stati inviati alla tua email. Controlla la posta. - Florence with Locals',  // 96 chars
        'es' => 'Sus entradas Uffizi han sido enviadas a su email. Revise su bandeja de entrada. - Florence with Locals',  // 105 chars
        'de' => 'Ihre Uffizi-Tickets wurden an Ihre E-Mail gesendet. Bitte Posteingang prüfen. - Florence with Locals',  // 103 chars
        'fr' => 'Vos billets Uffizi ont ete envoyes par email. Verifiez votre boite de reception. - Florence with Locals',  // 106 chars
        'pt' => 'Seus ingressos Uffizi foram enviados ao seu email. Verifique sua caixa de entrada. - Florence with Locals',  // 107 chars
        'tr' => 'Uffizi biletleriniz e-postaniza gonderildi. Lutfen gelen kutunuzu kontrol edin. - Florence with Locals',  // 105 chars

        // Unicode languages (70 char limit, UCS-2) - will be 2 segments
        'ja' => 'ウフィツィ美術館のチケットをメールで送信しました。受信箱をご確認ください。Florence with Locals',  // 52 chars
        'ko' => '우피치 미술관 티켓이 이메일로 발송되었습니다. 받은편지함을 확인해주세요. - Florence with Locals',  // 57 chars
        'el' => 'Τα εισιτήρια Uffizi στάλθηκαν στο email σας. Ελέγξτε τα εισερχόμενα. - Florence with Locals',  // 93 chars (will be 2 segments due to Greek)
    ],

    // Fallback language if requested language not found
    'fallback_language' => 'en',
];
