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
            $_data[Inflector::variablize($key)] = $this->underscoreToVariable($value);

        return $_data;
    }

    public function variableToUnderscore($data)
    {
        if (!is_array($data)) return $data;

        $_data = [];

        foreach ($data as $key => $value)
            $_data[Inflector::underscore($key)] = $this->variableToUnderscore($value);

        return $_data;
    }
}
