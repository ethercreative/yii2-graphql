<?php
namespace ether\graph\types;

use GraphQL\Error\Error;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\CustomScalarType;
use GraphQL\Utils;

class DateTimeType extends CustomScalarType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'DateTime',
            'serialize' => [$this, 'serialize'],
            'parseValue' => [$this, 'parseValue'],
            'parseLiteral' => [$this, 'parseLiteral'],
        ]);
    }

    public function serialize($value)
    {
        if (is_string($value))
            return $value;
        elseif (is_array($value))
            return substr($value['date'], 0, 19);
        elseif ($value InstanceOf \DateTime)
            return $value->format('Y-m-d H:i:s');
        elseif ($value InstanceOf \yii\db\Expression)
            return date('Y-m-d H:i:s');

        return null;
    }
}
