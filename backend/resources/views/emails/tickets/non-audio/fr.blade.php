<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vos billets pour la Galerie des Offices</title>
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
  <h2 style="margin:0; font-family:'Georgia','Times New Roman',serif; font-size:24px; font-weight:400; color:#1F2937;">Vos billets pour la Galerie des Offices</h2>
</td></tr>

<!-- GREETING + INTRO -->
<tr><td style="padding:8px 40px 28px;">
  <p style="margin:0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:15px; color:#4B5563; line-height:1.6;">
    Cher/Chère <strong style="color:#1F2937;">{{ $customerName }}</strong>,
  </p>
  <p style="margin:12px 0 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:15px; color:#4B5563; line-height:1.6;">
    Merci d'avoir choisi Florence with Locals. Vous trouverez ci-joint vos billets pour la Galerie des Offices.
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
      <p style="margin:0 0 2px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; font-weight:600; color:#065F46;">Billets PDF en pièce jointe</p>
      <p style="margin:0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#047857;">Enregistrez-les sur votre téléphone pour un accès facile à l'entrée</p>
    </td>
  </tr>
  </table>
</td></tr>

<!-- ENTRY DETAILS CARD -->
<tr><td style="padding:0 40px 28px;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #E5E7EB; border-radius:8px; overflow:hidden;">
  <tr><td style="background-color:#7C3AED; padding:12px 20px;">
    <p style="margin:0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; font-weight:600; color:#FFFFFF; letter-spacing:1px; text-transform:uppercase;">📍 Détails d'entrée</p>
  </td></tr>
  <tr><td style="padding:0;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
      <tr>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; width:110px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#6B7280;">Entrée</td>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#1F2937; font-weight:600;">Porte 01, Galerie des Offices</td>
      </tr>
      <tr>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#6B7280;">Date et heure</td>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#1F2937; font-weight:600;">{{ $tourDateTime }}</td>
      </tr>
      <tr>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#6B7280;">Invités</td>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#1F2937; font-weight:600;">{{ $pax }}</td>
      </tr>
      <tr>
        <td style="padding:14px 20px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#6B7280;">Référence</td>
        <td style="padding:14px 20px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#1F2937; font-weight:600;">{{ $referenceNumber }}</td>
      </tr>
    </table>
  </td></tr>
  </table>
</td></tr>

<!-- ENTRY INSTRUCTIONS -->
<tr><td style="padding:0 40px 24px;">
  <h3 style="margin:0 0 10px; font-family:'Georgia','Times New Roman',serif; font-size:17px; font-weight:400; color:#1F2937;">Instructions d'entrée</h3>
  <p style="margin:0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#4B5563; line-height:1.65;">
    Rendez-vous directement à la Porte 01 de la Galerie des Offices. Présentez votre billet PDF au personnel et passez le contrôle de sécurité. Il n'est pas nécessaire de rencontrer un représentant ou de récupérer des billets physiques — votre billet PDF joint peut être affiché directement sur votre appareil mobile.
  </p>
</td></tr>

