<?php

namespace Thiagoprz\CrudTools\Models;

/**
 * Trait ModelCrud
 * @package Thiagoprz\EasyCrud\Model
 * @property array $validations Validations definitions on create, update and delete scenarios
 * <code>
 *  static $validations  = [
 *      'create' => [
 *          'field' => string|mixed,
 *      ],
 *      'update' => [
 *          'field' => string|mixed,
 *      ],
 *      'delete' => [
 *          'field' => string|mixed,
 *      ]
 *  ];
 * </code>
 * @property array $searchable Allows specifying fields that can be searched on search() method
 * <code>
 *  static $searchable = [
 *      'string_field' => 'string',
 *      'int_field' => 'int',
 *  ];
 * </code>
 * @property array $search_order Defines search() method order fields. Through request use field with name order and defined value like this: "field,direction|field_2,direction_2|..." (use as many fields to order as you wish just separating them with pipes "|")
 * <code>
 *  static $search_order = ['field' => 'DIRECTION'];
 * </code>
 * @property array $search_with Defines the relations to be brought in the search() method
 * @property array $search_count Defines which relationship will be counted along in the search() method. Use standard Laravel (see https://laravel.com/docs/master/eloquent-relationships#counting-related-models)
 * <code>
 *  static $search_count = ['related_model', 'other_related_model'];
 * </code>
 * @property array $resourceForSearch Defines a Resource to be used as the return of the search() method allowing to use Resources on api's for instance (see https://laravel.com/docs/master/eloquent-resources)
 * @property int $paginationForSearch Pagination Variable
 * @property boolean $withTrashedForbidden withTrashed() gets forbidden on this class
 * @property boolean $onlyTrashedForbidden onlyTrashed() gets forbidden on this class
 * @property boolean $noPaginationForbidden allow remove pagination forbidden on this class
 * @method array fileUploads($model) Used to define which fields are file based and will be using a upload method with customized storage path defined in it
 * <code>
 *  public static function fileUploads(Model $model)
 *  {
 *      return [
 *          'photo' => [
 *              'path' => 'photos/' . str_slug($model->name) . '.jpg',
 *          ],
 *      ];
 *  }
 * </code>
 * @method array validations() Define validations rules with a method instead of using $validations static property
 * <code>
 *  public static function validations() {
 *      return [
 *          'create' => [
 *              'field' => string|mixed,
 *          ],
 *          'update' => [
 *              'field' => string|mixed,
 *          ],
 *          'delete' => [
 *              'field' => string|mixed,
 *          ]
 *      ];
 *  }
 * </code>
 */
trait ModelCrud
{

    /**
     * Return the validations for the given scenario
     * @param string $scenario
     * @param int $id
     * @return mixed
     */
    public static function validateOn($scenario = 'create', $id = null)
    {
        if (method_exists(self::class, 'validations')) { // Using validations through a method
            // Overrides update scenario to create just to allow leaving update blank avoiding unnecessary code
            if ($scenario == 'update' && empty(self::validations($scenario, $id))) {
                $scenario = 'create';
            }
            return self::validations($scenario, $id);
        }
        if (isset(self::$validations)) {
            // Overrides update scenario to create just to allow leaving update blank avoiding unnecessary code
            if ($scenario == 'update' && empty(self::$validations['update'])) {
                $scenario = 'create';
            }
            return self::$validations[$scenario];
        }
        return [];
    }

    /**
     * @param $data
     * @return mixed
     */
    public static function search($data)
    {
        $query = self::query();
        if (isset(self::$search_count)) {
            foreach (self::$search_count as $search_countable) {
                $query->withCount($search_countable);
            }
        }
        if (isset(self::$search_with)) {
            foreach (self::$search_with as $search_withable) {
                $query->with($search_withable);
            }
        }

        if (isset(self::$searchable)) {
            $search_fields = self::$searchable;
            // Global "search" field query
            $query->where(function($where_search) use($data, $search_fields) {
                foreach ($search_fields as $field => $type) {
                    if (isset($data['search']) && !is_null($data['search'])) {
                        if ($type == 'string') {
                            $where_search->orWhere($field, 'LIKE', '%' . $data['search'] . '%');
                        } elseif ($type == 'int') {
                            $where_search->Orwhere($field, $data['search']);
                        }
                    }
                }
            });
            // Specific fields query
            $query->where(function($where) use($data, $search_fields) {
                foreach ($search_fields as $field => $type) {
                    if (strstr($field, '.') !== false) {
                        continue;
                    }
                    self::buildQuery($where, $field, $type, $data);
                }
            });
            foreach ($search_fields as $field => $definiton) {
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
        }
        if (isset($data['order'])) {
            $orders = explode('|', $data['order']);
            foreach ($orders as $order) {
                list($field, $direction) = explode(',', $order);
                $query->orderBy($field, $direction);
            }
        } elseif (isset(self::$search_order)) {
            foreach (self::$search_order as $field => $direction) {
                $query->orderBy($field, $direction);
            }
        }

        // Showing all records including deleted (withTrashed)
        if (isset($data['with_trashed']) && $data['with_trashed'] && (!isset(self::$withTrashedForbidden) || !self::$withTrashedForbidden)) { // Brings excluded records also
            $query->withTrashed();
        }

        // Showing only deleted records  (onlyTrashed)
        if (isset($data['only_trashed']) && $data['only_trashed'] && (!isset(self::$onlyTrashedForbidden) || !self::$onlyTrashedForbidden)) { // Brings only excluded records (deleted_at not null)
            $query->onlyTrashed();
        }

        $pagination=10;
        if (isset(self::$paginationForSearch)){
            $pagination = intval(self::$paginationForSearch);
        }
        $result = isset($data['no_pagination']) && (!isset(self::$noPaginationForbidden) || !self::$noPaginationForbidden) ? $query->get() : $query->paginate($pagination);
        if (isset(self::$resourceForSearch)) {
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
     * @param string $aliasField Alias name for field (where inside a related table "table.column")
     */
    private static function buildQuery(&$where, $field, $type, $data, $aliasField = null)
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
                } else if ($type == 'string') { // Like Search
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
            }
        }
        // Date, Datetime and Decimal implementation for range field search (_from and _to suffixed fields)
        if ($type == 'date' || $type == 'datetime' || $type == 'decimal' || $type == 'int') {
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
    }

}
