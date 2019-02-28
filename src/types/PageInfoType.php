<?php

namespace ether\graph\types;

use GraphQL\Type\Definition\Type as GraphType;
use GraphQL\Type\Definition\ResolveInfo;
use yii\helpers\ArrayHelper;
use yii\db\Expression;
use ether\graph\traits\CloneQuery;

class PageInfoType extends Type
{
    use CloneQuery;

    public $name = 'PageInfo';

    public function interfaces()
    {
        return [];
    }

    public function fields()
    {
        return [
            'endCursor' => [
                'type' => GraphType::string(),
                'description' => 'When paginating forwards, the cursor to continue.',
                'resolve' => function($root, $args)
                {
                    if (!$root) return null;

                    if (is_array($root))
                    {
                        if ($models = ArrayHelper::getValue($root, 'models'))
                        {
                            if ($limit = ArrayHelper::getValue($root, 'args.first', 50))
                            {
                                if (count($models) > $limit)
                                    $models = array_slice($models, 0, $limit);
                            }

                            $models = array_reverse($models);
                            return ArrayHelper::getValue($models, '0.nodeId');
                        }
                        else
                        {
                            $root = array_reverse($root);
                            return ArrayHelper::getValue($root, '0.nodeId');
                        }
                    }

                    $query = $this->cloneQuery($root);

                    $limit = $query->limit;
                    $offset = $query->offset;

                    $this->findBySql($query);

                    $tableName = $query->modelClass::tableName();

                    $select = ["{$tableName}.id"];

                    $gettingNodeId = false;

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

                    $query
                        // ->select($select)
                        ->with([])
                        ->limit(1)
                        ->offset($offset + $limit - 1);

                    $model = $query->one();

                    return ArrayHelper::getValue($model, '_node_id');
                }
            ],
            'hasNextPage' => [
                'type' => GraphType::nonNull(GraphType::boolean()),
                'description' => 'When paginating forwards, are there more items?',
                'resolve' => function($root, $args, $context, ResolveInfo $resolve)
                {
                    if (getenv('GRAPH_PAGE_INFO_FORCE_NEXT_PAGE'))
                        return true;

                    if (is_array($root) || !is_object($root))
                    {
                        if (array_key_exists('models', $root))
                        {
                            $limit = ArrayHelper::getValue($root, 'args.first', 50); 
                            return count($root['models']) > $limit;
                        }
                        else
                        {
                            return true;
                        }
                    }

                    $query = $this->cloneQuery($root);
                    $limit = $query->limit;
                    $offset = $query->offset;

                    $this->findBySql($query);

                    $offset += $limit;

                    $tableName = $query->modelClass::tableName();

                    $select = ["{$tableName}.id"];

                    $query
                        ->select($select)
                        ->with([])
                        ->limit(1)
                        ->offset($offset);

                    return (bool) $query->one();
                }
            ],
            'hasPreviousPage' => [
                'type' => GraphType::boolean(),
                'description' => 'When paginating backwards, are there more items?',
                'resolve' => function($root)
                {
                    return false;
                }
            ],
            'startCursor' => [
                'type' => GraphType::string(),
                'description' => 'When paginating backwards, the cursor to continue.',
                'resolve' => function($root)
                {
                    if (is_array($root))
                        return ArrayHelper::getValue($root, '0.nodeId');

                    $query = clone $root;

                    $this->findBySql($query);

                    $tableName = $query->modelClass::tableName();

                    $select = ["{$tableName}.id"];

                    foreach ($query->select as $where)
                    {
                        if (!($where InstanceOf Expression))
                            continue;

                        if (strpos($where->expression, '_node_id') !== false)
                        {
                            $select = $where;
                            break;
                        }
                    }

                    $query->limit(1)->with([])->select($select);
                    $model = $query->one();

                    if ($model)
                        return $model->nodeId;

                    return null;
                }
            ],
        ];
    }

    private function findBySql(&$query)
    {
        if (!ArrayHelper::getValue($query->having, '_findBySql'))
            return;

        $having = $query->having;

        unset($having['_findBySql']);

        $query->having = $having;

        $query = $this->cloneQuery($query);

        return;
    }
}
