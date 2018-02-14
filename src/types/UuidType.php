<?php
namespace ether\graph\types;

use GraphQL\Error\Error;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\CustomScalarType;
use GraphQL\Utils;

class UuidType extends CustomScalarType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'UUID',
            'description' => 'UUID v4.',
            'serialize' => [$this, 'serialize'],
            'parseValue' => [$this, 'parseValue'],
            'parseLiteral' => [$this, 'parseLiteral'],
        ]);
    }

    public function serialize($value)
    {
        return $value;
    }

    public function parseValue($value)
    {
        return $value;
    }

    public function parseLiteral($value)
    {
        return $value->value;
    }
}
