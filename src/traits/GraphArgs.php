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
use yii\helpers\Json;

trait GraphArgs
{
    public function convertArgs($args = null)
    {
        if ($args === null)
            $args = property_exists($this, 'args') ? $this->args : $this->fields;

        $_args = [];

        foreach ($args as $attribute => $type)
        {
            if (is_array($type))
            {
                if (ArrayHelper::getValue($type, 'connection'))
                {
                    unset($type['connection']);

                    $type['type'] = $this->resolveArgString($type['type']);

                    $type['args'] = [
                        'first' => Type::int(),
                        'after' => Type::string(),
                        'last' => Type::int(),
                        'before' => Type::string(),
                        'orderBy' => Type::string(),
                        'query' => Type::string(),
                        'filter' => Type::string(),
                    ];

                    $relation = $type['resolve'];

                    if (!is_array($relation))
                    {
                        $type['resolve'] = function($root, $args, $context, $resolve) use ($relation)
                        {
                            return $this->resolveConnectionRelation($root, $relation, $args);
                        };
                    }

                    $_args[$attribute] = $type;
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
        if (is_object($type))
            return $type;

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

        list($setType, $typeValues) = array_pad(explode(':', $type, 2), 2, null);

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
                preg_match_all('/([0-9A-z_:]+)/', $typeValues, $matches);

                $values = [];

                foreach ($matches[0] as $match)
                {
                    list($key, $value) = array_pad(explode(':', trim($match, ':'), 2), 2, null);;

                    $values[$key] = ['value' => $value ?: $key];
                }

                $name = property_exists($this, 'name') ? $this->name : StringHelper::basename(get_class($this));

                $type = new EnumType([
                    'name' => Inflector::camelize($name . ' ' . $attribute),
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

    public function resolveConnectionRelation($root, $relation, $args)
    {
        if (!$args)
            return $root->{Inflector::variablize($relation)};

        $query = $root->{'get' . Inflector::camelize($relation)}();

        if ($orderBy = ArrayHelper::getValue($args, 'orderBy'))
        {
            $this->resolveOrder($query, $orderBy, $query->modelClass);
        }

        if ($first = ArrayHelper::getValue($args, 'first', 10))
            $query->limit($first);

        if ($filter = ArrayHelper::getValue($args, 'filter'))
        {
            if (is_string($filter))
                $filter = Json::decode($filter);

            $query->andWhere($filter);
        }

        return $query;
    }
}
