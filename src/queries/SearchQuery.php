<?php

namespace ether\graph\queries;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use yii\graphql\GraphQL;

class SearchQuery extends Query
{
    public $types = [];

    public $args = [];

    public function type()
    {
        return Type::listOf(GraphQL::type(ltrim($this->type, '\\')));
    }

    public function args()
    {
        return $this->convertArgs(array_replace([
            'first' => 'integer',
            'after' => 'string',
            'last' => 'integer',
            'before' => 'string',
            'orderBy' => 'string',
            'query' => 'string',
            'type' => 'enum:' . join(',', $this->types),
            'filters' => 'string',
        ], $this->args));
    }

    public function resolve($value, $args, $context, ResolveInfo $info)
    {
        throw new \Exception('SearchQuery::resolve has not been implemented.');
    }
}
