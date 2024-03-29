<?php

namespace ether\graph\types;

use ether\graph\traits\GraphArgs;
use ether\graph\traits\ConvertVariableType;
use ether\graph\traits\ResolveQuery;
use ether\graph\traits\GatherWith;
use GraphQL\Type\Definition\Type as GraphType;
use yii\graphql\base\GraphQLType;
use yii\graphql\GraphQL;
use yii\helpers\ArrayHelper;

class Type extends GraphQLType
{
    use GraphArgs;
    use ConvertVariableType;
    use ResolveQuery;
    use GatherWith;

    public $name;
    public $description;
    public $fields = [];
    public static $with = [];
    public static $withMap = [];
    public $useNodeId = true;

    public function interfaces()
    {
        if ($this->useNodeId) {
            return [GraphQL::type(NodeType::class)];
        }

        return [];
    }

    public function __construct()
    {
        $this->attributes = [
            'name' => $this->name,
            'description' => $this->description,
        ];
    }

    public function fields()
    {
        $fields = func_get_args();

        if ($fields) {
            $fields = $fields[0];
        } else {
            $fields = $this->args();

            if (empty($fields)) {
                $fields = $this->fields();
            }
        }

        if ($this->useNodeId) {
            $fields['nodeId'] = [
                'type' => GraphType::string(),
                'resolve' => function($root)
                {
                    return ArrayHelper::getValue($root, 'nodeId');
                }
            ];
        }

        return $fields;
    }
}
