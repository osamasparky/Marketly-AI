<?php

namespace App\Domains\Shared\Enums;

enum ContentType: string
{
    case POST = 'post';
    case CAROUSEL = 'carousel';
    case STORY = 'story';
    case REEL = 'reel';
    case VIDEO = 'video';
    case THREAD = 'thread';
}
