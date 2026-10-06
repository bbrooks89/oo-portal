<?php
declare(strict_types=1);

namespace App\Portal;

/**
 * The list of automations people can request, and the rules for their inputs.
 *
 * Validating here, before anything reaches OO, means a flow never starts
 * with bad inputs. This is the "validate and authorize" step from the
 * firewall redesign, done in the portal.
 */
final class FlowCatalog
{
    /** @param array<string, array> $flows keyed by flow ID */
    public function __construct(private array $flows)
    {
    }

    /**
     * The practice catalog. In real OO, each 'uuid' would be the flow's
     * UUID from Central; the mock server accepts any value.
     */
    public static function default(): self
    {
        $states = [];
        foreach (range(1, 13) as $n) {
            $id = sprintf('state-%02d', $n);
            $states[$id] = sprintf('State jurisdiction %02d', $n);
        }

        return new self([
            'firewall-maintenance' => [
                'uuid' => 'firewall-maintenance',
                'name' => 'Open or close client firewall access',
                'description' => 'Opens access for a maintenance window, or closes it afterward. Checks the current state first.',
                'inputs' => [
                    ['name' => 'client', 'label' => 'Client', 'type' => 'select', 'options' => $states],
                    ['name' => 'action', 'label' => 'Action', 'type' => 'select', 'options' => [
                        'open' => 'Open access',
                        'close' => 'Close access',
                        'fail' => 'Simulate a failure (practice)',
                    ]],
                ],
            ],
            'service-restart' => [
                'uuid' => 'service-restart',
                'name' => 'Restart a Windows service',
                'description' => 'Restarts one service on one server and confirms it is running again.',
                'inputs' => [
                    [
                        'name' => 'server',
                        'label' => 'Server name',
                        'type' => 'text',
                        'pattern' => '/^[a-z0-9-]{3,30}$/i',
                        'patternMessage' => 'Use 3–30 letters, numbers, or hyphens, like web-prod-01.',
                    ],
                    [
                        'name' => 'service',
                        'label' => 'Service name',
                        'type' => 'text',
                        'pattern' => '/^[A-Za-z0-9_.-]{2,60}$/',
                        'patternMessage' => 'Use the service name, not the display name, like W3SVC.',
                    ],
                ],
            ],
            'daily-report' => [
                'uuid' => 'daily-report',
                'name' => 'Send the daily operations report',
                'description' => 'Builds the report for one day and emails it to the configured distribution list.',
                'inputs' => [
                    [
                        'name' => 'reportDate',
                        'label' => 'Report date',
                        'type' => 'date',
                        'pattern' => '/^\d{4}-\d{2}-\d{2}$/',
                        'patternMessage' => 'Choose a date.',
                    ],
                ],
            ],
        ]);
    }

    /** @return array<string, array> */
    public function all(): array
    {
        return $this->flows;
    }

    public function find(string $flowId): ?array
    {
        return $this->flows[$flowId] ?? null;
    }

    /**
     * Checks submitted inputs against the flow's rules.
     *
     * @return array{inputs: array<string, string>, errors: array<string, string>}
     */
    public function validate(string $flowId, array $submitted): array
    {
        $flow = $this->find($flowId);
        if ($flow === null) {
            return ['inputs' => [], 'errors' => ['flow' => 'Choose an automation from the list.']];
        }

        $inputs = [];
        $errors = [];

        foreach ($flow['inputs'] as $field) {
            $name = $field['name'];
            $raw = $submitted[$name] ?? '';
            $value = is_string($raw) ? trim($raw) : '';

            if ($value === '') {
                $errors[$name] = "{$field['label']} is required.";
                continue;
            }

            if ($field['type'] === 'select' && !array_key_exists($value, $field['options'])) {
                $errors[$name] = "Choose one of the listed options for {$field['label']}.";
                continue;
            }

            if (isset($field['pattern']) && !preg_match($field['pattern'], $value)) {
                $errors[$name] = $field['patternMessage'];
                continue;
            }

            $inputs[$name] = $value;
        }

        return ['inputs' => $inputs, 'errors' => $errors];
    }
}
