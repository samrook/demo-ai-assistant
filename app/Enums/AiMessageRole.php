<?php

namespace App\Enums;

enum AiMessageRole: string
{
    case USER = 'user';
    case SYSTEM = 'system';
    case ASSISTANT = 'assistant';
}
