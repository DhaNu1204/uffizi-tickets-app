<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>I tuoi biglietti per la Galleria degli Uffizi</title>
</head>
<body style="margin:0; padding:0; background-color:#F3F0FF;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F3F0FF;">
<tr><td align="center" style="padding:24px 12px;">
<table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color:#FFFFFF; border-radius:12px; overflow:hidden; box-shadow:0 4px 24px rgba(109,40,217,0.08);">

<!-- HEADER -->
<tr><td style="background:linear-gradient(135deg, #7C3AED 0%, #4C1D95 100%); padding:32px 40px; text-align:center;">
  <h1 style="margin:0; font-family:'Georgia','Times New Roman',serif; font-size:26px; font-weight:400; color:#FFFFFF; letter-spacing:1.5px;">FLORENCE</h1>
  <p style="margin:2px 0 0; font-family:'Georgia','Times New Roman',serif; font-size:15px; color:#DDD6FE; letter-spacing:4px; text-transform:uppercase;">WITH LOCALS</p>
</td></tr>

<!-- TICKET BADGE -->
<tr><td style="padding:36px 40px 16px; text-align:center;">
  <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;">
  <tr><td style="background-color:#F3F0FF; border-radius:50%; width:72px; height:72px; text-align:center; vertical-align:middle;">
    <span style="font-size:36px; line-height:72px;">🎟️</span>
  </td></tr>
  </table>
</td></tr>

<!-- MAIN HEADING -->
<tr><td style="padding:0 40px 8px; text-align:center;">
  <h2 style="margin:0; font-family:'Georgia','Times New Roman',serif; font-size:24px; font-weight:400; color:#1F2937;">I tuoi biglietti per la Galleria degli Uffizi</h2>
</td></tr>

<!-- GREETING + INTRO -->
<tr><td style="padding:8px 40px 28px;">
  <p style="margin:0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:15px; color:#4B5563; line-height:1.6;">
    Gentile <strong style="color:#1F2937;">{{ $customerName }}</strong>,
  </p>
  <p style="margin:12px 0 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:15px; color:#4B5563; line-height:1.6;">
    Grazie per aver scelto Florence with Locals. In allegato troverai i tuoi biglietti per la Galleria degli Uffizi.
  </p>
</td></tr>

<!-- PDF ATTACHMENT NOTICE (GREEN) -->
<tr><td style="padding:0 40px 28px;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#ECFDF5; border:1px solid #A7F3D0; border-radius:8px;">
  <tr>
    <td style="padding:16px 20px; width:44px; vertical-align:middle;">
      <div style="background-color:#D1FAE5; border-radius:8px; width:44px; height:44px; text-align:center; line-height:44px;">
        <span style="font-size:22px;">📄</span>
      </div>
    </td>
    <td style="padding:16px 16px 16px 0; vertical-align:middle;">
      <p style="margin:0 0 2px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; font-weight:600; color:#065F46;">Biglietti PDF in allegato</p>
      <p style="margin:0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#047857;">Salvali sul telefono per un accesso rapido all'ingresso</p>
    </td>
  </tr>
  </table>
</td></tr>

<!-- ENTRY DETAILS CARD -->
<tr><td style="padding:0 40px 28px;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #E5E7EB; border-radius:8px; overflow:hidden;">
  <tr><td style="background-color:#7C3AED; padding:12px 20px;">
    <p style="margin:0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; font-weight:600; color:#FFFFFF; letter-spacing:1px; text-transform:uppercase;">📍 Dettagli ingresso</p>
  </td></tr>
  <tr><td style="padding:0;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
      <tr>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; width:110px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#6B7280;">Ingresso</td>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#1F2937; font-weight:600;">Porta 01, Galleria degli Uffizi</td>
      </tr>
      <tr>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#6B7280;">Data e ora</td>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#1F2937; font-weight:600;">{{ $tourDateTime }}</td>
      </tr>
      <tr>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#6B7280;">Ospiti</td>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#1F2937; font-weight:600;">{{ $pax }}</td>
      </tr>
      <tr>
        <td style="padding:14px 20px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#6B7280;">Riferimento</td>
        <td style="padding:14px 20px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#1F2937; font-weight:600;">{{ $referenceNumber }}</td>
      </tr>
    </table>
  </td></tr>
  </table>
</td></tr>

<!-- ENTRY INSTRUCTIONS -->
<tr><td style="padding:0 40px 24px;">
  <h3 style="margin:0 0 10px; font-family:'Georgia','Times New Roman',serif; font-size:17px; font-weight:400; color:#1F2937;">Istruzioni per l'ingresso</h3>
  <p style="margin:0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#4B5563; line-height:1.65;">
    Recati direttamente alla Porta 01 della Galleria degli Uffizi. Mostra il biglietto PDF al personale e prosegui attraverso i controlli di sicurezza. Non è necessario incontrare un rappresentante né ritirare biglietti fisici — i biglietti PDF allegati possono essere mostrati sul tuo dispositivo mobile.
  </p>
</td></tr>

