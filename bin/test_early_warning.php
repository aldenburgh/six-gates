<?php
require __DIR__ . '/../vendor/autoload.php';

use SixGates\DataProviders\MockDataProvider;
use SixGates\Services\Data\MarketDataService;
use SixGates\Services\EarlyWarning\MacroMonitor;
use SixGates\Services\EarlyWarning\RiskAssessment;

echo "🧪 Testing Early Warning System...\n";

// 1. Setup Dependencies
$mockProvider = new MockDataProvider();
// Note: MockDataProvider constructor args might be needed? 
// Checking config requirements... MockDataProvider takes $config array.

$marketData = new MarketDataService($mockProvider);
$macroMonitor = new MacroMonitor($marketData);
$riskAssessment = new RiskAssessment($macroMonitor);

// 2. Run Assessment
echo "Running Risk Assessment...\n";
$report = $riskAssessment->assess();

// 3. Output Results
echo "\n📊 Early Warning Report:\n";
print_r($report);

// 4. Verification assertions
$score = $report['systemic_risk_score'];
$level = $report['risk_level'];

echo "\n--------------------------------\n";
if ($score === 60 && $level === 'HIGH') {
    echo "✅ VERIFICATION PASSED: Logic matches expected mock scenario.\n";
    echo "   (VIX 18.5 => 10, Spread -0.3 => 40, Inf 3.5 => 10 | Total 60)\n";
} else {
    echo "❌ VERIFICATION FAILED: Unexpected score $score or level $level.\n";
}
