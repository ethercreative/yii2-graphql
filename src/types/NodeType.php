<?php

namespace ether\graph\types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use yii\graphql\GraphQL;

class NodeType extends \yii\graphql\base\GraphQLInterfaceType
{
    protected $attributes = [
        'name' => 'Node'
    ];

    public function fields()
    {
        return [
            'id' => GraphQL::type(UuidType::class),
        ];
    }
}
