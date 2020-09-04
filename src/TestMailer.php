<?php

namespace Horat1us\Yii\PHPUnit;

use yii\mail\BaseMailer;
use yii\mail\MessageInterface;

/**
 * Class TestMailer
 * @package Horat1us\Yii\PHPUnit
 */
class TestMailer extends BaseMailer
{
    /** @var MessageInterface[] */
    private $sentMessages = [];

    /**
     * @inheritdoc
     */
    protected function sendMessage($message)
    {
        $this->sentMessages[] = $message;
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function saveMessage($message)
    {
        return $this->sendMessage($message);
    }

    public function getSentMessages()
    {
        return $this->sentMessages;
    }

    public function reset()
    {
        $this->sentMessages = [];
    }
}
