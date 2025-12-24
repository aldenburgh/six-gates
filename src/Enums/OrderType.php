<?php

namespace SixGates\Enums;

enum OrderType: string
{
    case MARKET = 'market';
    case LIMIT = 'limit';
}
