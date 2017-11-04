<?php
namespace Codeages\PhalconBiz;

use Codeception\Module as CodeceptionModule;
use Codeception\Configuration;
use Codeages\Biz\Framework\Utility\Env;
use Codeages\Biz\Framework\Context\Biz;
use Codeception\Exception\ModuleRequireException;

class BizCodeceptionModule extends CodeceptionModule
{
    protected $bizConfig;

    /**
     * @var Biz
     */
    protected $biz;

    public $config = [
        'class' => '\Biz\AppBiz',
        'env_path' => 'env.testing.php',
        'config_path' => 'config/biz.php',
    ];

    public function _initialize()
    {
        $envFilePath = Configuration::projectDir() . $this->config['env_path'];
        if (!file_exists($envFilePath)) {
            throw new ModuleRequireException(
                __CLASS__,
                "Biz env file not found in {$envFilePath} \n\n".
                "Please specify path to bootstrap file using `env_path` config option\n \n"
            );
        }
        Env::load(require $envFilePath);

        $bizConfigFilePath = Configuration::projectDir() . $this->config['config_path'];
        if (!file_exists($bizConfigFilePath)) {
            throw new ModuleRequireException(
                __CLASS__,
                "Biz config file not found in {$bizConfigFilePath} \n\n".
                "Please specify path to bootstrap file using `config_path` config option\n \n"
            );
        }
        $this->bizConfig = require $bizConfigFilePath;

        if (!class_exists($this->config['class'])) {
            throw new ModuleRequireException(
                __CLASS__,
                "Biz class {$this->config['class']} \n\n".
                "Please specify biz class using `class` config option\n\n"
            );
        }
    }

    public function biz()
    {
        return $this->biz;
    }

    public function createService($service)
    {
        return $this->biz->service($service);
    }

    public function createDao($dao)
    {
        return $this->biz->dao($dao);
    }

    public function _before(\Codeception\TestInterface $test)
    {
        $this->biz = new $this->config['class']($this->bizConfig);
        $this->biz->boot();

        if (isset($this->biz['db'])) {
            $this->biz['db']->beginTransaction();
        }

        if (isset($this->biz['redis'])) {
            $this->biz['redis']->flushDB();
        }

        parent::_before($test);
    }

    public function _after(\Codeception\TestInterface $test)
    {

        if (isset($this->biz['db'])) {
            $this->biz['db']->rollBack();
        }

        if (isset($this->biz['redis'])) {
            $this->biz['redis']->flushDB();
        }

        unset($this->biz);
        parent::_after($test);
    }

    public function seeHello()
    {
        echo 'hello';
    }
}