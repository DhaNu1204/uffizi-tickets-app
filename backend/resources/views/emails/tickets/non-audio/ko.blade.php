<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>우피치 미술관 입장권</title>
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
  <h2 style="margin:0; font-family:'Georgia','Times New Roman',serif; font-size:24px; font-weight:400; color:#1F2937;">우피치 미술관 입장권</h2>
</td></tr>

<!-- GREETING + INTRO -->
<tr><td style="padding:8px 40px 28px;">
  <p style="margin:0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:15px; color:#4B5563; line-height:1.6;">
    <strong style="color:#1F2937;">{{ $customerName }}</strong> 님
  </p>
  <p style="margin:12px 0 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:15px; color:#4B5563; line-height:1.6;">
    Florence with Locals를 선택해 주셔서 감사합니다. 우피치 미술관 입장권이 첨부되어 있습니다.
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

<!-- ENTRY INSTRUCTIONS -->
<tr><td style="padding:0 40px 24px;">
  <h3 style="margin:0 0 10px; font-family:'Georgia','Times New Roman',serif; font-size:17px; font-weight:400; color:#1F2937;">입장 안내</h3>
  <p style="margin:0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#4B5563; line-height:1.65;">
    우피치 미술관 도어 01로 직접 가세요. 직원에게 PDF 티켓을 보여주고 보안 검색을 통과하세요. 담당자를 만나거나 실물 티켓을 수령할 필요가 없습니다 — 첨부된 PDF 티켓을 모바일 기기에서 바로 보여주시면 됩니다.
  </p>
</td></tr>

<!-- IMPORTANT INFORMATION (YELLOW WARNING) -->
<tr><td style="padding:0 40px 24px;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#FEF3C7; border-left:4px solid #F59E0B; border-radius:4px;">
  <tr><td style="padding:16px 20px;">
    <h3 style="margin:0 0 8px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; font-weight:600; color:#92400E;">⚠️ 중요 안내</h3>
    <p style="margin:0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#78350F; line-height:1.6;">
      예정된 입장 시간 최소 <strong>15분 전</strong>에 도어 01에 도착하시기 바랍니다. 티켓은 우선 입장(티켓 구매 줄 건너뛰기)을 제공하지만, 모든 방문객은 보안 검색을 통과해야 하며 대기 시간이 발생할 수 있습니다.
    </p>
  </td></tr>
  </table>
</td></tr>

<!-- ENHANCE YOUR VISIT -->
<tr><td style="padding:0 40px 24px; text-align:center;">
  <h3 style="margin:0 0 6px; font-family:'Georgia','Times New Roman',serif; font-size:17px; font-weight:400; color:#1F2937;">방문을 더욱 풍요롭게</h3>
  <p style="margin:0 0 16px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; color:#4B5563; line-height:1.6;">
    최고의 경험을 위해 온라인 가이드를 둘러보세요. 이 인터랙티브 자료에는 우피치 미술관의 종합적인 역사와 1층 및 2층에 있는 걸작들에 대한 상세 정보가 포함되어 있습니다.
  </p>
  <a href="https://uffizi.florencewithlocals.com/" style="display:inline-block; background-color:#7C3AED; color:#FFFFFF; text-decoration:none; padding:14px 36px; border-radius:8px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; font-weight:600;">
    온라인 가이드 열기 →
  </a>
</td></tr>

<!-- PLEASE NOTE (GRAY BOX) -->
<tr><td style="padding:0 40px 24px;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F9FAFB; border-radius:8px; border:1px solid #E5E7EB;">
  <tr><td style="padding:16px 20px;">
    <h3 style="margin:0 0 10px; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:14px; font-weight:600; color:#1F2937;">📋 참고 사항</h3>
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
      <tr>
        <td style="padding:4px 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#4B5563; vertical-align:top; width:20px;">•</td>
        <td style="padding:4px 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#4B5563; line-height:1.5;">이 예약에는 실제 가이드가 포함되어 있지 않습니다.</td>
      </tr>
      <tr>
        <td style="padding:4px 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#4B5563; vertical-align:top;">•</td>
        <td style="padding:4px 0; font-family:'Segoe UI',Helvetica,Arial,sans-serif; font-size:13px; color:#4B5563; line-height:1.5;">우피치 미술관 티켓은 <strong>기명식</strong>입니다. 티켓의 이름과 일치하는 유효한 신분증을 지참하세요. 정보가 일치하지 않으면 미술관에서 입장을 거부할 수 있습니다.</td>
      </tr>
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
