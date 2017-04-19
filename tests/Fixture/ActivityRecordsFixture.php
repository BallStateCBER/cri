<?php
namespace App\Test\Fixture;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\Fixture\TestFixture;

/**
 * ActivityRecordsFixture
 *
 */
class ActivityRecordsFixture extends TestFixture
{

    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'event' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'user_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'community_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'survey_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'meta' => ['type' => 'text', 'length' => null, 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'utf8_general_ci'
        ],
    ];
    // @codingStandardsIgnoreEnd

    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'id' => 1,
            'event' => 'Model.Community.afterAdd',
            'user_id' => 1,
            'community_id' => 1,
            'survey_id' => null,
            'meta' => 'a:3:{s:13:"communityName";s:14:"Test Community";s:8:"userName";s:13:"Graham Watson";s:8:"userRole";s:5:"admin";}',
            'created' => '2016-12-21 19:13:51'
        ],
    ];

    public $Communities;
    public $Users;

    public function setUp()
    {
        parent::setUp();
        $this->Users = TableRegistry::get('Users');
        $admin = $this->Users
            ->find('all')
            ->select(['id'])
            ->where(['role' => 'admin'])
            ->first();

        $this->records[] = [
            'id' => 1,
            'event' => 'Model.Community.afterAdd',
            'user_id' => $admin->id,
            'community_id' => 1,
            'survey_id' => null,
            'meta' => '',
            'created' => '2016-12-21 19:13:51'
        ];
    }
}
