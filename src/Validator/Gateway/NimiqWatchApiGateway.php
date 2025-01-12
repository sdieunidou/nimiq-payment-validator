<?php

namespace Seb\NimiqLib\Validator\Gateway;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Seb\NimiqLib\Model\Transaction;

class NimiqWatchApiGateway implements ApiGatewayInterface
{
    private string $network;
    private string $apiDomain;
    private ClientInterface $httpClient;
    private int $rateLimit; // in milliseconds

    /**
     * NimiqWatchApiGateway constructor.
     *
     * @param string          $network    'main' or 'test'
     * @param null|string     $apiDomain  custom API domain, defaults based on network
     * @param ClientInterface $httpClient guzzle HTTP client
     * @param int             $rateLimit  rate limit in milliseconds between requests
     */
    public function __construct(
        string $network,
        ?string $apiDomain,
        ClientInterface $httpClient,
        int $rateLimit = 1000
    ) {
        $this->network = $network;
        $this->apiDomain = $apiDomain ?? ('main' === $network ? 'https://v2.nimiqwatch.com/api/v1/' : 'https://v2.test.nimiqwatch.com/api/v1/');
        $this->httpClient = $httpClient;
        $this->rateLimit = $rateLimit;
    }

    public function getTransactionByHash(string $transactionHash): ?Transaction
    {
        $url = rtrim($this->apiDomain, '/').'/transaction/'.$transactionHash;

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'timeout' => 5,
            ]);

            if (200 === $response->getStatusCode()) {
                $data = json_decode($response->getBody()->getContents(), true);

                if (empty($data)) {
                    return null;
                }

                return new Transaction(
                    $data['hash'],
                    $data['sender_address'],
                    $data['receiver_address'],
                    $data['value'],
                    $data['message'] ?? '',
                    $data['block_height'],
                    $data['timestamp'],
                    $data['extra'] ?? null
                );
            }

            return null;
        } catch (GuzzleException $e) {
            // Log the exception or handle it as needed.
            return null;
        }
    }
}
