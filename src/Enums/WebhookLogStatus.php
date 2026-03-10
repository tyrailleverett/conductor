<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Enums;

enum WebhookLogStatus: string
{
    case Received = 'received';
    case Verified = 'verified';
    case Processed = 'processed';
    case Failed = 'failed';
}
