<?php

namespace SixGates\Enums;

enum DenialReason: string
{
    case INSUFFICIENT_FUNDS = 'insufficient_funds';
    case DISAGREE_WITH_ANALYSIS = 'disagree_analysis';
    case ALREADY_ENOUGH_EXPOSURE = 'enough_exposure';
    case PREFER_TO_WAIT = 'wait_better_price';
    case EXTERNAL_FACTORS = 'external_factors';
    case PERSONAL_PREFERENCE = 'personal_preference';
    case OTHER = 'other';
}
