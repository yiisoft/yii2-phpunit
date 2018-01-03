# Yii2 PhpUnit
Yii 2 PHPUnit compatibility layer and enhancements
This package includes:
- [TestLogger](./src/TestLogger.php) - will display all log messages to console
- [TestMailer](./src/TestMailer.php) - will collect all sent mails in memory
- [TestCase](./src/TestCase.php) - base TestCase, extends PHPUnit TestCase.
It will create new `\yii\console\Application` instance before each test and apply Yii2 fixtures.
- [MigrateFixture](./src/MigrateFixture.php) - Yii2 fixture that will apply migration.
Use case: your package contains migrations and you need to apply it before tests.

## Usage
- Install package and *phpunit/phpunit* as dev dependencies
```bash
composer require --dev yiisoft/yii2-phpunit phpunit/phpunit
```
- Configure your PHPUnit and create bootstrap file with alias to config:
```php
<?php
// bootstrap.php

Yii::setAlias('@configFile', 'path-to-config.php');
```
- Create your test cases that extend [yii\phpunit\TestCase](./src/TestCase.php)

## Example
See [horat1us/yii2-advanced-package](https://github.com/Horat1us/yii2-advanced-package) for details.