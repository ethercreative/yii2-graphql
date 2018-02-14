<?php

namespace ether\graph\types;

use ether\graph\traits\GraphArgs;
use GraphQL\Type\Definition\Type;
use yii\graphql\GraphQL;

// class EdgeType extends \GraphQL\Type\Definition\ObjectType
class EdgeType extends \yii\graphql\base\GraphQLType
// class EdgeType extends Type
{
    use GraphArgs;

    public $name;
    public $description;
    public $node;

    public function __construct()
    {
        $this->attributes = [
            'name' => $this->name,
            'description' => $this->description,
        ];
    }

    public function interfaces()
    {
        return [];
    }

    public function fields()
    {
        return [
            'node' => [
                'type' => GraphQL::type($this->node),
                'resolve' => function ($root, $args)
                {
                    // die('<pre>'.print_r('variable', 1).'</pre>');
                    return $root;
                },
            ],
        ];
    }

    // public function fields()
    // {
    //     $fields = $this->args();

    //     // die('<pre>'.print_r($fields['node'], 1).'</pre>');

    //     $fields['node']->resolveFieldFn = function()
    //     {
    //         die('<pre>'.print_r('resolve node', 1).'</pre>');
    //     };

    //     // die('<pre>'.print_r($fields['node'], 1).'</pre>');

    //     return $fields;
    // }
}
