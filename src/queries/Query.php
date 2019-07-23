<?php

namespace ether\graph\queries;

use ether\graph\traits\GraphArgs;
use ether\graph\traits\ResolveConnection;
use ether\graph\traits\ResolveQuery;
use ether\graph\traits\ConvertVariableType;
use ether\graph\traits\GatherWith;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Yii;
use yii\graphql\base\GraphQLQuery;
use yii\graphql\GraphQL;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

class Query extends GraphQLQuery
{
    use GraphArgs;
    use ResolveConnection;
    use ResolveQuery;
    use ConvertVariableType;
    use GatherWith;

    public $modelClass;
    public $type;
    public $args = [];
    public $checkAccess;
    public $withMap = [];
    public $underscoreToVariable;
    public $beforeQuery;
    public $typeNamespace;
    public $cacheFind;

    public function __construct()
    {
        $this->underscoreToVariable = ArrayHelper::getValue(Yii::$app->params, 'graph.underscore_to_variable');
        $this->typeNamespace = ArrayHelper::getValue(Yii::$app->params, 'graph.type_namespace');
    }

    public function type()
    {
        return GraphQL::type(ltrim($this->type, '\\'));
    }

    public function resolve($root, $args, $context, ResolveInfo $info)
    {
        $args = $this->variableToUnderscore($args);

        $modelClass = $this->modelClass;

        $_args = [];
        $tableName = $modelClass::tableName();

        foreach ($args as $key => $value) {
            $_args[$tableName . '.' . $key] = $value;
        }

        $query = ($modelClass)::find()
            ->andFilterWhere($_args)
            ->limit(1);

        if ($this->cacheFind) {
            $query->cache($this->cacheFind);
        }

        $this->with($query, $info);

        if ($this->beforeQuery) {
            call_user_func([$this, $this->beforeQuery], $query);
        }

        $fields = $info->getFieldSelection(10);

        if (!empty($fields)) {
            $totalOnly = true;

            foreach ($fields as $field) {
                if ($field !== ['totalCount' => true]) {
                    $totalOnly = false;
                }
            }

            if ($totalOnly) {
                unset($query->with);
            }
        }

        $model = $query->one();

        if (!$model) {
            return null;
        }

        if ($checkAccess = $this->checkAccess) {
            if (is_string($checkAccess)) {
                Yii::$app->scope->checkAccess($checkAccess, $model);
            } else {
                call_user_func($this->checkAccess, $model);
            }
        }

        return $model;
    }
}