<!-- IMPORTANT INFORMATION (YELLOW WARNING) -->
<tr><td style="padding:0 40px 24px;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#FEF3C7; border-left:4px solid #F59E0B; border-radius:4px;">
  <tr><td style="padding:16px 20px;">
    <h3 style="margin:0 0 8px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; font-weight:600; color:#92400E;">⚠️ Informations importantes</h3>
    <p style="margin:0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#78350F; line-height:1.6;">
      Nous recommandons d'arriver à la Porte 01 au moins <strong>15 minutes avant</strong> votre heure d'entrée prévue. Bien que vos billets vous accordent un accès prioritaire (en évitant la file d'achat), tous les visiteurs doivent passer le contrôle de sécurité, ce qui peut occasionner une attente.
    </p>
  </td></tr>
  </table>
</td></tr>

<!-- ENHANCE YOUR VISIT -->
<tr><td style="padding:0 40px 24px; text-align:center;">
  <h3 style="margin:0 0 6px; font-family:'Georgia','Times New Roman',serif; font-size:17px; font-weight:400; color:#1F2937;">Enrichissez votre visite</h3>
  <p style="margin:0 0 16px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#4B5563; line-height:1.6;">
    Pour profiter au maximum de votre expérience, nous vous invitons à explorer notre guide en ligne. Cette ressource interactive comprend une histoire complète de la Galerie des Offices, ainsi que des informations détaillées sur les chefs-d'œuvre situés aux premier et deuxième étages.
  </p>
  <a href="https://uffizi.florencewithlocals.com/" style="display:inline-block; background-color:#7C3AED; color:#FFFFFF; text-decoration:none; padding:14px 36px; border-radius:8px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; font-weight:600;">
    Ouvrir le guide en ligne →
  </a>
</td></tr>

<!-- PLEASE NOTE (GRAY BOX) -->
<tr><td style="padding:0 40px 24px;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F9FAFB; border-radius:8px; border:1px solid #E5E7EB;">
  <tr><td style="padding:16px 20px;">
    <h3 style="margin:0 0 10px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; font-weight:600; color:#1F2937;">📋 À noter</h3>
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
      <tr>
        <td style="padding:4px 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#4B5563; vertical-align:top; width:20px;">•</td>
        <td style="padding:4px 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#4B5563; line-height:1.5;">Cette réservation n'inclut pas de guide physique.</td>
      </tr>
      <tr>
        <td style="padding:4px 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#4B5563; vertical-align:top;">•</td>
        <td style="padding:4px 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#4B5563; line-height:1.5;">Les billets de la Galerie des Offices sont <strong>nominatifs</strong>. Veuillez apporter une pièce d'identité valide correspondant au nom sur votre billet, car le musée peut refuser l'entrée si les informations ne correspondent pas.</td>
      </tr>
    </table>
  </td></tr>
  </table>
</td></tr>

<!-- KNOW BEFORE YOU GO -->
<tr><td style="padding:0 40px 28px; text-align:center;">
  <a href="https://uffizi.florencewithlocals.com/know-before-you-go" style="font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#7C3AED; text-decoration:underline;">📋 À savoir — Conseils essentiels</a>
</td></tr>

<!-- DIVIDER -->
<tr><td style="padding:0 40px;"><hr style="border:none; border-top:1px solid #E5E7EB; margin:0;"></td></tr>

<!-- CLOSING -->
<tr><td style="padding:24px 40px 8px;">
  <p style="margin:0 0 8px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#4B5563; line-height:1.6;">
    Si vous avez des questions, n'hésitez pas à répondre à ce message.
  </p>
  <p style="margin:0 0 16px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#4B5563;">Nous vous souhaitons une agréable visite.</p>
  <p style="margin:0 0 2px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#1F2937;">Cordialement,</p>
  <p style="margin:0; font-family:'Georgia','Times New Roman',serif; font-size:16px; color:#7C3AED; font-weight:400;">L'équipe Florence with Locals</p>
</td></tr>

<!-- CONTACT BAR -->
<tr><td style="background-color:#F9FAFB; padding:16px 40px; text-align:center; margin-top:20px; border-top:1px solid #E5E7EB;">
  <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;">
  <tr>
    <td style="padding:0 12px;"><a href="https://wa.me/393272491282" style="font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#7C3AED; text-decoration:none;">💬 WhatsApp</a></td>
    <td style="color:#D1D5DB;">|</td>
    <td style="padding:0 12px;"><a href="mailto:contact@florencewithlocals.com" style="font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#7C3AED; text-decoration:none;">✉️ Contact</a></td>
    <td style="color:#D1D5DB;">|</td>
    <td style="padding:0 12px;"><a href="https://www.florencewithlocals.com" style="font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#7C3AED; text-decoration:none;">🌐 Site web</a></td>
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
