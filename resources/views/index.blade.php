@php
    $manifestExists = file_exists($conductorManifestPath);
    $entryFile = null;
    $entryCss = [];

    if ($manifestExists) {
        $manifest = json_decode((string) file_get_contents($conductorManifestPath), true);

        if (is_array($manifest)) {
            $entry = $manifest['resources/js/main.tsx'] ?? $manifest['main.tsx'] ?? null;

            if (is_array($entry)) {
                $entryFile = is_string($entry['file'] ?? null) ? $entry['file'] : null;
                $entryCss = is_array($entry['css'] ?? null) ? $entry['css'] : [];
            }
        }
    }

    $assetsAvailable = $manifestExists && $entryFile !== null;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Conductor</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if ($assetsAvailable)
        @foreach ($entryCss as $cssFile)
            <link rel="stylesheet" href="/vendor/conductor/{{ $cssFile }}">
        @endforeach
    @endif
</head>

<body>
    <script>
        window.__conductor__ =            {!! json_encode([
    'basePath' => (string) config('conductor.path', 'conductor'),
]) !!};
    </script>
    <div id="app"></div>
    @if (!$assetsAvailable)
        <div style="font-family:sans-serif;padding:2rem;color:#ef4444;">
            <strong>Conductor assets not published.</strong>
            Run: <code>php artisan conductor:publish</code>
        </div>
    @else
        <script type="module" src="/vendor/conductor/{{ $entryFile }}"></script>
    @endif
</body>

</html>