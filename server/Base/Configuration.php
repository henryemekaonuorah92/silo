<?php

namespace Silo\Base;

use Doctrine\Common\Cache\Cache;
use Pimple\Container;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;


class Configuration extends \SplObjectStorage
{
    const CACHE_KEY = "_SILO_BASE_CONFIGURATION_KEY_";

    private $app;

    private $cache;

    private $key;

    private $validator;

    public function __construct(Container $app, Cache $cache, $key = self::CACHE_KEY)
    {
        $this->app = $app;
        $this->cache = $cache;
        $this->key = $key;

        $this->validator = Validation::createValidator();

        // Inject configuration values from cache
        $this->injectFromCache();
    }

    private function validate($value, $validation)
    {
        if (is_null($validation)) {
            return;
        }
        $violations = $this->validator->validate($value, $validation);
        if (0 == count($violations)) {
            return;
        } else {
            $errorArray = [];
            foreach($violations as $error)
            {
                $errorArray[] = $error->getPropertyPath().' '.$error->getMessage();
            }
            throw new \Exception(implode(PHP_EOL, $errorArray));
        }
    }

    /**
     * Declare a tracked configuration value. Pretty similar to $app[$name] = $defaultValue
     * $defaultValue is only used when no values has been previously set in the configuration
     * cache.
     *
     * @param $name
     * @param $defaultValue
     * @param null $validation
     * @throws \Exception
     */
    public function has($name, $defaultValue, $validation = null)
    {
        $this->attach(new ConfigurationKey($name, $defaultValue, $validation));
        if (isset($this->app[$name])) {
            $this->validate($this->app[$name], $validation);
        } else {
            $this->validate($defaultValue, $validation);
            $this->app[$name] = $defaultValue;
        }
    }

    public function set($name, $value)
    {
        $configKey = $this->getConfigurationKeyByName($name);
        if (!$configKey) {
            throw new \Exception("$name has not been declared as a tracked configuration key");
        }
        $this->validate($value, $configKey->getValidation());
        $this->app[$name] = $value;
    }

    public function save()
    {
        $this->cache->save($this->key, $this->getAll());
    }

    private function getConfigurationKeyByName($name)
    {
        /** @var ConfigurationKey[] $data */
        $data = iterator_to_array($this);
        foreach ($data as $configKey) {
            if ($configKey->getName() === $name) {
                return $configKey;
            }
        }

        return null;
    }

    /**
     * @return array All tracked configuration values from the container
     */
    public function getAll()
    {
        $app = $this->app;
        $data = iterator_to_array($this);
        $result = [];
        array_walk($data, function(ConfigurationKey $config) use ($app, &$result){
            $name = $config->getName();
            $result[$name] = $app[$name];
        });
        return $result;
    }

    public function injectFromCache()
    {
        $cacheValues = $this->cache->fetch($this->key);
        if (!empty($cacheValues)) {
            foreach ($cacheValues as $name => $value) {
                // Do not overwrite previously set values
                if (!isset($this->app[$name])) {
                    $this->app[$name] = $value;
                }
            }
        }

    }
}
