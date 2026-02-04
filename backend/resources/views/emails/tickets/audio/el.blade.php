<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Τα εισιτήριά σας + Ξενάγηση για την Πινακοθήκη Ουφίτσι</title>
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

<!-- TICKET + AUDIO BADGE -->
<tr><td style="padding:36px 40px 16px; text-align:center;">
  <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;">
  <tr><td style="background-color:#F3F0FF; border-radius:50%; width:72px; height:72px; text-align:center; vertical-align:middle;">
    <span style="font-size:32px; line-height:72px;">🎟️🎧</span>
  </td></tr>
  </table>
</td></tr>

<!-- MAIN HEADING -->
<tr><td style="padding:0 40px 8px; text-align:center;">
  <h2 style="margin:0; font-family:'Georgia','Times New Roman',serif; font-size:24px; font-weight:400; color:#1F2937;">Τα εισιτήριά σας + Ξενάγηση</h2>
</td></tr>

<!-- GREETING + INTRO -->
<tr><td style="padding:8px 40px 28px;">
  <p style="margin:0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:15px; color:#4B5563; line-height:1.6;">
    Αγαπητέ/ή <strong style="color:#1F2937;">{{ $customerName }}</strong>,
  </p>
  <p style="margin:12px 0 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:15px; color:#4B5563; line-height:1.6;">
    Σας ευχαριστούμε που επιλέξατε την Florence with Locals. Επισυνάπτονται τα εισιτήριά σας για την Πινακοθήκη Ουφίτσι μαζί με την πρόσβαση στην ξενάγηση.
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
      <p style="margin:0 0 2px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; font-weight:600; color:#065F46;">Εισιτήρια PDF επισυνάπτονται</p>
      <p style="margin:0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#047857;">Αποθηκεύστε τα στο τηλέφωνό σας για εύκολη πρόσβαση στην είσοδο</p>
    </td>
  </tr>
  </table>
</td></tr>

<!-- ENTRY DETAILS CARD -->
<tr><td style="padding:0 40px 28px;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #E5E7EB; border-radius:8px; overflow:hidden;">
  <tr><td style="background-color:#7C3AED; padding:12px 20px;">
    <p style="margin:0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; font-weight:600; color:#FFFFFF; letter-spacing:1px; text-transform:uppercase;">📍 Λεπτομέρειες εισόδου</p>
  </td></tr>
  <tr><td style="padding:0;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
      <tr>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; width:110px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#6B7280;">Είσοδος</td>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#1F2937; font-weight:600;">Πόρτα 01, Πινακοθήκη Ουφίτσι</td>
      </tr>
      <tr>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#6B7280;">Ημερομηνία & Ώρα</td>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#1F2937; font-weight:600;">{{ $tourDateTime }}</td>
      </tr>
      <tr>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#6B7280;">Επισκέπτες</td>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#1F2937; font-weight:600;">{{ $pax }}</td>
      </tr>
      <tr>
        <td style="padding:14px 20px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#6B7280;">Αναφορά</td>
        <td style="padding:14px 20px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#1F2937; font-weight:600;">{{ $referenceNumber }}</td>
      </tr>
    </table>
  </td></tr>
  </table>
</td></tr>

<!-- AUDIO GUIDE SECTION (DARK INDIGO) -->
<tr><td style="padding:0 40px 24px;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:linear-gradient(135deg, #1E1B4B 0%, #312E81 100%); border-radius:12px; overflow:hidden;">
  <tr><td style="padding:28px 30px; text-align:center;">
    <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 12px;">
    <tr><td style="background-color:rgba(255,255,255,0.1); border-radius:50%; width:56px; height:56px; text-align:center; vertical-align:middle;">
      <span style="font-size:28px; line-height:56px;">🎧</span>
    </td></tr>
    </table>
    <h3 style="margin:0 0 8px; font-family:'Georgia','Times New Roman',serif; font-size:20px; font-weight:400; color:#FFFFFF;">Οδηγίες ρύθμισης ξενάγησης</h3>
    <p style="margin:0 0 20px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#C4B5FD; line-height:1.6;">
      Πατήστε το κουμπί παρακάτω για να κατεβάσετε την εφαρμογή PopGuide και να ενεργοποιήσετε την ξενάγησή σας. Ο σύνδεσμος θα ρυθμίσει αυτόματα την πρόσβασή σας — δεν χρειάζεται όνομα χρήστη ή κωδικός.
    </p>
    <a href="{{ $audioGuideUrl }}" style="display:inline-block; background-color:#EF4444; color:#FFFFFF; text-decoration:none; padding:16px 40px; border-radius:8px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:15px; font-weight:700; letter-spacing:0.3px;">
      🎧 Ενεργοποίηση Ξενάγησης
    </a>
    <p style="margin:16px 0 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:12px; color:#A5B4FC; line-height:1.5;">
      Μετά τη ρύθμιση, η ξενάγηση μπορεί να χρησιμοποιηθεί εκτός σύνδεσης. Σας συνιστούμε να ολοκληρώσετε αυτό το βήμα πριν την επίσκεψή σας.
    </p>
  </td></tr>
  </table>
