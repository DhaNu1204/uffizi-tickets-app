<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\VoxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * VOX/PopGuide Audio Guide Controller
 *
 * Handles API endpoints for creating and managing VOX audio guide accounts.
 */
class VoxController extends Controller
{
    protected VoxService $voxService;

    public function __construct(VoxService $voxService)
    {
        $this->voxService = $voxService;
    }

    /**
     * Create VOX account for a booking
     * POST /api/bookings/{id}/create-vox-account
     */
    public function createAccount(int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        // Validate booking has audio guide
        if (!$booking->hasAudioGuide()) {
            return response()->json([
                'success' => false,
                'error' => 'This booking does not include an audio guide',
            ], 422);
        }

        // Check if VOX account already exists
        if ($booking->hasVoxAccount()) {
            return response()->json([
                'success' => true,
                'message' => 'VOX account already exists',
                'dynamic_link' => $booking->vox_dynamic_link,
                'account_id' => $booking->vox_account_id,
                'username' => $booking->audio_guide_username,
                'password' => $booking->audio_guide_password,
                'already_exists' => true,
            ]);
        }

        // Create VOX account
        $result = $this->voxService->createAccount($booking);

        if ($result['success']) {
            // Refresh booking to get updated values
            $booking->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Audio guide account created successfully',
                'dynamic_link' => $result['dynamic_link'],
                'account_id' => $result['account_id'],
                'username' => $result['username'],
                'password' => $result['password'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Failed to create audio guide account',
        ], 422);
    }

    /**
     * Test VOX API connection
     * GET /api/vox/test
     */
    public function testConnection(): JsonResponse
    {
        $result = $this->voxService->testConnection();

        $statusCode = $result['success'] ? 200 : 503;

        return response()->json($result, $statusCode);
    }

    /**
     * Get VOX status for a booking
     * GET /api/bookings/{id}/vox-status
     */
    public function getStatus(int $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        return response()->json([
            'booking_id' => $booking->id,
            'has_audio_guide' => $booking->hasAudioGuide(),
            'needs_vox_account' => $booking->needsVoxAccount(),
            'has_vox_account' => $booking->hasVoxAccount(),
            'vox_dynamic_link' => $booking->vox_dynamic_link,
            'vox_account_id' => $booking->vox_account_id,
            'vox_created_at' => $booking->vox_created_at?->toIso8601String(),
            'audio_guide_username' => $booking->audio_guide_username,
            'audio_guide_password' => $booking->audio_guide_password,
            'audio_guide_url' => $booking->audio_guide_url,
            'ticket_type' => $booking->getTicketType(),
        ]);
    }

    /**
     * Get VOX account details
     * GET /api/vox/accounts/{accountId}
     */
    public function getAccount(string $accountId): JsonResponse
    {
        $result = $this->voxService->getAccount($accountId);

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json($result, 404);
    }
}
