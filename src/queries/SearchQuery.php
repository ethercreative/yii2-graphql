<?php

namespace ether\graph\queries;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use yii\graphql\GraphQL;

class SearchQuery extends Query
{
    public $types = [];

    public function type()
    {
        return Type::listOf(GraphQL::type(ltrim($this->type, '\\')));
    }

    public function args()
    {
        return $this->convertArgs([
            'first' => 'interger',
            'after' => 'string',
            'last' => 'interger',
            'before' => 'string',
            'orderBy' => 'string',
            'query' => 'string!',
            'type' => 'enum:' . join(',', $this->types),
        ]);
    }

    public function resolve($value, $args, $context, ResolveInfo $info)
    {
        throw new \Exception('SearchQuery::resolve has not been implemented.');
    }
}
