<?php

declare(strict_types=1);

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('conductor classes use strict types')
    ->expect('HotReloadStudios\\Conductor')
    ->toUseStrictTypes();

arch('conductor classes are final')
    ->expect('HotReloadStudios\\Conductor')
    ->classes()
    ->toBeFinal();
