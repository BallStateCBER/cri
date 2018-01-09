<?php
namespace App\Test\TestCase\Event;

use App\Event\EmailListener;
use App\Test\TestCase\ApplicationTest;
use Cake\Event\Event;

class EmailListenerTest extends ApplicationTest
{
    public $fixtures = [
        'app.communities',
        'app.users',
        'app.queued_jobs',
    ];

    /**
     * SetUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Tests EmailListener::sendDeliverMandatoryPresentationEmail()
     *
     * @return void
     * @throws \Exception
     */
    public function testSendMandPresEmail()
    {
        $listener = new EmailListener();
        $event = new Event('Model.Survey.afterDeactivate');
        $meta = ['communityId' => 1];
        $listener->sendDeliverMandatoryPresentationEmail($event, $meta);
        $this->assertAdminTaskEmailEnqueued('deliverMandatoryPresentation');
    }
}
