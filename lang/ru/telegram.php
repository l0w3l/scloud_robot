<?php

declare(strict_types=1);

return [
    'soundcloud' => [
        'handlers' => [
            'link' => [
                'error_large' => 'Результирующий файл слишком большой (>50МБ)',
                'metadata' => 'Подготовка...',
                'download' => 'Загрузка...',
                'extract' => 'Извлечение...',
                'thumnail' => 'Метаданные...',
            ],
        ],
        'inline' => [
            'chosen' => [
                'initial' => 'Подготовка...',
                'metadata' => 'Сбор информации о треке...',
                'thumnail' => 'Метаданные...',
                'error' => 'Ошибка',
                'download' => 'Загрузка...',
                'extract' => 'Извлечение...',
            ],
        ],
    ],
];
