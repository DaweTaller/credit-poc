<?php

declare(strict_types=1);

$creditTypes = [
    [
        'name' => 'refund',
        'expiration' => null,
        'expirate_at' => null,
        'priority' => 1,
    ],
    [
        'name' => 'marketing',
        'expiration' => 30,
        'expirate_at' => null,
        'priority' => 2,
    ],
    [
        'name' => 'christmas',
        'expiration' => 60,
        'expirate_at' => new DateTimeImmutable('2025-01-31 00:00:00'),
        'priority' => 3,
    ],
];