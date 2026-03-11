<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Commands;

use Illuminate\Console\Command;

final class PublishCommand extends Command
{
    protected $signature = 'conductor:publish {--force : Overwrite existing assets}';

    protected $description = 'Publish Conductor frontend assets';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'conductor-assets',
            '--force' => (bool) $this->option('force'),
        ]);

        $this->info('Conductor assets published successfully.');

        return self::SUCCESS;
    }
}
