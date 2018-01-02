<?php

namespace yii\phpunit;

use yii\base\Module;

use yii\console\controllers\BaseMigrateController;
use yii\console\controllers\MigrateController;

use yii\test\Fixture;

/**
 * Class MigrateFixture
 * @package yii\phpunit
 */
class MigrateFixture extends Fixture
{
    /**
     * @see BaseMigrateController::$migrationPath
     */
    public $migrationPath = ['@app/migrations'];

    /**
     * @var MigrateController::$migrationNamespaces
     */
    public $migrationNamespaces = [];

    /** @var MigrateController */
    protected $controller;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->controller = new MigrateController('migrate', new Module('test-fixture'));

        $this->controller->migrationNamespaces = $this->migrationNamespaces;
        $this->controller->migrationPath = $this->migrationPath;
    }

    public function load()
    {
        $this->controller->actionUp();
    }

    public function unload()
    {
        $this->controller->actionDown();
    }
}
