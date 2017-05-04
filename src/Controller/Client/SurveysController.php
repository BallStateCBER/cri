<?php
namespace App\Controller\Client;

use App\Controller\AppController;
use App\Mailer\Mailer;
use Cake\Network\Exception\BadRequestException;
use Cake\Network\Exception\ForbiddenException;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;

/**
 * Surveys Controller
 *
 * @property \App\Model\Table\SurveysTable $Surveys
 */
class SurveysController extends AppController
{

    /**
     * Initialize method
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('SurveyProcessing');
        $this->loadComponent('RequestHandler');
    }

    /**
     * Invite method
     *
     * @param string|null $respondentTypePlural Either 'officials' or 'organizations'
     * @return \Cake\Http\Response
     * @throws BadRequestException
     */
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
            $msg = 'Sorry, we couldn\'t find the community corresponding with your account (#' . $clientId . ')';
            throw new NotFoundException($msg);
        }

        if ($respondentTypePlural !== 'officials' && $respondentTypePlural != 'organizations') {
            throw new BadRequestException('Questionnaire type not specified');
        }

        $respondentType = str_replace('s', '', $respondentTypePlural);
        $surveyId = $this->Surveys->getSurveyId($communityId, $respondentType);
        if (! $this->Surveys->isActive($surveyId)) {
            throw new ForbiddenException('New invitations cannot be sent out: Questionnaire is inactive');
        }

        $userId = $this->Auth->user('id');
        if ($this->request->is('post')) {
            $submitMode = $this->request->getData('submit_mode');
            if (stripos($submitMode, 'send') !== false) {
                $this->SurveyProcessing->sendInvitations($communityId, $respondentType, $surveyId);
                $this->SurveyProcessing->clearSavedInvitations($surveyId, $userId);
            } elseif (stripos($submitMode, 'save') !== false) {
                list($saveResult, $msg) = $this->SurveyProcessing->saveInvitations(
                    $this->request->getData('invitees'),
                    $surveyId,
                    $userId
                );
                if ($saveResult) {
                    $this->Flash->success($msg);

                    return $this->redirect([
                        'prefix' => 'clients',
                        'controller' => 'Communities',
                        'action' => 'index'
                    ]);
                } else {
                    $this->Flash->error($msg);
                }
            } else {
                $msg = 'There was an error submitting your form. ';
                $msg .= 'Please try again or email cri@bsu.edu for assistance.';
                $this->Flash->error($msg);
            }
        } else {
            $this->request->data['invitees'] = $this->SurveyProcessing->getSavedInvitations($surveyId, $userId);
        }

        $respondentsTable = TableRegistry::get('Respondents');
        $approvedRespondents = $respondentsTable->getApprovedList($surveyId);
        $unaddressedUnapprovedRespondents = $respondentsTable->getUnaddressedUnapprovedList($surveyId);
        $allRespondents = array_merge($approvedRespondents, $unaddressedUnapprovedRespondents);

        $survey = $this->Surveys->get($surveyId);
        $this->set([
            'surveyType' => $survey->type,
            'titleForLayout' => 'Invite Community ' . ucwords($respondentTypePlural),
        ]);
        $this->set(compact(
            'allRespondents',
            'approvedRespondents',
            'communityId',
            'respondentTypePlural',
            'surveyId',
            'titleForLayout',
            'unaddressedUnapprovedRespondents'
        ));
    }

    /**
     * Remind function
     *
     * @param string $surveyType Survey type
     * @return \App\Controller\Response|\Cake\Http\Response|null
     * @throws NotFoundException
     * @throws ForbiddenException
     */
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
        if (! $survey->active) {
            throw new ForbiddenException('Reminders cannot currently be sent out: Questionnaire is inactive');
        }

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
            $msg .= ' Email <a href="mailto:' . $adminEmail . '">' . $adminEmail . '</a> for assistance.';
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
        $unresponsive = $respondentsTable->getUnresponsive($surveyId);
        $this->set([
            'community' => $communitiesTable->get($communityId),
            'survey' => $survey,
            'titleForLayout' => 'Send Reminders to Community ' . ucwords($survey->type) . 's',
            'unresponsive' => $unresponsive,
            'unresponsiveCount' => count($unresponsive),
        ]);
    }
}
