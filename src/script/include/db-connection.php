<?php

declare(strict_types = 1);

$dsnString = sprintf('mysql:host=db;dbname=%s', getenv('DATABASE_NAME'));
$pdo = new PDO(
    $dsnString,
    'root',
    getenv('DATABASE_PASSWORD'),
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]
);
