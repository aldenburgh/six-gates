<?php
require __DIR__ . '/../vendor/autoload.php';

use SixGates\DataProviders\MockDataProvider;
use SixGates\Services\Data\MarketDataService;
use SixGates\Services\EarlyWarning\MacroMonitor;
use SixGates\Services\EarlyWarning\RiskAssessment;
use SixGates\Services\Execution\EnhancedCircuitBreaker;

echo "🧪 Testing Enhanced Circuit Breaker...\n";

// 1. Setup Dependencies (Same stack as Early Warning)
$mockProvider = new MockDataProvider();
$marketData = new MarketDataService($mockProvider);
$macroMonitor = new MacroMonitor($marketData);
$riskAssessment = new RiskAssessment($macroMonitor);
$breaker = new EnhancedCircuitBreaker($riskAssessment);

// 2. Run Checks
echo "Checking Status...\n";
$status = $breaker->getStatus();

// 3. Output Results
echo "\n📊 Circuit Breaker Report:\n";
print_r($status);

// 4. Verification assertions
// Current Mock Data yields Score 60 (High Risk, but < 80 Critical).
// So Halt: False, Opportunity: True (Top of range 40-60).

echo "\n--------------------------------\n";
if ($status['halted'] === false && $status['opportunity_zone'] === true) {
    echo "✅ VERIFICATION PASSED: Logic matches expected mock scenario (Score 60).\n";
    echo "   (Halt=False because 60 < 80 | Opp=True because 60 in 40-60 range)\n";
} else {
    echo "❌ VERIFICATION FAILED: Unexpected status.\n";
}
