<?php
namespace App\Controller\Admin;

use App\Controller\AppController;

class ResponsesController extends AppController
{
    private function adminViewPagination($surveyId)
    {
        $this->paginate['Response'] = [
            'conditions' => ['survey_id' => $surveyId],
            'contain' => [
                'Respondent' => [
                    'fields' => ['id', 'email', 'name', 'approved']
                ]
            ],
            'order' => ['response_date' => 'DESC']
        ];
        $count = $this->Responses->find('all')
            ->where(['survey_id' => $surveyId])
            ->count();
        if ($count) {
            $this->paginate['Response']['limit'] = $count;
        }
        $this->cookieSort('AdminResponsesView');
    }

    public function view($surveyId = null)
    {
        $surveysTable = TableRegistry::get('Surveys');
        $areasTable = TableRegistry::get('Areas');

        try {
            $survey = $surveysTable->get($surveyId);
        } catch (RecordNotFoundException $e) {
            throw new NotFoundException('Sorry, we couldn\'t find a survey in the database with that ID number.');
        }

        $communitiesTable = TableRegistry::get('Communities');
        $areaId = $communitiesTable->getAreaId($survey->community_id);
        $area = $areasTable->get($areaId);

        $totalAlignment = 0;
        $this->adminViewPagination($surveyId);
        $responses = $this->paginate();

        // Only return the most recent response for each respondent
        $responsesReturned = [];
        $alignmentSum = 0;
        $approvedCount = 0;
        foreach ($responses as $i => $response) {
            $respondentId = $response['Respondent']['id'];

            if (isset($responsesReturned[$respondentId]['revision_count'])) {
                $responsesReturned[$respondentId]['revision_count']++;
                continue;
            }

            $responsesReturned[$respondentId] = $response;
            $responsesReturned[$respondentId]['revision_count'] = 0;
            if ($response['Respondent']['approved']) {
                $alignmentSum += $response['Response']['alignment'];
                $approvedCount++;
            }
        }

        // Process update
        if ($this->request->is('post') || $this->request->is('put')) {
            $survey = $this->Surveys->patchEntity($this->request->data);
            if ($this->Surveys->save($survey)) {
                $this->Flash->success('Alignment set');
                $survey->alignment_calculated = $survey->modified;
                $this->Surveys->save($survey);
            } else {
                $this->Flash->error('There was an error updating this survey');
            }

        // Set default form field values
        } else {
            $this->request->data = [
                'Survey' => [
                    'id' => $survey->id,
                    'alignment' => $survey->alignment,
                    'alignment_passed' => $survey->alignment_passed
                ]
            ];
        }

        if ($this->Surveys->newResponsesHaveBeenReceived($surveyId)) {
            $this->Flash->set('New responses have been received since this community\'s alignment was last set.');
        }

        if ($survey->alignment_calculated) {
            $timestamp = strtotime($survey->alignment_calculated);
            $alignmentLastSet = date('F j', $timestamp).'<sup>'.date('S', $timestamp).'</sup>'.date(', Y', $timestamp);
        } else {
            $alignmentLastSet = null;
        }

        $community = $communitiesTable->get($survey->community_id);
        $this->set([
            'titleForLayout' => 'View and Update Alignment',
            'communityName' => $community->name,
            'surveyType' => $survey->type,
            'responses' => $responsesReturned,
            'area' => $area,
            'sectors' => $this->Surveys->getSectors(),
            'surveyId' => $surveyId,
            'alignmentLastSet' => $alignmentLastSet,
            'totalAlignment' => $approvedCount ? round($alignmentSum / $approvedCount) : 0
        ]);
    }
}