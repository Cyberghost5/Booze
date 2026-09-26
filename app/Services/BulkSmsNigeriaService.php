<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BulkSmsNigeriaService
{
    protected string $apiToken;
    protected string $senderId;

    public function __construct()
    {
        $this->apiToken = (string) config('services.bulksms_nigeria.api_token', env('BULKSMS_NIGERIA_API_TOKEN', ''));
        $this->senderId = (string) config('services.bulksms_nigeria.sender_id', env('BULKSMS_NIGERIA_SENDER_ID', 'BoozeApp'));
    }

    /**
     * Send SMS via BulkSMS Nigeria API v2.
     *
     * @param string $to Recipient phone number
     * @param string $message SMS message body
     * @return bool
     */
    public function sendSms(string $to, string $message): bool
    {
        $formattedPhone = PhoneNumberService::formatForWhatsApp($to);

        if (empty($formattedPhone)) {
            Log::warning("BulkSMS Nigeria: Skipped due to empty phone number.");
            return false;
        }

        // If no real API token is configured, log the SMS output cleanly for local development & testing
        if (empty($this->apiToken) || $this->apiToken === 'your_bulksms_nigeria_api_token_here' || $this->apiToken === 'demo_api_token') {
            Log::info("BulkSMS Nigeria [LOCAL LOG / SIMULATION]:", [
                'to' => $formattedPhone,
                'from' => $this->senderId,
                'message' => $message,
            ]);
            return true;
        }

        try {
            $response = Http::post('https://www.bulksmsnigeria.com/api/v2/sms/create', [
                'api_token' => $this->apiToken,
                'to' => $formattedPhone,
                'from' => substr($this->senderId, 0, 11), // Sender ID max 11 chars
                'body' => $message,
                'dnd' => 2, // Direct bypass DND
            ]);

            if ($response->successful()) {
                Log::info("BulkSMS Nigeria: SMS successfully dispatched to {$formattedPhone}.");
                return true;
            }

            Log::error("BulkSMS Nigeria API error [{$response->status()}]: " . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error("BulkSMS Nigeria exception: " . $e->getMessage());
            return false;
        }
    }
}
