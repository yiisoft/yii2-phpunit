<?php

namespace yii\phpunit;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

use Yii;

use yii\base\Application;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;

use yii\db\ActiveRecord;
use yii\db\ActiveRecordInterface;
use yii\db\Connection;
use yii\db\Transaction;

use yii\di\Container;

use yii\log\Logger;

use yii\mail\MailerInterface;

use yii\web\IdentityInterface;
use yii\web\UploadedFile;

use yii\test\FixtureTrait;

/**
 * Class TestCase
 * @package yii\phpunit
 */
class TestCase extends PHPUnitTestCase
{
    use FixtureTrait;

    /** @var Container */
    protected $container;

    /** @var Application */
    protected $app;

    /**
     * Wrap all database connection inside a transaction and roll it back after the test.
     * @var bool
     */
    protected $isTransactional = true;

    /**
     * Cleanup fixtures after the test
     * @var bool
     */
    protected $cleanup = true;

    /** @var Transaction */
    private $transaction;

    /**
     * Authorizes user on a site without submitting login form.
     * Use it for fast pragmatic authorization in functional tests.
     *
     * ```php
     * <?php
     * // User is found by id
     * $this->loginAs(1);
     *
     * // User object is passed as parameter
     * $admin = \app\models\User::findByUsername('admin');
     * $this->loginAs($admin);
     * ```
     * Requires `user` component to be enabled and configured.
     *
     * @param $user
     * @throws InvalidConfigException
     */
    public function loginAs($user)
    {
        if (!Yii::$app->has('user')) {
            throw new InvalidConfigException('User component is not loaded');
        }
        if ($user instanceof IdentityInterface) {
            $identity = $user;
        } else {
            $identityClass = Yii::$app->user->identityClass;
            /** @see IdentityInterface::findIdentity() */
            $identity = call_user_func([$identityClass, 'findIdentity'], $user);
        }
        Yii::$app->user->login($identity);
    }


    /**
     * Inserts record into the database.
     *
     * ``` php
     * <?php
     * $user_id = $thid->haveRecord(app\models\User::class, ['name' => 'Davert']);
     * ?>
     * ```
     *
     * @param $model
     * @param array $attributes
     * @return mixed
     */
    public function haveRecord($model, $attributes = [])
    {
        $record = new $model;
        if (!$record instanceof ActiveRecord) {
            throw new InvalidParamException("{$model} have to implement " . ActiveRecord::class);
        }
        $record->setAttributes($attributes, false);
        $res = $record->save(false);
        if (!$res) {
            $this->fail("Record $model was not saved");
        }
        return $record->primaryKey;
    }

    /**
     * Checks that record exists in database.
     *
     * ``` php
     * $this->seeRecord(app\models\User::class, ['name' => 'davert']);
     * ```
     *
     * @param $model
     * @param array $attributes
     */
    public function seeRecord($model, $attributes = [])
    {
        $record = $this->findRecord($model, $attributes);
        if (!$record) {
            $this->fail("Couldn't find $model with " . json_encode($attributes));
        }
    }

    /**
     * Checks that record does not exist in database.
     *
     * ``` php
     * $this->dontSeeRecord(app\models\User::class, ['name' => 'davert']);
     * ```
     *
     * @param $model
     * @param array $attributes
     */
    public function dontSeeRecord($model, $attributes = [])
    {
        $record = $this->findRecord($model, $attributes);
        if ($record) {
            $this->fail("Unexpectedly managed to find $model with " . json_encode($attributes));
        }
    }

    /**
     * Retrieves record from database
     *
     * ``` php
     * $category = $this->grabRecord(app\models\User::class, ['name' => 'davert']);
     * ```
     *
     * @param $model
     * @param array $attributes
     * @return ActiveRecordInterface|null
     */
    public function grabRecord($model, $attributes = [])
    {
        return $this->findRecord($model, $attributes);
    }

    /**
     * Checks that email is sent.
     *
     * ```php
     * <?php
     * // check that at least 1 email was sent
     * $this->seeEmailIsSent();
     *
     * // check that only 3 emails were sent
     * $this->seeEmailIsSent(3);
     * ```
     *
     * @param int $num
     * @throws InvalidConfigException
     */
    public function seeEmailIsSent($num = null)
    {
        if ($num === null) {
            $this->assertNotEmpty($this->grabSentEmails(), 'emails were sent');
            return;
        }
        $this->assertCount($num, $this->grabSentEmails(), 'number of sent emails is equal to ' . $num);
    }

    /**
     * Checks that no email was sent
     *
     * @throws InvalidConfigException
     */
    public function dontSeeEmailIsSent()
    {
        $this->seeEmailIsSent(0);
    }

