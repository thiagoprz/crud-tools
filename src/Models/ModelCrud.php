<?php

namespace Thiagoprz\CrudTools\Models;

/**
 * Trait ModelCrud
 * @package Thiagoprz\EasyCrud\Model
 * @property static array $validations  = [
        'create' => [],
        'update' => [],
        'delete' => [],
    ];
 * @method static array fileUploads()
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
                            $where->where($field, 'LIKE', '%' . $data[$field] . '%');
                        } elseif ($type == 'int') {
                            $where->where($field, $data[$field]);
                        }
                    }
                }
            });
        }
        if (isset(self::$resourceForSearch)) {
            return self::$resourceForSearch::collection(isset($data['no_pagination']) ? $query->get() : $query->paginate(10));
        }
        return isset($data['no_pagination']) ? $query->get() : $query->paginate(10);
    }

}