</td></tr>

<!-- IMPORTANT INFORMATION (YELLOW WARNING) -->
<tr><td style="padding:0 40px 24px;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#FEF3C7; border-left:4px solid #F59E0B; border-radius:4px;">
  <tr><td style="padding:16px 20px;">
    <h3 style="margin:0 0 8px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; font-weight:600; color:#92400E;">⚠️ Σημαντικές υπενθυμίσεις</h3>
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
      <tr><td style="padding:4px 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#78350F;">⏰ Φτάστε <strong>15 λεπτά νωρίτερα</strong> — οι έλεγχοι ασφαλείας μπορεί να προκαλέσουν καθυστερήσεις</td></tr>
      <tr><td style="padding:4px 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#78350F;">🪪 Φέρτε <strong>έγκυρη ταυτότητα</strong> που να ταιριάζει με το όνομα στο εισιτήριο</td></tr>
      <tr><td style="padding:4px 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#78350F;">📱 Δείξτε το <strong>εισιτήριο PDF</strong> στο προσωπικό στην Πόρτα 01</td></tr>
      <tr><td style="padding:4px 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#78350F;">🎧 Φέρτε τα δικά σας <strong>ακουστικά</strong> για την ξενάγηση</td></tr>
    </table>
  </td></tr>
  </table>
</td></tr>

<!-- KNOW BEFORE YOU GO -->
<tr><td style="padding:0 40px 28px; text-align:center;">
  <a href="https://uffizi.florencewithlocals.com/know-before-you-go" style="font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#7C3AED; text-decoration:underline;">📋 Τι πρέπει να γνωρίζετε — Βασικές συμβουλές</a>
</td></tr>

<!-- DIVIDER -->
<tr><td style="padding:0 40px;"><hr style="border:none; border-top:1px solid #E5E7EB; margin:0;"></td></tr>

<!-- CLOSING -->
<tr><td style="padding:24px 40px 8px;">
  <p style="margin:0 0 8px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#4B5563; line-height:1.6;">
    Αν έχετε ερωτήσεις, μη διστάσετε να απαντήσετε σε αυτό το μήνυμα.
  </p>
  <p style="margin:0 0 16px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#4B5563;">Σας ευχόμαστε μια ευχάριστη επίσκεψη.</p>
  <p style="margin:0 0 2px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#1F2937;">Με εκτίμηση,</p>
  <p style="margin:0; font-family:'Georgia','Times New Roman',serif; font-size:16px; color:#7C3AED; font-weight:400;">Η ομάδα Florence with Locals</p>
</td></tr>

<!-- CONTACT BAR -->
<tr><td style="background-color:#F9FAFB; padding:16px 40px; text-align:center; margin-top:20px; border-top:1px solid #E5E7EB;">
  <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;">
  <tr>
    <td style="padding:0 12px;"><a href="https://wa.me/393272491282" style="font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#7C3AED; text-decoration:none;">💬 WhatsApp</a></td>
    <td style="color:#D1D5DB;">|</td>
    <td style="padding:0 12px;"><a href="mailto:contact@florencewithlocals.com" style="font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#7C3AED; text-decoration:none;">✉️ Επικοινωνία</a></td>
    <td style="color:#D1D5DB;">|</td>
    <td style="padding:0 12px;"><a href="https://www.florencewithlocals.com" style="font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#7C3AED; text-decoration:none;">🌐 Ιστοσελίδα</a></td>
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
