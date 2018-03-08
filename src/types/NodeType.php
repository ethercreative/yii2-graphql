<?php

namespace ether\graph\types;

use yii\graphql\base\GraphQLInterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use yii\graphql\GraphQL;

class NodeType extends GraphQLInterfaceType
{
    protected $attributes = [
        'name' => 'Node'
    ];

    public function fields()
    {
        return [
            'nodeId' => Type::string(),
        ];
    }

    public function resolveType($object)
    {
        return GraphQL::type($object->graphType, true);
    }
}
