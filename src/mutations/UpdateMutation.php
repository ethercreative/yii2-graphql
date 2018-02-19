<?php

namespace ether\graph\mutations;

use GraphQL\Type\Definition\ResolveInfo;
use yii\web\NotFoundHttpException;

class UpdateMutation extends Mutation
{
    public function resolve($root, $args, $context, ResolveInfo $resolve)
    {
        $model = ($this->modelClass)::findOne($args['id']);

        if (!$model)
            throw new NotFoundHttpException('Entity not found.');

        $model->attributes = $args;
        $model->save();

        return $model;
    }
}
