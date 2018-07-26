<?php

namespace ether\graph\types;

use GraphQL\Type\Definition\Type as GraphType;
use GraphQL\Type\Definition\ResolveInfo;
use yii\helpers\ArrayHelper;
use yii\db\Expression;

class PageInfoType extends Type
{
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
                'resolve' => function($root)
                {
                    if (!$root) return null;

                    if (is_array($root))
                    {
                        $root = array_reverse($root);
                        return ArrayHelper::getValue($root, '0.nodeId');
                    }

                    $query = clone $root;
                    $limit = $query->limit;
                    $offset = $query->offset;

                    $select = ['id'];

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

                    $query
                        ->select($select)
                        ->with([])
                        ->limit(1)
                        ->offset($offset + $limit - 1);

                    $model = $query->one();

                    return ArrayHelper::getValue($model, 'nodeId');
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
                        return true;

                    $query = clone $root;
                    $limit = $query->limit;
                    $offset = $query->offset;

                    $offset += $limit;

                    $query
                        ->select(['id'])
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

                    $select = ['id'];

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
}
