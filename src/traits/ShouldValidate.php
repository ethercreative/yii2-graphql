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

                if (getenv('GRAPH_SHOULD_VALIDATE_SET_DEFAULTS'))
                    $args = $this->setAttributeDefaults($args, $rules);

                if (getenv('GRAPH_SHOULD_VALIDATE_MODEL'))
                {
                    $model = new $this->modelClass;
                    $model->attributes = $args;
                    $model->validate();

                    if ($model->errors)
                    {
                        foreach ($model->errors as $key => $value)
                        {
                            $attributeParts = explode('.', $key);

                            $message = ArrayHelper::getValue($value, '0');
                            $message[0] = strtolower($message[0]);

                            if (count($attributeParts) > 1)
                            {
                                if (strpos($message, $attributeParts[1]) === 0)
                                {
                                    $message = preg_replace("/^{$attributeParts[1]}/", join(' ', $attributeParts), $message, 1);
                                }
                            }

                            $message[0] = strtoupper($message[0]);

                            throw new InvalidParamException($message);
                        }
                    }
                }
                else
                {
                    $val = DynamicModel::validateData($args, $rules);

                    if ($error = $val->getFirstErrors())
                    {
                        $msg = reset($error);
                        throw new InvalidParamException($msg);
                    }
                }
            }

            return call_user_func_array($resolver, $arguments);
        };
    }

    protected function setAttributeDefaults($args, $rules)
    {
        $skip = [
            'default',
        ];

        $default = [];

        foreach ($rules as $key => &$rule)
        {
            $attribute = ArrayHelper::getValue($rule, 0);
            $validator = ArrayHelper::getValue($rule, 1);

            if ($validator === 'default')
            {
                $value = ArrayHelper::getValue($rule, 'value');

                if (is_array($attribute))
                {
                    foreach ($attribute as $k => $attr)
                    {
                        $default[$attr] = $value;
                    }
                }
                else
                {
                    $default[$attribute] = $value;
                }
            }
            elseif (is_array($attribute))
            {
                foreach ($attribute as $k => $attr)
                {
                    $default[] = $attr;
                }
            }
            else
            {
                $default[] = $attribute;
            }
        }

        foreach (array_unique($default) as $key => $attribute)
        {
            if (!is_numeric($key))
            {
                if (empty($args[$key]))
                    $args[$key] = $attribute;
            }
            else
            {
                if (empty($args[$attribute]))
                    $args[$attribute] = null;
            }
        }

        return $args;
    }
}
