<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\ApplicationTest;

/**
 * App\Controller\StatCategoriesController Test Case
 *
 * @uses \App\Controller\Admin\StatCategoriesController
 */
class StatCategoriesControllerTest extends ApplicationTest
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.StatCategories',
        'app.Statistics',
    ];

    /**
     * Test for /admin/stat-categories/import
     *
     * @return void
     */
    public function testImport()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
