<?php

namespace Tests\Unit;

use App\Services\EthereumService;
use RuntimeException;
use Tests\TestCase;

class EthereumServiceGasValidationTest extends TestCase
{
    private function serviceWithResponses(array $responses): EthereumService
    {
        return new class($responses) extends EthereumService {
            private array $responses;

            public function __construct(array $responses)
            {
                $this->responses = $responses;
                parent::__construct();
            }

            protected function runNode(array $args, ?string $privateKey = null): array
            {
                $key = json_encode($args);
                if (!array_key_exists($key, $this->responses)) {
                    throw new RuntimeException('Unexpected node call: ' . $key);
                }

                return $this->responses[$key];
            }
        };
    }

    public function test_successful_eip1559_fee_calculation(): void
    {
        $service = $this->serviceWithResponses([
            json_encode(['estimateTokenGas', '0xUSDT', '0xsender', '0xrecipient', '10.00', '6']) => [
                'contractAddress' => '0xUSDT',
                'from' => '0xsender',
                'to' => '0xrecipient',
                'amountToken' => '10.00',
                'decimals' => 6,
                'gasLimit' => '210000',
            ],
            json_encode(['feeData']) => [
                'gasPrice' => '20000000000',
                'maxFeePerGas' => '30000000000',
                'maxPriorityFeePerGas' => '1500000000',
            ],
        ]);

        $prepared = $service->prepareTokenTransfer('0xUSDT', '0xsender', '0xrecipient', '10.00', 6, '1');

        $this->assertSame('eip1559', $prepared['feeMode']);
        $this->assertSame('210105', $prepared['gasLimit']);
        $this->assertSame('30000000000', $prepared['maxFeePerGas']);
        $this->assertSame('1500000000', $prepared['maxPriorityFeePerGas']);
    }

    public function test_legacy_gas_price_fallback_when_eip1559_unavailable(): void
    {
        $service = $this->serviceWithResponses([
            json_encode(['estimateTokenGas', '0xUSDT', '0xsender', '0xrecipient', '10.00', '6']) => [
                'contractAddress' => '0xUSDT',
                'from' => '0xsender',
                'to' => '0xrecipient',
                'amountToken' => '10.00',
                'decimals' => 6,
                'gasLimit' => '210000',
            ],
            json_encode(['feeData']) => [
                'gasPrice' => '22000000000',
            ],
        ]);

        $prepared = $service->prepareTokenTransfer('0xUSDT', '0xsender', '0xrecipient', '10.00', 6, '1');

        $this->assertSame('legacy', $prepared['feeMode']);
        $this->assertSame('22000000000', $prepared['gasPrice']);
    }

    public function test_estimate_gas_failure_blocks_broadcast(): void
    {
        $service = $this->serviceWithResponses([
            json_encode(['estimateTokenGas', '0xUSDT', '0xsender', '0xrecipient', '10.00', '6']) => [
                'contractAddress' => '0xUSDT',
                'from' => '0xsender',
                'to' => '0xrecipient',
                'amountToken' => '10.00',
                'decimals' => 6,
                'gasLimit' => null,
            ],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to estimate gas for USDT transfer.');

        $service->prepareTokenTransfer('0xUSDT', '0xsender', '0xrecipient', '10.00', 6, '1');
    }

    public function test_fee_data_failure_blocks_broadcast(): void
    {
        $service = $this->serviceWithResponses([
            json_encode(['estimateTokenGas', '0xUSDT', '0xsender', '0xrecipient', '10.00', '6']) => [
                'contractAddress' => '0xUSDT',
                'from' => '0xsender',
                'to' => '0xrecipient',
                'amountToken' => '10.00',
                'decimals' => 6,
                'gasLimit' => '210000',
            ],
            json_encode(['feeData']) => [
                'gasPrice' => null,
                'maxFeePerGas' => null,
                'maxPriorityFeePerGas' => null,
            ],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to obtain current fee data for USDT transfer.');

        $service->prepareTokenTransfer('0xUSDT', '0xsender', '0xrecipient', '10.00', 6, '1');
    }

    public function test_insufficient_eth_blocks_broadcast(): void
    {
        $service = $this->serviceWithResponses([
            json_encode(['estimateTokenGas', '0xUSDT', '0xsender', '0xrecipient', '10.00', '6']) => [
                'contractAddress' => '0xUSDT',
                'from' => '0xsender',
                'to' => '0xrecipient',
                'amountToken' => '10.00',
                'decimals' => 6,
                'gasLimit' => '210000',
            ],
            json_encode(['feeData']) => [
                'gasPrice' => '30000000000',
            ],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insufficient ETH balance to pay gas');

        $service->prepareTokenTransfer('0xUSDT', '0xsender', '0xrecipient', '10.00', 6, '0.00001');
    }

    public function test_no_hardcoded_100000_gas_fallback_is_used(): void
    {
        $service = $this->serviceWithResponses([
            json_encode(['estimateTokenGas', '0xUSDT', '0xsender', '0xrecipient', '10.00', '6']) => [
                'contractAddress' => '0xUSDT',
                'from' => '0xsender',
                'to' => '0xrecipient',
                'amountToken' => '10.00',
                'decimals' => 6,
                'gasLimit' => '61000',
            ],
            json_encode(['feeData']) => [
                'gasPrice' => '30000000000',
            ],
        ]);

        $prepared = $service->prepareTokenTransfer('0xUSDT', '0xsender', '0xrecipient', '10.00', 6, '0.5');

        $this->assertSame('61030', $prepared['gasLimit']);
    }

    public function test_no_fixed_eth_fallback_is_used_for_missing_fee_data(): void
    {
        $service = $this->serviceWithResponses([
            json_encode(['estimateTokenGas', '0xUSDT', '0xsender', '0xrecipient', '10.00', '6']) => [
                'contractAddress' => '0xUSDT',
                'from' => '0xsender',
                'to' => '0xrecipient',
                'amountToken' => '10.00',
                'decimals' => 6,
                'gasLimit' => '210000',
            ],
            json_encode(['feeData']) => [
                'gasPrice' => null,
                'maxFeePerGas' => null,
                'maxPriorityFeePerGas' => null,
            ],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to obtain current fee data for USDT transfer.');

        $service->prepareTokenTransfer('0xUSDT', '0xsender', '0xrecipient', '10.00', 6, '0.00001');
    }
}
