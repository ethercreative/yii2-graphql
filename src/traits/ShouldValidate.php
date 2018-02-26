<?php

namespace ether\graph\traits;

use yii\base\DynamicModel;
use yii\base\InvalidParamException;
use yii\helpers\ArrayHelper;

trait ShouldValidate
{
    protected function getResolver()
    {
        $resolver = parent::getResolver();

        if (!$resolver)
            return null;

        return function () use ($resolver)
        {
            $arguments = func_get_args();
            $rules = $this->rules();

            if (sizeof($rules))
            {
                $args = ArrayHelper::getValue($arguments, 1, []);
                $val = DynamicModel::validateData($args, $rules);

                if ($error = $val->getFirstErrors())
                {
                    $msg = reset($error);
                    throw new InvalidParamException($msg);
                }
            }

            return call_user_func_array($resolver, $arguments);
        };
    }
}
