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
        $newQuery = $query->modelClass::find();

        foreach (['select', 'joinWith', 'with', 'where', 'having', 'offset', 'limit', 'orderBy'] as $method)
        {
            if ($query->{$method})
                $newQuery->{$method} = $query->{$method};
        }

        return $newQuery;
    }
}
