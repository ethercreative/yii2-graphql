<?php

namespace ether\graph\types;

use ether\graph\traits\GraphArgs;
use ether\graph\traits\ResolveConnection;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use yii\graphql\GraphQL;

class ConnectionType extends \yii\graphql\base\GraphQLType
{
    use GraphArgs;
    use ResolveConnection;

    public $name;
    public $description;
    public $edges;

    public static $with = [];

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

    public function args()
    {
        return [
            'first' => Type::int(),
            'after' => Type::string(),
            'last' => Type::int(),
            'before' => Type::string(),
            'orderBy' => Type::string(),
            'query' => Type::string(),
            'filter' => Type::string(),
        ];
    }

    public function fields()
    {
        return [
            'edges' => [
                'type' => Type::listOf(GraphQL::type($this->edges)),
                'resolve' => function($root, $args, $context, ResolveInfo $info)
                {
                    if (is_array($root))
                        return $root;

                    if (!$root->orderBy)
                        $root->orderBy('created_at');

                    return $root->all();
                },
            ],
            'pageInfo' => [
                'type' => Type::nonNull(GraphQL::type(PageInfoType::class)),
                'resolve' => function($root)
                {
                    return $root;
                },
            ],
            'totalCount' => [
                'type' => Type::nonNull(Type::int()),
                'resolve' => function($root)
                {
                    if (is_array($root))
                        return count($root);

                    $query = clone $root;

                    return $query->limit(null)->with([])->count();
                },
            ],
        ];
    }
}
