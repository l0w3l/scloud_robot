<?php

namespace App\Http\Controllers;

use App\Services\FFMpeg\FFMpegServiceInterface;
use App\Services\YtDlp\YtDlpServiceFactory;

class StreamController extends Controller
{
    public function stream(FFMpegServiceInterface $fFMpegService, YtDlpServiceFactory $ytDlpServiceFactory)
    {
        $url = request('url');

        if (! $url) {
            abort(400, 'No URL');
        }

        if (request()->isMethod('head')) {
            return response('', 200)->header('Content-Type', 'audio/mpeg');
        }

        $streamUrl = $ytDlpServiceFactory->soundcloud()->streamUrl($url);

        if (! $streamUrl) {
            abort(500, 'Stream not found');
        }

        return response()->stream(static function () use ($fFMpegService, $streamUrl) {
            $fFMpegService->streamMp3($streamUrl);
        }, 200, [
            'Content-Type' => 'audio/mpeg',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
