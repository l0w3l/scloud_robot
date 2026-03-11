<?php

declare(strict_types=1);

namespace App\Services\YtDlp;

use Lowel\LaravelServiceMaker\Services\ServiceInterface;

/**
 * @template TInfo
 */
interface YtDlpServiceInterface extends ServiceInterface
{
    /**
     * @return string - storage file path
     */
    public function download(string $url, ?callable $callback = null, ?string $format = null): string;

    /**
     * @return TInfo
     */
    public function getInfo(string $url);
}
