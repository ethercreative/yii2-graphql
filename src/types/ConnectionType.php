<?php

namespace ether\graph\types;

use ether\graph\traits\GraphArgs;
use ether\graph\traits\ResolveConnection;
use ether\graph\traits\ResolveQuery;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use yii\graphql\GraphQL;
use yii\helpers\ArrayHelper;
use yii\db\Expression;

class ConnectionType extends \yii\graphql\base\GraphQLType
{
    use GraphArgs;
    use ResolveConnection;
    use ResolveQuery;

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

                    $this->nodeIds($root);
                    $this->findBySql($root);

                    $this->resolveQuery($root->modelClass, $root, $args);

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

                    $this->findBySql($query);

                    return $query->count();
                },
            ],
        ];
    }

    private function nodeIds(&$query)
    {
        if (!$query->select)
            $query->addSelect('*');

        foreach ((array) $query->select as $where)
        {
            if (!($where InstanceOf Expression))
                continue;

            if (strpos($where->expression, '_node_id') !== false)
            {
                $gettingNodeId = true;
                $select = $where;
                break;
            }
        }

        if (!$gettingNodeId)
        {
            $orderByString = null;

            if ($query->orderBy)
                $orderByString = 'order by ' . array_keys($query->orderBy)[0];

            $query->addSelect(new Expression("ENCODE(CONVERT_TO((row_number() over ({$orderByString}))::text, 'UTF-8'), 'base64') as _node_id"));
        }
    }

    private function findBySql(&$query)
    {
        if (!ArrayHelper::getValue($query->having, '_findBySql'))
            return;

        $having = $query->having;

        unset($having['_findBySql']);

        $query->having = $having;

        $query = $query->modelClass::find()
            ->select($query->select)
            ->where($query->where)
            ->having($query->having)
            ->offset($query->offset)
            ->limit($query->limit);

        return;
    }
}
