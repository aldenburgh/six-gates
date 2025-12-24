<?php

namespace SixGates\Enums;

enum RecommendationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case DENIED = 'denied';
    case EXPIRED = 'expired';
    case EXECUTED = 'executed';
}
