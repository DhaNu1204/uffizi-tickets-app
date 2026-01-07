<?php

namespace App\Console\Commands;

use App\Services\BokunService;
use Illuminate\Console\Command;

class DebugBookingStructure extends Command
{
    protected $signature = 'booking:debug {codes* : Booking confirmation codes to debug}';
    protected $description = 'Debug the Bokun API response structure for specific bookings';

    public function handle(BokunService $bokunService)
    {
        $codes = $this->argument('codes');

        foreach ($codes as $code) {
            $this->info("\n" . str_repeat('=', 80));
            $this->info("DEBUGGING BOOKING: {$code}");
            $this->info(str_repeat('=', 80));

            $details = $bokunService->getBookingDetails($code);

            if (!$details) {
                $this->error("Could not fetch booking details for {$code}");
                continue;
            }

            // Basic info
            $this->info("\n--- BASIC INFO ---");
            $this->line("Customer: " . ($details['customer']['firstName'] ?? '') . ' ' . ($details['customer']['lastName'] ?? ''));
            $this->line("Total Participants: " . ($details['totalParticipants'] ?? 'N/A'));
            $this->line("Status: " . ($details['status'] ?? 'N/A'));
            $this->line("Vendor: " . ($details['vendor']['title'] ?? $details['vendor']['name'] ?? 'N/A'));

            // Check for reseller/channel info
            if (isset($details['channel'])) {
                $this->line("Channel: " . json_encode($details['channel']));
            }
            if (isset($details['reseller'])) {
                $this->line("Reseller: " . json_encode($details['reseller']));
            }
            if (isset($details['affiliateCode'])) {
                $this->line("Affiliate Code: " . $details['affiliateCode']);
            }

            // Top-level keys
            $this->info("\n--- TOP-LEVEL KEYS ---");
            $this->line(implode(', ', array_keys($details)));

            // Check activityBookings
            if (isset($details['activityBookings'])) {
                $this->info("\n--- ACTIVITY BOOKINGS ---");
                $this->line("Count: " . count($details['activityBookings']));

                foreach ($details['activityBookings'] as $idx => $activity) {
                    $this->warn("\n  Activity [{$idx}] Keys: " . implode(', ', array_keys($activity)));

                    // Check for passengers array
                    if (isset($activity['passengers'])) {
                        $this->info("  Activity [{$idx}] has 'passengers' array with " . count($activity['passengers']) . " entries:");
                        foreach ($activity['passengers'] as $pIdx => $passenger) {
                            $this->line("    Passenger [{$pIdx}]: " . json_encode($passenger));
                        }
                    }

                    // Check for pricingCategoryBookings
                    if (isset($activity['pricingCategoryBookings'])) {
                        $this->info("  Activity [{$idx}] has 'pricingCategoryBookings' with " . count($activity['pricingCategoryBookings']) . " entries:");
                        foreach ($activity['pricingCategoryBookings'] as $pcbIdx => $pcb) {
                            $this->line("    PCB [{$pcbIdx}] Keys: " . implode(', ', array_keys($pcb)));
                            if (isset($pcb['passengerInfo'])) {
                                $this->line("    PCB [{$pcbIdx}] passengerInfo: " . json_encode($pcb['passengerInfo']));
                            } else {
                                $this->line("    PCB [{$pcbIdx}] NO passengerInfo field");
                            }
                            if (isset($pcb['pricingCategory'])) {
                                $this->line("    PCB [{$pcbIdx}] pricingCategory: " . ($pcb['pricingCategory']['title'] ?? 'N/A'));
                            }
                        }
                    }

                    // Check for other passenger-related fields
                    $passengerFields = ['guests', 'travelers', 'travellers', 'participants', 'attendees'];
                    foreach ($passengerFields as $field) {
                        if (isset($activity[$field])) {
                            $this->info("  Activity [{$idx}] has '{$field}' with " . count($activity[$field]) . " entries:");
                            foreach ($activity[$field] as $pIdx => $p) {
                                $this->line("    {$field}[{$pIdx}]: " . json_encode($p));
                            }
                        }
                    }
                }
            }

            // Check productBookings
            if (isset($details['productBookings'])) {
                $this->info("\n--- PRODUCT BOOKINGS ---");
                $this->line("Count: " . count($details['productBookings']));

                foreach ($details['productBookings'] as $idx => $pb) {
                    $this->warn("\n  ProductBooking [{$idx}] Keys: " . implode(', ', array_keys($pb)));

                    if (isset($pb['passengers'])) {
                        $this->info("  ProductBooking [{$idx}] has 'passengers' array with " . count($pb['passengers']) . " entries:");
                        foreach ($pb['passengers'] as $pIdx => $passenger) {
                            $this->line("    Passenger [{$pIdx}]: " . json_encode($passenger));
                        }
                    }

                    if (isset($pb['fields'])) {
                        $this->line("  ProductBooking [{$idx}] fields keys: " . implode(', ', array_keys($pb['fields'])));

                        if (isset($pb['fields']['passengers'])) {
                            $this->info("  ProductBooking [{$idx}] fields.passengers:");
                            foreach ($pb['fields']['passengers'] as $pIdx => $passenger) {
                                $this->line("    Passenger [{$pIdx}]: " . json_encode($passenger));
                            }
                        }
                    }
                }
            }

            // Check top-level passengers/guests
            $topLevelFields = ['passengers', 'guests', 'travelers', 'travellers', 'participants'];
            foreach ($topLevelFields as $field) {
                if (isset($details[$field]) && is_array($details[$field])) {
                    $this->info("\n--- TOP-LEVEL {$field} ---");
                    $this->line("Count: " . count($details[$field]));
                    foreach ($details[$field] as $idx => $p) {
                        $this->line("  [{$idx}]: " . json_encode($p));
                    }
                }
            }

            // Check questions/answers (sometimes participant info is here)
            if (isset($details['questionAnswers'])) {
                $this->info("\n--- QUESTION ANSWERS ---");
                foreach ($details['questionAnswers'] as $qa) {
                    $this->line("  Q: " . ($qa['question'] ?? 'N/A'));
                    $this->line("  A: " . ($qa['answer'] ?? 'N/A'));
                }
            }

            // Check fields at top level
            if (isset($details['fields'])) {
                $this->info("\n--- TOP-LEVEL FIELDS ---");
                $this->line("Keys: " . implode(', ', array_keys($details['fields'])));

                foreach (['passengers', 'guests', 'participants', 'priceCategoryBookings'] as $field) {
                    if (isset($details['fields'][$field])) {
                        $this->info("fields.{$field}:");
                        $this->line(json_encode($details['fields'][$field], JSON_PRETTY_PRINT));
                    }
                }
            }

            // Try to extract participants with current logic
            $this->info("\n--- EXTRACTED PARTICIPANTS (Current Logic) ---");
            $participants = BokunService::extractParticipants($details);
            if (empty($participants)) {
                $this->error("No participants extracted!");
            } else {
                foreach ($participants as $idx => $p) {
                    $this->line("  [{$idx}] {$p['name']} ({$p['type']})");
                }
            }

            // Output full JSON for manual inspection
            $this->info("\n--- FULL JSON (first 5000 chars) ---");
            $json = json_encode($details, JSON_PRETTY_PRINT);
            $this->line(substr($json, 0, 5000));
            if (strlen($json) > 5000) {
                $this->warn("... truncated (full length: " . strlen($json) . " chars)");
            }
        }

        return 0;
    }
}
