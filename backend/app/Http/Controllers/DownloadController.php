<?php

namespace App\Http\Controllers;

use App\Services\DownloadTokenService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    protected DownloadTokenService $tokenService;

    public function __construct(DownloadTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Handle public download request via short token
     *
     * Route: GET /t/{token}
     */
    public function download(string $token): Response|StreamedResponse
    {
        // Find the token
        $downloadToken = $this->tokenService->findByToken($token);

        // Token not found
        if (!$downloadToken) {
            Log::warning('Download attempted with invalid token', ['token' => $token]);
            return $this->errorResponse(
                'Ticket not found',
                'This download link is invalid or has been removed.',
                404
            );
        }

        // Token expired
        if ($downloadToken->isExpired()) {
            Log::warning('Download attempted with expired token', [
                'token' => $token,
                'expired_at' => $downloadToken->expires_at->toDateTimeString(),
            ]);
            return $this->errorResponse(
                'Link Expired',
                'This download link has expired. Please contact Florence with Locals for a new ticket link.',
                410
            );
        }

        // Get file from storage (S3 or local)
        $fileContent = $this->tokenService->getFileFromStorage($downloadToken);

        if (!$fileContent) {
            Log::error('File not found in storage for valid token', [
                'token' => $token,
                's3_path' => $downloadToken->s3_path,
            ]);
            return $this->errorResponse(
                'File Not Found',
                'The ticket file could not be found. Please contact Florence with Locals for assistance.',
                404
            );
        }

        // Record the download
        $downloadToken->recordDownload();

        Log::info('File downloaded successfully', [
            'token' => $token,
            'filename' => $downloadToken->filename,
            'download_count' => $downloadToken->download_count,
        ]);

        // Return the file
        return response($fileContent, 200, [
            'Content-Type' => $downloadToken->mime_type,
            'Content-Disposition' => 'inline; filename="' . $downloadToken->filename . '"',
            'Content-Length' => strlen($fileContent),
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Generate a friendly error response
     */
    protected function errorResponse(string $title, string $message, int $status): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} - Florence with Locals</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            margin-bottom: 16px;
            font-size: 24px;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .contact {
            background: #f5f5f5;
            padding: 16px;
            border-radius: 8px;
            font-size: 14px;
        }
        .contact a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">😕</div>
        <h1>{$title}</h1>
        <p>{$message}</p>
        <div class="contact">
            Need help? Contact us at<br>
            <a href="mailto:info@florencewithlocals.com">info@florencewithlocals.com</a>
        </div>
    </div>
</body>
</html>
HTML;

        return response($html, $status, ['Content-Type' => 'text/html']);
    }
}