<!-- IMPORTANT INFORMATION (YELLOW WARNING) -->
<tr><td style="padding:0 40px 24px;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#FEF3C7; border-left:4px solid #F59E0B; border-radius:4px;">
  <tr><td style="padding:16px 20px;">
    <h3 style="margin:0 0 8px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; font-weight:600; color:#92400E;">⚠️ Informazioni importanti</h3>
    <p style="margin:0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#78350F; line-height:1.6;">
      Ti consigliamo di arrivare alla Porta 01 almeno <strong>15 minuti prima</strong> dell'orario di ingresso previsto. Sebbene i biglietti garantiscano l'ingresso prioritario (saltando la coda per l'acquisto), tutti i visitatori devono passare attraverso i controlli di sicurezza, il che potrebbe comportare un'attesa.
    </p>
  </td></tr>
  </table>
</td></tr>

<!-- ENHANCE YOUR VISIT -->
<tr><td style="padding:0 40px 24px; text-align:center;">
  <h3 style="margin:0 0 6px; font-family:'Georgia','Times New Roman',serif; font-size:17px; font-weight:400; color:#1F2937;">Arricchisci la tua visita</h3>
  <p style="margin:0 0 16px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#4B5563; line-height:1.6;">
    Per sfruttare al meglio la tua esperienza, ti invitiamo a esplorare la nostra guida online. Questa risorsa interattiva include una storia completa della Galleria degli Uffizi e informazioni dettagliate sui capolavori presenti al primo e secondo piano.
  </p>
  <a href="https://uffizi.florencewithlocals.com/" style="display:inline-block; background-color:#7C3AED; color:#FFFFFF; text-decoration:none; padding:14px 36px; border-radius:8px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; font-weight:600;">
    Apri la guida online →
  </a>
</td></tr>

<!-- PLEASE NOTE (GRAY BOX) -->
<tr><td style="padding:0 40px 24px;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F9FAFB; border-radius:8px; border:1px solid #E5E7EB;">
  <tr><td style="padding:16px 20px;">
    <h3 style="margin:0 0 10px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; font-weight:600; color:#1F2937;">📋 Note importanti</h3>
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
      <tr>
        <td style="padding:4px 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#4B5563; vertical-align:top; width:20px;">•</td>
        <td style="padding:4px 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#4B5563; line-height:1.5;">Una guida fisica non è inclusa in questa prenotazione.</td>
      </tr>
      <tr>
        <td style="padding:4px 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#4B5563; vertical-align:top;">•</td>
        <td style="padding:4px 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#4B5563; line-height:1.5;">I biglietti della Galleria degli Uffizi sono <strong>nominativi</strong>. Si prega di portare un documento d'identità valido corrispondente al nome sul biglietto, poiché il museo potrebbe rifiutare l'ingresso se i dati non corrispondono.</td>
      </tr>
    </table>
  </td></tr>
  </table>
</td></tr>

<!-- KNOW BEFORE YOU GO -->
<tr><td style="padding:0 40px 28px; text-align:center;">
  <a href="https://uffizi.florencewithlocals.com/know-before-you-go" style="font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#7C3AED; text-decoration:underline;">📋 Da sapere prima di partire — Consigli essenziali</a>
</td></tr>

<!-- DIVIDER -->
<tr><td style="padding:0 40px;"><hr style="border:none; border-top:1px solid #E5E7EB; margin:0;"></td></tr>

<!-- CLOSING -->
<tr><td style="padding:24px 40px 8px;">
  <p style="margin:0 0 8px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#4B5563; line-height:1.6;">
    Per qualsiasi domanda, non esitare a rispondere a questo messaggio.
  </p>
  <p style="margin:0 0 16px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#4B5563;">Ti auguriamo una piacevole visita.</p>
  <p style="margin:0 0 2px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#1F2937;">Cordiali saluti,</p>
  <p style="margin:0; font-family:'Georgia','Times New Roman',serif; font-size:16px; color:#7C3AED; font-weight:400;">Il team di Florence with Locals</p>
</td></tr>

<!-- CONTACT BAR -->
<tr><td style="background-color:#F9FAFB; padding:16px 40px; text-align:center; margin-top:20px; border-top:1px solid #E5E7EB;">
  <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;">
  <tr>
    <td style="padding:0 12px;"><a href="https://wa.me/393272491282" style="font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#7C3AED; text-decoration:none;">💬 WhatsApp</a></td>
    <td style="color:#D1D5DB;">|</td>
    <td style="padding:0 12px;"><a href="mailto:contact@florencewithlocals.com" style="font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#7C3AED; text-decoration:none;">✉️ Scrivici</a></td>
    <td style="color:#D1D5DB;">|</td>
    <td style="padding:0 12px;"><a href="https://www.florencewithlocals.com" style="font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#7C3AED; text-decoration:none;">🌐 Sito web</a></td>
  </tr>
  </table>
</td></tr>

<!-- BOTTOM PURPLE STRIPE -->
<tr><td style="background:linear-gradient(135deg, #7C3AED 0%, #4C1D95 100%); height:6px; font-size:0; line-height:0;">&nbsp;</td></tr>

</table>
</td></tr>
</table>

</body>
</html>
