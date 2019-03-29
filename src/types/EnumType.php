<?php

namespace ether\graph\types;

use yii\helpers\Inflector;

class EnumType extends \GraphQL\Type\Definition\EnumType
{
    public $name;
    public $description;
    public $values = [];

    public function __construct()
    {
        return parent::__construct([
            'name' => $this->name,
            'description' => $this->description,
            'values' => $this->getValues(),
        ]);
    }

    public function getValues()
    {
        $enum = [];

        foreach ($this->values as $key => $value) {
            if (is_numeric($key)) {
                $key = $value;
            }

            $key = strtoupper(Inflector::underscore($key));

            $enum[] = ['name' => $key, 'value' => $value];
        }

        return $enum;
    }
}
