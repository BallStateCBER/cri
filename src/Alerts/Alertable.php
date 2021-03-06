<?php
declare(strict_types=1);

namespace App\Alerts;

use App\Model\Table\DeliverablesTable;
use App\Model\Table\ProductsTable;
use Cake\ORM\TableRegistry;

/**
 * Class Alertable
 *
 * Methods return a boolean values representing whether or not the selected communities qualify to receive the
 * specified alerts. Only active communities qualify for alerts.
 *
 * @package App\Alerts
 * @property \App\Model\Table\CommunitiesTable $communities
 * @property \App\Model\Entity\Community $community
 * @property \App\Model\Table\DeliveriesTable $deliveries
 * @property \App\Model\Table\ProductsTable $products
 * @property \App\Model\Table\SurveysTable $surveys
 * @property \App\Model\Table\OptOutsTable $optOuts
 */
class Alertable
{
    private $communities;
    private $community;
    private $deliveries;
    private $products;
    private $surveys;

    /**
     * Alertable constructor.
     *
     * @param int $communityId Community ID
     */
    public function __construct($communityId)
    {
        $this->communities = TableRegistry::getTableLocator()->get('Communities');
        $this->deliveries = TableRegistry::getTableLocator()->get('Deliveries');
        $this->optOuts = TableRegistry::getTableLocator()->get('OptOuts');
        $this->products = TableRegistry::getTableLocator()->get('Products');
        $this->surveys = TableRegistry::getTableLocator()->get('Surveys');
        $this->community = $this->communities->get($communityId);
    }

    /**
     * Checks if the community is eligible to receive "deliver presentation A materials" alert
     *
     * Checks if
     * - Community is active
     * - Survey is inactive and has responses
     * - Presentation has not been delivered
     * - The corresponding product has been purchased
     * - The presentation has not been opted out of
     * - The date of the presentation has not passed
     *
     * @return bool
     */
    public function deliverPresentationA()
    {
        $surveyType = 'official';
        $surveyId = $this->surveys->getSurveyId($this->community->id, $surveyType);
        $productId = ProductsTable::OFFICIALS_SURVEY;
        $deliverableId = DeliverablesTable::PRESENTATION_A_MATERIALS;
        $presentationLetter = 'a';

        return $this->deliverMandatoryPresentation($surveyId, $deliverableId, $productId, $presentationLetter);
    }

    /**
     * Checks if the community is eligible to receive "deliver presentation C materials" alert
     *
     * Checks if
     * - Community is active
     * - Survey is inactive and has responses
     * - Presentation has not been delivered
     * - The corresponding product has been purchased
     * - The presentation has not been opted out of
     * - The date of the presentation has not passed
     *
     * @return bool
     */
    public function deliverPresentationC()
    {
        $surveyType = 'organization';
        $surveyId = $this->surveys->getSurveyId($this->community->id, $surveyType);
        $productId = ProductsTable::ORGANIZATIONS_SURVEY;
        $deliverableId = DeliverablesTable::PRESENTATION_C_MATERIALS;
        $presentationLetter = 'c';

        return $this->deliverMandatoryPresentation($surveyId, $deliverableId, $productId, $presentationLetter);
    }

    /**
     * Checks if the community is eligible to receive "deliver mandatory presentation materials" alert
     *
     * Checks if
     * - Survey is inactive and has responses
     * - Presentation has not been delivered
     * - The corresponding product has been purchased
     * - The presentation has not been opted out of
     * - The date of the presentation has not passed
     *
     * @param int $surveyId Survey ID
     * @param int $deliverableId Deliverable ID
     * @param int $productId Product ID
     * @param string $presentationLetter a, b, c, or d
     * @return bool
     */
    private function deliverMandatoryPresentation($surveyId, $deliverableId, $productId, $presentationLetter)
    {
        if (!$this->community->active) {
            return false;
        }

        if ($this->surveys->isActive($surveyId)) {
            return false;
        }

        if (!$this->surveys->hasResponses($surveyId)) {
            return false;
        }

        return $this->deliverOptionalPresentation($deliverableId, $productId, $presentationLetter);
    }

