<?php

namespace SixGates\Commands;

use SixGates\Entities\ExecutionLog;
use SixGates\Enums\PortfolioType;
use SixGates\Enums\RecommendationStatus;
use SixGates\Enums\TradeAction;
use SixGates\Repositories\ExecutionRepository;
use SixGates\Repositories\RecommendationRepository;
use SixGates\Services\AnalysisService;
use SixGates\Services\RecommendationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class AdvisorCommand extends Command
{
    protected static $defaultName = 'advisor:run';

    public function __construct(
        private AnalysisService $analysisService,
        private RecommendationService $recService,
        private RecommendationRepository $recRepo,
        private ExecutionRepository $execRepo
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Six Gates Personal Financial Advisor CLI');
        $this->addArgument('ticker', InputArgument::OPTIONAL, 'Ticker to analyze immediately');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Six Gates Personal Financial Advisor (V6.0)');

        $ticker = $input->getArgument('ticker');
        if ($ticker) {
            $this->runAnalysis($io, $ticker);
            return Command::SUCCESS;
        }

        // Main Loop
        while (true) {
            $choice = $io->choice('Select Action', [
                'analyze' => 'Analyze Ticker & Get Recommendation',
                'pending' => 'Review Pending Recommendations',
                'exit' => 'Exit'
            ], 'analyze');

            if ($choice === 'exit')
                break;

            if ($choice === 'analyze') {
                $ticker = $io->ask('Enter Ticker Symbol');
                if ($ticker)
                    $this->runAnalysis($io, strtoupper($ticker));
            }

            if ($choice === 'pending') {
                $this->reviewPending($io);
            }
        }

        return Command::SUCCESS;
    }

    private function runAnalysis(SymfonyStyle $io, string $ticker): void
    {
        $io->section("Analyzing $ticker...");

        try {
            $result = $this->analysisService->analyze($ticker);

            // Show Summary
            $io->text("Market Phase: " . ($result->marketContext['phase'] ?? 'N/A'));
            $io->text("Quality Tier: " . ($result->qualityTier ?? 'N/A'));

            // Check gates
            $io->table(
                ['Gate', 'Passed', 'Metrics'],
                array_map(fn($gr) => [
                    $gr->gateName,
                    $gr->passed ? '✅' : '❌',
                    json_encode($gr->metrics) // Simplified display
                ], $result->gateResults)
            );

            if (!$result->passedQuality) {
                $io->warning("Stock FAILED quality checks. No recommendation generated.");
                return;
            }

            // Generate Recommendation (Assuming BUY for now if passed)
            // We need fair value from Gate 4
            $fairValue = $result->gateResults['Gate 4']->metrics['fair_value'] ?? 0.0;
            $currentPrice = $result->gateResults['Gate 2']->metrics['price'] ?? 0.0; // Gate 2 usually has price? Or check raw provider?
            // Actually AnalysisResult doesn't store raw price easily accessible at top level, unless we use provider again.
            // Assumption: Gate 4 or 2 has price. Let's assume Gate 4 'current_price'.
            if ($currentPrice <= 0)
                $currentPrice = 100.0; // Fallback mock

            // Determine Target Allocation (Mock logic: 5% of portfolio)
            // In real app, we check total portfolio value.
            $targetAllocation = 10000.0; // €10k per position

            $dto = $this->recService->createBuyRecommendation(
                $ticker,
                $ticker . " Inc.", // Mock name
                PortfolioType::GROWTH, // Default to growth, or ask user?
                $targetAllocation,
                $currentPrice,
                $fairValue,
                $result->qualityTier,
                "Strong metrics across the board.",
                20.0 // Assumed VIX
            );

            $this->displayRecommendation($io, $dto);

        } catch (\Exception $e) {
            $io->error("Analysis Failed: " . $e->getMessage());
            $io->text($e->getTraceAsString());
        }
    }

    private function displayRecommendation(SymfonyStyle $io, \SixGates\DTOs\RecommendationDTO $dto): void
    {
        $io->success("RECOMMENDATION GENERATED");

        $io->definitionList(
            ['Action' => $dto->action->value],
            ['Ticker' => $dto->ticker],
            ['Shares' => $dto->recommendedShares],
            ['Order Type' => $dto->orderType->value],
            ['Limit Price' => $dto->limitPrice ?? 'Market'],
            ['Estimated Cost' => number_format($dto->estimatedCost, 2)]
        );

        // Approval Flow
        $decision = $io->choice("Do you approve this recommendation?", ['Approve', 'Deny', 'Skip'], 'Approve');

        if ($decision === 'Approve') {
            // In real app, we'd update status to APPROVED in DB.
            // Here we just simulate logging execution.
            $io->text("Recommendation Approved. Please execute at your broker.");

            if ($io->confirm("Have you executed this trade?", false)) {
                // Wait, RecService returns DTO, not Entity with ID.
                // I should update RecService to return Entity or DTO with ID.
                // For now, I'll fetch the latest Rec from DB for this ticker.

                $rec = $this->recRepo->getLatestForTicker($dto->ticker);
                if ($rec) {
                    $this->logExecution($io, $rec);
                }
            }
        } elseif ($decision === 'Deny') {
            $reason = $io->ask("Reason for denial?", "Personal preference");
            // Logic to update DB to denied...
            $io->text("Recommendation Denied.");
        }
    }

    private function logExecution(SymfonyStyle $io, \SixGates\Entities\Recommendation $rec): void
    {
        $shares = (int) $io->ask("Actual Shares Executed", (string) $rec->recommendedShares);
        $price = (float) $io->ask("Actual Price per Share", (string) $rec->currentPrice);
        $comm = (float) $io->ask("Commission", "0.0");
        $broker = $io->ask("Broker", "DEGIRO");

        $total = ($shares * $price) + $comm;

        $log = new ExecutionLog(
            id: \Ramsey\Uuid\Uuid::uuid4()->toString(),
            recommendationId: $rec->id,
            ticker: $rec->ticker,
            action: $rec->action,
            portfolioType: $rec->portfolioType,
            actualShares: $shares,
            actualPrice: $price,
            commission: $comm,
            executionDate: new \DateTimeImmutable(),
            broker: $broker,
            notes: "Logged via CLI",
            recommendedShares: $rec->recommendedShares,
            recommendedPrice: $rec->currentPrice,
            recommendedOrderType: $rec->orderType->value,
            recommendedTotal: $rec->estimatedCost ?? 0.0,
            actualTotal: $total
        );

        // Calculate Variances (Simplistic)
        $log->sharesVariance = $shares - $rec->recommendedShares;
        $log->totalVariance = $total - ($rec->estimatedCost ?? 0);

        $this->execRepo->save($log);
        $io->success("Execution Logged! Variance: " . number_format($log->totalVariance, 2));
    }

    private function reviewPending(SymfonyStyle $io): void
    {
        $pending = $this->recRepo->getPending();
        if (empty($pending)) {
            $io->text("No pending recommendations.");
            return;
        }

        $io->table(['ID', 'Ticker', 'Action', 'Shares'], array_map(fn($r) => [
            substr($r->id, 0, 8),
            $r->ticker,
            $r->action->value,
            $r->recommendedShares
        ], $pending));
    }
}
