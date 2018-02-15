<?php

namespace ether\graph\models;

use yii\helpers\Inflector;
use yii\helpers\ArrayHelper;

class DataModel extends \yii\base\DynamicModel
{
    public function __call($name, $arguments)
    {
        if (strpos($name, 'get') === false) return parent::__call($name, $arguments);

        $name = Inflector::variablize(str_replace('get', '', $name));

        return ArrayHelper::getValue($this->attributes, $name, []);
    }
}
