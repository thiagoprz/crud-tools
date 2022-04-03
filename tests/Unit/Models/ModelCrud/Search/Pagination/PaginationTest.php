<?php

namespace Unit\Models\ModelCrud\Search\Pagination;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Thiagoprz\CrudTools\Models\Dummy;
use Thiagoprz\CrudTools\Tests\TestCase;

class PaginationTest extends TestCase
{

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Dummy::factory()->count(3)->make();
    }

    /**
     * @return void
     * @testdox Search with pagination success
     */
    public function test_search_with_pagination_success(): void
    {
        $paginatedCollection = Dummy::search([]);
        $this->assertInstanceOf(LengthAwarePaginator::class, $paginatedCollection);
        $this->assertNotInstanceOf(Collection::class, $paginatedCollection);
    }

    /**
     * @return void
     * @testdox Search with no pagination success
     */
    public function test_search_with_no_pagination_success(): void
    {
        $collection = Dummy::search([
            'no_pagination' => 1,
        ]);
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertNotInstanceOf(LengthAwarePaginator::class, $collection);
    }
}