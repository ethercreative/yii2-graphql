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
use ether\graph\traits\CloneQuery;
use ether\graph\traits\GatherWith;

class ConnectionType extends \yii\graphql\base\GraphQLType
{
    use GraphArgs;
    use ResolveConnection;
    use ResolveQuery;
    use CloneQuery;
    use GatherWith;

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
                'resolve' => function ($root, $args, $context, ResolveInfo $info) {
                    if ($this->hasRoot($root)) {
                        return $root;
                    }

                    $this->nodeIds($root);
                    $this->resolveQuery($root->modelClass, $root, $args);
                    $this->with($root, $info);
                    $this->findBySql($root);

                    return $root->all();
                },
            ],
            'pageInfo' => [
                'type' => Type::nonNull(GraphQL::type(PageInfoType::class)),
                'resolve' => function ($root) {
                    return $root;
                },
            ],
            'totalCount' => [
                'type' => Type::nonNull(Type::int()),
                'resolve' => function ($root) {
                    if (is_array($root) && !empty($root['query'])) {
                        $root = $root['query'];
                    } elseif ($this->hasRoot($root)) {
                        return count($root);
                    }

                    $query = clone $root;

                    $query->limit(null)->with([])->orderBy(null);

                    $this->findBySql($query);

                    return $query->count();
                },
            ],
        ];
    }

    private function hasRoot(&$root)
    {
        if (is_array($root)) {
            if (ArrayHelper::getValue($root, 'query') || ArrayHelper::keyExists('nodes', $root) || ArrayHelper::keyExists('models', $root)) {
                $return = null;

                if (ArrayHelper::keyExists('nodes', $root)) {
                    $return = $root['nodes'];
                }

                if (ArrayHelper::keyExists('models', $root)) {
                    $return = $root['models'];
                }

                if ($return !== null) {
                    if ($limit = ArrayHelper::getValue($root, 'args.first', 50)) {
                        if (count($return) > $limit) {
                            $return = array_slice($return, 0, $limit);
                        }
                    }

                    $root = $return;
                    return true;
                }

                if (!empty($root['query'])) {
                    $root = $root['query'];
                }
            } else {
                return true;
            }
        }

        return false;
    }

    private function nodeIds(&$query)
    {
        if (!$query->select) {
            $query->addSelect("{$query->modelClass::tableName()}.*");
        }

        $gettingNodeId = false;

        foreach ((array) $query->select as $where) {
            if (!($where instanceof Expression)) {
                continue;
            }

            if (strpos($where->expression, '_node_id') !== false) {
                $gettingNodeId = true;
                $select = $where;
                break;
            }
        }

        if (!$gettingNodeId) {
            $orderByString = null;

            if ($query->orderBy) {
                $orderByString = 'order by ' . array_keys($query->orderBy)[0];
            }

            $query->addSelect(new Expression("ENCODE(CONVERT_TO((row_number() over ({$orderByString}))::text, 'UTF-8'), 'base64') as _node_id"));
        }
    }

    private function findBySql(&$query)
    {
        if (!ArrayHelper::getValue($query->having, '_findBySql')) {
            return clone $query;
        }

        $having = $query->having;

        unset($having['_findBySql']);

        $query->having = $having;

        $query = $this->cloneQuery($query);

        return $query;
    }
}
