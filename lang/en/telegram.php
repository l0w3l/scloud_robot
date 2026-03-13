<?php

declare(strict_types=1);

return [
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
