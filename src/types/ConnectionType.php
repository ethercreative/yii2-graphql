<?php

namespace ether\graph\types;

use ether\graph\traits\GraphArgs;
use ether\graph\traits\ResolveConnection;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use yii\graphql\GraphQL;
use yii\helpers\ArrayHelper;

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

                    if (ArrayHelper::getValue($root->having, '_findBySql'))
                    {
                        $having = $root->having;

                        unset($having['_findBySql']);

                        $root->having = $having;

                        $sql = $root->createCommand()->rawSql;
                        $sql = str_replace('AND (0=1)', '', $sql);

                        return $root->modelClass::findBySql($sql)->all();
                    }

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

                    $query->limit(null)->with([])->orderBy(null);

                    if (ArrayHelper::getValue($query->having, '_findBySql'))
                    {
                        $having = $query->having;

                        unset($having['_findBySql']);

                        $query->having = $having;

                        $sql = $query->createCommand()->rawSql;
                        $sql = str_replace('AND (0=1)', '', $sql);

                        return $query->modelClass::findBySql($sql)->count();
                    }

                    return $query->count();
                },
            ],
        ];
    }
}
