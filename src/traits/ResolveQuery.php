<?php

namespace ether\graph\traits;

use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\Inflector;

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
            $orderBy = 'id';

        if ($orderBy)
            $this->resolveOrder($query, $orderBy, $modelClass, $args);

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

    protected function resolveOrder(&$query, $orderBy, $modelClass, $args = null, $model = null)
    {
        if (!$model)
        {
            if (is_string($modelClass))
                $model = new $modelClass;
            else
                $model = $modelClass;
        }

        $direction = SORT_ASC;

        if ($orderBy[0] === '-')
            $direction = SORT_DESC;

        $orderBy = trim($orderBy, '-+ ');

        if (strpos($orderBy, '.') !== false)
        {
            $orderParts = explode('.', $orderBy);

            if ($model->hasAttribute($orderParts[0]))
            {
                switch(ArrayHelper::getValue($model, 'jsonTypes.' . join('.', $orderParts)))
                {
                    case 'int':
                    case 'integer':
                        $type = '::int';
                        break;

                    case 'float':
                        $type = '::float';
                        break;

                    default:
                        $type = null;
                        break;
                }

                $orderBy = join('', [
                    '(',
                    $model::tableName(),
                    '."',
                    $orderParts[0],
                    '"->>',
                    '\'',
                    $orderParts[1],
                    '\'',
                    ')',
                    $type,
                ]);
            }
            else
            {
                $query->joinWith("{$orderParts[0]} rel");
                $orderBy = join('.', ['rel', $orderParts[1]]);
                // check related attributes
            }
        }
        else
        {
            $orderBy = Inflector::underscore($orderBy);

            if (!$model->hasAttribute($orderBy))
            {
                $orderBy = $modelClass::tableName() . '.id';
            }
        }

        // die('<pre>'.print_r([$orderBy], 1).'</pre>');

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

        $orderByString = sprintf(
            '%s %s %s',
            $orderBy,
            $direction === SORT_ASC ? 'ASC' : 'DESC',
            $direction === SORT_ASC ? 'NULLS FIRST' : 'NULLS LAST'
        );

        $query
            ->andFilterWhere([$afterOperator, 'id', $after])
            ->andFilterWhere([$beforeOperator, 'id', $before])
            ->orderBy($orderByString);
    }
}
