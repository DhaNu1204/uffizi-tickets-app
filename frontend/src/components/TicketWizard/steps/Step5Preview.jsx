import './Steps.css';

// Format date/time for display
const formatDateTime = (booking) => {
  if (!booking.tour_date) return 'Your scheduled time';

  const date = new Date(booking.tour_date);
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  const dateStr = date.toLocaleDateString('en-US', options);
  const timeStr = booking.tour_time || '10:00 AM';

  return `${dateStr} at ${timeStr}`;
};

// Format file size
const formatFileSize = (bytes) => {
  if (!bytes) return '0 KB';
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

// WhatsApp template content (matches actual Twilio templates)
const getWhatsAppPreviewContent = (booking, language, hasAudioGuide) => {
  const name = booking.customer_name || 'Guest';
  const dateTime = formatDateTime(booking);

  const templates = {
    en: {
      withoutAudio: `🎫 Your Uffizi Gallery Tickets

Dear ${name},

Thank you for booking with Florence with Locals. Your Uffizi Gallery tickets are attached.

📍 ENTRY INSTRUCTIONS
Proceed to Door 01 at the Uffizi Gallery. Present your PDF ticket (mobile or printed) to staff and continue through security. No meeting point or ticket collection required.

⏰ IMPORTANT
• Entry time: ${dateTime} – arrive 15 min early
• Security screening may cause delays
• Bring valid ID matching ticket name

🖼️ ENHANCE YOUR VISIT
Explore our online guide: https://uffizi.florencewithlocals.com

📖 KNOW BEFORE YOU GO
Essential tips: https://uffizi.florencewithlocals.com/know-before-you-go

Enjoy your visit!
The Florence with Locals Team`,

      withAudio: `🎫 Your Uffizi Gallery Tickets + Audio Guide

Dear ${name},

Thank you for booking with Florence with Locals. Your Uffizi Gallery tickets are attached.

📍 ENTRY INSTRUCTIONS
Proceed to Door 01 at the Uffizi Gallery. Present your PDF ticket (mobile or printed) to staff and continue through security.

⏰ IMPORTANT
• Entry time: ${dateTime} – arrive 15 min early
• Security screening may cause delays
• Bring valid ID matching ticket name

🎧 YOUR AUDIO GUIDE
Tap to activate your PopGuide audio tour (link in message)

📖 KNOW BEFORE YOU GO
Essential tips: https://uffizi.florencewithlocals.com/know-before-you-go

Enjoy your visit!
The Florence with Locals Team`,
    },

    it: {
      withoutAudio: `🎫 I Tuoi Biglietti per la Galleria degli Uffizi

Gentile ${name},

Grazie per aver prenotato con Florence with Locals. I tuoi biglietti per la Galleria degli Uffizi sono in allegato.

📍 ISTRUZIONI PER L'INGRESSO
Recati direttamente alla Porta 01 della Galleria degli Uffizi. Mostra il biglietto PDF (su telefono o stampato) al personale e prosegui attraverso i controlli di sicurezza.

⏰ IMPORTANTE
• Orario d'ingresso: ${dateTime} – arriva 15 min prima
• I controlli di sicurezza potrebbero causare ritardi
• Porta un documento d'identità valido

🖼️ MIGLIORA LA TUA VISITA
Esplora la nostra guida online: https://uffizi.florencewithlocals.com

📖 DA SAPERE PRIMA DI PARTIRE
Consigli essenziali: https://uffizi.florencewithlocals.com/know-before-you-go

Buona visita!
Il Team di Florence with Locals`,

      withAudio: `🎫 I Tuoi Biglietti + Audioguida per la Galleria degli Uffizi

Gentile ${name},

Grazie per aver prenotato con Florence with Locals. I tuoi biglietti sono in allegato.

📍 ISTRUZIONI PER L'INGRESSO
Recati alla Porta 01 della Galleria degli Uffizi.

⏰ IMPORTANTE
• Orario d'ingresso: ${dateTime} – arriva 15 min prima

🎧 LA TUA AUDIOGUIDA
Tocca per attivare il tour audio PopGuide (link nel messaggio)

Buona visita!
Il Team di Florence with Locals`,
    },

    es: {
      withoutAudio: `🎫 Tus Entradas para la Galería Uffizi

Estimado/a ${name},

Gracias por reservar con Florence with Locals. Tus entradas para la Galería Uffizi están adjuntas.

📍 INSTRUCCIONES DE ENTRADA
Dirígete a la Puerta 01 de la Galería Uffizi. Presenta tu entrada PDF al personal.

⏰ IMPORTANTE
• Hora de entrada: ${dateTime} – llega 15 min antes
• Los controles de seguridad pueden causar retrasos

🖼️ MEJORA TU VISITA
https://uffizi.florencewithlocals.com

¡Disfruta tu visita!
El Equipo de Florence with Locals`,
      withAudio: `🎫 Tus Entradas + Audioguía para la Galería Uffizi

Estimado/a ${name},

Tus entradas están adjuntas.

🎧 TU AUDIOGUÍA
Toca para activar tu tour de audio PopGuide

¡Disfruta tu visita!`,
    },

    de: {
      withoutAudio: `🎫 Ihre Uffizien-Galerie Tickets

Liebe/r ${name},

Vielen Dank für Ihre Buchung bei Florence with Locals. Ihre Tickets sind angehängt.

📍 EINLASSINFORMATIONEN
Gehen Sie direkt zu Eingang 01 der Uffizien-Galerie.

⏰ WICHTIG
• Einlasszeit: ${dateTime} – 15 Min. früher erscheinen

Genießen Sie Ihren Besuch!
Ihr Florence with Locals Team`,
      withAudio: `🎫 Ihre Uffizien Tickets + Audioguide

Liebe/r ${name},

Ihre Tickets sind angehängt.

🎧 IHR AUDIOGUIDE
Tippen Sie, um Ihren PopGuide Audio-Tour zu aktivieren

Genießen Sie Ihren Besuch!`,
    },

    fr: {
      withoutAudio: `🎫 Vos Billets pour la Galerie des Offices

Cher/Chère ${name},

Merci d'avoir réservé avec Florence with Locals. Vos billets sont en pièce jointe.

📍 INSTRUCTIONS D'ENTRÉE
Rendez-vous à la Porte 01 de la Galerie des Offices.

⏰ IMPORTANT
• Heure d'entrée: ${dateTime} – arrivez 15 min à l'avance

Bonne visite!
L'équipe Florence with Locals`,
      withAudio: `🎫 Vos Billets + Audioguide pour la Galerie des Offices

Cher/Chère ${name},

Vos billets sont en pièce jointe.

🎧 VOTRE AUDIOGUIDE
Appuyez pour activer votre visite audio PopGuide

Bonne visite!`,
    },
  };

  const langTemplates = templates[language] || templates['en'];
  return hasAudioGuide ? langTemplates.withAudio : langTemplates.withoutAudio;
};

// SMS notification content
const getSmsPreviewContent = (language) => {
  const smsTemplates = {
    en: "Your Uffizi Gallery tickets have been sent to your email. Please check your inbox and spam folder. - Florence with Locals",
    it: "I tuoi biglietti per la Galleria degli Uffizi sono stati inviati alla tua email. Controlla la posta in arrivo e lo spam. - Florence with Locals",
    es: "Tus entradas para la Galería Uffizi han sido enviadas a tu email. Revisa tu bandeja de entrada y spam. - Florence with Locals",
    de: "Ihre Uffizi-Galerie-Tickets wurden an Ihre E-Mail gesendet. Überprüfen Sie Ihren Posteingang und Spam-Ordner. - Florence with Locals",
    fr: "Vos billets pour la Galerie des Offices ont été envoyés à votre email. Vérifiez votre boîte de réception et spam. - Florence with Locals",
    pt: "Seus ingressos para a Galeria Uffizi foram enviados para seu email. Verifique sua caixa de entrada e spam. - Florence with Locals",
    ja: "ウフィツィ美術館のチケットをメールで送信しました。受信トレイと迷惑メールフォルダをご確認ください。- Florence with Locals",
    ko: "우피치 미술관 티켓이 이메일로 전송되었습니다. 받은편지함과 스팸 폴더를 확인해주세요. - Florence with Locals",
    el: "Τα εισιτήριά σας στάλθηκαν στο email σας. Ελέγξτε τα εισερχόμενα και τα spam. - Florence with Locals",
    tr: "Uffizi Galerisi biletleriniz e-postanıza gönderildi. Gelen kutunuzu ve spam klasörünü kontrol edin. - Florence with Locals",
  };

  return smsTemplates[language] || smsTemplates['en'];
};

// Language names
const LANGUAGE_NAMES = {
  en: 'English',
  it: 'Italian',
  es: 'Spanish',
  de: 'German',
  fr: 'French',
  pt: 'Portuguese',
  ja: 'Japanese',
  ko: 'Korean',
  el: 'Greek',
  tr: 'Turkish',
  custom: 'Custom',
};

export default function Step5Preview({ booking, wizardData, isLoading }) {
  const { channelInfo, attachments, language, customMessage } = wizardData;
  const isCustomMessage = language === 'custom' && customMessage;

  if (isLoading) {
    return (
      <div className="wizard-step-content step-preview">
        <div className="loading-state">
          <span className="spinner large" />
          <p>Detecting messaging channel...</p>
        </div>
      </div>
    );
  }

  // Determine if WhatsApp is available (primary channel is whatsapp)
  const hasWhatsApp = channelInfo?.primary === 'whatsapp';
  const hasEmail = !!booking.customer_email;
  const primaryChannel = channelInfo?.primary;

  return (
    <div className="wizard-step-content step-preview">
      <h3>Preview & Confirm</h3>
      <p className="step-description">
        Review the message before sending.
      </p>

      {/* Delivery Channel - Fixed Logic */}
      <div className="channel-detection">
        <h4>Delivery Channel</h4>
        <div className="channel-info">
          {hasWhatsApp ? (
            // PRIMARY: WhatsApp only
            <div className="channel-badge primary whatsapp-badge">
              <svg viewBox="0 0 24 24" fill="currentColor" className="channel-icon whatsapp">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
              </svg>
              <span>WhatsApp</span>
            </div>
          ) : hasEmail ? (
            // FALLBACK: Email + SMS
            <>
              <div className="channel-badge primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="channel-icon email">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                  <polyline points="22,6 12,13 2,6" />
                </svg>
                <span>Email</span>
              </div>
              {booking.customer_phone && (
                <>
                  <span className="plus">+</span>
                  <div className="channel-badge secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="channel-icon sms">
                      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                    </svg>
                    <span>SMS</span>
                  </div>
                </>
              )}
            </>
          ) : (
            <div className="channel-badge error">
              <span>⚠️ No delivery method available</span>
            </div>
          )}
        </div>

        {/* Delivery description */}
        <p className="channel-description">
          {hasWhatsApp
            ? '📱 Will send via WhatsApp with PDF attachment'
            : hasEmail
              ? '📧 Will send PDF via Email' + (booking.customer_phone ? ' + SMS notification' : '')
              : '❌ Cannot send - no contact information'
          }
        </p>
      </div>

      {/* Attachments Summary - Show detailed info */}
      <div className="attachments-summary">
        <h4>📎 Attachments for Booking #{booking.id}</h4>
        <div className="attachments-list">
          {attachments.map((att) => (
            <div key={att.id} className="attachment-item-preview">
              <div className="attachment-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                  <polyline points="14 2 14 8 20 8" />
                </svg>
              </div>
              <div className="attachment-details">
                <span className="attachment-name">{att.original_name}</span>
                <span className="attachment-meta">
                  {formatFileSize(att.size)} • Booking #{booking.id}
                </span>
              </div>
              <div className="attachment-check">✓</div>
            </div>
          ))}
        </div>
        {booking.reference_number && !attachments.some(a =>
          a.original_name?.toLowerCase().includes(booking.reference_number?.toLowerCase())
        ) && (
          <div className="attachment-warning">
            ⚠️ Filename does not contain reference "{booking.reference_number}".
            Please verify this is the correct ticket.
          </div>
        )}
      </div>

      {/* Audio Guide Section */}
      {wizardData.hasVoxAccount && wizardData.voxDynamicLink && (
        <div className="preview-section audio-guide-preview">
          <h4>🎧 Audio Guide</h4>
          <div className="audio-guide-info">
            <p>PopGuide link will be included in the message:</p>
            <code>{wizardData.voxDynamicLink}</code>
          </div>
        </div>
      )}

      {/* Message Preview - Show ONLY relevant channel */}
      {isCustomMessage ? (
        // Custom Message Preview
        <div className="message-preview custom">
          <h4>✏️ Custom Message Preview</h4>
          <div className="custom-message-badge">
            <span className="badge-icon">✏️</span>
            <span>Custom Message</span>
          </div>
          {customMessage.subject && (
            <div className="preview-subject">
              <strong>Subject:</strong> {customMessage.subject}
            </div>
          )}
          <div className="preview-content">
            <pre>{customMessage.content}</pre>
          </div>
          <div className="preview-recipient">
            <strong>To:</strong> {booking.customer_email || booking.customer_phone}
          </div>
        </div>
      ) : hasWhatsApp ? (
        // WhatsApp Preview ONLY
        <div className="message-preview whatsapp">
          <h4>📱 WhatsApp Message Preview ({LANGUAGE_NAMES[language] || language})</h4>
          <div className="preview-content whatsapp-style">
            <pre>{getWhatsAppPreviewContent(booking, language, booking.has_audio_guide)}</pre>
          </div>
          <div className="preview-attachment">
            <span className="pdf-icon">📄</span>
            <span>PDF Attachment: {attachments[0]?.original_name || 'ticket.pdf'}</span>
          </div>
          <div className="preview-recipient">
            <strong>To:</strong> {booking.customer_phone}
          </div>
        </div>
      ) : hasEmail ? (
        // Email + SMS Preview (fallback)
        <>
          <div className="message-preview email">
            <h4>📧 Email Preview ({LANGUAGE_NAMES[language] || language})</h4>
            <div className="preview-subject">
              <strong>Subject:</strong> Your Uffizi Gallery Tickets - {booking.reference_number || 'Booking'}
            </div>
            <div className="preview-content">
              <pre>{getWhatsAppPreviewContent(booking, language, booking.has_audio_guide)}</pre>
            </div>
            <div className="preview-attachment">
              <span className="pdf-icon">📄</span>
              <span>PDF Attachment: {attachments[0]?.original_name || 'ticket.pdf'}</span>
            </div>
            <div className="preview-recipient">
              <strong>To:</strong> {booking.customer_email}
            </div>
          </div>

          {booking.customer_phone && (
            <div className="message-preview sms fallback">
              <h4>📱 SMS Notification ({LANGUAGE_NAMES[language] || language})</h4>
              <div className="preview-content sms-style">
                <pre>{getSmsPreviewContent(language)}</pre>
              </div>
              <div className="preview-recipient">
                <strong>To:</strong> {booking.customer_phone}
              </div>
            </div>
          )}
        </>
      ) : null}

      <div className="confirm-box">
        <strong>✅ Ready to send?</strong>
        <p>Click "Send Ticket" to deliver the ticket to the customer.</p>
      </div>
    </div>
  );
}
