<?php
namespace App\Test\TestCase\Controller\Admin;

use App\Model\Table\DeliverablesTable;
use App\Model\Table\DeliveriesTable;
use App\Test\TestCase\ApplicationTest;
use Cake\Event\EventList;
use Cake\ORM\TableRegistry;

/**
 * App\Controller\DeliveriesController Test Case
 *
 * @property DeliveriesTable $Deliveries
 */
class DeliveriesControllerTest extends ApplicationTest
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.activity_records',
        'app.communities',
        'app.deliveries',
        'app.deliverables',
        'app.queued_jobs',
        'app.users'
    ];

    /**
     * SetUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->configRequest([
            'environment' => ['HTTPS' => 'on']
        ]);

        $this->Deliveries = TableRegistry::get('Surveys');
        $this->Deliveries->getEventManager()->setEventList(new EventList());
    }

    /**
     * Tests that the correct event is fired after adding a delivery
     *
     * @return void
     */
    public function testAddEvent()
    {
        $this->session($this->adminUser);
        $url = [
            'prefix' => 'admin',
            'controller' => 'Deliveries',
            'action' => 'add'
        ];
        $data = [
            'community_id' => 1,
            'deliverable_id' => DeliverablesTable::PRESENTATION_A_MATERIALS
        ];
        $this->post($url, $data);
        $this->assertEventFired('Model.Delivery.afterAdd', $this->_controller->getEventManager());
    }
}