    /**
     * Returns array of all sent email messages.
     * Each message implements `yii\mail\MessageInterface` interface.
     * Useful to perform additional checks using `Asserts` module:
     *
     * ```php
     * <?php
     * $this->seeEmailIsSent();
     * $messages = $this->grabSentEmails();
     * $this->assertEquals('admin@site.com', $messages[0]->getTo());
     * ```
     *
     * @return array
     * @throws InvalidConfigException
     */
    public function grabSentEmails()
    {
        $mailer = $this->app->has('mailer') ? $this->app->mailer : null;

        if (!$mailer instanceof TestMailer) {
            throw new InvalidConfigException("Mailer module is not mocked, can't test emails");
        }

        return $mailer->getSentMessages();
    }

    /**
     * Returns last sent email:
     *
     * ```php
     * <?php
     * $this->seeEmailIsSent();
     * $message = $this->grabLastSentEmail();
     * $this->assertEquals('admin@site.com', $message->getTo());
     * ```
     * @throws InvalidConfigException
     */
    public function grabLastSentEmail()
    {
        $this->seeEmailIsSent();
        $messages = $this->grabSentEmails();
        return end($messages);
    }

    /**
     * @throws InvalidConfigException
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (empty(Yii::$container)) {
            Yii::$container = Yii::createObject(Container::class);
        }
        $this->container = Yii::$container;

        $configFile = Yii::getAlias('@configFile');
        if (!is_file($configFile)) {
            throw new InvalidConfigException(
                "The application config file does not exist: " . $configFile
            );
        }

        $config = require($configFile);

        $this->persistDb($config);
        $this->mockMailer($config);

        if (!array_key_exists('class', $config)) {
            $config['class'] = \yii\console\Application::class;
        };

        $this->app = Yii::createObject($config);

        $this->loadFixtures();

        if ($this->isTransactional && $this->app->has('db')) {
            $this->transaction = $this->app->db->beginTransaction();
        }

        Yii::setLogger($this->getLogger());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        \Yii::setLogger(null);

        $mailer = $this->app->mailer;
        if ($mailer instanceof TestMailer) {
            $mailer->reset();
        }

        UploadedFile::reset();
        Event::offAll();

        if ($this->isTransactional && $this->transaction instanceof Transaction) {
            $this->transaction->rollBack();
        }

        if ($this->cleanup) {
            $this->unloadFixtures();
        }

        $mailer = $this->app->has('mailer') ? $this->app->mailer : null;
        if ($mailer instanceof TestMailer) {
            $mailer->reset();
        }
    }

    /**
     * @param array $config
     *
     * @throws InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    private function persistDb(array &$config)
    {
        if ($this->container->hasSingleton(Connection::class)) {
            $config['components']['db'] = $this->container->get(Connection::class);
        } elseif (isset($config['components']['db'])) {
            $db = Yii::createObject($config['components']['db']);
            $this->container->setSingleton(Connection::class, $db);
            $this->persistDb($config);
        }
    }

    /**
     * @param array $config
     *
     * @throws InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    private function mockMailer(array &$config)
    {
        if ($this->container->hasSingleton(MailerInterface::class)) {
            $config['components']['mailer'] = $mailer = $this->container->get(MailerInterface::class);
            return;
        }

        // options that make sense for mailer mock
        $allowedOptions = [
            'htmlLayout',
            'textLayout',
            'messageConfig',
            'messageClass',
            'useFileTransport',
            'fileTransportPath',
            'fileTransportCallback',
            'view',
            'viewPath',
        ];
        $mailerConfig = [
            'class' => TestMailer::class,
        ];

        if (isset($config['components']['mailer']) && is_array($config['components']['mailer'])) {
            foreach ($config['components']['mailer'] as $name => $value) {
                if (in_array($name, $allowedOptions, true)) {
                    $mailerConfig[$name] = $value;
                }
            }
        }

        $this->container->setSingleton(MailerInterface::class, $mailerConfig);
        $this->mockMailer($config);
    }

    /**
     * @return Logger
     *
     * @throws InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    private function getLogger()
    {
        if ($this->container->hasSingleton(Logger::class)) {
            return $this->container->get(TestLogger::class);
        }

        $this->container->setSingleton(Logger::class, TestLogger::class);
        return $this->getLogger();
    }

    /**
     * @param $model
     * @param array $attributes
     * @return ActiveRecordInterface|null
     */
    private function findRecord($model, $attributes = [])
    {
        return call_user_func([$model, 'find'])
            ->andWhere($attributes)
            ->one();
    }
}
