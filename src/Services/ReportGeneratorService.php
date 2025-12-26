<?php

namespace SixGates\Services;

use SixGates\DataProviders\AnthropicProvider;
use SixGates\Repositories\ReportRepository;
use SixGates\Scoring\AnalysisResult;
use SixGates\Entities\AnalysisReport;
use Exception;

class ReportGeneratorService
{
    public function __construct(
        private AnthropicProvider $llmProvider,
        private ReportRepository $reportRepository
    ) {
    }

    public function generateAndSave(AnalysisResult $result): AnalysisReport
    {
        // 1. Fetch Previous Report
        $previousReport = $this->reportRepository->findLatestByTicker($result->ticker);

        // 2. Construct Prompt
        $systemPrompt = "You are a senior financial analyst. Your job is to write a concise, human-readable report based on the provided algorithmic analysis of a stock. Focus on the 'Why'. Explain the key drivers of the pass/fail decision. If a previous report is provided, highlight what has changed.";

        $prompt = $this->buildPrompt($result, $previousReport);

        // 3. Call LLM
        try {
            $content = $this->llmProvider->generate(
                $systemPrompt,
                $prompt,
                2000 // Max tokens
            );
        } catch (Exception $e) {
            $content = "Error generating report: " . $e->getMessage();
        }

        // 4. Create Entity
        $report = new AnalysisReport(
            id: null,
            ticker: $result->ticker,
            analysisDate: date('Y-m-d'),
            reportContent: $content
        );

        // 5. Save
        $this->reportRepository->save($report);

        return $report;
    }

    private function buildPrompt(AnalysisResult $result, ?AnalysisReport $previousReport): string
    {
        $prompt = "Analyze the following stock data for {$result->ticker}.\n\n";

        // Summary
        $prompt .= "## Current Status\n";
        $prompt .= "Quality Tier: " . ($result->qualityTier ?? 'N/A') . "\n";
        $prompt .= "Recommendation: " . ($result->positionSize > 0 ? "BUY / HOLD (Size: " . ($result->positionSize * 100) . "%)" : "AVOID / SELL") . "\n";
        $prompt .= "Quality Check: " . ($result->passedQuality ? "Passed" : "Failed") . "\n\n";

        // Gates Review
        $prompt .= "## Gate Results\n";
        foreach ($result->gateResults as $gate => $gr) {
            $status = $gr->passed ? "Passed" : "Failed";
            $prompt .= "- {$gate}: {$status}\n";
            // Add key metrics causing fail/pass if available
            if (!$gr->passed && $gr->killReason) {
                $prompt .= "  Reason: {$gr->killReason}\n";
            }
            // Dump metrics
            foreach ($gr->metrics as $k => $v) {
                if (is_array($v) || is_object($v)) {
                    $v = json_encode($v);
                } elseif (is_numeric($v)) {
                    $v = number_format($v, 4);
                }
                $prompt .= "  - {$k}: {$v}\n";
            }
        }

        // Context
        if (!empty($result->context)) {
            $prompt .= "\n## Market Context\n";
            $prompt .= "Phase: " . ($result->context['phase'] ?? 'Unknown') . "\n";
            $prompt .= "Risk Score: " . ($result->context['risk'] ?? 'N/A') . "\n";
        }

        // Previous Report for Comparison
        if ($previousReport) {
            $prompt .= "\n## Previous Report ({$previousReport->analysisDate})\n";
            $prompt .= "Use this context to highlight changes (improvements or deterioration) in your narrative:\n";
            $prompt .= "```\n{$previousReport->reportContent}\n```\n";
        } else {
            $prompt .= "\n## Previous Report\nNone available. This is the first coverage.\n";
        }

        $prompt .= "\n## Instructions\n";
        $prompt .= "Write a professional summary (approx 2-3 paragraphs). \n";
        $prompt .= "1. Start with the recommendation and the primary reason.\n";
        $prompt .= "2. Discuss the most critical strengths (passed gates) and weaknesses (failed gates).\n";
        $prompt .= "3. If previous report exists, explicitly mention if the outlook has improved, worsened, or stayed the same.\n";
        $prompt .= "4. Do not just list numbers; interpret them.\n";
        $prompt .= "5. Include a section titled 'Conditions for Revision'. List specific market conditions or company-specific events (e.g., related to {$result->ticker}'s sector or financials) that would warrant a re-evaluation or revision of this report.\n";

        return $prompt;
    }
}
