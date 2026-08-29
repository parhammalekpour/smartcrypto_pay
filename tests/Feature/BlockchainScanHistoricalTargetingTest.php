<?php

namespace Tests\Feature;

use App\Services\BlockchainDepositService;
use Mockery;
use Tests\TestCase;

class BlockchainScanHistoricalTargetingTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_historical_range_scan_is_passed_through_without_cursor_update(): void
    {
        $service = Mockery::mock(BlockchainDepositService::class);
        $service->shouldReceive('scanOnce')
            ->once()
            ->with(20, 186, 11521497, 11521497)
            ->andReturn([
                'created' => 1,
                'skipped' => 0,
                'errors' => [],
            ]);

        $this->app->instance(BlockchainDepositService::class, $service);

        $this->artisan('blockchain:scan', [
            '--limit' => 20,
            '--wallet' => 186,
            '--from-block' => 11521497,
            '--to-block' => 11521497,
        ])
            ->assertSuccessful()
            ->expectsOutput('Targeted scan: Wallet: 186 Range: 11521497 -> 11521497')
            ->expectsOutput('Final summary: Created: 1, Skipped: 0, Errors: 0');
    }

    public function test_historical_range_requires_valid_order(): void
    {
        $this->artisan('blockchain:scan', [
            '--wallet' => 186,
            '--from-block' => 200,
            '--to-block' => 100,
        ])
            ->assertExitCode(1)
            ->expectsOutput('The --from-block value must be less than or equal to --to-block.');
    }
}
