<?php

namespace Silo\Base;

class ConfigurationKey
{
    private $name;

    private $defaultValue;

    private $validation;

    public function __construct($name, $defaultValue, $validation)
    {
        $this->name = $name;
        $this->defaultValue = $defaultValue;
        $this->validation = $validation;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getValidation()
    {
        return $this->validation;
    }
}
