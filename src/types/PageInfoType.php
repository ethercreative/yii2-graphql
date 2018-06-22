<?php

namespace ether\graph\types;

use GraphQL\Type\Definition\Type as GraphType;
use GraphQL\Type\Definition\ResolveInfo;
use yii\helpers\ArrayHelper;

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

                    // return ArrayHelper::getValue($root, (count($root) - 1) . '.nodeData.id');

                    if (is_array($root))
                    {
                        $root = array_reverse($root);

                        return ArrayHelper::getValue($root, '0.nodeId');
                    }

                    $query = clone $root;
                    // $query->offset = $query->limit - 1;
                    // $query->limit = 1;
                    // $query->select(array_keys($query->orderBy ?: ['id' => 1]));

                    $query->select(['id']);

                    $models = array_reverse($query->all());

                    if ($model = ArrayHelper::getValue($models, 0))
                        return $model->nodeId;

                    return null;
                }
            ],
            'hasNextPage' => [
                'type' => GraphType::nonNull(GraphType::boolean()),
                'description' => 'When paginating forwards, are there more items?',
                'resolve' => function($root, $args, $context, ResolveInfo $resolve)
                {
                    if (getenv('GRAPH_PAGE_INFO_FORCE_NEXT_PAGE'))
                        return true;

                    $query = clone $root;
                    $limit = $query->limit;

                    $hasNextPage = (bool) ((count($query->select(['id'])->limit($limit+1)->all()) / $limit) > 1);

                    return (bool) $hasNextPage ? true : false;
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
                    $query->limit(1)->select(['id']);
                    $model = $query->one();

                    if ($model)
                        return $model->nodeId;

                    return null;
                }
            ],
        ];
    }
}
