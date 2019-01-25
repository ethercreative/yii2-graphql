<?php

namespace ether\graph\traits;

use yii\base\DynamicModel;
use yii\base\InvalidParamException;
use yii\helpers\ArrayHelper;
use GraphQL\Type\Definition\ResolveInfo;
use yii\helpers\Inflector;

trait GatherWith
{
    protected function with(&$query, ResolveInfo $info, $type = null)
    {
        $with = [];

        if (!$type && $this->hasProperty('type') && $this->type)
            $type = $this->type;

        if ($type)
        {
            $with = $type::$with;
            $withMap = $this->withMap ?: $type::$withMap;
        }

        $this->gatherWith($info->getFieldSelection(10), $with);

        if (!empty($with))
        {
            if (!empty($withMap))
            {
                foreach ($with as &$relation)
                {
                    if (array_key_exists($relation, $withMap))
                        $relation = $withMap[$relation];

                    if (is_array($relation))
                    {
                        $first = array_shift($relation);

                        foreach ($relation as $r)
                        {
                            $with[] = $r;
                        }

                        $relation = $first;
                    }
                }
            }

            $with = array_values(array_unique(array_filter($with)));

            if (($key = array_search('pageInfo', $with)) !== false) {
                unset($with[$key]);
            }

            $query->with($with);
        }
    }

    protected function gatherWith($selectedFields, &$with)
    {
        if (!is_array($selectedFields))
            return;

        foreach($selectedFields as $field => $children)
        {
            if ($field === 'edges')
            {
                $children = ['edges' => $children];
                // $field = $rootFieldFallback;
                $field = null;
            }

            $isEdge = !empty($children['edges']);

            if ($isEdge)
            {
                $connectionTypeName = Inflector::camelize(Inflector::singularize($field)) . 'ConnectionType';
                $connectionType = $this->typeNamespace . str_replace('Linked', '', $connectionTypeName);

                $with[] = $field;
                // $with += $connectionType::$with;

                $nodes = ArrayHelper::getValue($children, 'edges.node');

                if (!$nodes)
                    $nodes = ArrayHelper::getValue($children, 'node');

                if ($nodes)
                {
                    $sub = [];

                    foreach ($nodes as $key => $value)
                    {
                        if (is_array($value))
                            $this->gatherWith([$key => $value], $sub);
                    }

                    if (!empty($sub))
                    {
                        foreach ($sub as $s)
                            $with[] = join('.', array_filter([$field, $s]));
                    }
                }
            }
            elseif($field === 'node')
            {
                $sub = [];

                foreach ($children as $key => $value)
                {
                    if (is_array($value))
                        $this->gatherWith([$key => $value], $sub);
                }

                if (!empty($sub))
                {
                    foreach ($sub as $s)
                        $with[] = $s;
                }

            }
            elseif(is_array($children))
            {
                $with[] = $field;
            }
        }

        $with = array_filter($with);
    }
}
