<?php

namespace Boyo\LinkMobility;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LinkMobilityViberApi
{
    private string $apiKey;

    private string $apiSecret;

    private int $serviceId;

    private string $sc;

    private bool $log;

    private string $logChannel;

    private string $urlProduction = 'https://api.msghub.cloud';

    private string $urlTesting = 'https://api-test.msghub.cloud';

    private array $headers = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
        'Expect' => '',
    ];

    public function __construct()
    {
        $this->apiKey = config('services.link-mobility.api_key');
        $this->apiSecret = config('services.link-mobility.api_secret');
        $this->serviceId = (int) config('services.link-mobility.service_id');
        $this->sc = config('services.link-mobility.sc_viber');
        $this->log = config('services.link-mobility.log');
        $this->logChannel = config('services.link-mobility.log_channel');
    }

    public function getTemplates(): array
    {
        $response = $this->request('/viber_templates/get', [
            'sc' => $this->sc,
        ]);

        $data = $response['data'] ?? $response;

        if (isset($data['templates'])) {
            return $data['templates'];
        }

        if (isset($data['template']) && isset($data['template']['template_id'])) {
            return [$data['template']];
        }

        return [];
    }

    public function createTemplate(string $lang, string $template, array $params, array $varExample): array
    {
        return $this->request('/viber_templates/create', [
            'sc' => $this->sc,
            'params' => $params,
            'locales' => [
                'lang' => $lang,
                'template' => $template,
            ],
            'varExample' => $varExample,
        ]);
    }

    public function deleteTemplate(string $templateId): array
    {
        return $this->request('/viber_templates/delete', [
            'sc' => $this->sc,
            'template_id' => $templateId,
        ]);
    }

    private function request(string $endpoint, array $payload): array
    {
        $payload['service_id'] = $this->serviceId;

        $signature = hash_hmac('sha512', json_encode($payload), $this->apiSecret);

        $headers = $this->headers;
        $headers['x-api-key'] = $this->apiKey;
        $headers['x-api-sign'] = $signature;

        $baseUrl = app()->environment('production') ? $this->urlProduction : $this->urlTesting;

        if ($this->log) {
            Log::channel($this->logChannel)->info("LinkMobility Viber API {$endpoint}", $payload);
        }

        $response = Http::withHeaders($headers)->post($baseUrl . $endpoint, $payload)->json();

        if ($this->log) {
            Log::channel($this->logChannel)->info("LinkMobility Viber API {$endpoint} response", $response ?? []);
        }

        return $response ?? [];
    }
}
