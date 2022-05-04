<?php

namespace App\Helpers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use function is_array;

class QueryHelper
{
    private const RESERVED_REQUEST_KEYWORDS = [
        'with',
        'withCount',
        'paginate',
        'perPage',
        'page',
        'with_deleted',
        'search',
    ];

    /**
     * @throws Exception
     */
    public static function apply(Builder $query, Model $model, array $filter = [], bool $first = true): void
    {
        $table = $model->getTable();
        $relations = [];

        if (isset($filter['limit'])) {
            $query->limit((int)$filter['limit']);
            unset($filter['limit']);
        }

        if (isset($filter['offset'])) {
            $query->offset((int)$filter['offset']);
            unset($filter['offset']);
        }

        if (isset($filter['orderBy'])) {
            $order_by = $filter['orderBy'];
            [$column, $dir] = is_array($order_by) ? array_values($order_by) : [$order_by, 'asc'];
            if (Schema::hasColumn($table, $column)) {
                $query->orderBy($column, $dir);
            }

            unset($filter['orderBy']);
        }

        if (isset($filter['with'])) {
            $query->with(self::getRelationsFilter($filter['with']));
        }

        if (isset($filter['withCount'])) {
            $query->withCount(self::getRelationsFilter($filter['withCount']));
        }

        if (isset($filter['search']['query'], $filter['search']['fields'])) {
            $query = self::buildSearchQuery($query, $filter['search']['query'], $filter['search']['fields']);
        }

        foreach ($filter as $key => $param) {
            if (str_contains($key, '.')) {
                $params = explode('.', $key);
                $domain = array_shift($params);
                $filterParam = implode('.', $params);

                if (!isset($relations[$domain])) {
                    $relations[$domain] = [];
                }

                $relations[$domain][$filterParam] = $param;
            } elseif (Schema::hasColumn($table, $key) &&
                !in_array($key, static::RESERVED_REQUEST_KEYWORDS, true) &&
                !in_array($key, $model->getHidden(), true)
            ) {
                [$operator, $value] = is_array($param) ? array_values($param) : ['=', $param];

                if (is_array($value) && $operator !== 'in') {
                    if ($operator === '=') {
                        $query->whereIn("$table.$key", $value);
                    } elseif ($operator === 'between' && count($value) >= 2) {
                        $query->whereBetween("$table.$key", [$value[0], $value[1]]);
                    }
                } elseif ($operator === 'in') {
                    $inArgs = is_array($value) ? $value : [$value];
                    $query->whereIn("$table.$key", $inArgs);
                } else {
                    $query->where("$table.$key", $operator, $value);
                }
            }
        }

        if (!empty($relations)) {
            foreach ($relations as $domain => $filters) {
                if (!method_exists($model, $domain)) {
                    $cls = get_class($model);
                    throw new RuntimeException("Unknown relation $cls::$domain()");
                }

                /** @var Relation $relationQuery */
                $relationQuery = $model->{$domain}();
                if (!$first) {
                    $query->orWhereHas($domain, static function ($q) use ($filters, $relationQuery, $first) {
                        self::apply($q, $relationQuery->getModel(), $filters, $first);
                    });
                } else {
                    $query->whereHas($domain, static function ($q) use ($filters, $relationQuery, $first) {
                        self::apply($q, $relationQuery->getModel(), $filters, $first);
                    });
                }
                $first = false;
            }
        }
    }

    /**
     * @throws Exception
     */
    private static function getRelationsFilter(array|string $filter): array
    {
        if (is_string($filter)) {
            $filter = explode(',', str_replace(' ', '', $filter));
        }

        return $filter;
    }

    /**
     * @param Builder $query
     * @param string $search
     * @param string[] $fields
     *
     * @return Builder
     */
    private static function buildSearchQuery(Builder $query, string $search, array $fields): Builder
    {
        $value = "%$search%";

        return $query->where(static function ($query) use ($value, $fields) {
            $field = array_shift($fields);
            if (str_contains($field, '.')) {
                [$relation, $relationField] = explode('.', $field);
                $query->whereHas($relation, static fn (Builder $query) =>
                    $query->where($relationField, 'like', $value)
                );
            } else {
                $query->where($field, 'like', $value);
            }

            foreach ($fields as $field) {
                if (str_contains($field, '.')) {
                    [$relation, $relationField] = explode('.', $field);
                    $query->orWhereHas($relation, static fn (Builder $query) =>
                        $query->where($relationField, 'like', $value)
                    );
                } else {
                    $query->orWhere($field, 'like', $value);
                }
            }
        });
    }
}
