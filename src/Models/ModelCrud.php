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
 * @property int $paginationForSearch Pagination Variable *
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
     * @return mixed
     */
    public static function validateOn($scenario = 'create')
    {
        // Overrides update scenario to create just to allow leaving update blank avoiding unnecessary code
        if (isset(self::$validations)) {
            if ($scenario == 'update' && empty(self::$validations['update'])) {
                $scenario = 'create';
            }
            return self::$validations[$scenario];
        }
        if (method_exists(self::class, 'validations')) { // Using validations through a method
            $validations = self::validations($scenario);
            return $validations[$scenario];
        }
        return [];
    }

    /**
     * @param $data
     * @return mixed
     */
    public static function search($data)
    {
        $query = self::whereNotNull('created_at');
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
                    if (isset($data[$field]) && !is_null($data[$field])) {
                        if ($type == 'string_match' || $type == 'date' || $type == 'datetime' || $type == 'int') { // Exact search
                            if (is_array($data[$field])) {
                                $where->where(function($query_where) use($field, $data) {
                                    foreach ($data[$field] as $datum) {
                                        $query_where->orWhere($field, $datum);
                                    }
                                });
                            } else {
                                $where->where($field, $data[$field]);
                            }
                        } else if ($type == 'string') { // Like Search
                            if (is_array($data[$field])) {
                                $where->where(function($query_where) use($field, $data) {
                                    foreach ($data[$field] as $datum) {
                                        $query_where->orWhere($field, 'LIKE', '%' . $datum . '%');
                                    }
                                });
                            } else {
                                $where->where($field, 'LIKE', '%' . $data[$field] . '%');
                            }
                        }
                    }
                    // Date, Datetime and Decimal implementation for range field search (_from and _to suffixed fields)
                    if ($type == 'date' || $type == 'datetime' || $type == 'decimal') {
                        if (!empty($data[$field . '_from'])) {
                            $value = $data[$field . '_from'];
                            if ($type == 'datetime' && strlen($value) < 16) { // If datetime was informed only by its date (Y-m-d instead of Y-m-d H:i:s)
                                $value .= ' 00:00:00';
                            }
                            $where->where($field, '>=', $value);
                        }
                        if (!empty($data[$field . '_to'])) {
                            $value = $data[$field . '_from'];
                            if ($type == 'datetime' && strlen($value) < 16) { // If datetime was informed only by its date (Y-m-d instead of Y-m-d H:i:s)
                                $value .= ' 00:00:00';
                            }
                            $where->where($field, '<=', $value);
                        }
                    }
                }
            });
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
        $pagination=10;
        if (isset(self::$paginationForSearch)){
            $pagination = intval(self::$paginationForSearch);
        }
        $result = isset($data['no_pagination']) ? $query->get() : $query->paginate($pagination);
        if (isset(self::$resourceForSearch)) {
            return self::$resourceForSearch::collection($result);
        }
        return $result;
    }

}
