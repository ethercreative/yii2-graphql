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

        $max = ArrayHelper::getValue($this, 'maxPageSize') ?? 50;

        if ($query->limit)
        {
            // do nothing
        }
        elseif ($first = ArrayHelper::getValue($args, 'first'))
        {
            if ($first > $max)
                $first = $max;

            $query->limit($first);
        }
        elseif ($last = ArrayHelper::getValue($args, 'last'))
        {
            if ($last > $max)
                $last = $max;

            $query->limit($last);
        }
        else
        {
            $query->limit($max);
        }

        if ($after = ArrayHelper::getValue($args, 'after'))
        {
            $after = Json::decode(base64_decode($after));
            $query->offset($after);
        }

        $orderBy = ArrayHelper::getValue($args, 'orderBy');

        if (!$orderBy)
            $orderBy = ArrayHelper::getValue($this, 'defaultOrder');

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
            if ($alias = ArrayHelper::getValue($model->sortAlias, $orderBy))
            {
                $orderBy = $alias;
            }
            else
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
        }
        else
        {
            $orderBy = Inflector::underscore($orderBy);

            if (!$model->hasAttribute($orderBy))
            {
                $orderBy = $modelClass::tableName() . '.id';
            }

            if (strpos($orderBy, '.') === false)
                $orderBy = $modelClass::tableName() . '.' . $orderBy;
        }

        if ($orderBy === 'id')
            $orderBy = $modelClass::tableName() . '.id';

        $orderBy = '(' . $orderBy . ')';

        $query->select([$modelClass::tableName() . '.*']);

        $query
            ->orderBy([$orderBy => $direction]);
    }
}