    /**
     * Returns true if the presentation date is non-null and in the past
     *
     * @param string $presentationLetter a, b, c, or d
     * @return bool
     */
    private function presentationHasPassed($presentationLetter)
    {
        $presentationLetter = strtolower($presentationLetter);
        if ($this->community->{"presentation_$presentationLetter"} == null) {
            return false;
        }

        if ($this->community->{"presentation_$presentationLetter"}->timestamp >= time()) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the community is eligible to receive "deliver presentation B materials" alert
     *
     * Checks if
     * - Community is active
     * - Presentation has not been delivered
     * - The corresponding product has been purchased
     * - The presentation has not been opted out of
     * - The date of the presentation has not passed
     *
     * @return bool
     */
    public function deliverPresentationB()
    {
        $productId = ProductsTable::OFFICIALS_SUMMIT;
        $deliverableId = DeliverablesTable::PRESENTATION_B_MATERIALS;
        $presentationLetter = 'b';

        return $this->deliverOptionalPresentation($deliverableId, $productId, $presentationLetter);
    }

    /**
     * Checks if the community is eligible to receive "deliver presentation D materials" alert
     *
     * Checks if
     * - Community is active
     * - Presentation has not been delivered
     * - The corresponding product has been purchased
     * - The presentation has not been opted out of
     * - The date of the presentation has not passed
     *
     * @return bool
     */
    public function deliverPresentationD()
    {
        $productId = ProductsTable::ORGANIZATIONS_SUMMIT;
        $deliverableId = DeliverablesTable::PRESENTATION_D_MATERIALS;
        $presentationLetter = 'd';

        return $this->deliverOptionalPresentation($deliverableId, $productId, $presentationLetter);
    }

    /**
     * Checks if the community is eligible to receive "deliver optional presentation materials" alert
     *
     * Checks if
     * - Presentation has not been delivered
     * - The corresponding product has been purchased
     * - The presentation has not been opted out of
     * - The date of the presentation has not passed
     *
     * @param int $deliverableId Deliverable ID
     * @param int $productId Product ID
     * @param string $presentationLetter a, b, c, or d
     * @return bool
     */
    private function deliverOptionalPresentation($deliverableId, $productId, $presentationLetter)
    {
        if (!$this->community->active) {
            return false;
        }

        if ($this->deliveries->isRecorded($this->community->id, $deliverableId)) {
            return false;
        }

        if (!$this->products->isPurchased($this->community->id, $productId)) {
            return false;
        }

        if ($this->optOuts->optedOut($this->community->id, $productId)) {
            return false;
        }

        if ($this->presentationHasPassed($presentationLetter)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the community is eligible to receive a "create officials survey" alert
     *
     * Checks if
     * - Survey does not exist
     * - The corresponding product has been purchased
     *
     * @return bool
     */
    public function createOfficialsSurvey()
    {
        return $this->createSurvey('official');
    }

    /**
     * Checks if the community is eligible to receive a "create officials survey" alert
     *
     * Checks if
     * - Survey does not exist
     * - The corresponding product has been purchased
     *
     * @return bool
     */
    public function createOrganizationsSurvey()
    {
        return $this->createSurvey('organization');
    }

    /**
     * Checks if the community is eligible to receive a "create survey" alert
     *
     * Checks if
     * - Survey does not exist
     * - The corresponding product has been purchased
     *
     * @param string $type Survey type (official or organization)
     * @return bool
     */
    private function createSurvey($type)
    {
        if (!$this->community->active) {
            return false;
        }

        if ($this->surveys->hasBeenCreated($this->community->id, $type)) {
            return false;
        }

        $productId = $type == 'official' ? ProductsTable::OFFICIALS_SURVEY : ProductsTable::ORGANIZATIONS_SURVEY;
        if (!$this->products->isPurchased($this->community->id, $productId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the community is eligible to receive a "create clients" alert
     *
     * Checks if
     * - Community has no clients
     *
     * @return bool
     */
    public function createClients()
    {
        if (!$this->community->active) {
            return false;
        }

        return $this->communities->getClientCount($this->community->id) === 0;
    }

    /**
     * Checks if the community is eligible to receive an "activate officials survey" alert
     *
     * Checks if
     * - Survey is inactive
     * - Survey has no responses
     * - The corresponding product has been purchased
     *
     * @return bool
     */
    public function activateOfficialsSurvey()
    {
        $surveyType = 'official';
        $surveyId = $this->surveys->getSurveyId($this->community->id, $surveyType);
        $productId = ProductsTable::OFFICIALS_SURVEY;

        return $this->activateSurvey($surveyId, $productId);
    }

    /**
     * Checks if the community is eligible to receive an "activate organizations survey" alert
     *
     * Checks if
     * - Survey is inactive
     * - Survey has no responses
     * - The corresponding product has been purchased
     *
     * @return bool
     */
    public function activateOrganizationsSurvey()
    {
        $surveyType = 'organization';
        $surveyId = $this->surveys->getSurveyId($this->community->id, $surveyType);
        $productId = ProductsTable::ORGANIZATIONS_SURVEY;

        return $this->activateSurvey($surveyId, $productId);
    }

    /**
     * Checks if the community is eligible to receive an "activate survey" alert
     *
     * Checks if
     * - Survey is inactive
     * - Survey has no responses
     * - The corresponding product has been purchased
     *
     * @param int $surveyId Survey ID
     * @param int $productId Product ID
     * @return bool
     */
    private function activateSurvey($surveyId, $productId)
    {
        if (!$this->community->active) {
            return false;
        }

        if ($this->surveys->isActive($surveyId)) {
            return false;
        }

        if ($this->surveys->hasResponses($surveyId)) {
            return false;
        }

        if (!$this->products->isPurchased($this->community->id, $productId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the community is eligible to receive a "schedule presentation a" alert
     *
     * Checks if
     * - Presentation materials have been delivered
     * - Presentation has not been scheduled
     * - The corresponding product has been purchased
     * - The presentation has not been opted out of
     *
     * @return bool
     */
    public function schedulePresentationA()
    {
        $presentationLetter = 'a';
        $productId = ProductsTable::OFFICIALS_SURVEY;
        $deliverableId = DeliverablesTable::PRESENTATION_A_MATERIALS;

        return $this->schedulePresentation($presentationLetter, $productId, $deliverableId);
    }

    /**
     * Checks if the community is eligible to receive a "schedule presentation b" alert
     *
     * Checks if
     * - Presentation materials have been delivered
     * - Presentation has not been scheduled
     * - The corresponding product has been purchased
     * - The presentation has not been opted out of
     *
     * @return bool
     */
    public function schedulePresentationB()
    {
        $presentationLetter = 'b';
        $productId = ProductsTable::OFFICIALS_SUMMIT;
        $deliverableId = DeliverablesTable::PRESENTATION_B_MATERIALS;

        return $this->schedulePresentation($presentationLetter, $productId, $deliverableId);
    }

    /**
     * Checks if the community is eligible to receive a "schedule presentation c" alert
     *
     * Checks if
     * - Presentation materials have been delivered
     * - Presentation has not been scheduled
     * - The corresponding product has been purchased
     * - The presentation has not been opted out of
     *
     * @return bool
     */
    public function schedulePresentationC()
    {
        $presentationLetter = 'c';
        $productId = ProductsTable::ORGANIZATIONS_SURVEY;
        $deliverableId = DeliverablesTable::PRESENTATION_C_MATERIALS;

        return $this->schedulePresentation($presentationLetter, $productId, $deliverableId);
    }

    /**
     * Checks if the community is eligible to receive a "schedule presentation d" alert
     *
     * Checks if
     * - Presentation materials have been delivered
     * - Presentation has not been scheduled
     * - The corresponding product has been purchased
     * - The presentation has not been opted out of
     *
     * @return bool
     */
    public function schedulePresentationD()
    {
        $presentationLetter = 'd';
        $productId = ProductsTable::ORGANIZATIONS_SUMMIT;
        $deliverableId = DeliverablesTable::PRESENTATION_D_MATERIALS;

        return $this->schedulePresentation($presentationLetter, $productId, $deliverableId);
    }

    /**
     * Checks if the community is eligible to receive a "schedule presentation" alert
     *
     * Checks if
     * - Presentation materials have been delivered
     * - Presentation has not been scheduled
     * - The corresponding product has been purchased
     * - The presentation has not been opted out of
     *
     * @param string $presentationLetter a, b, c, or d
     * @param int $productId Product ID
     * @param int $deliverableId Deliverable ID
     * @return bool
     */
    private function schedulePresentation($presentationLetter, $productId, $deliverableId)
    {
        if (!$this->community->active) {
            return false;
        }

        if (!$this->deliveries->isRecorded($this->community->id, $deliverableId)) {
            return false;
        }

        if ($this->community->{"presentation_$presentationLetter"} != null) {
            return false;
        }

        if (!$this->products->isPurchased($this->community->id, $productId)) {
            return false;
        }

        if ($this->optOuts->optedOut($this->community->id, $productId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the community is eligible to receive a "deliver policy development materials" alert
     *
     * Checks if
     * - Community is on Step Four
     * - Policy dev has not yet been delivered
     *
     * @return bool
     */
    public function deliverPolicyDev()
    {
        if (!$this->community->active) {
            return false;
        }

        if ($this->community->score != 4) {
            return false;
        }

        $deliverableId = DeliverablesTable::POLICY_DEVELOPMENT;
        if ($this->deliveries->isRecorded($this->community->id, $deliverableId)) {
            return false;
        }

        return true;
    }
}
