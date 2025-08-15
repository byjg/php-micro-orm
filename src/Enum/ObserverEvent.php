<?php

namespace ByJG\MicroOrm\Enum;

enum ObserverEvent: string
{
    case Insert = 'insert';
    case Update = 'update';
    case Delete = 'delete';
} 