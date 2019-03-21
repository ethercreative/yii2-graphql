<?php

namespace ether\graph\traits;

use yii\base\DynamicModel;
use yii\base\InvalidParamException;
use yii\helpers\ArrayHelper;
use GraphQL\Type\Definition\ResolveInfo;
use yii\helpers\Inflector;

trait GatherWith
{
    private $_filterWithCache = [];
    private $_filterWithMap = [];
    private $_filterWithAliased = [];

    protected function with(&$query, ResolveInfo $info, $type = null)
    {
        $with = [];

        if (!$type && $this->hasProperty('type') && $this->type) {
            $type = $this->type;
        }

        if ($type) {
            $with = $type::$with;
            $withMap = $this->withMap ?: $type::$withMap;
        }

        $this->gatherWith($info->getFieldSelection(10), $with, null, $query->modelClass);
        $with = array_unique($with);
        $this->filterWith($with, $query->modelClass);
        $with = array_merge($with, $this->_filterWithAliased);

        // die('<pre>'.print_r([ 'with' => $with, 'alias' => $this->_filterWithAliased, ],1).'</pre>');

        if (!empty($with)) {
            if (!empty($withMap)) {
                foreach ($with as &$relation) {
                    if (array_key_exists($relation, $withMap)) {
                        $relation = $withMap[$relation];
                    }

                    if (is_string($relation)) {
                        $firstPart = explode('.', $relation)[0];

                        if (ArrayHelper::getValue($withMap, $firstPart) === false) {
                            $relation = null;
                            continue;
                        }
                    }

                    if (is_array($relation)) {
                        $first = array_shift($relation);

                        foreach ($relation as $r) {
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

    protected function gatherWith($selectedFields, &$with, $parent = null)
    {
        if ($parent && !in_array($parent, $with)) {
            $with[] = $parent;
        }

        foreach ($selectedFields as $field => $value) {
            $key = !in_array($field, ['edges', 'node', 'pageInfo', 'totalCount']) ? $field : null;

            if (is_array($value)) {
                $this->gatherWith($value, $with, join('.', array_filter([$parent, $key])));
            }

            if ($key) {
                $with[] = join('.', array_filter([$parent, $field]));
            }
        }
    }

    protected function filterWith(&$with, $modelClass, $parent = null)
    {
        // if ($parent && !in_array($with, ['id', 'title', 'status'])) die('<pre>'.print_r([ $with, $modelClass, $parent ],1).'</pre>');

        if (is_string($with)) {
            $with = [$with];
        }

        foreach ($with as $key => $path) {
            if (in_array($path, $this->_filterWithAliased) || !$modelClass) {
                continue;
            }

            if (strpos($path, '.') === false) {
                try {
                    if (empty($this->_filterWithCache[$modelClass])) {
                        $this->_filterWithCache[$modelClass] = new $modelClass;
                    }

                    if ($alias = ArrayHelper::getValue($this->_filterWithCache[$modelClass], "filterWithMap.{$path}")) {
                        if (!is_array($alias)) {
                            $alias = [$alias];
                        }

                        foreach ($alias as $additional) {
                            $value = join('.', array_filter([$parent, $additional]));
                            $with[] = $value;
                            $this->_filterWithAliased[] = $value;
                        }

                        unset($with[$key]);
                    } else {
                        $isQuery = $this->_filterWithCache[$modelClass]->{'get'.$path}();

                        if ($isQuery instanceof \yii\db\Query) {
                            $this->_filterWithMap[$path] = $isQuery->modelClass;
                        } else {
                            unset($with[$key]);
                        }
                    }
                } catch (\Exception $e) {
                    unset($with[$key]);
                }

                continue;
            }

            list($root, $subPath) = explode('.', $path, 2);

            $this->filterWith($subPath, $this->_filterWithMap[$root], join('.', array_filter([$parent, $root])));

            if (is_array($subPath)) {
                $subPath = ArrayHelper::getValue($subPath, '0');
            }

            if (!$subPath) {
                unset($with[$key]);
            }
        }
    }

    protected function gatherWithLegacy($selectedFields, &$with)
    {
        if (!is_array($selectedFields)) {
            return;
        }

        foreach ($selectedFields as $field => $children) {
            if ($field === 'edges') {
                $children = ['edges' => $children];
                // $field = $rootFieldFallback;
                $field = null;
            }

            $isEdge = !empty($children['edges']);

            if ($isEdge) {
                $connectionTypeName = Inflector::camelize(Inflector::singularize($field)) . 'ConnectionType';
                $connectionType = $this->typeNamespace . str_replace('Linked', '', $connectionTypeName);

                $with[] = $field;
                // $with += $connectionType::$with;

                $nodes = ArrayHelper::getValue($children, 'edges.node');

                if (!$nodes) {
                    $nodes = ArrayHelper::getValue($children, 'node');
                }

                if ($nodes) {
                    $sub = [];

                    foreach ($nodes as $key => $value) {
                        if (is_array($value)) {
                            $this->gatherWith([$key => $value], $sub);
                        }
                    }

                    if (!empty($sub)) {
                        foreach ($sub as $s) {
                            $with[] = join('.', array_filter([$field, $s]));
                        }
                    }
                }
            } elseif ($field === 'node') {
                $sub = [];

                foreach ($children as $key => $value) {
                    if (is_array($value)) {
                        $this->gatherWith([$key => $value], $sub);
                    }
                }

                if (!empty($sub)) {
                    foreach ($sub as $s) {
                        $with[] = $s;
                    }
                }
            } elseif (is_array($children)) {
                $with[] = $field;
            }
        }

        $with = array_filter($with);
    }
}
