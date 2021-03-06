<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Community;
use Cake\Chronos\Date;
use Cake\Network\Exception\InternalErrorException;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use DateTime;

/**
 * Communities Model
 *
 * @property \App\Model\Table\AreasTable&\Cake\ORM\Association\BelongsTo $LocalAreas
 * @property \App\Model\Table\AreasTable&\Cake\ORM\Association\BelongsTo $ParentAreas
 * @property \App\Model\Table\PurchasesTable&\Cake\ORM\Association\HasMany $Purchases
 * @property \App\Model\Table\SurveysTable&\Cake\ORM\Association\HasMany $Surveys
 * @property \Cake\ORM\Table&\Cake\ORM\Association\HasMany $SurveysBackup
 * @property \App\Model\Table\OptOutsTable&\Cake\ORM\Association\HasMany $OptOuts
 * @property \App\Model\Table\ActivityRecordsTable&\Cake\ORM\Association\HasMany $ActivityRecords
 * @property \App\Model\Table\SurveysTable&\Cake\ORM\Association\HasOne $OfficialSurvey
 * @property \App\Model\Table\SurveysTable&\Cake\ORM\Association\HasOne $OrganizationSurvey
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsToMany $Clients
 * @method \App\Model\Entity\Community get($primaryKey, $options = [])
 * @method \App\Model\Entity\Community newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Community[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Community|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Community patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Community[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Community findOrCreate($search, callable $callback = null, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @property \Cake\ORM\Table&\Cake\ORM\Association\HasMany $CommunitiesUsers
 * @method \App\Model\Entity\Community saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Community[]|\Cake\Datasource\ResultSetInterface|false saveMany($entities, $options = [])
 * @mixin \Muffin\Slug\Model\Behavior\SlugBehavior
 */
class CommunitiesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        $this->setTable('communities');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->addBehavior('Muffin/Slug.Slug');
        $this->belongsTo('LocalAreas', [
            'className' => 'Areas',
            'foreignKey' => 'local_area_id',
        ]);
        $this->belongsTo('ParentAreas', [
            'className' => 'Areas',
            'foreignKey' => 'parent_area_id',
        ]);
        $this->hasMany('Purchases', [
            'foreignKey' => 'community_id',
        ]);
        $this->hasMany('OptOuts', [
            'foreignKey' => 'community_id',
        ]);
        $this->hasMany('Surveys', [
            'foreignKey' => 'community_id',
        ]);
        $this->hasMany('SurveysBackup', [
            'foreignKey' => 'community_id',
        ]);
        $this->hasOne('OfficialSurvey', [
            'className' => 'Surveys',
            'foreignKey' => 'community_id',
            'conditions' => ['OfficialSurvey.type' => 'official'],
            'dependent' => true,
        ]);
        $this->hasOne('OrganizationSurvey', [
            'className' => 'Surveys',
            'foreignKey' => 'community_id',
            'conditions' => ['OrganizationSurvey.type' => 'organization'],
            'dependent' => true,
        ]);
        $this->belongsToMany('Clients', [
            'className' => 'Users',
            'joinTable' => 'clients_communities',
            'foreignKey' => 'community_id',
            'targetForeignKey' => 'client_id',
            'saveStrategy' => 'replace',
        ]);
        $this->hasMany('ActivityRecords', [
            'foreignKey' => 'community_id',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->add('id', 'valid', ['rule' => 'numeric'])
            ->allowEmpty('id', 'create');

        $validator
            ->requirePresence('name', 'create')
            ->notEmpty('name');

        $validator
            ->requirePresence('parent_area_id', 'create')
            ->notEmpty('parent_area_id');

        $validator
            ->add('public', 'valid', ['rule' => 'boolean'])
            ->requirePresence('public', 'create')
            ->notEmpty('public');

        $validator
            ->add('score', 'valid', ['rule' => 'decimal'])
            ->requirePresence('score', 'create')
            ->notEmpty('score');

        $validator
            ->add('intAlignmentAdjustment', 'decimalFormat', [
                'rule' => ['decimal', null],
            ])
            ->add('intAlignmentAdjustment', 'valueInRange', [
                'rule' => ['range', 0, 99.99],
            ])
            ->requirePresence('intAlignmentAdjustment', 'create');

        $validator
            ->add('intAlignmentThreshold', 'decimalFormat', [
                'rule' => ['decimal', null],
            ])
            ->add('intAlignmentThreshold', 'valueInRange', [
                'rule' => ['range', 0, 99.99],
            ])
            ->requirePresence('intAlignmentThreshold', 'create');

        $validator
            ->add('active', 'valid', ['rule' => 'boolean']);

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn('local_area_id', 'LocalAreas'));
        $rules->add($rules->existsIn('parent_area_id', 'ParentAreas'));

        // Ensure that every scheduled presentation has actually been purchased
        foreach (['a', 'b', 'c', 'd'] as $letter) {
            $isPurchased = function ($entity, $options) use ($letter) {
                $scheduled = $entity->{'presentation_' . $letter} !== null;
                if (! $scheduled) {
                    return true;
                }

                $productsTable = TableRegistry::getTableLocator()->get('Products');
                $productId = $productsTable->getProductIdForPresentation($letter);
                if ($productsTable->isPurchased($entity->id, $productId)) {
                    return true;
                }

                return false;
            };
            $rules->add(
                $isPurchased,
                'presentation' . strtoupper($letter) . 'Purchased',
                [
                    'errorField' => 'presentation_' . $letter,
                    'message' => 'Presentation ' . strtoupper($letter) . ' has not been purchased yet',
                ]
            );
        }

        return $rules;
    }

    /**
     * Returns a an array of communities that a client is assigned to
     *
     * @param int $clientId Client user ID
     * @return array $communityId => $community_name
     */
    public function getClientCommunityList($clientId = null)
    {
        $joinTable = TableRegistry::getTableLocator()->get('ClientsCommunities');
        $query = $joinTable->find('list')
            ->select(['id' => 'community_id'])
            ->distinct(['community_id']);
        if ($clientId) {
            $query->where(['client_id' => $clientId]);
        } else {
            $usersTable = TableRegistry::getTableLocator()->get('Users');
            $clients = $usersTable->getClientList();
            $clientIds = array_keys($clients);

            /* Seems redundant, but helps keep this list clean
             * in the event of lingering "admin account was
             * temporarily a client account associated with this
             * community" situations. */
            $query->where(function ($exp, $q) use ($clientIds) {
                return $exp->in('client_id', $clientIds);
            });
        }

        $results = $query->toArray();
        if (! $results) {
            return [];
        }

        $communityIds = array_values($results);

        return $this->find('list')
            ->where(function ($exp, $q) use ($communityIds) {
                return $exp->in('id', $communityIds);
            })
            ->order(['Communities.name' => 'ASC'])
            ->toArray();
    }

    /**
     * Returns the ID of the (first) Community associated with the specified client, or NULL none found
     *
     * @param int|null $clientId Client user ID
     * @return int|null
     */
    public function getClientCommunityId($clientId)
    {
        if (empty($clientId)) {
            return null;
        }
        $communities = $this->getClientCommunityList($clientId);
        if (empty($communities)) {
            return null;
        }
        $communityIds = array_keys($communities);

        return $communityIds[0];
    }

    /**
     * Returns an arbitrary client ID associated with the selected community
     *
     * @param int $communityId Community ID
     * @return int
     */
    public function getCommunityClientId($communityId)
    {
        $result = $this->find('all')
            ->select(['id'])
            ->where(['Communities.id' => $communityId])
            ->contain([
                'Clients' => function ($q) {
                    return $q
                        ->autoFields(false)
                        ->select(['id'])
                        ->limit(1);
                },
            ])
            ->enableHydration(false)
            ->first();

        return $result ? $result['clients'][0]['id'] : null;
    }

    /**
     * Returns an array of a community's clients
     *
     * @param int $communityId Community ID
     * @return array
     */
    public function getClients($communityId)
    {
        $result = $this->find('all')
            ->select(['id'])
            ->where(['id' => $communityId])
            ->contain([
                'Clients' => function ($q) {
                    return $q
                        ->select(['id', 'salutation', 'name', 'email'])
                        ->order(['Clients.name' => 'ASC']);
                },
            ])
            ->first();

        $retval = [];
        if (isset($result['clients'])) {
            foreach ($result['clients'] as $client) {
                unset($client['clients_communities']);
                $retval[] = $client;
            }
        }

        return $retval;
    }

    /**
     * Returns the count of a community's clients
     *
     * @param int $communityId Community ID
     * @return int
     */
    public function getClientCount($communityId)
    {
        $clients = $this->getClients($communityId);

        return count($clients);
    }

    /**
     * Returns a terribly complex array used in the client home page that sums up progress information
     *
     * @param int $communityId Community ID
     * @param bool $isAdmin Current user is an administrator
     * @return array
     */
    public function getProgress($communityId, $isAdmin = false)
    {
        $criteria = [];
        $deliveriesTable = TableRegistry::getTableLocator()->get('Deliveries');
        $optOutsTable = TableRegistry::getTableLocator()->get('OptOuts');
        $productsTable = TableRegistry::getTableLocator()->get('Products');
        $respondentsTable = TableRegistry::getTableLocator()->get('Respondents');
        $responsesTable = TableRegistry::getTableLocator()->get('Responses');
        $surveysTable = TableRegistry::getTableLocator()->get('Surveys');

        // Step 1
        $criteria[1]['client_assigned'] = [
            'At least one client account has been created for this community',
            $this->getClientCount($communityId) > 0,
        ];

        $productId = ProductsTable::OFFICIALS_SURVEY;
        $product = $productsTable->get($productId);
        $optOuts = $optOutsTable->getOptOuts($communityId);
        $productDescription = $product->description . ' ($' . number_format($product->price) . ')';
        if (in_array($productId, $optOuts)) {
            $criteria[1]['survey_purchased'] = [
                "Opted out of purchasing $productDescription",
                true,
            ];
        } else {
            $criteria[1]['survey_purchased'] = [
                "Purchased $productDescription",
                $productsTable->isPurchased($communityId, $productId),
            ];
        }

        // If survey is not ready, put this at the end of step one
        // Otherwise, at the beginning of step two
        $surveyId = $surveysTable->getSurveyId($communityId, 'official');
        $isActive = $surveyId ? $surveysTable->isActive($surveyId) : false;
        $isComplete = $surveyId ? $surveysTable->isComplete($surveyId) : false;
        if ($surveyId && ($isActive || $isComplete)) {
            $survey = $surveysTable->get($surveyId);
            $note = '<br />Questionnaire URL: <a href="' . $survey->sm_url . '">' . $survey->sm_url . '</a>';
            $completed = true;
        } else {
            $survey = null;
            $note = '';
            $completed = false;
        }
        $step = $surveyId ? 2 : 1;
        $criteria[$step]['survey_created'] = [
            'Leadership alignment assessment questionnaire has been prepared' . $note,
            $completed,
        ];

        // Step 2
        $count = $surveyId ? $respondentsTable->getInvitedCount($surveyId) : 0;
        $note = $count ? " ($count " . __n('invitation', 'invitations', $count) . ' sent)' : '';
        $criteria[2]['invitations_sent'] = [
            'Community leaders have been sent questionnaire invitations' . $note,
            $surveyId && $count > 0,
        ];

        $count = $surveyId ? $responsesTable->getDistinctCount($surveyId) : 0;
        $note = $count ? " ($count " . __n('response', 'responses', $count) . ' received)' : '';
        $criteria[2]['responses_received'] = [
            'Responses to the questionnaire have been collected' . $note,
            $surveyId && $count > 0,
        ];

        $criteria[2]['response_threshold_reached'] = [
            'At least 25% of invited community leaders have responded to the questionnaire',
            $surveysTable->getInvitedResponsePercentage($surveyId) >= 25,
        ];

        $hasUninvitedResponses = $surveysTable->hasUninvitedResponses($surveyId);
        $criteria[2]['hasUninvitedResponses'] = $hasUninvitedResponses;
        if ($hasUninvitedResponses) {
            $criteria[2]['unapproved_addressed'] = [
                'All unapproved responses have been approved or dismissed',
                ! $surveysTable->hasUnaddressedUnapprovedRespondents($surveyId),
            ];
        } else {
            $criteria[2]['unapproved_addressed'] = [
                'This questionnaire has no uninvited responses',
                true,
            ];
        }

        $productId = ProductsTable::OFFICIALS_SUMMIT;
        $product = $productsTable->get($productId);
        $productDescription = $product->description . ' ($' . number_format($product->price) . ')';
        $optedOut = in_array($productId, $optOuts);
        if ($optedOut) {
            $criteria[2]['leadership_summit_purchased'] = [
                "Opted out of purchasing $productDescription",
                true,
            ];
        } else {
            $criteria[2]['leadership_summit_purchased'] = [
                "Purchased optional $productDescription",
                $productsTable->isPurchased($communityId, $productId),
            ];
        }

        $community = $this->get($communityId);
        foreach (['a', 'b'] as $letter) {
            $date = $community->{"presentation_$letter"};
            $criteria[2]["presentation_{$letter}_scheduled"] = [
                'Scheduled Presentation ' . strtoupper($letter),
                $date != null,
            ];
            $criteria[2]["presentation_{$letter}_completed"] = [
                'Completed Presentation ' . strtoupper($letter),
                $date ? ($date->format('Y-m-d') <= date('Y-m-d')) : false,
            ];
        }

        $productId = ProductsTable::ORGANIZATIONS_SURVEY;
        $product = $productsTable->get($productId);
        $productDescription = $product->description . ' ($' . number_format($product->price) . ')';
        $optedOut = in_array($productId, $optOuts);
        if ($optedOut) {
            $criteria[2]['survey_purchased'] = [
                "Opted out of purchasing $productDescription",
                true,
            ];
        } else {
            $criteria[2]['survey_purchased'] = [
                "Purchased $productDescription",
                $productsTable->isPurchased($communityId, $productId),
            ];
        }

        // Step 3
        $surveyId = $surveysTable->getSurveyId($communityId, 'organization');
        $survey = $surveyId ? $surveysTable->get($surveyId) : null;
        $surveyUrl = $survey ? $survey->sm_url : null;
        if ($surveyUrl) {
            $note = '<br />Questionnaire URL: <a href="' . $surveyUrl . '">' . $surveyUrl . '</a>';
        } else {
            $note = '';
        }
        $criteria[3]['survey_created'] = [
            'Community organization alignment assessment questionnaire has been prepared' . $note,
            (bool)$surveyId,
        ];

        $count = $surveyId ? $respondentsTable->getInvitedCount($surveyId) : 0;
        $note = $count ? " ($count " . __n('invitation', 'invitations', $count) . ' sent)' : '';
        $criteria[3]['invitations_sent'] = [
            'Community organizations have been sent questionnaire invitations' . $note,
            $surveyId && $count > 0,
        ];

        $count = $surveyId ? $responsesTable->getDistinctCount($surveyId) : 0;
        if ($count) {
            $note = " ($count " . __n('response', 'responses', $count) . ' received)';
        } else {
            $note = '';
        }
        $criteria[3]['responses_received'] = [
            'Responses to the questionnaire have been collected' . $note,
            $surveyId && $count > 0,
        ];

        $criteria[3]['response_threshold_reached'] = [
            'At least 25% of invited community organizations have responded to the questionnaire',
            $surveysTable->getInvitedResponsePercentage($surveyId) >= 25,
        ];

        $productId = ProductsTable::ORGANIZATIONS_SUMMIT;
        $product = $productsTable->get($productId);
        $productDescription = $product->description . ' ($' . number_format($product->price) . ')';
        $optedOut = in_array($productId, $optOuts);
        if ($optedOut) {
            $criteria[3]['orgs_summit_purchased'] = [
                "Opted out of purchasing optional $productDescription",
                true,
            ];
        } else {
            $criteria[3]['orgs_summit_purchased'] = [
                "Purchased optional $productDescription",
                $productsTable->isPurchased($communityId, $productId),
            ];
        }

        foreach (['c', 'd'] as $letter) {
            $date = $community->{"presentation_$letter"};
            $criteria[3]["presentation_{$letter}_scheduled"] = [
                'Scheduled Presentation ' . strtoupper($letter),
                $date != null,
            ];
            $criteria[3]["presentation_{$letter}_completed"] = [
                'Completed Presentation ' . strtoupper($letter),
                $date ? ($date->format('Y-m-d') <= date('Y-m-d')) : false,
            ];
        }

        $productId = ProductsTable::POLICY_DEVELOPMENT;
        $product = $productsTable->get($productId);
        $price = '$' . number_format($product->price);
        $productDescription = str_replace('PWRRR', 'PWR<sup>3</sup>', $product->description);
        $optedOut = in_array($productId, $optOuts);
        if ($optedOut) {
            $criteria[3]['policy_dev_purchased'] = [
                "Opted out of purchasing $productDescription",
                true,
            ];
        } else {
            $criteria[3]['policy_dev_purchased'] = [
                "Purchased $productDescription ($price)",
                $productsTable->isPurchased($communityId, $productId),
            ];
        }

        // Step Four
        $isDelivered = $deliveriesTable->isRecorded($communityId, DeliverablesTable::POLICY_DEVELOPMENT);
        $msg = $isDelivered
            ? "Received $productDescription"
            : "Your $productDescription is currently being prepared";
        $criteria[4]['policy_dev_delivered'] = [
            $msg,
            $isDelivered,
        ];

        return $criteria;
    }

    /**
     * Removes all client associations from a community
     *
     * @param int $communityId Community ID
     * @return mixed
     */
    public function removeAllClientAssociations($communityId)
    {
        $community = $this->get($communityId);
        $joinTable = TableRegistry::getTableLocator()->get('ClientsCommunities');
        $clients = $joinTable->find('all')
            ->select(['id'])
            ->where(['community_id' => $communityId])
            ->toArray();

        return $this->Clients->unlink($community, $clients);
    }

    /**
     * Removes all consultant associations from a community
     *
     * @param int $communityId Community ID
     * @return mixed
     */
    public function removeAllConsultantAssociations($communityId)
    {
        $community = $this->get($communityId);
        $joinTable = TableRegistry::getTableLocator()->get('CommunitiesConsultants');
        $consultants = $joinTable->find('all')
            ->select(['id'])
            ->where(['community_id' => $communityId])
            ->toArray();

        return $this->Consultants->unlink($community, $consultants);
    }

    /**
     * A finder for /admin/communities/index
     *
     * @param \Cake\ORM\Query $query Query
     * @param array $options Options array
     * @return \Cake\ORM\Query
     */
    public function findAdminIndex(\Cake\ORM\Query $query, array $options)
    {
        $query
            ->contain([
                'Clients' => function ($q) {
                    return $q->select([
                        'Clients.email',
                        'Clients.name',
                    ]);
                },
                'OfficialSurvey' => function ($q) {
                    return $q->select([
                        'OfficialSurvey.id',
                        'OfficialSurvey.sm_id',
                        'OfficialSurvey.alignment_vs_local',
                        'OfficialSurvey.alignment_vs_parent',
                        'OfficialSurvey.respondents_last_modified_date',
                        'OfficialSurvey.active',
                    ]);
                },
                'OrganizationSurvey' => function ($q) {
                    return $q->select([
                        'OrganizationSurvey.id',
                        'OrganizationSurvey.sm_id',
                        'OrganizationSurvey.alignment_vs_local',
                        'OrganizationSurvey.alignment_vs_parent',
                        'OrganizationSurvey.respondents_last_modified_date',
                        'OrganizationSurvey.active',
                    ]);
                },
                'ParentAreas' => function ($q) {
                    return $q->select(['ParentAreas.name']);
                },
            ])
            ->group('Communities.id')
            ->select([
                'Communities.id',
                'Communities.name',
                'Communities.score',
                'Communities.created',
                'Communities.active',
                'Communities.slug',
            ])
            ->order(['Communities.name' => 'ASC']);

        return $query;
    }

    /**
     * A finder for /admin/reports/index
     *
     * @param \Cake\ORM\Query $query Query
     * @param array $options Options array
     * @return \Cake\ORM\Query
     */
    public function findForReport(\Cake\ORM\Query $query, array $options)
    {
        $dateThreshold = new Date('-30 days');
        $query
            ->select([
                'id',
                'name',
                'score',
                'presentation_a',
                'presentation_b',
                'presentation_c',
                'notes',
            ])
            ->where(['dummy' => 0])
            ->contain([
                'ParentAreas' => function ($q) {
                    return $q->select(['id', 'name', 'fips']);
                },
                'OfficialSurvey' => function ($q) {
                    return $q->select(['id', 'alignment_vs_local', 'alignment_vs_parent']);
                },
                'OrganizationSurvey' => function ($q) {
                    return $q->select(['id', 'alignment_vs_local', 'alignment_vs_parent']);
                },
                'ActivityRecords' => function ($q) use ($dateThreshold) {
                    return $q
                        ->where(function ($exp, $q) use ($dateThreshold) {
                            return $exp->gte('ActivityRecords.created', $dateThreshold);
                        })
                        ->order(['ActivityRecords.created' => 'DESC']);
                },
            ])
            ->order(['Communities.name' => 'ASC']);

        return $query;
    }

    /**
     * A finder for the AutoAdvanceShell
     *
     * @param \Cake\ORM\Query $query Query
     * @return \Cake\ORM\Query
     */
    public function findForAutoAdvancement(Query $query)
    {
        return $query
            ->select([
                'id',
                'name',
                'score',
                'active',
                'presentation_a',
                'presentation_b',
                'presentation_c',
                'presentation_d',
            ])
            ->contain([
                'OptOuts',
                'OfficialSurvey' => function ($q) {
                    /** @var \Cake\ORM\Query $q */

                    return $q
                        ->select(['id', 'community_id', 'active'])
                        ->contain([
                            'Responses' => function ($q) {
                                /** @var \Cake\ORM\Query $q */

                                return $q
                                    ->select(['id', 'survey_id'])
                                    ->matching('Respondents', function ($q) {
                                        /** @var \Cake\ORM\Query $q */

                                        return $q->where(['approved' => 1]);
                                    });
                            },
                        ]);
                },
                'OrganizationSurvey' => function ($q) {
                    /** @var \Cake\ORM\Query $q */

                    return $q
                        ->select(['id', 'community_id', 'active'])
                        ->contain([
                            'Responses' => function ($q) {
                                /** @var \Cake\ORM\Query $q */

                                return $q->select(['id', 'survey_id']);
                            },
                        ]);
                },
                'Purchases' => function ($q) {
                    /** @var \Cake\ORM\Query $q */

                    return $q
                        ->select(['id', 'product_id', 'community_id'])
                        ->where(function ($exp) {
                            /** @var \Cake\Database\Expression\QueryExpression $exp */

                            return $exp->isNull('refunded');
                        });
                },
            ])
            ->orderAsc('name');
    }

    /**
     * Returns dummy community IDs
     *
     * @return array
     */
    public function getDummyCommunityIds()
    {
        $communities = $this->find('all')
            ->select(['id'])
            ->where(['dummy' => 1])
            ->toArray();

        return Hash::extract($communities, '{n}.id');
    }

    /**
     * Returns a string with a warning against prematurely deactivating a community, or null
     *
     * @param int $communityId Community ID
     * @return null|string
     */
    public function getDeactivationWarning($communityId)
    {
        $community = $this->get($communityId);

        $productsTable = TableRegistry::getTableLocator()->get('Products');
        $deliveriesTable = TableRegistry::getTableLocator()->get('Deliveries');
        $presentations = [
            'a' => [
                'product_id' => ProductsTable::OFFICIALS_SURVEY,
                'deliverable_id' => DeliverablesTable::PRESENTATION_A_MATERIALS,
            ],
            'b' => [
                'product_id' => ProductsTable::OFFICIALS_SUMMIT,
                'deliverable_id' => DeliverablesTable::PRESENTATION_B_MATERIALS,
            ],
            'c' => [
                'product_id' => ProductsTable::ORGANIZATIONS_SURVEY,
                'deliverable_id' => DeliverablesTable::PRESENTATION_C_MATERIALS,
            ],
            'd' => [
                'product_id' => ProductsTable::ORGANIZATIONS_SUMMIT,
                'deliverable_id' => DeliverablesTable::PRESENTATION_D_MATERIALS,
            ],
        ];

        foreach ($presentations as $letter => $presentation) {
            $isPurchased = $productsTable->isPurchased($communityId, $presentation['product_id']);
            if (! $isPurchased) {
                continue;
            }

            $isPrepared = $deliveriesTable->isRecorded($communityId, $presentation['deliverable_id']);
            if (! $isPrepared) {
                return 'Presentation ' . strtoupper($letter) . ' materials have not yet been delivered to ICI.';
            }

            $date = $community->{"presentation_$letter"};
            $isScheduled = $date != null;
            if (! $isScheduled) {
                return 'Presentation ' . strtoupper($letter) . ' has not yet been scheduled.';
            }

            $isCompleted = $date->format('Y-m-d') <= date('Y-m-d');
            if (! $isCompleted) {
                return 'Presentation ' . strtoupper($letter) . ' has not yet been completed.';
            }
        }

        $isPurchased = $productsTable->isPurchased($communityId, ProductsTable::POLICY_DEVELOPMENT);
        if (! $isPurchased) {
            return null;
        }

        $isDelivered = $deliveriesTable->isRecorded($communityId, DeliverablesTable::POLICY_DEVELOPMENT);
        if (! $isDelivered) {
            return 'Policy development has not yet been delivered to this community.';
        }

        return null;
    }

    /**
     * Returns a string that sums up the status of the specified community
     *
     * @param \App\Model\Entity\Community $community Community entity
     * @return string
     */
    public function getStatusDescription(Community $community)
    {
        if (! $this->getClientCount($community->id)) {
            return 'No client assigned yet';
        }

        // Return the status of the current survey, if one is in progress
        $surveysTable = TableRegistry::getTableLocator()->get('Surveys');
        foreach (['official', 'organization'] as $surveyType) {
            $surveyStatus = $surveysTable->getStatusDescription($community, $surveyType);

            if ($surveyStatus == 'Complete') {
                continue;
            }

            if ($surveyStatus == 'Opted out') {
                return 'Opted out of further participation';
            }

            return 'Community ' . ucwords($surveyType) . 's Questionnaire: ' . $surveyStatus;
        }

        $optOutsTable = TableRegistry::getTableLocator()->get('OptOuts');
        $productId = ProductsTable::POLICY_DEVELOPMENT;
        $optedOut = $optOutsTable->optedOut($community->id, $productId);
        if ($optedOut) {
            return 'Opted out of further participation';
        }

        $productsTable = TableRegistry::getTableLocator()->get('Products');
        $hasPurchased = $productsTable->isPurchased($community->id, $productId);
        if ($hasPurchased) {
            $deliveriesTable = TableRegistry::getTableLocator()->get('Deliveries');
            $policyDevDelivered = $deliveriesTable->isRecorded($community->id, DeliverablesTable::POLICY_DEVELOPMENT);

            return $policyDevDelivered
                ? 'Completed participation'
                : 'Waiting to receive policy development';
        } else {
            return 'Waiting to purchase or opt out of policy development';
        }
    }

    /**
     * Returns whether or not the specified community has scheduled the specified presentation
     *
     * @param int $communityId Community ID
     * @param string $presentationLetter Presentation letter
     * @return bool
     */
    public function presentationIsScheduled($communityId, $presentationLetter)
    {
        $result = $this->find()
            ->select(['id'])
            ->where([
                'id' => $communityId,
                function ($exp, $q) use ($presentationLetter) {
                    /** @var \Cake\Database\Expression\QueryExpression $exp */

                    return $exp->isNotNull('presentation_' . strtolower($presentationLetter));
                },
            ]);

        return !$result->isEmpty();
    }

    /**
     * Finds communities that qualify for the "time to assign a client" alert
     *
     * Skips over recently-created communities (within last two hours) to avoid sending unnecessary alerts to
     * administrators who are in the process of adding clients
     *
     * @param \Cake\ORM\Query $query Query
     * @return \Cake\ORM\Query
     */
    public function findNoClientAssignedAlertable(Query $query)
    {
        return $query->select(['id', 'name'])
            ->where([
                'Communities.active' => true,
                'Communities.created <=' => new DateTime('-2 hours'),
            ])
            ->matching('OfficialSurvey')
            ->notMatching('Clients');
    }

    /**
     * Finds communities that qualify for the "time to create an officials survey" alert
     *
     * Skips over recently-created communities (within last two hours) to avoid sending unnecessary alerts to
     * administrators who are in the process of adding clients
     *
     * @param \Cake\ORM\Query $query Query
     * @return \Cake\ORM\Query
     */
    public function findNoOfficialsSurveyAlertable(Query $query)
    {
        return $query->select(['id', 'name', 'slug'])
            ->where([
                'Communities.active' => true,
                'Communities.created <=' => new DateTime('-2 hours'),
            ])
            ->notMatching('OfficialSurvey');
    }

    /**
     * Finds communities that qualify for the "time to activate this survey" alert
     *
     * Skips over recently-created communities and surveys (within last two hours) to avoid sending unnecessary alerts
     * to administrators who are in the process of adding clients
     *
     * @param \Cake\ORM\Query $query Query
     * @param array $options Query options
     * @return \Cake\ORM\Query
     * @throws \Cake\Network\Exception\InternalErrorException
     */
    public function findSurveyInactiveAlertable(Query $query, array $options = [])
    {
        if (!isset($options['surveyType'])) {
            throw new InternalErrorException('No survey type specified');
        }

        $associationName = ucfirst($options['surveyType']) . 'Survey';

        return $query
            ->select(['id', 'name'])
            ->contain([
                $associationName => function ($q) {
                    /** @var \Cake\ORM\Query $q */

                    return $q->select(['id', 'type', 'community_id']);
                },
            ])
            ->where([
                'Communities.active' => true,
                'Communities.created <=' => new DateTime('-2 hours'),
            ])
            ->matching($associationName, function ($q) use ($associationName) {
                /** @var \Cake\ORM\Query $q */

                return $q
                    ->where([
                        $associationName . '.active' => false,
                        $associationName . '.created <=' => new DateTime('-2 hours'),
                    ])
                    ->notMatching('Responses');
            });
    }
}
