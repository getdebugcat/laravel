<?php

declare(strict_types=1);

use DebugCat\Laravel\Support\Censor;

it('redacts configured fields case-insensitively', function () {
    $censor = new Censor(['password', 'token']);

    $result = $censor->scrub([
        'email' => 'user@example.com',
        'Password' => 'secret',
        'TOKEN' => 'abc123',
    ]);

    expect($result)->toBe([
        'email' => 'user@example.com',
        'Password' => '[CENSORED]',
        'TOKEN' => '[CENSORED]',
    ]);
});

it('redacts nested fields', function () {
    $censor = new Censor(['secret']);

    $result = $censor->scrub([
        'payload' => ['secret' => 'hush', 'keep' => 'me'],
    ]);

    expect($result['payload']['secret'])->toBe('[CENSORED]')
        ->and($result['payload']['keep'])->toBe('me');
});

it('uses a custom replacement string', function () {
    $censor = new Censor(['password'], '***');

    expect($censor->scrub(['password' => 'x']))->toBe(['password' => '***']);
});
