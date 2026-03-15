<?php

declare(strict_types=1);

return [
    'commands' => [
        'start' => <<<'EOT'
Hello! 👋
I'm @scloud_robot — your assistant for finding and downloading tracks.

What I can do:

🔗 Download by link
Send me a track link in private messages and I’ll download it for you.

🔎 Inline search & download
Type in any chat:
@scloud_robot track name or link
and choose the track you want.

Enjoy the music! 🎧
EOT
    ],
    'soundcloud' => [
        'handlers' => [
            'link' => [
                'error_large' => 'File too heavy and cannot be downloaded',
                'metadata' => 'Initialize...',
                'download' => 'Download...',
                'extract' => 'Extracting...',
                'thumnail' => 'Thumnails...',
            ],
        ],
        'inline' => [
            'chosen' => [
                'initial' => 'Initialization...',
                'metadata' => 'Collecting metadata...',
                'thumnail' => 'Thumnails...',
                'error' => 'Download failed',
                'download' => 'Downloading...',
                'extract' => 'Extracting...',
            ],
        ],
    ],
];
