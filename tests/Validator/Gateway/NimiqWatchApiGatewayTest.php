<?php

namespace Tests\HostMe\NimiqLib\Validator\Gateway;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use HostMe\NimiqLib\Model\Transaction;
use HostMe\NimiqLib\Validator\Gateway\NimiqWatchApiGateway;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * @internal
 *
 * @coversNothing
 */
class NimiqWatchApiGatewayTest extends TestCase
{
    private ClientInterface $httpClient;
    private NimiqWatchApiGateway $gateway;
    private string $network = 'main';
    private ?string $apiDomain = null; // Corrected to nullable
    private int $rateLimit = 1000;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->gateway = new NimiqWatchApiGateway(
            $this->network,
            $this->apiDomain,
            $this->httpClient,
            $this->rateLimit
        );
    }

    public function testGetTransactionByHashSuccess(): void
    {
        $transactionHash = 'abcdef1234567890abcdef1234567890abcdef12';
        $apiResponseData = [
            'hash' => 'abcdef1234567890abcdef1234567890abcdef12',
            'sender_address' => 'NQXX SENDER',
            'receiver_address' => 'NQ01 RECEIVER',
            'value' => '500000',
            'message' => 'Payment for services',
            'block_height' => 123456,
            'timestamp' => 1672531200,
            'extra' => ['key' => 'value'],
        ];

        $responHostMeody = json_encode($apiResponseData);
        $response = new Response(200, ['Content-Type' => 'application/json'], $responHostMeody);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://v2.nimiqwatch.com/api/v1/transaction/'.$transactionHash, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'timeout' => 5,
            ])
            ->willReturn($response)
        ;

        $transaction = $this->gateway->getTransactionByHash($transactionHash);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertSame($apiResponseData['hash'], $transaction->getHash());
        $this->assertSame($apiResponseData['sender_address'], $transaction->getSenderAddress());
        $this->assertSame($apiResponseData['receiver_address'], $transaction->getRecipientAddress());
        $this->assertSame($apiResponseData['value'], $transaction->getValue());
        $this->assertSame($apiResponseData['message'], $transaction->getMessage());
        $this->assertSame($apiResponseData['block_height'], $transaction->getHeight());
        $this->assertSame($apiResponseData['timestamp'], $transaction->getTimestamp());
        $this->assertSame($apiResponseData['extra'], $transaction->getExtra());
    }

    public function testGetTransactionByHashNotFound(): void
    {
        $transactionHash = 'abcdef1234567890abcdef1234567890abcdef12';
        $response = new Response(404, ['Content-Type' => 'application/json'], '');

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://v2.nimiqwatch.com/api/v1/transaction/'.$transactionHash, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'timeout' => 5,
            ])
            ->willReturn($response)
        ;

        $transaction = $this->gateway->getTransactionByHash($transactionHash);

        $this->assertNull($transaction);
    }

    public function testGetTransactionByHashApiException(): void
    {
        $transactionHash = 'abcdef1234567890abcdef1234567890abcdef12';
        $requestMock = $this->createMock(RequestInterface::class);
        $exception = new RequestException('Error Communicating with Server', $requestMock);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://v2.nimiqwatch.com/api/v1/transaction/'.$transactionHash, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'timeout' => 5,
            ])
            ->willThrowException($exception)
        ;

        $transaction = $this->gateway->getTransactionByHash($transactionHash);

        $this->assertNull($transaction);
    }

    public function testGetTransactionByHashMalformedJson(): void
    {
        $transactionHash = 'abcdef1234567890abcdef1234567890abcdef12';
        $responHostMeody = 'Invalid JSON';
        $response = new Response(200, ['Content-Type' => 'application/json'], $responHostMeody);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://v2.nimiqwatch.com/api/v1/transaction/'.$transactionHash, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'timeout' => 5,
            ])
            ->willReturn($response)
        ;

        // To prevent accessing array offset on null, ensure the gateway handles malformed JSON
        // For the purpose of this test, we expect the gateway to return null
        // Adjust the gateway's getTransactionByHash method to handle this case appropriately

        $transaction = $this->gateway->getTransactionByHash($transactionHash);

        $this->assertNull($transaction);
    }

    public function testGetTransactionByHashCustomNetwork(): void
    {
        // Test with 'test' network and custom apiDomain
        $customApiDomain = 'https://custom.test.api/';
        $gateway = new NimiqWatchApiGateway('test', $customApiDomain, $this->httpClient, $this->rateLimit);
        $transactionHash = '1234567890abcdef1234567890abcdef12345678';

        $apiResponseData = [
            'hash' => '1234567890abcdef1234567890abcdef12345678',
            'sender_address' => 'NQXX SENDER',
            'receiver_address' => 'NQ01 RECEIVER',
            'value' => '600000',
            'message' => 'Test payment',
            'block_height' => 123457,
            'timestamp' => 1672531300,
            'extra' => null,
        ];

        $responHostMeody = json_encode($apiResponseData);
        $response = new Response(200, ['Content-Type' => 'application/json'], $responHostMeody);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://custom.test.api/transaction/'.$transactionHash, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'timeout' => 5,
            ])
            ->willReturn($response)
        ;

        $transaction = $gateway->getTransactionByHash($transactionHash);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertSame($apiResponseData['hash'], $transaction->getHash());
        $this->assertSame($apiResponseData['sender_address'], $transaction->getSenderAddress());
        $this->assertSame($apiResponseData['receiver_address'], $transaction->getRecipientAddress());
        $this->assertSame($apiResponseData['value'], $transaction->getValue());
        $this->assertSame($apiResponseData['message'], $transaction->getMessage());
        $this->assertSame($apiResponseData['block_height'], $transaction->getHeight());
        $this->assertSame($apiResponseData['timestamp'], $transaction->getTimestamp());
        $this->assertSame($apiResponseData['extra'], $transaction->getExtra());
    }
}
