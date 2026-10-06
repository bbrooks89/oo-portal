<?php
declare(strict_types=1);

namespace App\Oo;

/**
 * Talks to OO Central's REST API: start a flow, check its status, wait for it.
 *
 * Endpoints are modeled on OO 10.x (/oo/rest/v2/...). Check your OO version's
 * REST API docs before pointing this at a real Central server.
 */
final class FlowClient implements FlowRunner
{
    // Constructor property promotion (PHP 8): declares AND assigns these properties.
    public function __construct(
        private string $baseUrl,
        private string $username,
        private string $password,
        private int $timeoutSeconds = 10,
    ) {
    }

    /**
     * Starts a flow and returns its execution ID (the "run ID").
     */
    public function startFlow(string $flowUuid, array $inputs = [], ?string $runName = null): string
    {
        $body = ['flowUuid' => $flowUuid, 'inputs' => $inputs];
        if ($runName !== null) {
            $body['runName'] = $runName;
        }

        $response = $this->request('POST', '/executions', $body);

        // OO returns the new execution ID in the response body.
        return trim($response, "\" \r\n");
    }

    /**
     * Fetches the current status of one execution.
     */
    public function getExecution(string $executionId): Execution
    {
        $json = $this->request('GET', "/executions/{$executionId}/summary");
        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        // The summary endpoint returns a list; we asked for one run.
        return Execution::fromArray($data[0] ?? $data);
    }

    /**
     * Polls until the run finishes or the wait times out.
     */
    public function waitForCompletion(
        string $executionId,
        int $pollSeconds = 2,
        int $maxWaitSeconds = 60,
    ): Execution {
        $deadline = time() + $maxWaitSeconds;

        do {
            $execution = $this->getExecution($executionId);
            if ($execution->isFinished()) {
                return $execution;
            }
            sleep($pollSeconds);
        } while (time() < $deadline);

        throw new OoException("Execution {$executionId} did not finish within {$maxWaitSeconds} seconds");
    }

    /**
     * One place for every HTTP call, so auth, timeouts, and error handling
     * are handled the same way everywhere. Private: only this class can call it.
     */
    private function request(string $method, string $path, ?array $body = null): string
    {
        $headers = ['Accept: application/json'];
        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_USERPWD => "{$this->username}:{$this->password}",
        ];

        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_THROW_ON_ERROR);
        }
        $options[CURLOPT_HTTPHEADER] = $headers;

        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);

        if ($response === false) {
            throw new OoException('Request to OO failed: ' . curl_error($ch));
        }

        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($status >= 400) {
            throw new OoException("OO returned HTTP {$status}: {$response}", $status);
        }

        return $response;
    }
}
