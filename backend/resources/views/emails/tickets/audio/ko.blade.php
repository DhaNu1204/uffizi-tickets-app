<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>우피치 미술관 입장권 + 오디오 가이드</title>
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
  <h2 style="margin:0; font-family:'Georgia','Times New Roman',serif; font-size:24px; font-weight:400; color:#1F2937;">입장권 + 오디오 가이드</h2>
</td></tr>

<!-- GREETING + INTRO -->
<tr><td style="padding:8px 40px 28px;">
  <p style="margin:0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:15px; color:#4B5563; line-height:1.6;">
    <strong style="color:#1F2937;">{{ $customerName }}</strong> 님
  </p>
  <p style="margin:12px 0 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:15px; color:#4B5563; line-height:1.6;">
    Florence with Locals를 선택해 주셔서 감사합니다. 우피치 미술관 입장권과 오디오 가이드 접속 정보가 첨부되어 있습니다.
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
      <p style="margin:0 0 2px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; font-weight:600; color:#065F46;">PDF 티켓 첨부됨</p>
      <p style="margin:0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#047857;">입구에서 쉽게 접근할 수 있도록 휴대폰에 저장하세요</p>
    </td>
  </tr>
  </table>
</td></tr>

<!-- ENTRY DETAILS CARD -->
<tr><td style="padding:0 40px 28px;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #E5E7EB; border-radius:8px; overflow:hidden;">
  <tr><td style="background-color:#7C3AED; padding:12px 20px;">
    <p style="margin:0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; font-weight:600; color:#FFFFFF; letter-spacing:1px; text-transform:uppercase;">📍 입장 정보</p>
  </td></tr>
  <tr><td style="padding:0;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
      <tr>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; width:110px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#6B7280;">입구</td>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#1F2937; font-weight:600;">도어 01, 우피치 미술관</td>
      </tr>
      <tr>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#6B7280;">날짜 및 시간</td>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#1F2937; font-weight:600;">{{ $tourDateTime }}</td>
      </tr>
      <tr>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#6B7280;">인원</td>
        <td style="padding:14px 20px; border-bottom:1px solid #F3F4F6; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#1F2937; font-weight:600;">{{ $pax }}</td>
      </tr>
      <tr>
        <td style="padding:14px 20px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#6B7280;">예약 번호</td>
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
    <h3 style="margin:0 0 8px; font-family:'Georgia','Times New Roman',serif; font-size:20px; font-weight:400; color:#FFFFFF;">오디오 가이드 설정 안내</h3>
    <p style="margin:0 0 20px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#C4B5FD; line-height:1.6;">
      아래 버튼을 탭하여 PopGuide 앱을 다운로드하고 오디오 가이드를 활성화하세요. 링크가 자동으로 접속을 설정해 드립니다 — 사용자 이름이나 비밀번호가 필요 없습니다.
    </p>
    <a href="{{ $audioGuideUrl }}" style="display:inline-block; background-color:#EF4444; color:#FFFFFF; text-decoration:none; padding:16px 40px; border-radius:8px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:15px; font-weight:700; letter-spacing:0.3px;">
      🎧 오디오 가이드 활성화
    </a>
    <p style="margin:16px 0 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:12px; color:#A5B4FC; line-height:1.5;">
      설정 후 오디오 가이드는 오프라인으로 사용할 수 있습니다. 방문 전에 이 단계를 완료하시기 바랍니다.
    </p>
  </td></tr>
  </table>
</td></tr>

<!-- IMPORTANT INFORMATION (YELLOW WARNING) -->
<tr><td style="padding:0 40px 24px;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#FEF3C7; border-left:4px solid #F59E0B; border-radius:4px;">
  <tr><td style="padding:16px 20px;">
    <h3 style="margin:0 0 8px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; font-weight:600; color:#92400E;">⚠️ 중요 안내</h3>
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
      <tr><td style="padding:4px 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#78350F;">⏰ <strong>15분 전</strong>에 도착하세요 — 보안 검색으로 지연될 수 있습니다</td></tr>
      <tr><td style="padding:4px 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#78350F;">🪪 티켓 이름과 일치하는 <strong>유효한 신분증</strong>을 지참하세요</td></tr>
      <tr><td style="padding:4px 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#78350F;">📱 도어 01 직원에게 <strong>PDF 티켓</strong>을 보여주세요</td></tr>
      <tr><td style="padding:4px 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#78350F;">🎧 오디오 가이드용 <strong>이어폰</strong>을 지참하세요</td></tr>
    </table>
  </td></tr>
  </table>
</td></tr>

<!-- KNOW BEFORE YOU GO -->
<tr><td style="padding:0 40px 28px; text-align:center;">
  <a href="https://uffizi.florencewithlocals.com/know-before-you-go" style="font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#7C3AED; text-decoration:underline;">📋 방문 전 알아두세요 — 필수 팁</a>
</td></tr>

<!-- DIVIDER -->
<tr><td style="padding:0 40px;"><hr style="border:none; border-top:1px solid #E5E7EB; margin:0;"></td></tr>

<!-- CLOSING -->
<tr><td style="padding:24px 40px 8px;">
  <p style="margin:0 0 8px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#4B5563; line-height:1.6;">
    질문이 있으시면 이 메시지에 회신해 주세요.
  </p>
  <p style="margin:0 0 16px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#4B5563;">즐거운 방문 되시기 바랍니다.</p>
  <p style="margin:0 0 2px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#1F2937;">감사합니다,</p>
  <p style="margin:0; font-family:'Georgia','Times New Roman',serif; font-size:16px; color:#7C3AED; font-weight:400;">Florence with Locals 팀</p>
</td></tr>

<!-- CONTACT BAR -->
<tr><td style="background-color:#F9FAFB; padding:16px 40px; text-align:center; margin-top:20px; border-top:1px solid #E5E7EB;">
  <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;">
  <tr>
    <td style="padding:0 12px;"><a href="https://wa.me/393272491282" style="font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#7C3AED; text-decoration:none;">💬 WhatsApp</a></td>
    <td style="color:#D1D5DB;">|</td>
    <td style="padding:0 12px;"><a href="mailto:contact@florencewithlocals.com" style="font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#7C3AED; text-decoration:none;">✉️ 문의하기</a></td>
    <td style="color:#D1D5DB;">|</td>
    <td style="padding:0 12px;"><a href="https://www.florencewithlocals.com" style="font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#7C3AED; text-decoration:none;">🌐 웹사이트</a></td>
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
