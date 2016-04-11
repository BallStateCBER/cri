<?php
namespace App\Controller\Client;

use App\Controller\AppController;
use App\Mailer\Mailer;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;

/**
 * Surveys Controller
 *
 * @property \App\Model\Table\SurveysTable $Surveys
 */
class SurveysController extends AppController
{

    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('SurveyProcessing');
    }

    public function invite($respondentTypePlural = null)
    {
        // Find and validate community
        $clientId = $this->getClientId();
        if (! $clientId) {
            return $this->chooseClientToImpersonate();
        }
        $communitiesTable = TableRegistry::get('Communities');
        $communityId = $communitiesTable->getClientCommunityId($clientId);
        if (! $communityId || ! $communitiesTable->exists(['id' => $communityId])) {
            throw new NotFoundException('Sorry, we couldn\'t find the community corresponding with your account (#'.$clientId.')');
        }

        $this->Surveys->validateRespondentTypePlural($respondentTypePlural, $communityId);
        $respondentType = str_replace('s', '', $respondentTypePlural);
        $surveyId = $this->Surveys->getSurveyId($communityId, $respondentType);

        if ($this->request->is('post')) {
            $this->SurveyProcessing->processInvitations($communityId, $respondentType, $surveyId);
        }

        $respondentsTable = TableRegistry::get('Respondents');
        $approvedRespondents = $respondentsTable->getApprovedList($surveyId);
        $unaddressedUnapprovedRespondents = $respondentsTable->getUnaddressedUnapprovedList($surveyId);
        $allRespondents = array_merge($approvedRespondents, $unaddressedUnapprovedRespondents);

        $survey = $this->Surveys->get($surveyId);
        $this->set([
            'surveyType' => $survey->type,
            'titleForLayout' => 'Invite Community '.ucwords($respondentTypePlural),
        ]);
        $this->set(compact(
            'allRespondents',
            'approvedRespondents',
            'communityId',
            'respondentTypePlural',
            'titleForLayout',
            'unaddressedUnapprovedRespondents'
        ));
    }

    public function remind($surveyType)
    {
        $clientId = $this->getClientId();
        if (! $clientId) {
            return $this->chooseClientToImpersonate();
        }

        $communitiesTable = TableRegistry::get('Communities');
        $communityId = $communitiesTable->getClientCommunityId($clientId);
        if (! $communityId) {
            throw new NotFoundException('Your account is not currently assigned to a community');
        }

        $surveysTable = TableRegistry::get('Surveys');
        $surveyId = $surveysTable->getSurveyId($communityId, $surveyType);
        $survey = $surveysTable->get($surveyId);

        if ($this->request->is('post')) {
            $Mailer = new Mailer();
            $sender = $this->Auth->user();
            if ($Mailer->sendReminders($surveyId, $sender)) {
                $this->Flash->success('Reminder email successfully sent');
                return $this->redirect([
                    'prefix' => 'client',
                    'controller' => 'Communities',
                    'action' => 'index'
                ]);
            }

            $msg = 'There was an error sending reminder emails.';
            $adminEmail = Configure::read('admin_email');
            $msg .= ' Email <a href="mailto:'.$adminEmail.'">'.$adminEmail.'</a> for assistance.';
            $this->Flash->error($msg);

            // Redirect so that hitting refresh won't re-send POST request
            return $this->redirect([
                'prefix' => 'client',
                'controller' => 'Surveys',
                'action' => 'remind',
                $survey->type
            ]);

        }

        $respondentsTable = TableRegistry::get('Respondents');
        $unresponsiveCount = $respondentsTable->getUnresponsiveCount($surveyId);
        $community = $communitiesTable->get($communityId);
        $this->set([
            'community' => $community,
            'survey' => $survey,
            'titleForLayout' => 'Send Reminders to Community '.ucwords($survey->type).'s',
            'unresponsiveCount' => $unresponsiveCount
        ]);
    }
}
