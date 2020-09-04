<?php declare(strict_types=1);

$dotEnv = \Dotenv\Dotenv::createImmutable(getcwd(), ['.env', '.env.example']);
$dotEnv->safeLoad();
