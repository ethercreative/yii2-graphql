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
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

class Query extends GraphQLQuery
{
    use GraphArgs;
    use ResolveConnection;
    use ResolveQuery;

    public $modelClass;
    public $type;
    public $args = [];
    public $checkAccess;
    public $withMap = [];
    public $underscoreToVariable;
    public $beforeQuery;
    public $typeNamespace;

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
        $modelClass = $this->modelClass;

        $_args = [];
        $tableName = $modelClass::tableName();

        foreach ($args as $key => $value)
        {
            $_args[$tableName . '.' . $key] = $value;
        }

        $query = ($modelClass)::find()
            ->andFilterWhere($_args)
            ->limit(1);

        $with = $this->type::$with;

        $this->gatherWith($info->getFieldSelection(10), $with);

        if (!empty($with))
        {
            if (!empty($this->withMap))
            {
                foreach ($with as &$relation)
                {
                    if (array_key_exists($relation, $this->withMap))
                        $relation = $this->withMap[$relation];

                    if (is_array($relation))
                    {
                        $first = array_pop($relation);

                        foreach ($relation as $r)
                        {
                            $with[] = $r;
                        }

                        $relation = $first;
                    }
                }
            }

            $query->with($with);
        }

        if ($this->beforeQuery)
            call_user_func($this->beforeQuery, $query);

        $model = $query->one();

        if (!$model)
            return null;

        if ($this->checkAccess)
            call_user_func($this->checkAccess, $model);

        return $query->one();
    }

    private function gatherWith($selectedFields, &$with)
    {
        if (!is_array($selectedFields))
            return;

        foreach($selectedFields as $field => $children)
        {
            $isEdge = !empty($children['edges']);

            if ($isEdge)
            {
                $connectionTypeName = Inflector::camelize(Inflector::singularize($field)) . 'ConnectionType';
                $connectionType = $this->typeNamespace . $connectionTypeName;

                $with[] = $field;
                $with += $connectionType::$with;

                if (!empty($children['edges']['node']))
                {
                    $sub = [];

                    foreach ($children['edges']['node'] as $key => $value)
                    {
                        if (is_array($value))
                            $this->gatherWith([$key => $value], $sub);
                    }

                    if (!empty($sub))
                    {
                        foreach ($sub as $s)
                            $with[] = join('.', [$field, $s]);
                    }
                }
            }
        }
    }

    private function underscoreToVariable($data)
    {
        if (!is_array($data)) return $data;

        $_data = [];

        foreach ($data as $key => $value)
            $_data[Inflector::variablize($key)] = $this->underscoreToVariable($value);

        return $_data;
    }
}
