<?php

namespace ether\graph\traits;

use ether\graph\types\DateTimeType;
use ether\graph\types\JsonType;
use ether\graph\types\UuidType;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use yii\graphql\GraphQL;
use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;
use yii\helpers\Inflector;

trait GraphArgs
{
    public function convertArgs($args = null)
    {
        if ($args === null)
            $args = property_exists($this, 'args') ? $this->args : $this->fields;

        $_args = [];

        foreach ($args as $attribute => $type)
        {
            // if (!is_string($type))
            // {
            //     $_args[$attibute] = $type;
            //     continue;
            // }

            if (is_array($type))
            {
                if (ArrayHelper::getValue($type, 'connection'))
                {
                    unset($type['connection']);

                    $type['type'] = $this->resolveArgString($type['type']);

                    $relation = $type['resolve'];

                    $type['resolve'] = function($root, $args, $context, $resolve) use ($relation)
                    {
                        $models = $root->{'get' . $relation}();//->all();
                        return $models;
                    };

                    $_args[$attribute] = $type;

                    // die('<pre>'.print_r($type, 1).'</pre>');
                }

                continue;
            }

            $type = $this->resolveArgString($type, $attribute);
            $_args[$attribute] = $type;
        }

        return $_args;
    }

    public function args()
    {
        return $this->convertArgs();
    }

    public function fields()
    {
        return $this->convertArgs();
    }

    private function resolveArgString($type, $attribute = null)
    {
        if (strpos(trim($type, '[ '), 'type:') === 0)
        {
            $type = str_replace('type:', '', $type);
            $listOf = strpos($type, '[') !== false;
            $nonNull = strpos($type, '!') !== false;

            $type = trim($type, '![] ');
            $type = GraphQL::type($type);

            if ($listOf)
                $type = Type::listOf($type);

            if ($nonNull)
                $type = Type::nonNull($type);

            return $type;
        }

        $nonNull = strpos($type, '!') !== false;
        $listOf = strpos($type, '[') !== false;

        $type = trim($type, '![] ');

        $setType = explode(':', $type)[0];

        switch ($setType)
        {
            case 'int':
            case 'integer':
                $type = Type::int();
                break;

            case 'bool':
            case 'boolean':
                $type = Type::boolean();
                break;

            case 'float':
                $type = Type::float();
                break;

            case 'uuid':
                $type = GraphQL::type(UuidType::class);
                break;

            case 'json':
                $type = GraphQL::type(JsonType::class);
                break;

            case 'datetime':
                $type = GraphQL::type(DateTimeType::class);
                break;

            case 'enum':
                preg_match_all('/([A-Z_]+)/', $type, $matches);

                $values = [];

                foreach ($matches[0] as $match) {
                    $values[$match] = ['value' => $match];
                }

                $type = new EnumType([
                    'name' => Inflector::camelize($attribute),
                    'values' => $values,
                ]);
                break;

            default:
                if (strpos($type, '\\') || StringHelper::endsWith($type, 'Type'))
                    $type = GraphQL::type($type);
                else
                    $type = Type::string();
                break;
        }

        if ($listOf)
            $type = Type::listOf($type);

        if ($nonNull)
            $type = Type::nonNull($type);

        return $type;
    }
}
