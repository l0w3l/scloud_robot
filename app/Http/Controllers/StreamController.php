<?php

namespace App\Http\Controllers;

use App\Services\FFMpeg\FFMpegServiceInterface;
use App\Services\YtDlp\YtDlpServiceFactory;
use Illuminate\Support\Facades\Cache;

class StreamController extends Controller
{
    public function stream(string $hash, FFMpegServiceInterface $fFMpegService, YtDlpServiceFactory $ytDlpServiceFactory)
    {
        // Расшифровываем URL из хэша (или берем из БД/кэша)
        $url = base64_decode($hash);

        // 1. Telegram часто проверяет доступность через HEAD
        if (request()->isMethod('head')) {
            return response('', 200)
                ->header('Content-Type', 'audio/mpeg')
                ->header('Accept-Ranges', 'bytes');
        }

        // 2. Получаем прямую ссылку (через кэш, чтобы не дергать yt-dlp лишний раз)
        // Срок жизни ссылки обычно 15-20 минут, кэшируем на 10.
        $streamUrl = Cache::remember('stream_link:'.md5($url), 600, function () use ($ytDlpServiceFactory, $url) {
            return $ytDlpServiceFactory->soundcloud()->streamUrl($url);
        });

        if (! $streamUrl) {
            abort(404);
        }

        try {
            // 3. Создаем фрагмент
            $file = $fFMpegService->getFragmentPath($url, $streamUrl, 10);

            // 4. Отдаем файл
            return response()->file($file, [
                'Content-Type' => 'audio/mpeg',
                'Content-Disposition' => 'inline; filename="track.mp3"',
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        } catch (\Exception $e) {
            \Log::error('FFmpeg Stream Error: '.$e->getMessage());

            return abort(500);
        }
    }
}
