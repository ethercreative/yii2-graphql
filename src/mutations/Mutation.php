<?php

namespace ether\graph\mutations;

use ether\graph\traits\GraphArgs;
use ether\graph\traits\ShouldValidate;
use ether\graph\traits\ConvertVariableType;
use GraphQL\Type\Definition\ResolveInfo;
use yii\graphql\base\GraphQLMutation;
use yii\graphql\GraphQL;

class Mutation extends GraphQLMutation
{
    use GraphArgs;
    use ShouldValidate;
    use ConvertVariableType;

    public $modelClass;
    public $name;
    public $description;
    public $type;
    public $args = [];
    public $refresh = false;

    public function __construct()
    {
        $this->attributes = [
            'name' => $this->name,
            'description' => $this->description,
        ];
    }

    public function type()
    {
        return GraphQL::type(ltrim($this->type, '\\'));
    }

    public function resolve($root, $args, $context, ResolveInfo $resolve)
    {
        throw new \Exception(get_class($this) . '::resolve() has not been implemented.');
    }

    public function rules()
    {
        return (new $this->modelClass)->rules();
    }
}
