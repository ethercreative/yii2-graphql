<?php

namespace ether\graph\types;

use ether\graph\traits\GraphArgs;
use GraphQL\Type\Definition\Type as GraphType;
use yii\graphql\base\GraphQLType;
use yii\graphql\GraphQL;
use yii\helpers\ArrayHelper;

class Type extends GraphQLType
{
    use GraphArgs;

    public $name;
    public $description;
    public $fields = [];
    public static $with = [];

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

        $args['nodeId'] = [
            'type' => GraphType::string(),
            'resolve' => function($root)
            {
                return ArrayHelper::getValue($root, 'nodeData.id');
            }
        ];

        return $args;
    }
}
