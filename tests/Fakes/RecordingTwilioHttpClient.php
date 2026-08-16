<?php

namespace Shetabit\Sms\Tests\Fakes;

use Twilio\AuthStrategy\AuthStrategy;
use Twilio\Http\Client;
use Twilio\Http\Response;

class RecordingTwilioHttpClient implements Client
{
    /**
     * @var array<int, array{method: string, url: string, params: array<string, mixed>, data: array<string, mixed>}>
     */
    public array $requests = [];

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $data
     * @param array<string, mixed> $headers
     */
    public function request(
        string $method,
        string $url,
        array $params = [],
        array $data = [],
        array $headers = [],
        ?string $user = null,
        ?string $password = null,
        ?int $timeout = null,
        ?AuthStrategy $authStrategy = null
    ) : Response {
        $this->requests[] = ['method' => $method, 'url' => $url, 'params' => $params, 'data' => $data];

        return new Response(201, (string) json_encode([
            'sid' => 'SM00000000000000000000000000000000',
            'to' => $data['To'] ?? null,
            'from' => $data['From'] ?? null,
            'body' => $data['Body'] ?? null,
            'status' => 'queued',
        ]));
    }
}
