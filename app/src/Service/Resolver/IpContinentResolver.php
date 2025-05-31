<?php

namespace App\Service\Resolver;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class IpContinentResolver
{
    private const API_URL = 'https://api.ipgeolocation.io/ipgeo';
    private const API_KEY = 'b9c9e0c9e04642f5a66b2278c4cb1e25';

    public function __construct(private readonly HttpClientInterface $http_client) {}

    public function resolve(string $ip): ?string
    {
        $response = $this->http_client->request('GET', self::API_URL, [
            'query' => [
                'apiKey' => self::API_KEY,
                'ip'     => $ip,
                'fields' => 'continent_code'
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $data = $response->toArray();

        return strtolower(trim($data['continent_code'])) ?? null;
    }
}