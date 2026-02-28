<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\AiConversation;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\Process\Process;

use function Pest\Laravel\actingAs;

it('redacts sensitive headers and request body in outbound HTTP debug logs', function () {
    config()->set('app.debug', true);

    (new AppServiceProvider(app()))->boot();

    Http::fake([
        'https://example.com/*' => Http::response(['ok' => true], 200),
    ]);

    Log::spy();

    Http::withHeaders([
        'Authorization' => 'Bearer super-secret-token',
        'X-Api-Key' => 'top-secret-key',
    ])->post('https://example.com/api/chat/completions', [
        'prompt' => 'private prompt content',
    ]);

    Log::shouldHaveReceived('debug')
        ->withArgs(function (string $message, array $context): bool {
            if (!str_contains($message, 'POST https://example.com/api/chat/completions')) {
                return false;
            }

            $request = $context['request'] ?? [];

            return ($request['headers']['Authorization'][0] ?? null) === '[REDACTED]'
                && ($request['headers']['X-Api-Key'][0] ?? null) === '[REDACTED]'
                && ($request['body'] ?? null) === '[REDACTED]';
        })
        ->atLeast()
        ->once();
});

it('does not include inertia sharing middleware in the api middleware group', function () {
    $apiMiddleware = app('router')->getMiddlewareGroups()['api'] ?? [];

    expect($apiMiddleware)->not->toContain(HandleInertiaRequests::class);
});

it('locks markdown rendering to escaped output in fallback paths', function () {
    $markdownUtility = file_get_contents(resource_path('js/Utils/Markdown.ts'));

    expect($markdownUtility)->toBeString()
        ->and($markdownUtility)->toContain('html: false')
        ->and($markdownUtility)->toContain('md.utils.escapeHtml(code)')
        ->and($markdownUtility)->toContain('return `<pre><code class="language-${safeLang}">${safeCode}</code></pre>`');
});

it('escapes hostile html in markdown highlight fallback behavior', function () {
    $nodeVersion = new Process(['node', '--version']);
    $nodeVersion->run();

    if (! $nodeVersion->isSuccessful()) {
        $this->markTestSkipped('Node is required for markdown behavior test.');
    }

    $script = <<<'JS'
import MarkdownIt from "markdown-it";

const md = new MarkdownIt({ html: false });
md.set({
  highlight: (code, lang) => {
    const safeCode = md.utils.escapeHtml(code);
    const safeLang = md.utils.escapeHtml(lang || "text");
    return `<pre><code class="language-${safeLang}">${safeCode}</code></pre>`;
  }
});

const payload = "```unknown\n</code><img src=x onerror=alert(1)>\n```";
const rendered = md.render(payload);

if (rendered.includes("<img")) {
  console.error("unsafe html emitted");
  process.exit(1);
}

if (!rendered.includes("&lt;img")) {
  console.error("expected escaped html not present");
  process.exit(2);
}
JS;

    $process = Process::fromShellCommandline('node --input-type=module -e '.escapeshellarg($script));
    $process->run();

    expect($process->isSuccessful())->toBeTrue(
        trim($process->getErrorOutput().' '.$process->getOutput())
    );
});

it('shares conversations newest-first by updated_at on inertia web routes', function () {
    $user = User::factory()->create();

    $oldest = AiConversation::create([
        'user_id' => $user->id,
        'title' => 'Oldest',
        'model_used' => 'laravel-expert',
    ]);

    $middle = AiConversation::create([
        'user_id' => $user->id,
        'title' => 'Middle',
        'model_used' => 'laravel-expert',
    ]);

    $newest = AiConversation::create([
        'user_id' => $user->id,
        'title' => 'Newest',
        'model_used' => 'laravel-expert',
    ]);

    $oldest->forceFill(['updated_at' => now()->subMinutes(30)])->save();
    $middle->forceFill(['updated_at' => now()->subMinutes(10)])->save();
    $newest->forceFill(['updated_at' => now()])->save();

    $response = actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Chat/Index')
        ->where('conversations.0.id', $newest->id)
        ->where('conversations.1.id', $middle->id)
        ->where('conversations.2.id', $oldest->id)
    );
});
