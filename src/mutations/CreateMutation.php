<?php

namespace ether\graph\mutations;

use GraphQL\Type\Definition\ResolveInfo;

class CreateMutation extends Mutation
{
    public function resolve($root, $args, $context, ResolveInfo $resolve)
    {
        $model = new $this->modelClass;
        $model->attributes = $args;

        $scenarios = $model->scenarios();

        if (!empty($scenarios['create']))
            $model->scenario = 'create';

        if ($this->hasMethod('checkAccess'))
            $this->checkAccess($model);

        if (!$model->save())
            throw new \Exception('Mutation validation passed, but model validation failed.');

        if ($this->refresh)
            $model->refresh();

        return $model->toArray([], $this->type::$with);
    }
}
