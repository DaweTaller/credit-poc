<?php

$env = parse_ini_file('.env');

$dsnString = sprintf('mysql:host=%s;dbname=%s', $env['DEV_IP'], $env['DATABASE_NAME']);
$pdo = new PDO(
    $dsnString,
    'root',
    $env['DATABASE_PASSWORD'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]
);
