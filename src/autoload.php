<?php

if (!class_exists(\Yii::class)) {
    $reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
    $vendorDir = dirname(dirname($reflection->getFileName()));
    require_once($vendorDir . '/yiisoft/yii2/Yii.php');
}
