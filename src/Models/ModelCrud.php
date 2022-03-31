<?php

namespace Thiagoprz\CrudTools\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Thiagoprz\CrudTools\Interfaces\ModelCrudInterface;

/**
 * Trait ModelCrud
 * @package Thiagoprz\EasyCrud\Model
 * @implements ModelCrudInterface
 *
 * @property array $validations Validations definitions on create, update and delete scenarios
 * @property array $searchable Allows specifying fields that can be searched on search() method
 * @property array $search_order Defines search() method order fields. Through request use field with name order and defined value like this: "field,direction|field_2,direction_2|..." (use as many fields to order as you wish just separating them with pipes "|")
 * @property array $search_with Defines the relations to be brought in the search() method
 * @property array $search_count Defines which relationship will be counted along in the search() method. Use standard Laravel (see https://laravel.com/docs/master/eloquent-relationships#counting-related-models)
 * @property array $resourceForSearch Defines a Resource to be used as the return of the search() method allowing to use Resources on api's for instance (see https://laravel.com/docs/master/eloquent-resources)
 * @property int $paginationForSearch Pagination Variable
 * @property boolean $withTrashedForbidden withTrashed() gets forbidden on this class
 * @property boolean $onlyTrashedForbidden onlyTrashed() gets forbidden on this class
 * @property boolean $noPaginationForbidden allow remove pagination forbidden on this class
 */
trait ModelCrud
{

    /**
     * @see ModelCrud::$search_order
     * @param $query
     * @return void
     */
    public function searchOrder(&$query)
    {
        if (isset(self::$search_order)) {
            foreach (self::$search_order as $field => $direction) {
                $query->orderBy($field, $direction);
            }
        }
    }

    /**
     * @see ModelCrud::$validations
     * @param int|null $id
     * @return array
     */
    public static function validations(int $id = null): array
    {
        $validations = self::$validations;
        return array_map(function(string $rules) use($id) {
            return Str::replace('$id', $id, $rules);
        }, $validations);
    }

    /**
     * Return the validations for the given scenario
     * @see ModelCrud::$validations
     * @param string $scenario
     * @param int|null $id
     * @return mixed
     */
    public static function validateOn(string $scenario = 'create', int $id = null): array
    {
        $validations = self::validations($id);
        if ($scenario === 'update' && empty($validations['update'])) {
            $scenario = 'create';
        }
        return self::$validations[$scenario];
    }

    /**
     * Attaches related records to every result on the search query
     * @see ModelCrud::$search_count
     * @param $query
     * @return void
     */
    public function searchWithCount(&$query)
    {
        if (isset(self::$search_count)) {
            foreach (self::$search_count as $search_countable) {
                $query->withCount($search_countable);
            }
        }
    }

    /**
     * @see ModelCrud::$search_with
     * @param $query
     * @return void
     */
    public function searchWith(&$query)
    {
        if (isset(self::$search_with)) {
            foreach (self::$search_with as $search_withable) {
                $query->with($search_withable);
            }
        }
    }

    /**
     * @see ModelCrud::$paginationForSearch
     * @param $query
     * @return mixed
     */
    public function setSearchPagination($query)
    {
        $pagination = self::$paginationForSearch ?? 10;
        return $query->paginate($pagination);
    }

    /**
     * @see ModelCrud::$withTrashedForbidden
     * @param $query
     * @param array $data
     * @return void
     */
    public function applyWithTrashed($query, array $data)
    {
        if (!self::$withTrashedForbidden && $data['with_trashed']) {
            $query->withTrashed();
        }
    }

    /**
     * @see ModelCrud::$onlyTrashedForbidden
     * @param $query
     * @param array $data
     * @return void
     */
    public function applyOnlyTrashed($query, array $data)
    {
        if (!self::$onlyTrashedForbidden && $data['only_trashed']) {
            $query->onlyTrashed();
        }
    }

