<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Schema;

/**
 * Class QueryHelper
*/
class QueryHelper
{
    const RESERVED_REQUEST_KEYWORDS = [
        'with', 'withCount', 'paginate', 'perPage', 'page', 'with_deleted', 'search',
    ];

    /**
     * @param  array|string  $filter
     *
     * @return array
     * @throws \Exception
     */
    protected function getRelationsFilter($filter): array
    {
        if (!is_array($filter)) {
            if (is_string($filter)) {
                $filter = explode(',', str_replace(' ', '', $filter));
            } else {
                throw new \Exception(
                    "Relation filter must be a type of array or string, ".gettype($filter)." given in"
                );
            }
        }

        return $filter;
    }

    /**
     * @param EloquentBuilder|QueryBuilder $query
     * @param string $search
     * @param string[] $fields
     *
     * @return EloquentBuilder|QueryBuilder
     */
    protected function buildSearchQuery($query, string $search, array $fields)
    {
        $value = "%$search%";

        return $query->where(function ($query) use ($value, $fields) {
            $field = array_shift($fields);
            if (strpos($field, '.') !== false) {
                [$relation, $relationField] = explode('.', $field);
                $query->whereHas($relation, function ($query) use ($relationField, $value) {
                    $query->where($relationField, 'like', $value);
                });
            } else {
                $query->where($field, 'like', $value);
            }

            foreach ($fields as $field) {
                if (strpos($field, '.') !== false) {
                    [$relation, $relationField] = explode('.', $field);
                    $query->orWhereHas($relation, function ($query) use ($relationField, $value) {
                        $query->where($relationField, 'like', $value);
                    });
                } else {
                    $query->orWhere($field, 'like', $value);
                }
            }
        });
    }

    /**
     * @param  QueryBuilder|EloquentBuilder  $query
     * @param  array                         $filter
     * @param  Model                         $model
     * @param  bool                          $first
     *
     * @throws \Exception
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

        if (isset($filter['orderBy'])) {
            $order_by = $filter['orderBy'];
            [$column, $dir] = \is_array($order_by) ? array_values($order_by) : [$order_by, 'asc'];
            if (Schema::hasColumn($table, $column)) {
                $query->orderBy($column, $dir);
            }

            unset($filter['orderBy']);
        }

        if (isset($filter['with'])) {
            $query->with($this->getRelationsFilter($filter['with']));
        }

        if (isset($filter['withCount'])) {
            $query->withCount($this->getRelationsFilter($filter['withCount']));
        }

        if (isset($filter['search']['query']) && isset($filter['search']['fields'])) {
            $query = $this->buildSearchQuery($query, $filter['search']['query'], $filter['search']['fields']);
        }

        foreach ($filter as $key => $param) {
            if (strpos($key, '.') !== false) {
                $params = explode('.', $key);
                $domain = array_shift($params);
                $filterParam = implode('.', $params);

                if (!isset($relations[$domain])) {
                    $relations[$domain] = [];
                }

                $relations[$domain][$filterParam] = $param;
            } else {
                // TODO: refactor or remove this
                if (!in_array($key, static::RESERVED_REQUEST_KEYWORDS)) {
                    [$operator, $value] = \is_array($param) ? array_values($param) : ['=', $param];

                    if (\is_array($value) && $operator === '=') {
                        $query->whereIn($key, $value);
                    } elseif ($operator == "in") {
                        $inArgs = \is_array($value) ? $value : [$value];
                        $query->whereIn($key, $inArgs);
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
