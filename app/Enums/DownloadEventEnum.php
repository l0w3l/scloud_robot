<?php

namespace App\Enums;

enum DownloadEventEnum: string
{
    case EXTRACT = 'extract';
    case INFO = 'info';
    case FORMAT = 'format';
    case DOWNLOAD = 'download';
    case MANIFEST = 'manifest';
    case FRAGMENTS = 'fragments';
    case DESTINATION = 'destination';
    case PROGRESS = 'progress';
    case FIXUP = 'fixup';
    case EXTRACT_AUDIO = 'extract_audio';
    case METADATA = 'metadata';
    case DELETE = 'delete';
    case UNKNOWN = 'unknown';
}
