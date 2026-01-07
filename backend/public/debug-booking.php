<?php
/**
 * Simple debug page to inspect Bokun booking structure
 * Access via: https://your-domain.com/debug-booking.php?code=GYG6H8LKF93A&key=uffizi2024debug
 *
 * DELETE THIS FILE AFTER DEBUGGING!
 */

// Security key - change this or delete file after use
$secretKey = 'uffizi2024debug';

if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
    http_response_code(403);
    die('Unauthorized. Add ?key=uffizi2024debug to the URL');
}

if (!isset($_GET['code'])) {
    die('Please provide a booking code: ?code=GYG6H8LKF93A&key=uffizi2024debug');
}

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = Illuminate\Http\Request::capture());

// Get the booking details
$bokunService = app(\App\Services\BokunService::class);
$code = $_GET['code'];
$details = $bokunService->getBookingDetails($code);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Booking: <?= htmlspecialchars($code) ?></title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a2e; color: #eee; }
        h1, h2, h3 { color: #00d9ff; }
        .section { background: #16213e; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .error { color: #ff6b6b; }
        .success { color: #51cf66; }
        .warning { color: #ffd43b; }
        pre { background: #0f0f23; padding: 10px; overflow-x: auto; border-radius: 4px; }
        .key { color: #ffd43b; }
        .highlight { background: #2d4a22; padding: 2px 5px; }
    </style>
</head>
<body>
    <h1>Debug Booking: <?= htmlspecialchars($code) ?></h1>

    <?php if (!$details): ?>
        <p class="error">Could not fetch booking details for <?= htmlspecialchars($code) ?></p>
    <?php else: ?>

        <div class="section">
            <h2>Basic Info</h2>
            <p><strong>Customer:</strong> <?= htmlspecialchars(($details['customer']['firstName'] ?? '') . ' ' . ($details['customer']['lastName'] ?? '')) ?></p>
            <p><strong>Total Participants:</strong> <?= htmlspecialchars($details['totalParticipants'] ?? 'N/A') ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($details['status'] ?? 'N/A') ?></p>
            <p><strong>Confirmation Code:</strong> <?= htmlspecialchars($details['confirmationCode'] ?? 'N/A') ?></p>
            <?php if (isset($details['vendor'])): ?>
                <p><strong>Vendor:</strong> <?= htmlspecialchars($details['vendor']['title'] ?? $details['vendor']['name'] ?? 'N/A') ?></p>
            <?php endif; ?>
            <?php if (isset($details['seller'])): ?>
                <p><strong>Seller:</strong> <?= htmlspecialchars(json_encode($details['seller'])) ?></p>
            <?php endif; ?>
            <?php if (isset($details['resellerReference'])): ?>
                <p><strong>Reseller Reference:</strong> <?= htmlspecialchars($details['resellerReference']) ?></p>
            <?php endif; ?>
            <?php if (isset($details['externalBookingReference'])): ?>
                <p><strong>External Booking Reference:</strong> <?= htmlspecialchars($details['externalBookingReference']) ?></p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Top-Level Keys</h2>
            <pre><?= implode(', ', array_keys($details)) ?></pre>
        </div>

        <?php if (isset($details['activityBookings'])): ?>
        <div class="section">
            <h2>Activity Bookings (<?= count($details['activityBookings']) ?> found)</h2>
            <?php foreach ($details['activityBookings'] as $idx => $activity): ?>
                <h3>Activity [<?= $idx ?>]</h3>
                <p><strong>Keys:</strong> <?= implode(', ', array_keys($activity)) ?></p>

                <?php if (isset($activity['passengers'])): ?>
                    <p class="success">Has 'passengers' array with <?= count($activity['passengers']) ?> entries:</p>
                    <pre><?= htmlspecialchars(json_encode($activity['passengers'], JSON_PRETTY_PRINT)) ?></pre>
                <?php endif; ?>

                <?php if (isset($activity['pricingCategoryBookings'])): ?>
                    <p class="warning">Has 'pricingCategoryBookings' with <?= count($activity['pricingCategoryBookings']) ?> entries:</p>
                    <?php foreach ($activity['pricingCategoryBookings'] as $pcbIdx => $pcb): ?>
                        <div style="margin-left: 20px; border-left: 2px solid #444; padding-left: 10px;">
                            <p><strong>PCB [<?= $pcbIdx ?>] Keys:</strong> <?= implode(', ', array_keys($pcb)) ?></p>
                            <?php if (isset($pcb['passengerInfo'])): ?>
                                <p class="success">passengerInfo:</p>
                                <pre><?= htmlspecialchars(json_encode($pcb['passengerInfo'], JSON_PRETTY_PRINT)) ?></pre>
                            <?php else: ?>
                                <p class="error">NO passengerInfo field</p>
                            <?php endif; ?>
                            <?php if (isset($pcb['pricingCategory'])): ?>
                                <p>Category: <?= htmlspecialchars($pcb['pricingCategory']['title'] ?? 'N/A') ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php
                $otherFields = ['guests', 'travelers', 'travellers', 'participants', 'attendees'];
                foreach ($otherFields as $field):
                    if (isset($activity[$field])):
                ?>
                    <p class="success">Has '<?= $field ?>' with <?= count($activity[$field]) ?> entries:</p>
                    <pre><?= htmlspecialchars(json_encode($activity[$field], JSON_PRETTY_PRINT)) ?></pre>
                <?php
                    endif;
                endforeach;
                ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (isset($details['questionAnswers']) && !empty($details['questionAnswers'])): ?>
        <div class="section">
            <h2 class="highlight">Question Answers (May contain participant names!)</h2>
            <pre><?= htmlspecialchars(json_encode($details['questionAnswers'], JSON_PRETTY_PRINT)) ?></pre>
        </div>
        <?php endif; ?>

        <?php if (isset($details['answers']) && !empty($details['answers'])): ?>
        <div class="section">
            <h2 class="highlight">Answers (May contain participant names!)</h2>
            <pre><?= htmlspecialchars(json_encode($details['answers'], JSON_PRETTY_PRINT)) ?></pre>
        </div>
        <?php endif; ?>

        <?php if (isset($details['fields'])): ?>
        <div class="section">
            <h2>Fields</h2>
            <p><strong>Keys:</strong> <?= implode(', ', array_keys($details['fields'])) ?></p>
            <pre><?= htmlspecialchars(json_encode($details['fields'], JSON_PRETTY_PRINT)) ?></pre>
        </div>
        <?php endif; ?>

        <?php
        $topLevelPassengerFields = ['passengers', 'guests', 'travelers', 'travellers', 'participants'];
        foreach ($topLevelPassengerFields as $field):
            if (isset($details[$field]) && is_array($details[$field])):
        ?>
        <div class="section">
            <h2 class="highlight">Top-Level <?= ucfirst($field) ?> (<?= count($details[$field]) ?> found)</h2>
            <pre><?= htmlspecialchars(json_encode($details[$field], JSON_PRETTY_PRINT)) ?></pre>
        </div>
        <?php
            endif;
        endforeach;
        ?>

        <div class="section">
            <h2>Extracted Participants (Current Logic)</h2>
            <?php
            $participants = \App\Services\BokunService::extractParticipants($details);
            if (empty($participants)):
            ?>
                <p class="error">No participants extracted with current logic!</p>
            <?php else: ?>
                <p class="success">Found <?= count($participants) ?> participants:</p>
                <ul>
                <?php foreach ($participants as $p): ?>
                    <li><?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['type']) ?>)</li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Full JSON Response</h2>
            <pre><?= htmlspecialchars(json_encode($details, JSON_PRETTY_PRINT)) ?></pre>
        </div>

    <?php endif; ?>

    <p style="margin-top: 30px; color: #888;">
        <strong>IMPORTANT:</strong> Delete this file (debug-booking.php) after you're done debugging!
    </p>
</body>
</html>
