<?php

namespace ether\graph\traits;

use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

trait ResolveQuery
{
    protected function resolveQuery($modelClass, &$query, $args, $fields = [])
    {
        if (is_string($modelClass))
            $model = new $modelClass;
        else
            $model = $modelClass;

        if ($first = ArrayHelper::getValue($args, 'first'))
            $query->limit($first <= 50 ? $first : 10);
        elseif ($last = ArrayHelper::getValue($args, 'last'))
            $query->limit($last <= 50 ? $last : 10);
        else
            $query->limit(10);

        $orderBy = ArrayHelper::getValue($args, 'orderBy');

        if (!$orderBy)
            $orderBy = 'created_at';

        if ($orderBy)
        {
            $direction = SORT_ASC;

            if ($orderBy[0] === '-')
            {
                $direction = SORT_DESC;
                $orderBy = trim($orderBy, '-');
            }

            if (strpos($orderBy, '.') !== false)
            {
                $orderParts = explode('.', $orderBy);
                $query->joinWith("{$orderParts[0]} rel");
                $orderBy = join('.', ['rel', $orderParts[1]]);
                // check related attributes
            }
            elseif (!$model->hasAttribute($orderBy))
            {
                $orderBy = $modelClass::tableName() . '.created_at';
            }

            $query->select([$modelClass::tableName() . '.*']);

            $afterOperator = $direction === SORT_ASC ? '>' : '<';
            $beforeOperator = $direction === SORT_ASC ? '<' : '>';

            $after = ArrayHelper::getValue($args, 'after');
            $before = ArrayHelper::getValue($args, 'before');

            if ($after)
            {
                $after = Json::decode(base64_decode($after));
                $after = $after[1];
            }

            $query
                ->andFilterWhere([$afterOperator, 'id', $after])
                ->andFilterWhere([$beforeOperator, 'id', $before])
                ->orderBy([$orderBy => $direction]);
        }

        $fields = ArrayHelper::getValue($fields, 'edges.node');

        if (!empty($fields))
        {
            foreach ($fields as $key => $value)
            {
                if (is_array($value))
                    $query->with($key);
            }
        }
    }
}
