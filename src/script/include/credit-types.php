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
        'name' => 'christmas',
        'expiration' => null,
        'expirate_at' => new DateTimeImmutable('2025-01-31 00:00:00'),
        'priority' => 2,
    ],
    [
        'name' => 'month credits',
        'expiration' => 30,
        'expirate_at' => null,
        'priority' => 3,
    ],
    [
        'name' => 'marketing with ending',
        'expiration' => 90,
        'expirate_at' => new DateTimeImmutable('2025-10-20 00:00:00'),
        'priority' => 4,
    ],
    [
        'name' => 'expired credit',
        'expiration' => null,
        'expirate_at' => new DateTimeImmutable('2022-01-01 00:00:00'),
        'priority' => 5,
    ],
];