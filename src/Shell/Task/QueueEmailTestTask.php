<?php
declare(strict_types=1);

namespace App\Shell\Task;

use Cake\Core\Configure;
use Cake\Mailer\MailerAwareTrait;
use Queue\Shell\Task\QueueTask;

class QueueEmailTestTask extends QueueTask
{
    use MailerAwareTrait;

    /**
     * Adds the EmailTest task
     *
     * @return void
     */
    public function add()
    {
        $this->out('CRI Email Test Task');
        $this->hr();
        $default = Configure::read('admin_email');
        $email = $this->in('What address should the test email be sent to?', null, $default);
        $result = $this->QueuedJobs->createJob(
            'EmailTest',
            compact('email'),
            ['reference' => $email]
        );

        if ($result) {
            $this->out('Job created');

            return;
        }

        $this->err('Error creating job');
    }

    /**
     * Run function.
     * This function is executed, when a worker is executing a task.
     * The return parameter will determine, if the task will be marked completed, or be requeued.
     *
     * @param array $data The array passed to QueuedTask->createJob()
     * @param int $id The id of the QueuedTask
     * @return void
     */
    public function run(array $data, $id)
    {
        $this->getMailer('Test')->send('test', [$data['email']]);
    }
}
