<?php

namespace ether\graph\types;

use ether\graph\traits\GraphArgs;
use yii\graphql\base\GraphQLType;
use yii\graphql\GraphQL;

class Type extends GraphQLType
{
    use GraphArgs;

    public $name;
    public $description;
    public $fields = [];

    public function interfaces()
    {
        return [GraphQL::type(NodeType::class)];
    }

    public function __construct()
    {
        $this->attributes = [
            'name' => $this->name,
            'description' => $this->description,
        ];
    }

    public function fields()
    {
        $args = $this->args();

        $args['nodeId'] = GraphType::string();

        return $args;
    }
}
