<?php

namespace ether\graph\types;

use ether\graph\traits\GraphArgs;
use GraphQL\Type\Definition\InputObjectType;
use yii\graphql\base\GraphQLType;

class InputType extends InputObjectType
{
    use GraphArgs;

    public $name;
    public $description;
    public $fields = [];

    public function __construct()
    {
        return parent::__construct([
            'name' => $this->name,
            'description' => $this->description,
            'fields' => $this->convertArgs(),
        ]);
    }
}
