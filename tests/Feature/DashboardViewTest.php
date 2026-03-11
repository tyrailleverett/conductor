<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    $this->setConductorAuth(static fn (Request $request): bool => true);

    $manifestPath = realpath(__DIR__.'/../..').'/resources/dist/.vite/manifest.json';

    $GLOBALS['conductor_dashboard_manifest_backup'] = file_exists($manifestPath)
        ? file_get_contents($manifestPath)
        : null;
});

afterEach(function (): void {
    $manifestPath = realpath(__DIR__.'/../..').'/resources/dist/.vite/manifest.json';
    $manifestBackup = $GLOBALS['conductor_dashboard_manifest_backup'] ?? null;

    if ($manifestBackup === null) {
        if (file_exists($manifestPath)) {
            unlink($manifestPath);
        }

        return;
    }

    file_put_contents($manifestPath, $manifestBackup);
});

it('renders the blade shell at the conductor path', function (): void {
    $distPath = realpath(__DIR__.'/../..').'/resources/dist/.vite';
    if (! is_dir($distPath)) {
        mkdir($distPath, 0755, true);
    }

    file_put_contents($distPath.'/manifest.json', json_encode([
        'main.tsx' => [
            'file' => 'assets/main-test.js',
            'css' => ['assets/main-test.css'],
        ],
    ]));

    $this->get('/conductor')
        ->assertSuccessful()
        ->assertSee('<div id="app"></div>', false)
        ->assertSee('assets/main-test.css', false)
        ->assertSee('assets/main-test.js', false);
});

it('shows an error message when assets are not published', function (): void {
    $manifestPath = realpath(__DIR__.'/../..').'/resources/dist/.vite/manifest.json';
    $backup = null;

    if (file_exists($manifestPath)) {
        $backup = file_get_contents($manifestPath);
        unlink($manifestPath);
    }

    $response = $this->get('/conductor')->assertSuccessful();
    expect($response->getContent())->toContain('conductor:publish');

    if ($backup !== null) {
        file_put_contents($manifestPath, $backup);
    }
});

it('passes config values to the SPA via window.__conductor__', function (): void {
    Config::set('conductor.path', 'conductor');

    $distPath = realpath(__DIR__.'/../..').'/resources/dist/.vite';
    if (! is_dir($distPath)) {
        mkdir($distPath, 0755, true);
    }

    file_put_contents($distPath.'/manifest.json', json_encode([
        'main.tsx' => [
            'file' => 'assets/main-test.js',
            'css' => [],
        ],
    ]));

    $this->get('/conductor')
        ->assertSuccessful()
        ->assertSee('window.__conductor__', false)
        ->assertSee('"conductor"', false);
});
