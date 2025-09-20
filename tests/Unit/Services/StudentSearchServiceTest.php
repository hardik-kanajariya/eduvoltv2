<?php

namespace Tests\Unit\Services;

use App\Models\Student;
use App\Services\StudentSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class StudentSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StudentSearchService $searchService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->searchService = new StudentSearchService();
    }

    public function test_search_returns_paginated_results()
    {
        // Create test students
        Student::factory()->count(15)->create();

        $request = Request::create('/students/search', 'GET');
        $result = $this->searchService->search($request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(15, $result->total());
    }

    public function test_search_filters_by_text()
    {
        // Create students with specific names
        Student::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        Student::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);
        Student::factory()->create(['first_name' => 'Bob', 'last_name' => 'Johnson']);

        $request = Request::create('/students/search', 'GET', ['search' => 'John']);
        $result = $this->searchService->search($request);

        $this->assertEquals(2, $result->total()); // John Doe and Bob Johnson (contains 'John')
    }

    public function test_search_filters_by_status()
    {
        Student::factory()->create(['status' => 'active']);
        Student::factory()->create(['status' => 'inactive']);
        Student::factory()->create(['status' => 'graduated']);

        $request = Request::create('/students/search', 'GET', ['status' => 'active']);
        $result = $this->searchService->search($request);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('active', $result->items()[0]->status);
    }

    public function test_search_filters_by_grade()
    {
        Student::factory()->create(['grade' => '9']);
        Student::factory()->create(['grade' => '10']);
        Student::factory()->create(['grade' => '11']);

        $request = Request::create('/students/search', 'GET', ['grade' => '10']);
        $result = $this->searchService->search($request);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('10', $result->items()[0]->grade);
    }

    public function test_get_filter_options_returns_correct_structure()
    {
        // Create some test data
        Student::factory()->create(['status' => 'active', 'grade' => '9']);
        Student::factory()->create(['status' => 'inactive', 'grade' => '10']);

        $options = $this->searchService->getFilterOptions();

        $this->assertArrayHasKey('statuses', $options);
        $this->assertArrayHasKey('grades', $options);
        $this->assertArrayHasKey('sections', $options);
        $this->assertArrayHasKey('genders', $options);

        $this->assertContains('active', $options['statuses']);
        $this->assertContains('inactive', $options['statuses']);
        $this->assertContains('9', $options['grades']);
        $this->assertContains('10', $options['grades']);
    }

    public function test_get_search_stats_returns_correct_data()
    {
        Student::factory()->count(5)->create(['status' => 'active']);
        Student::factory()->count(3)->create(['status' => 'inactive']);

        $request = Request::create('/students/search', 'GET');
        $stats = $this->searchService->getSearchStats($request);

        $this->assertArrayHasKey('total_count', $stats);
        $this->assertArrayHasKey('status_counts', $stats);
        $this->assertArrayHasKey('grade_counts', $stats);

        $this->assertEquals(8, $stats['total_count']);
        $this->assertEquals(5, $stats['status_counts']['active']);
        $this->assertEquals(3, $stats['status_counts']['inactive']);
    }

    public function test_export_results_returns_correct_data()
    {
        Student::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

        $request = Request::create('/students/export', 'GET', ['format' => 'csv']);
        $result = $this->searchService->exportResults($request);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total_count', $result);
        $this->assertArrayHasKey('format', $result);
        $this->assertEquals(1, $result['total_count']);
        $this->assertEquals('John', $result['data'][0]['first_name']);
        $this->assertEquals('Doe', $result['data'][0]['last_name']);
    }
}
