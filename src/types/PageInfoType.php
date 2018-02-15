<?php

namespace ether\graph\types;

use GraphQL\Type\Definition\Type as GraphType;

class PageInfoType extends Type
{
    public $name = 'PageInfo';

    public function fields()
    {
        return [
            'endCursor' => [
                'type' => GraphType::string(),
                'description' => 'When paginating forwards, the cursor to continue.',
                'resolve' => function($root)
                {
                    return '';

                    $query = clone $root;
                    // $query->offset = $query->limit - 1;
                    // $query->limit = 1;
                    // $query->select(array_keys($query->orderBy ?: ['id' => 1]));

                    $query->select($query->select[0]);

                    $result = array_reverse($query->asArray()->all());

                    return ArrayHelper::getValue($result, '0.current_cursor', false);
                }
            ],
            'hasNextPage' => [
                'type' => GraphType::boolean(),
                'description' => 'When paginating forwards, are there more items?',
                'resolve' => function($root)
                {
                    return true;

                    $query = clone $root;
                    $limit = $query->limit;

                    return count($query->select(['id'])->limit($limit+1)->all()) / $limit > 1;
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
                    return '';

                    $query = clone $root;
                    $query->limit = 1;
                    $query->select(array_keys($query->orderBy ?: ['id' => 1]));

                    return base64_encode($query->scalar());
                }
            ],
        ];
    }
}
