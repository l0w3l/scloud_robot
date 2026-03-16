<?php

declare(strict_types=1);

namespace App\Telegram\Keyboards\Inline\Youtube\ChoseFormat;

use App\Data\YtDlp\Youtube\YoutubeVideoData;
use App\Models\YoutubeVideo;
use App\Telegram\Keyboards\Inline\Youtube\ChoseFormat\Buttons\UploadChosenFormatButton;
use Lowel\Telepath\Core\Router\Keyboard\InlineKeyboardBuilder;
use Lowel\Telepath\Core\Router\Keyboard\KeyboardBuilderInterface;
use Lowel\Telepath\Core\Router\Keyboard\KeyboardFactoryInterface;

class ChoseFormatInlineKeyboardFactory implements KeyboardFactoryInterface
{
    public function fromVideo(YoutubeVideoData|YoutubeVideo $video): InlineKeyboardBuilder
    {
        $builder = new InlineKeyboardBuilder;

        $buttons = [];
        foreach ($video->formats as $format) {
            $buttons[] = new UploadChosenFormatButton($video, $format);
        }

        return $builder->column(...$buttons);
    }

    public function make(): KeyboardBuilderInterface
    {
        $builder = new InlineKeyboardBuilder;

        return $builder->row(new UploadChosenFormatButton);
    }
}
