<?php

namespace Horat1us\Yii\PHPUnit\Tests;

use Horat1us\Yii\PHPUnit;
use yii\base;

class TestCaseTest extends PHPUnit\TestCase
{
    public function testSetUp()
    {
        $this->assertInstanceOf(base\Application::class, $this->app);
    }
}
