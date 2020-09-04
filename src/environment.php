<?php declare(strict_types=1);

$repository = \Dotenv\Repository\RepositoryBuilder::createWithNoAdapters()
    ->addAdapter(\Dotenv\Repository\Adapter\EnvConstAdapter::class)
    ->addWriter(\Dotenv\Repository\Adapter\PutenvAdapter::class)
    ->immutable()
    ->make();

$dotEnv = \Dotenv\Dotenv::create($repository, getcwd(), ['.env', '.env.test']);
$dotEnv->safeLoad();
