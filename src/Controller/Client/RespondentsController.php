<?php
namespace App\Controller\Client;

use App\Controller\AppController;
use Cake\Network\Exception\BadRequestException;

class RespondentsController extends AppController
{
    private function setupPagination($communityId, $surveyType)
    {
        $surveysTable = TableRegistry::get('Surveys');
        $surveyId = $surveysTable->getSurveyId($communityId, $surveyType);
        $this->Paginator->settings = [
            'conditions' => ['Respondent.survey_id' => $surveyId],
            'contain' => [
                'Response' => [
                    'fields' => ['Response.response_date'],
                    'limit' => 1,
                    'order' => 'Response.response_date DESC'
                ]
            ],
            'fields' => [
                'Respondent.email',
                'Respondent.name',
                'Respondent.approved'
            ],
            'joins' => [
                [
                    'table' => 'responses',
                    'type' => 'left',
                    'alias' => 'Response',
                    'conditions' => ['Respondent.id = Response.respondent_id']
                ]
            ],
            'limit' => 50
        ];
    }

    public function index($surveyType = null)
    {
        if ($surveyType != 'official' && $surveyType != 'organization') {
            throw new BadRequestException('Survey type not specified');
        }

        $clientId = $this->getClientId();
        $communitiesTable = TableRegistry::get('Communities');
        $communityId = $communitiesTable->getClientCommunityId($clientId);
        if ($communityId) {
            $community = $communitiesTable->get($communityId);
            $titleForLayout = $community->name.' '.ucwords($surveyType).' Survey Respondents';
            $this->setupPagination($community->id, $surveyType);
            $respondents = $this->paginate();
        } else {
            $titleForLayout = 'Survey Respondents';
            $respondents = [];
        }
        $this->set(compact(
            'titleForLayout',
            'respondents',
            'surveyType'
        ));
    }

    public function unapproved($surveyType = null)
    {
        if ($surveyType != 'official' && $surveyType != 'organization') {
            throw new NotFoundException('Invalid survey type');
        }

        $communitiesTable = TableRegistry::get('Communities');
        $clientId = $this->getClientId();
        $communityId = $communitiesTable->getClientCommunityId($clientId);

        if (! $communityId) {
            throw new NotFoundException('Your account is not currently assigned to a community');
        }

        $community = $communitiesTable->get($communityId);
        $surveysTable = TableRegistry::get('Surveys');
        $surveyId = $surveysTable->getSurveyId($communityId, $surveyType);

        $this->set([
            'titleForLayout' => $community->name.' Uninvited '.ucwords($surveyType).' Survey Respondents',
            'respondents' => [
                'unaddressed' => $this->Respondents->getUnaddressedUnapproved($surveyId),
                'dismissed' => $this->Respondents->getDismissed($surveyId)
            ]
        ]);
    }
}
