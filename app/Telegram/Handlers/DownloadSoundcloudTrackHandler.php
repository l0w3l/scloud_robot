<?php

declare(strict_types=1);

namespace App\Telegram\Handlers;

use App\Data\Soundcloud\EventData;
use App\Models\SoundcloudTrack;
use App\Services\YtDlp\YtDlpServiceFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Lowel\Telepath\Core\Router\Handler\AbstractTelegramHandler;
use Lowel\Telepath\Facades\SpiritBox;
use Phptg\BotApi\Type\Message;

class DownloadSoundcloudTrackHandler extends AbstractTelegramHandler
{
	public function handler(): callable
	{
		return static function (YtDlpServiceFactory $ytDlpServiceFactory, Message $message): void {
			$soundcloudService = $ytDlpServiceFactory->soundcloud();

			$rawText = $message->text;

			// short link
			$shortUrl = Str::of($rawText)->match('/https:\/\/on\.soundcloud\.com\/[A-Za-z0-9_-]+/')->value();

			if (! empty($shortUrl)) {
				if ($trackInfo = SoundcloudTrack::whereShortUrl($shortUrl)->first()) {
					$trackInfo->send();

					return;
				} else {
					$response = Http::withOptions([
						'allow_redirects' => false,
					])->get($shortUrl);

					$directLink = $response->header('Location');
					$trackUrl = Str::of($directLink)->match('/https:\/\/soundcloud\.com\/[A-Za-z0-9_-]+\/[A-Za-z0-9_-]+/')->value();

					if ($trackInfo = SoundcloudTrack::wherePageUrl($trackUrl)->first()) {
						$trackInfo->update(['short_url' => $shortUrl]);

						$trackInfo->send();

						return;
					}
				}
			} else {
				$trackUrl = Str::of($rawText)->match('/https:\/\/soundcloud\.com\/[A-Za-z0-9_-]+\/[A-Za-z0-9_-]+/')->value();

				if ($trackInfo = SoundcloudTrack::wherePageUrl($trackUrl)->first()) {
					$trackInfo->send();

					return;
				}
			}

			if (empty($trackUrl)) {
				return;
			}

			$message = SpiritBox::replyMessage('Metadata');

			$metadata = $soundcloudService->getInfo($trackUrl);
			$metadata->page_url = $trackUrl;
			$metadata->short_url = $shortUrl;

			foreach ($metadata->formats as $format) {
				if ($format->filesize_approx / 1024 / 1024 > 45) {
					SpiritBox::deleteMessage($message->chat->id, $message->messageId);

					SpiritBox::replyMessage('File too heavy and cannot be downloaded');
				}
			}

			if ($trackInfo = SoundcloudTrack::whereSoundcloudId($metadata->soundcloud_id)->first()) {
				$trackInfo->send();

				return;
			}

			DB::transaction(function () use ($metadata, $soundcloudService, $trackUrl, $message): void {
				SpiritBox::editMessageText('Preparations', chatId: $message->chat->id, messageId: $message->messageId);

				$filePath = $soundcloudService->download($trackUrl, function (EventData $event) {});

				SoundcloudTrack::createFrom($filePath, $metadata)
					->send();

				SpiritBox::deleteMessage(chatId: $message->chat->id, messageId: $message->messageId);
			});
		};
	}
}
