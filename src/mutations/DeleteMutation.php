<?php

namespace ether\graph\mutations;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use yii\web\NotFoundHttpException;

class DeleteMutation extends Mutation
{
    public function type()
    {
        return Type::boolean();
    }

    public function resolve($root, $args, $context, ResolveInfo $resolve)
    {
        $model = ($this->modelClass)::findOne($args['id']);

        if (!$model)
            throw new NotFoundHttpException('Entity not found.');

        if ($this->hasMethod('checkAccess'))
            $this->checkAccess($model);

        return $model->delete();
    }

    public function rules()
    {
        return [
            ['id', 'required'],
        ];
    }
}
