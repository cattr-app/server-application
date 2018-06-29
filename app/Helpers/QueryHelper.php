<?php

namespace App\Helpers;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Schema;

/**
 * Class QueryHelper
 * @package App\Helpers
 */
class QueryHelper
{

    /**
     * @param QueryBuilder|EloquentBuilder $query
     * @param array $filter
     * @param Model $model
     * @param bool $first
     */
    public function apply($query, array $filter = [], $model, $first = true)
    {
        $table = $model->getTable();
        $relations = [];

        if (isset($filter['limit'])) {
            $query->limit((int) $filter['limit']);
            unset($filter['limit']);
        }

        if (isset($filter['offset'])) {
            $query->offset((int) $filter['offset']);
            unset($filter['offset']);
        }

        if (isset($filter['with'])) {
            $with = explode(',', $filter['with']);
            if ($with) {
                foreach ($with as $relation) {
                    if (method_exists($model, $relation)) {
                        $query->with($relation);
                    }
                }
            }
        }

        foreach ($filter as $key => $param) {
            if (strpos($key, '.') !== False) {
                $params = explode('.', $key);
                $domain = array_shift($params);
                $filterParam = implode('.',$params);

                if (!isset($relations[$domain])) {
                    $relations[$domain] = [];
                }

                $relations[$domain][$filterParam] = $param;
            } else {
                if (Schema::hasColumn($table, $key)) {
                    [$operator, $value] = \is_array($param) ? $param : ['=', $param];

                    if (\is_array($value) && $operator === '=') {
                        $query->whereIn($key, $value);
                    } else {
                        $query->where($key, $operator, $value);
                    }
                }
            }
        }

        $self = $this;

        if (!empty($relations)) {
            foreach ($relations as $domain => $filters) {
                if (!method_exists($model, $domain)) {
                    $cls = get_class($model);
                    throw new \RuntimeException("Unknown relation {$cls}::{$domain}()");
                }

                /** @var Relation $relationQuery */
                $relationQuery = $model->{$domain}();
                if (!$first) {
                    $query->orWhereHas($domain, function ($q) use ($self, $filters, $relationQuery, $first) {
                        $self->apply($q, $filters, $relationQuery->getModel(), $first);
                    });
                } else {
                    $query->WhereHas($domain, function ($q) use ($self, $filters, $relationQuery, $first) {
                        $self->apply($q, $filters, $relationQuery->getModel(), $first);
                    });
                }
                $first = false;
            }
        }
    }
}