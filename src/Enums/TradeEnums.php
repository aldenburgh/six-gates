<?php

namespace SixGates\Enums;

enum TradeAction: string
{
    case BUY = 'buy';
    case SELL = 'sell';
}

enum OrderType: string
{
    case MARKET = 'market';
    case LIMIT = 'limit';
}

enum PortfolioType: string
{
    case GROWTH = 'growth';
    case DIVIDEND = 'dividend';
}

enum RecommendationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case DENIED = 'denied';
    case EXPIRED = 'expired';
    case EXECUTED = 'executed';
}

enum Urgency: string
{
    case CRITICAL = 'critical';
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';
    case INFO = 'info';
}

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
