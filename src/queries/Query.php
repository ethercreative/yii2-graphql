<?php

namespace ether\graph\queries;

use ether\graph\traits\GraphArgs;
use ether\graph\traits\ResolveConnection;
use ether\graph\traits\ResolveQuery;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Yii;
use yii\graphql\base\GraphQLQuery;
use yii\graphql\GraphQL;

class Query extends GraphQLQuery
{
    use GraphArgs;
    use ResolveConnection;
    use ResolveQuery;

    public $modelClass;
    public $type;
    public $args = [];
    public $checkAccess;

    public function type()
    {
        return GraphQL::type(ltrim($this->type, '\\'));
    }

    public function resolve($root, $args, $context, ResolveInfo $info)
    {
        $modelClass = $this->modelClass;

        $_args = [];
        $tableName = $modelClass::tableName();

        foreach ($args as $key => $value)
        {
            $_args[$tableName . '.' . $key] = $value;
        }

        $model = ($modelClass)::find()
            ->andFilterWhere($_args)
            ->limit(1)
            ->one();

        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $model);
        }

        return $model;
    }
}
