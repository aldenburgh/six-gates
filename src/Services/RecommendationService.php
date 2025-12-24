<?php

namespace SixGates\Services;

use SixGates\Domain\OrderTypeAdvisor;
use SixGates\Domain\ShareCalculator;
use SixGates\DTOs\RecommendationDTO;
use SixGates\Entities\Position;
use SixGates\Entities\Recommendation;
use SixGates\Enums\PortfolioType;
use SixGates\Enums\RecommendationStatus;
use SixGates\Enums\TradeAction;
use SixGates\Enums\Urgency;
use SixGates\Repositories\RecommendationRepository;

class RecommendationService
{
    public function __construct(
        private RecommendationRepository $repo,
        private ShareCalculator $shareCalc,
        private OrderTypeAdvisor $orderAdvisor
    ) {
    }

    public function createBuyRecommendation(
        string $ticker,
        string $companyName,
        PortfolioType $type,
        float $targetAmount, // The total cash amount to allocate
        float $currentPrice,
        float $fairValue,
        ?string $qualityTier,
        ?string $narrative,
        float $vix = 20.0
    ): RecommendationDTO {
        $action = TradeAction::BUY;
        $urgency = Urgency::MEDIUM; // Standard buy

        // 1. Calculate Shares
        $shares = $this->shareCalc->calculateBuyShares($targetAmount, $currentPrice);

        // 2. Advise Order Type
        $orderType = $this->orderAdvisor->advise($urgency);
        $limitPrice = ($orderType === \SixGates\Enums\OrderType::LIMIT)
            ? $this->orderAdvisor->calculateLimitPrice('buy', $currentPrice, $fairValue)
            : null;

        $validity = ($orderType === \SixGates\Enums\OrderType::LIMIT)
            ? $this->orderAdvisor->calculateValidity(new \DateTimeImmutable(), $vix)
            : null;

        // 3. Estimate Values
        $estPrice = $limitPrice ?? $currentPrice;
        $estCost = $shares * $estPrice;

        // 4. Construct DTO
        $dto = new RecommendationDTO(
            action: $action,
            portfolioType: $type,
            ticker: $ticker,
            companyName: $companyName,
            recommendedShares: $shares,
            currentPrice: $currentPrice,
            estimatedCost: $estCost,
            estimatedProceeds: null,
            orderType: $orderType,
            limitPrice: $limitPrice,
            limitValidUntil: $validity,
            orderTypeReason: "Standard buy logic applied based on valuation discount",
            narrativeSummary: "Auto-generated buy recommendation based on Six Gates analysis.",
            fullNarrative: $narrative ?? "No detailed narrative provided.",
            urgency: $urgency,
            qualityTier: $qualityTier,
            fairValue: $fairValue,
            discountPercent: ($fairValue > 0) ? (($fairValue - $currentPrice) / $fairValue) * 100 : 0
        );

        // 5. Persist
        $entity = $this->dtoToEntity($dto);
        $this->repo->save($entity);

        return $dto;
    }

    private function dtoToEntity(RecommendationDTO $dto): Recommendation
    {
        return new Recommendation(
            id: \Ramsey\Uuid\Uuid::uuid4()->toString(),
            createdAt: new \DateTimeImmutable(),
            expiresAt: $dto->limitValidUntil ?? (new \DateTimeImmutable())->modify('+7 days'),
            action: $dto->action,
            portfolioType: $dto->portfolioType,
            ticker: $dto->ticker,
            companyName: $dto->companyName,
            recommendedShares: $dto->recommendedShares,
            currentPrice: $dto->currentPrice,
            estimatedCost: $dto->estimatedCost,
            estimatedProceeds: $dto->estimatedProceeds,
            orderType: $dto->orderType,
            limitPrice: $dto->limitPrice,
            limitValidUntil: $dto->limitValidUntil,
            orderTypeReason: $dto->orderTypeReason,
            narrativeSummary: $dto->narrativeSummary,
            fullNarrative: $dto->fullNarrative,
            urgency: $dto->urgency,
            status: RecommendationStatus::PENDING,
            qualityTier: $dto->qualityTier,
            incomeImpact: $dto->incomeImpact,
            // goalImpactPercent
        );
    }
}
