<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Survey Entity.
 *
 * @property int $id
 * @property string $type
 * @property int $community_id
 * @property string|null $sm_url
 * @property int|null $sm_id
 * @property string $pwrrr_qid
 * @property string $production_aid
 * @property string $wholesale_aid
 * @property string $recreation_aid
 * @property string $retail_aid
 * @property string $residential_aid
 * @property string $1_aid
 * @property string $2_aid
 * @property string $3_aid
 * @property string $4_aid
 * @property string $5_aid
 * @property string|null $aware_of_plan_qid
 * @property string|null $aware_of_city_plan_aid
 * @property string|null $aware_of_county_plan_aid
 * @property string|null $aware_of_regional_plan_aid
 * @property string|null $unaware_of_plan_aid
 * @property \Cake\I18n\FrozenTime|null $respondents_last_modified_date
 * @property \Cake\I18n\FrozenTime|null $responses_checked
 * @property int|null $alignment_vs_local
 * @property int|null $alignment_vs_parent
 * @property float|null $internal_alignment
 * @property \Cake\I18n\FrozenTime|null $alignment_calculated_date
 * @property \Cake\I18n\FrozenTime|null $reminder_sent
 * @property string|null $import_errors
 * @property bool $active
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 * @property \App\Model\Entity\Community $community
 * @property \App\Model\Entity\Respondent[] $respondents
 * @property \App\Model\Entity\Response[] $responses
 */
class Survey extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array
     */
    protected $_accessible = [
        'type' => true,
        'community_id' => true,
        'sm_url' => true,
        'sm_id' => true,
        'pwrrr_qid' => true,
        'production_aid' => true,
        'wholesale_aid' => true,
        'recreation_aid' => true,
        'retail_aid' => true,
        'residential_aid' => true,
        '1_aid' => true,
        '2_aid' => true,
        '3_aid' => true,
        '4_aid' => true,
        '5_aid' => true,
        'aware_of_plan_qid' => true,
        'aware_of_city_plan_aid' => true,
        'aware_of_county_plan_aid' => true,
        'aware_of_regional_plan_aid' => true,
        'unaware_of_plan_aid' => true,
        'respondents_last_modified_date' => true,
        'responses_checked' => true,
        'alignment_vs_local' => true,
        'alignment_vs_parent' => true,
        'internal_alignment' => true,
        'alignment_calculated_date' => true,
        'community' => true,
        'sm' => true,
        'respondents' => true,
        'responses' => true,
        'active' => true,
    ];
}
