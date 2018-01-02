<?php

namespace yii\phpunit;

use yii\db\Command;
use yii\helpers\Console;
use yii\helpers\VarDumper;
use yii\log\Logger;

/**
 * Class TestLogger
 * @package yii\phpunit
 */
class TestLogger extends Logger
{
    /**
     * Overridden to prevent register_shutdown_function
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     * @inheritdoc
     */
    public function log($message, $level, $category = 'application')
    {
        if (!in_array($level, [
            Logger::LEVEL_INFO,
            Logger::LEVEL_WARNING,
            Logger::LEVEL_ERROR,
        ])) {
            return;
        }

        if (strpos($category, Command::class) === 0) {
            return; // don't log queries
        }

        if ($message instanceof \yii\base\Exception) {
            $message = $message->__toString();
        }

        $color = Console::FG_GREY;
        if ($level === Logger::LEVEL_ERROR) {
            $color = Console::FG_RED;
        } elseif ($level === Logger::LEVEL_WARNING) {
            $color = Console::FG_YELLOW;
        }

        $text = Console::ansiFormat("[$category] " . VarDumper::export($message), [$color]);
        Console::output($text);
    }
}
