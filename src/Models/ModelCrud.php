<?php

namespace Thiagoprz\CrudTools\Models;

/**
 * Trait ModelCrud
 * @package Thiagoprz\EasyCrud\Model
 *
 * @property static array Validations definitions on create, update and delete scenarios
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
 *
 * @property static Allows specifying fields that can be searched on search() method
 *  static $searchable = [
 *      'string_field' => 'string',
 *      'int_field' => 'int',
 *  ];
 *
 * @property static $search_order Defines search() method order fields
 *  static $search_order = ['field' => 'DIRECTION'];
 *
 * @property static $search_with Defines the relations to be brought in the search() method
 *
 * @property static $search_count Defines which relationship will be counted along in the search() method. Use standard Laravel (see https://laravel.com/docs/master/eloquent-relationships#counting-related-models)
 *  static $search_count = ['related_model', 'other_related_model'];
 *
 * @property static $resourceForSearch Defines a Resource to be used as the return of the search() method allowing to use Resources on api's for instance (see https://laravel.com/docs/master/eloquent-resources)
 *
 * @method static array fileUploads($model) Used to define which fields are file based and will be using a upload method with customized storage path defined in it
 *  public static function fileUploads(Model $model)
 *  {
 *      return [
 *          'photo' => [
 *              'path' => 'photos/' . str_slug($model->name) . '.jpg',
 *          ],
 *      ];
 *  }
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
            $query->where(function($where) use($data, $search_fields) {
                foreach ($search_fields as $field => $type) {
                    if (!empty($data['search'])) {
                        if ($type == 'string') {
                            $where->orWhere($field, 'LIKE', '%' . $data['search'] . '%');
                        } elseif ($type == 'int') {
                            $where->Orwhere($field, $data['search']);
                        }
                    }
                    if (!empty($data[$field])) {
                        if ($type == 'string') {
                            if (is_array($data[$field])) {
                                $where->where(function($query_where) use($field, $data) {
                                    foreach ($data[$field] as $datum) {
                                        $query_where->orWhere($field, 'LIKE', '%' . $datum . '%');
                                    }
                                });
                            } else {
                                $where->where($field, 'LIKE', '%' . $data[$field] . '%');
                            }
                        } elseif ($type == 'int') {
                            if (is_array($data[$field])) {
                                $where->where(function($query_where) use($field, $data) {
                                    foreach ($data[$field] as $datum) {
                                        $query_where->orWhere($field, $datum);
                                    }
                                });
                            } else {
                                $where->where($field, $data[$field]);
                            }
                        }
                    }
                }
            });
        }
        if (isset(self::$search_order)) {
            foreach (self::$search_order as $field => $direction) {
                $query->orderBy($field, $direction);
            }
        }
        if (isset(self::$resourceForSearch)) {
            return self::$resourceForSearch::collection(isset($data['no_pagination']) ? $query->get() : $query->paginate(10));
        }
        return isset($data['no_pagination']) ? $query->get() : $query->paginate(10);
    }

}