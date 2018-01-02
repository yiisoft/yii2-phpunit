<?php

namespace yii\phpunit;

use Yii;
use yii\base\Module;

use yii\console\controllers\BaseMigrateController;
use yii\console\controllers\MigrateController;

use yii\db\MigrationInterface;
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

        $this->controller = new MigrateController('migrate', new Module('test-fixture'), [
            'db' => Yii::$app->db,
            'interactive' => false,
            'migrationNamespaces' => $this->migrationNamespaces,
            'migrationPath' => $this->migrationPath,
        ]);
    }

    public function load()
    {
        $migrations = $this->invokeControllerMethod('getNewMigrations');
        foreach ($migrations as $migration) {
            if ($migration === BaseMigrateController::BASE_MIGRATION) {
                continue;
            }
            /** @var MigrationInterface $migrationInstance */
            $migrationInstance = $this->invokeControllerMethod('createMigration', $migration);
            if ($migrationInstance->up() !== false) {
                $this->invokeControllerMethod('addMigrationHistory', $migration);
            } else {
                throw new \RuntimeException("Failed to apply $migration");
            }
        }
    }

    public function unload()
    {
        $migrations = $this->invokeControllerMethod('getMigrationHistory', null);
        foreach (array_keys($migrations) as $migration) {
            if ($migration === BaseMigrateController::BASE_MIGRATION) {
                continue;
            }
            /** @var MigrationInterface $migrationInstance */
            $migrationInstance = $this->invokeControllerMethod('createMigration', $migration);
            ob_start();
            if ($migrationInstance->down() !== false) {
                ob_end_clean();
                $this->invokeControllerMethod('removeMigrationHistory', $migration);
            } else {
                ob_end_flush();
                throw new \RuntimeException("Failed to revert $migration");
            }
        }
    }

    /**
     * @param string $method
     * @param array ...$args
     * @return mixed
     */
    protected function invokeControllerMethod($method, ...$args)
    {
        $methodInstance = new \ReflectionMethod(MigrateController::class, $method);
        $methodInstance->setAccessible(true);
        return $methodInstance->invoke($this->controller, ...$args);
    }
}
