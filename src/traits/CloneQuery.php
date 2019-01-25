<?php

namespace ether\graph\traits;

use yii\base\DynamicModel;
use yii\base\InvalidParamException;
use yii\helpers\ArrayHelper;
use GraphQL\Type\Definition\ResolveInfo;
use yii\helpers\Inflector;

trait CloneQuery
{
    private function cloneQuery($query)
    {
        return $query->modelClass::find()
            ->select($query->select)
            ->joinWith($query->joinWith)
            ->with($query->with)
            ->where($query->where)
            ->having($query->having)
            ->offset($query->offset)
            ->limit($query->limit);
    }
}
