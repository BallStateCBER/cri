<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\I18n\Time;
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
        $this->Auth->allow(['cronImport']);
    }

    public function import($surveyId = null)
    {
        $this->viewBuilder()->layout('blank');
        $importedCount = 0;

        // Collect respondents
        $respondentsTable = TableRegistry::get('Respondents');
        list($success, $respondents) = $respondentsTable->getNewFromSurveyMonkey($surveyId);
        if (! $success) {
            return $this->renderImportError($respondents);
        }

        // Convert IDs from integers to strings (the SurveyMonkey API is particular about this)
        $smRespondentIds = array_keys($respondents);
        foreach ($smRespondentIds as &$smRId) {
            $smRId = (string) $smRId;
        }

        // Collect responses
        $responsesTable = TableRegistry::get('Responses');
        list($success, $responses) = $responsesTable->getFromSurveyMonkeyForRespondents($surveyId, $smRespondentIds);
        if (! $success) {
            return $this->renderImportError($responses);
        }

        // Determine actual ranks (for alignment calculation)
        $survey = $this->Surveys->get($surveyId);
        $communitiesTable = TableRegistry::get('Communities');
        $areaId = $communitiesTable->getParentAreaId($survey->community_id);
        $areasTable = TableRegistry::get('Areas');
        $actualRanks = $areasTable->getPwrrrRanks($areaId);

        $usersTable = TableRegistry::get('Users');
        if (is_array($responses)) {
            foreach ($responses as $smRespondentId => $response) {
                $respondent = $responsesTable->extractRespondentInfo($response);
                $respondentRecord = $respondentsTable->getMatching($surveyId, $respondent, $smRespondentId);
                $serializedResponse = base64_encode(serialize($response));

                // Add new respondent
                if (empty($respondentRecord)) {
                    $approved = $respondentsTable->isAutoApproved($survey, $respondent['email']);
                    $newRespondent = $respondentsTable->newEntity([
                        'email' => $respondent['email'],
                        'name' => $respondent['name'],
                        'survey_id' => $surveyId,
                        'sm_respondent_id' => $smRespondentId,
                        'invited' => false,
                        'approved' => $approved ? 1 : 0
                    ]);
                    $errors = $newRespondent->errors();
                    if (empty($errors)) {
                        $respondentsTable->save($newRespondent);
                        $respondentId = $newRespondent->id;
                    } else {
                        $message = 'Error saving respondent.';
                        $message .= ' Validation errors: '.print_r($errors, true);
                        return $this->renderImportError($message);
                    }

                // Update existing respondent
                } else {
                    $newData = [];
                    if (empty($respondentRecord->smRespondentId)) {
                        $newData['sm_respondent_id'] = $smRespondentId;
                    }
                    if (empty($respondentRecord->name)) {
                        $newData['name'] = $respondent['name'];
                    }
                    if (! empty($newData)) {
                        $respondentRecord = $respondentsTable->patchEntity($respondentRecord, $newData);
                        $errors = $respondentRecord->errors();
                        if (empty($errors)) {
                            $respondentsTable->save($respondentRecord);
                        } else {
                            $message = 'Error updating respondent.';
                            $message .= ' Validation errors: '.print_r($errors, true);
                            return $this->renderImportError($message);
                        }
                    }
                    $respondentId = $respondentRecord->id;
                }

                // Skip recording response if it's already recorded
                if ($responsesTable->isRecorded($respondentId, $survey, $serializedResponse)) {
                    continue;
                }

                // Get individual ranks and alignment
                $responseRanks = $responsesTable->getResponseRanks($serializedResponse, $survey);
                $alignment = $responsesTable->calculateAlignment($actualRanks, $responseRanks);

                // Save response
                $responseFields = [
                    'respondent_id' => $respondentId,
                    'survey_id' => $surveyId,
                    'response' => $serializedResponse,
                    'alignment' => $alignment,
                    'response_date' => new Time($respondents[$smRespondentId])
                ];
                foreach ($responseRanks as $sector => $rank) {
                    $responseFields["{$sector}_rank"] = $rank;
                }
                $newResponse = $responsesTable->newEntity($responseFields);

                $errors = $newResponse->errors();
                if (empty($errors)) {
                    $responsesTable->save($newResponse);
                    $importedCount++;
                } else {
                    $message = 'Error saving response.';
                    $message .= ' Validation errors: '.print_r($errors, true);
                    return $this->renderImportError($message);
                }
            }

            // Set new last_modified_date
            $dates = array_values($respondents);
            $survey->respondents_last_modified_date = new Time(max($dates));
            $this->Surveys->save($survey);
        }

        // Finalize
        if ($importedCount) {
            $message = $importedCount.__n(' response', ' responses', $importedCount).' imported';
        } else {
            $message = 'No new responses to import';
        }
        $this->Surveys->setChecked($surveyId);

        $this->set(compact('message'));
    }

    private function renderImportError($message)
    {
        $this->response->statusCode(500);
        $this->set(compact('message'));
        return $this->render('import');
    }

    public function getSurveyList()
    {
        $params = $this->request->query;
        $result = $this->Surveys->getSMSurveyList($params);
        $this->set([
            'result' => json_encode($result)
        ]);
        $this->viewBuilder()->layout('json');
        $this->render('api');
    }

    public function getSurveyUrl($smId = null)
    {
        $this->set([
            'result' => $this->Surveys->getSMSurveyUrl($smId)
        ]);
        $this->viewBuilder()->layout('json');
        $this->render('api');
    }

    /**
     * Used by a JS call to find out what community, if any, a survey has already been assigned to
     */
    public function checkSurveyAssignment($smSurveyId = null)
    {
        $survey = $this->Surveys->find('all')
            ->select(['community_id', 'type'])
            ->where(['sm_id' => $smSurveyId])
            ->limit(1);
        if ($survey->isEmpty()) {
            $community = null;
        } else {
            $survey = $survey->first();
            $communitiesTable = TableRegistry::get('Communities');
            $community = [
                'id' => $survey->community_id,
                'name' => $communitiesTable->get($survey->community_id)->name,
                'type' => $survey->type
            ];
        }
        $this->viewBuilder()->layout('json');
        $this->set('community', $community);
    }

    public function getQnaIds($smId)
    {
        $result = $this->Surveys->getQuestionAndAnswerIds($smId);
        $this->set('result', json_encode($result));
        $this->viewBuilder()->layout('json');
        $this->render('api');
    }

    public function cronImport()
    {
        $surveyId = $this->Surveys->getNextAutoImportCandidate();
        if ($surveyId) {
            echo 'Importing survey #'.$surveyId.'<br />';
            $this->import($surveyId);
            $this->Surveys->setChecked($surveyId);
        } else {
            $this->set('message', 'No surveys are currently eligible for automatic imports');
            $this->viewBuilder()->layout('blank');
        }
        $this->render('import');
    }
}
