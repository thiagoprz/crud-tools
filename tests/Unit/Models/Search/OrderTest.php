<?php declare(strict_types = 1);

namespace Unit\Models\Search;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Thiagoprz\CrudTools\Tests\TestCase;
use Unit\Models\Dummy;

class OrderTest extends TestCase
{
    /**
     * @var Dummy[]
     */
    private $collection;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->collection = Dummy::factory()->count(20)->create();
    }

    /**
     * @return void
     * @testdox Search with well-defined order
     */
    public function test_search_order_success(): void
    {
        $requestData = [
            'order' => 'id,DESC',
        ];
        $dummy = new Dummy();
        $paginatedCollection = $dummy->search($requestData);
        $this->assertNotEquals($this->collection[0], $paginatedCollection[0]);
        $lastItem = Dummy::orderBy('id', 'DESC')->first();
        $this->assertEquals($lastItem->getAttributes(), $paginatedCollection->items()[0]->getAttributes());
        $this->assertInstanceOf(LengthAwarePaginator::class, $paginatedCollection);
        $this->assertNotInstanceOf(Collection::class, $paginatedCollection);
    }
}
