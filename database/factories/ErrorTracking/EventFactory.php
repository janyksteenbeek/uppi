<?php

namespace Database\Factories\ErrorTracking;

use App\Enums\ErrorTracking\IssueLevel;
use App\Models\ErrorTracking\Event;
use App\Models\ErrorTracking\Issue;
use App\Models\ErrorTracking\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $project = Project::factory();

        return [
            'project_id' => $project,
            'issue_id' => Issue::factory()->state(fn (array $attrs, ?Issue $issue) => [
                'project_id' => $attrs['project_id'] ?? $project,
            ]),
            'event_id' => Str::lower(Str::random(32)),
            'level' => IssueLevel::ERROR,
            'platform' => 'php',
            'environment' => 'production',
            'release' => '1.0.0',
            'server_name' => 'web-1',
            'transaction' => 'GET /',
            'message' => 'Something went wrong',
            'culprit' => 'App\\Http\\Controllers\\ExampleController->index',
            'exception' => [
                'values' => [[
                    'type' => 'RuntimeException',
                    'value' => 'Something went wrong',
                    'stacktrace' => [
                        'frames' => [[
                            'filename' => 'app/Http/Controllers/ExampleController.php',
                            'function' => 'index',
                            'lineno' => 42,
                            'in_app' => true,
                        ]],
                    ],
                ]],
            ],
            'stacktrace' => [
                'frames' => [[
                    'filename' => 'app/Http/Controllers/ExampleController.php',
                    'function' => 'index',
                    'lineno' => 42,
                    'in_app' => true,
                ]],
            ],
            'breadcrumbs' => null,
            'tags' => ['environment' => 'production'],
            'contexts' => null,
            'request' => null,
            'user_context' => null,
            'extra' => null,
            'sdk' => ['name' => 'sentry.php', 'version' => '4.10.0'],
            'received_at' => now(),
            'occurred_at' => now(),
        ];
    }
}