    /**
     * @param array $data
     * @return array
     */
    public function search(array $data): array
    {
        // Starts query
        $query = self::query();

        $searchableFields = !method_exists(__CLASS__, 'searchable') ? self::searchable() : self::$searchable;
        $query->where(function($where) use($data, $searchableFields) {
            foreach ($searchableFields as $field => $type) {
                if (strstr($field, '.') !== false) {
                    continue;
                }
                self::buildQuery($where, $field, $type, $data);
            }
        });

        $searchableRelatedFields = !method_exists(__CLASS__, 'searchable') ? self::searchable() : self::$searchableRelated;
        foreach ($searchableRelatedFields as $field => $definiton) {
            if (strstr($field, '.') === false) {
                continue;
            }
            $arr = explode('.', $field);
            $real_field = $arr[1];
            $table = $arr[0];
            $query->whereHas($table, function($where) use($data, $real_field, $definiton) {
                self::buildQuery($where, $real_field, $definiton['type'], $data, $definiton['table'] . '.' . $real_field);
            });
        }

        // Gets related records attached to
        self::searchWith($query);

        // Gets count result for related records attached to
        self::searchWithCount($query);

        // Defines "order by"
        self::searchOrder($query);

        /**
         * If model uses SoftDeletes allows query excluded records
         * @see ModelCrud::$onlyTrashedForbidden
         * @see ModelCrud::$withTrashedForbidden
         */
        if (in_array(SoftDeletes::class, class_uses(self::class), true)) {
            self::applyOnlyTrashed($query);
            self::applyWithTrashed($query);
        }

        $result = !empty($data['no_pagination']) && !self::$noPaginationForbidden ? $query->get() : self::setSearchPagination($query);
        if (!empty(self::$resourceForSearch)) {
            return self::$resourceForSearch::collection($result);
        }
        return $result;
    }

    /**
     * Builds the main query based on a informed field
     * @param mixed $where Query builder command
     * @param string $field "The" field
     * @param string $type Type of field (string, int, date, datetime...)
     * @param array $data Data sent on $request
     * @param string|null $aliasField Alias name for field (where inside a related table "table.column")
     */
    private static function buildQuery(&$where, string $field, string $type, array $data, string $aliasField = null)
    {
        if (!$aliasField) {
            $aliasField = $field;
        }
        if (isset($data[$field]) && !is_null($data[$field])) {
            $customMethod = 'search' . ucfirst($field);
            if (method_exists(self::class, $customMethod)) { // If field has custom "search" method uses it
                $where->where(function($custom_query) use($field, $data, $customMethod) {
                    self::$customMethod($custom_query, $data[$field]);
                });
            } else {
                if ($type == 'string_match' || $type == 'date' || $type == 'datetime' || $type == 'int') { // Exact search
                    self::exactFilter($where, $field, $data, $aliasField);
                } else if ($type == 'string') { // Like Search
                    self::likeFilter($where, $field, $data, $aliasField);
                }
            }
        }
        // Date, Datetime and Decimal implementation for range field search (_from and _to suffixed fields)
        if ($type == 'date' || $type == 'datetime' || $type == 'decimal' || $type == 'int') {
            self::rangeFilter($where, $field, $data, $aliasField, $type);
        }
    }

    /**
     * @param $where
     * @param $field
     * @param $data
     * @param $aliasField
     * @return void
     */
    private static function exactFilter(&$where, $field, $data, $aliasField): void
    {
        if (is_array($data[$field])) {
            $where->where(function($query_where) use($field, $data, $aliasField) {
                foreach ($data[$field] as $datum) {
                    $query_where->orWhere($aliasField, $datum);
                }
            });
        } elseif(strpos($data[$field], '!=') === 0) {
            $where->where($field, '!=', str_replace('!=', '', $data[$field]));
        } else {
            $where->where($field, $data[$field]);
        }
    }

    /**
     * @param $where
     * @param $field
     * @param $data
     * @param $aliasField
     * @return void
     */
    private static function likeFilter(&$where, $field, $data, $aliasField)
    {
        if (is_array($data[$field])) {
            $where->where(function($query_where) use($field, $data, $aliasField) {
                foreach ($data[$field] as $datum) {
                    $query_where->orWhere($aliasField, 'LIKE', '%' . $datum . '%');
                }
            });
        } else {
            $where->where($field, 'LIKE', '%' . $data[$field] . '%');
        }
    }

    /**
     * @param $where
     * @param $field
     * @param $data
     * @param $aliasField
     * @param $type
     * @return void
     */
    private static function rangeFilter(&$where, $field, $data, $aliasField, $type)
    {
        if (!empty($data[$field . '_from'])) {
            $value = $data[$field . '_from'];
            if ($type == 'datetime' && strlen($value) < 16) { // If datetime was informed only by its date (Y-m-d instead of Y-m-d H:i:s)
                $value .= ' 00:00:00';
            }
            $where->where($field, '>=', $value);
        }
        if (!empty($data[$field . '_to'])) {
            $value = $data[$field . '_to'];
            if ($type == 'datetime' && strlen($value) < 16) { // If datetime was informed only by its date (Y-m-d instead of Y-m-d H:i:s)
                $value .= ' 23:59:59';
            }
            $where->where($aliasField, '<=', $value);
        }
    }

    /**
     * @param ModelCrudInterface|null $model
     * @return array
     */
    public function fileUploads(ModelCrudInterface $model = null): array
    {
        return [];
    }
}
