<?php

namespace App\Jobs\Checks;

use App\Enums\Checks\Status;
use App\Enums\Tests\TestFlowBlockType;
use App\Enums\Tests\TestStatus;
use App\Models\Test;
use App\Models\TestRun;
use App\Models\TestRunStep;
use App\Models\TestStep;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\Browser;

class TestCheckJob extends CheckJob
{
    protected ?TestRun $testRun = null;

    protected ?Test $test = null;

    protected function performCheck(): array
    {
        // Check if user has the feature flag
        if (! $this->monitor->user->hasFeature('run-tests')) {
            return $this->failedResult('Tests feature is not enabled for this account');
        }

        $this->test = $this->monitor->test;

        if (! $this->test) {
            return $this->failedResult('No test configured for this monitor');
        }

        if ($this->test->steps->isEmpty()) {
            return $this->failedResult('Test has no steps configured');
        }

        $this->testRun = $this->createTestRun();

        try {
            return $this->runTest();
        } catch (\Exception $e) {
            return $this->handleTestException($e);
        }
    }

    protected function runTest(): array
    {
        $this->testRun->markAsRunning();
        $startTime = microtime(true);

        $browser = $this->createBrowser();

        try {
            // Visit entrypoint
            $browser->visit($this->test->entrypoint_url);

            // Execute each step
            foreach ($this->testRun->runSteps as $runStep) {
                $this->executeStep($browser, $runStep);

                // Check for early success
                if ($runStep->testStep->type === TestFlowBlockType::SUCCESS) {
                    break;
                }
            }

            $durationMs = $this->calculateDuration($startTime);
            $this->testRun->markAsSuccess($durationMs);
            $this->test->updateLastRun();

            return $this->successResult($durationMs);
        } finally {
            $browser->quit();
        }
    }

    protected function executeStep(Browser $browser, TestRunStep $runStep): void
    {
        $step = $runStep->testStep;
        $stepStart = microtime(true);

        $runStep->markAsRunning();

        try {
            $this->dispatchStepAction($browser, $step, $runStep);

            $durationMs = $this->calculateDuration($stepStart);
            $runStep->markAsSuccess($durationMs);
        } catch (\Exception $e) {
            $durationMs = $this->calculateDuration($stepStart);
            $htmlSnapshot = $this->captureHtml($browser);
            $runStep->markAsFailure($e->getMessage(), $durationMs, null, $htmlSnapshot);

            throw $e;
        }
    }

    protected function captureHtml(Browser $browser): ?string
    {
        try {
            return $browser->driver->getPageSource();
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function captureScreenshot(Browser $browser, TestRunStep $runStep): string
    {
        $filename = sprintf(
            'test-screenshots/%s/%s-%s.png',
            $this->testRun->id,
            $runStep->sort_order,
            now()->format('Y-m-d-His')
        );

        $screenshot = $browser->driver->takeScreenshot();

        \Storage::put($filename, $screenshot);

        return $filename;
    }

    protected function dispatchStepAction(Browser $browser, TestStep $step, TestRunStep $runStep): void
    {
        match ($step->type) {
            TestFlowBlockType::VISIT => $this->actionVisit($browser, $step),
            TestFlowBlockType::WAIT_FOR_TEXT => $this->actionWaitForText($browser, $step),
            TestFlowBlockType::TYPE => $this->actionType($browser, $step),
            TestFlowBlockType::PRESS => $this->actionPress($browser, $step),
            TestFlowBlockType::CLICK_LINK => $this->actionClickLink($browser, $step),
            TestFlowBlockType::CLICK => $this->actionClick($browser, $step),
            TestFlowBlockType::BACK => $this->actionBack($browser),
            TestFlowBlockType::FORWARD => $this->actionForward($browser),
            TestFlowBlockType::REFRESH => $this->actionRefresh($browser),
            TestFlowBlockType::SCREENSHOT => $this->actionScreenshot($browser, $runStep),
            TestFlowBlockType::SUCCESS => $this->actionSuccess(),
        };
    }

    // =========================================================================
    // Step Actions - Each action is a short, focused function
    // =========================================================================

    protected function actionVisit(Browser $browser, TestStep $step): void
    {
        $browser->visit($step->value);
    }

    protected function actionWaitForText(Browser $browser, TestStep $step): void
    {
        $browser->waitForText($step->value);
    }

    protected function actionType(Browser $browser, TestStep $step): void
    {
        $browser->type($step->selector, $step->value);
    }

    protected function actionPress(Browser $browser, TestStep $step): void
    {
        $browser->press($step->value);
    }

    protected function actionClickLink(Browser $browser, TestStep $step): void
    {
        $browser->clickLink($step->value);
    }

    protected function actionClick(Browser $browser, TestStep $step): void
    {
        $browser->click($step->selector);
    }

    protected function actionBack(Browser $browser): void
    {
        $browser->back();
    }

    protected function actionForward(Browser $browser): void
    {
        $browser->forward();
    }

    protected function actionRefresh(Browser $browser): void
    {
        $browser->refresh();
    }

    protected function actionScreenshot(Browser $browser, TestRunStep $runStep): void
    {
        $filename = $this->captureScreenshot($browser, $runStep);
        $runStep->update(['screenshot_path' => $filename]);
    }

    protected function actionSuccess(): void
    {
        // No-op: just marks success point
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function createTestRun(): TestRun
    {
        $testRun = TestRun::create([
            'test_id' => $this->test->id,
            'status' => TestStatus::PENDING,
        ]);

        // Create run steps for tracking
        foreach ($this->test->steps as $index => $step) {
            $testRun->runSteps()->create([
                'test_step_id' => $step->id,
                'sort_order' => $index,
                'status' => TestStatus::PENDING,
            ]);
        }

        $testRun->load('runSteps.testStep');

        return $testRun;
    }

    protected function createBrowser(): Browser
    {
        $options = (new ChromeOptions)->addArguments([
            '--headless=new',
            '--disable-gpu',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--window-size=1920,1080',
        ]);

        $capabilities = DesiredCapabilities::chrome()->setCapability(
            ChromeOptions::CAPABILITY,
            $options
        );

        $driver = RemoteWebDriver::create(
            env('DUSK_DRIVER_URL', 'http://localhost:9515'),
            $capabilities
        );

        return new Browser($driver);
    }

    protected function calculateDuration(float $startTime): int
    {
        return (int) ((microtime(true) - $startTime) * 1000);
    }

    protected function handleTestException(\Exception $e): array
    {
        $durationMs = $this->calculateDuration($this->startTime);
        $this->testRun->markAsFailure($durationMs);

        return [
            'status' => Status::FAIL,
            'output' => json_encode([
                'type' => 'test_failure',
                'error' => $e->getMessage(),
                'test_run_id' => $this->testRun->id,
                'failed_step' => $this->testRun->getFailedStep()?->testStep->type->value,
            ]),
        ];
    }

    protected function successResult(int $durationMs): array
    {
        return [
            'status' => Status::OK,
            'output' => json_encode([
                'type' => 'test_success',
                'test_run_id' => $this->testRun->id,
                'steps_completed' => $this->testRun->getCompletedStepsCount(),
                'duration_ms' => $durationMs,
            ]),
        ];
    }

    protected function failedResult(string $message): array
    {
        return [
            'status' => Status::FAIL,
            'output' => json_encode([
                'type' => 'configuration_error',
                'error' => $message,
            ]),
        ];
    }
}
