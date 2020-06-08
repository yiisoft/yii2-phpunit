<?php

try {
    $dotEnv = \Dotenv\Dotenv::createImmutable(getcwd(), ['.env', '.env.example']);
    $dotEnv->load();
} catch (\Dotenv\Exception\InvalidPathException $exception) {
    echo $exception->getMessage() . PHP_EOL;
}

if (!class_exists(\Yii::class)) {
    $reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
    $vendorDir = dirname(dirname($reflection->getFileName()));
    require_once($vendorDir . '/yiisoft/yii2/Yii.php');
}
if (!defined('YII_ENV')) {
    define('YII_ENV', getenv('YII_ENV') || 'test');
}
if (!defined('YII_DEBUG')) {
    define('YII_DEBUG', getenv('YII_DEBUG') || 'debug');
}
