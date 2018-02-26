<?php

namespace ether\graph\types;

use ether\graph\traits\GraphArgs;
use GraphQL\Type\Definition\Type;
use yii\graphql\GraphQL;

// class EdgeType extends \GraphQL\Type\Definition\ObjectType
class EdgeType extends \yii\graphql\base\GraphQLType
// class EdgeType extends Type
{
    use GraphArgs;

    public $name;
    public $description;
    public $node;

    public function __construct()
    {
        $this->attributes = [
            'name' => $this->name,
            'description' => $this->description,
        ];
    }

    public function interfaces()
    {
        return [];
    }

    public function fields()
    {
        return [
            'cursor' => [
                'type' => Type::string(),
                'resolve' => function ($root)
                {
                    if (!$root) return null;

                    $value = null;

                    $attributes = ['node_id', 'cursor', 'current_cursor'];

                    if (is_array($root))
                    {
                        foreach ($attributes as $attribute)
                        {
                            if ($_value = ArrayHelper::getValue($root, $attribute))
                            {
                                $value = $_value;
                                break;
                            }
                        }
                    }
                    else
                    {
                        foreach ($attributes as $attribute)
                        {
                            if ($root->hasAttribute($attribute))
                            {
                                $value = $root->attribute;
                                break;
                            }

                            if ($root->hasMethod('get' . $attribute))
                            {
                                $value = $root->{'get' . $attribute}();
                                break;
                            }
                        }
                    }

                    return $value;
                }
            ],
            'node' => [
                'type' => GraphQL::type($this->node),
                'resolve' => function ($root, $args)
                {
                    return $root;
                },
            ],
        ];
    }
}
