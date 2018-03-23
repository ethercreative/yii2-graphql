<?php

namespace ether\graph\traits;

use yii\helpers\Inflector;

trait ConvertVariableType
{
    public function underscoreToVariable($data)
    {
        if (!is_array($data)) return $data;

        $_data = [];

        foreach ($data as $key => $value)
        {
            $key = $this->keyToVariable($key);
            $_data[$key] = $this->underscoreToVariable($value);
        }

        return $_data;
    }

    public function keyToVariable($key)
    {
        $key = explode('.', $key);

        foreach ($key as &$value)
            $value = Inflector::variablize($value);

        return join('.', $key);
    }

    public function variableToUnderscore($data)
    {
        if (!is_array($data)) return $data;

        $_data = [];

        foreach ($data as $key => $value)
        {
            $key = $this->keyToUnderscore($key);
            $_data[$key] = $this->variableToUnderscore($value);
        }

        return $_data;
    }

    public function keyToUnderscore($key)
    {
        $key = explode('.', $key);

        foreach ($key as &$value)
            $value = Inflector::underscore($value);

        return join('.', $key);
    }
}
