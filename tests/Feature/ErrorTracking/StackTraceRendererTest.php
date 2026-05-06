<?php

use App\Services\ErrorTracking\StackTrace\Renderers\GenericRenderer;
use App\Services\ErrorTracking\StackTrace\Renderers\PhpLaravelRenderer;
use App\Services\ErrorTracking\StackTrace\StackTraceRendererManager;

it('selects the PHP renderer for php and laravel platforms', function () {
    $manager = new StackTraceRendererManager(
        renderers: [new PhpLaravelRenderer],
        fallback: new GenericRenderer,
    );

    expect($manager->resolveFor('php'))->toBeInstanceOf(PhpLaravelRenderer::class);
    expect($manager->resolveFor('laravel'))->toBeInstanceOf(PhpLaravelRenderer::class);
    expect($manager->resolveFor('python'))->toBeInstanceOf(GenericRenderer::class);
    expect($manager->resolveFor(null))->toBeInstanceOf(GenericRenderer::class);
});

it('renders php frames with in-app and vendor split', function () {
    $renderer = new PhpLaravelRenderer;

    $frames = [
        ['filename' => '/srv/vendor/laravel/framework/src/Foo.php', 'function' => 'frame1', 'lineno' => 5, 'in_app' => false],
        ['filename' => '/srv/app/Http/Controllers/Foo.php', 'function' => 'frame2', 'lineno' => 10, 'in_app' => true],
    ];

    $html = $renderer->render($frames, ['values' => [['type' => 'RuntimeException', 'value' => 'boom']]])->render();

    expect($html)->toContain('app/Http/Controllers/Foo.php');
    expect($html)->toContain('vendor');
    expect($html)->toContain('RuntimeException');
});
