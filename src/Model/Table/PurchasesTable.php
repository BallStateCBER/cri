<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Purchases Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\CommunitiesTable&\Cake\ORM\Association\BelongsTo $Communities
 * @property \App\Model\Table\ProductsTable&\Cake\ORM\Association\BelongsTo $Products
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Refunders
 * @property \App\Model\Table\InvoicesTable&\Cake\ORM\Association\HasOne $Invoices
 * @method \App\Model\Entity\Purchase get($primaryKey, $options = [])
 * @method \App\Model\Entity\Purchase newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Purchase[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Purchase|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Purchase patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Purchase[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Purchase findOrCreate($search, callable $callback = null, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 * @method \App\Model\Entity\Purchase saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Purchase[]|\Cake\Datasource\ResultSetInterface|false saveMany($entities, $options = [])
 */
class PurchasesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        $this->setTable('purchases');
        $this->setDisplayField('product_id');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('Communities', [
            'foreignKey' => 'community_id',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('Products', [
            'foreignKey' => 'product_id',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('Refunders', [
            'className' => 'App\Model\Table\UsersTable',
            'foreignKey' => 'refunder_id',
        ]);
        $this->hasOne('Invoices')
            ->setDependent(true);
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
            ->notEmpty('source', 'create')
            ->add('source', 'validOcra', [
                'rule' => function ($source, $context) {
                    if ($source != 'ocra') {
                        return true;
                    }
                    $ocraFundedProducts = [
                        ProductsTable::OFFICIALS_SURVEY,
                        ProductsTable::OFFICIALS_SUMMIT,
                    ];
                    $productId = $context['data']['product_id'];
                    if (in_array($productId, $ocraFundedProducts)) {
                        return true;
                    }

                    return false;
                },
                'message' => 'OCRA is only funding products related to Step Two at this time',
            ]);

        $validator
            ->requirePresence('postback', 'create')
            ->allowEmpty('postback');

        $validator
            ->add('refunded', 'valid', ['rule' => 'datetime'])
            ->allowEmpty('refunded');

        $validator
            ->add('product_id', 'valid', ['rule' => 'numeric'])
            ->notEmpty('product_id')
            ->requirePresence('product_id', 'create');

        $validator
            ->add('community_id', 'valid', ['rule' => 'numeric'])
            ->notEmpty('community_id')
            ->requirePresence('community_id', 'create');

        $validator
            ->add('user_id', 'valid', ['rule' => 'numeric'])
            ->notEmpty('user_id')
            ->requirePresence('user_id', 'create');

        $validator
            ->add('refunder_id', 'valid', ['rule' => 'numeric'])
            ->allowEmpty('refunder_id');

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
        $rules->add(
            $rules->existsIn(['user_id'], 'Users'),
            'userExists',
            ['message' => 'The selected user was not found in the database']
        );
        $rules->add(
            $rules->existsIn(['community_id'], 'Communities'),
            'communityExists',
            ['message' => 'The selected community was not found in the database']
        );
        $rules->add(
            $rules->existsIn(['product_id'], 'Products'),
            'productExists',
            ['message' => 'The selected product was not found in the database']
        );
        $rules->add(
            $rules->existsIn(['refunder_id'], 'Users'),
            'refunderExists',
            ['message' => 'The selected refunding user was not found in the database']
        );

        return $rules;
    }

    /**
     * Returns an array of all non-refunded purchases associated with a community
     *
     * @param int $communityId Community ID
     * @return array
     */
    public function getAllForCommunity($communityId)
    {
        return $this->find('all')
            ->where([
                'community_id' => $communityId,
                function ($exp) {
                    /** @var \Cake\Database\Expression\QueryExpression $exp */

                    return $exp->isNull('refunded');
                },
            ])
            ->order(['Purchases.created' => 'ASC'])
            ->contain([
                'Products' => function ($q) {
                    /** @var \Cake\ORM\Query $q */

                    return $q->select(['description', 'price']);
                },
                'Users' => function ($q) {
                    /** @var \Cake\ORM\Query $q */

                    return $q->select(['name', 'email']);
                },
            ])
            ->toArray();
    }

    /**
     * Returns an array of the accepted values for Payments.source as keys, and their displayed labels as values
     *
     * @return array
     */
    public function getSourceOptions()
    {
        return [
            'ocra' => 'OCRA',
            'bsu' => 'Ball State University',
            'self' => 'Paid for by client community',
        ];
    }

    /**
     * Finds valid OCRA-funded purchases
     *
     * @param \Cake\ORM\Query $query Query
     * @return \Cake\ORM\Query
     */
    public function findOcra(Query $query)
    {
        return $query
            ->where([
                'Purchases.source' => 'ocra',
                function ($exp) {
                    /** @var \Cake\Database\Expression\QueryExpression $exp */

                    return $exp->in('Purchases.product_id', [
                        ProductsTable::OFFICIALS_SURVEY,
                        ProductsTable::OFFICIALS_SUMMIT,
                    ]);
                },
            ])
            ->contain(['Communities', 'Products'])
            ->order(['Purchases.created' => 'DESC']);
    }

    /**
     * Finds purchases corresponding to products that have been delivered
     *
     * @param \Cake\ORM\Query $query Query
     * @return \Cake\ORM\Query
     */
    public function findBillable(Query $query)
    {
        return $query
            ->where(['OR' => [
                function ($exp) {
                    /** @var \Cake\Database\Expression\QueryExpression $exp */

                    return $exp
                        ->eq('Products.id', ProductsTable::OFFICIALS_SURVEY)

                        // The date of the community's Step Two mandatory presentation has passed
                        ->lt('Communities.presentation_a', date('Y-m-d'));
                },
                function ($exp) {
                    /** @var \Cake\Database\Expression\QueryExpression $exp */

                    return $exp
                        ->eq('Products.id', ProductsTable::OFFICIALS_SUMMIT)

                        // The date of the community's Step Two optional presentation has passed
                        ->lt('Communities.presentation_b', date('Y-m-d'));
                },
                function ($exp) {
                    /** @var \Cake\Database\Expression\QueryExpression $exp */

                    return $exp
                        ->eq('Products.id', ProductsTable::ORGANIZATIONS_SURVEY)

                        // The date of the community's Step Three mandatory presentation has passed
                        ->lt('Communities.presentation_c', date('Y-m-d'));
                },
                function ($exp) {
                    /** @var \Cake\Database\Expression\QueryExpression $exp */

                    return $exp
                        ->eq('Products.id', ProductsTable::ORGANIZATIONS_SUMMIT)

                        // The date of the community's Step Three optional presentation has passed
                        ->lt('Communities.presentation_d', date('Y-m-d'));
                },
                function ($exp) {
                    /** @var \Cake\Database\Expression\QueryExpression $exp */

                    return $exp
                        ->eq('Products.id', ProductsTable::POLICY_DEVELOPMENT)

                        // Community has been advanced to Step Four
                        ->gte('Communities.score', 4);
                },
            ]])
            ->where(function ($exp) {
                /** @var \Cake\Database\Expression\QueryExpression $exp */

                return $exp->isNull('refunded');
            })
            ->notMatching('Invoices');
    }

    /**
     * Finds purchases corresponding to products that have not yet been delivered
     *
     * @param \Cake\ORM\Query $query Query
     * @return \Cake\ORM\Query
     */
    public function findNotBillable(Query $query)
    {
        return $query
            ->where(['OR' => [
                [
                    'Products.id' => ProductsTable::OFFICIALS_SURVEY,
                    'OR' => [
                        function ($exp) {
                            /** @var \Cake\Database\Expression\QueryExpression $exp */

                            return $exp->gte('Communities.presentation_a', date('Y-m-d'));
                        },
                        function ($exp) {
                            /** @var \Cake\Database\Expression\QueryExpression $exp */

                            return $exp->isNull('Communities.presentation_a');
                        },
                    ],
                ],
                [
                    'Products.id' => ProductsTable::OFFICIALS_SUMMIT,
                    'OR' => [
                        function ($exp) {
                            /** @var \Cake\Database\Expression\QueryExpression $exp */

                            return $exp->gte('Communities.presentation_b', date('Y-m-d'));
                        },
                        function ($exp) {
                            /** @var \Cake\Database\Expression\QueryExpression $exp */

                            return $exp->isNull('Communities.presentation_b');
                        },
                    ],
                ],
                [
                    'Products.id' => ProductsTable::ORGANIZATIONS_SURVEY,
                    'OR' => [
                        function ($exp) {
                            /** @var \Cake\Database\Expression\QueryExpression $exp */

                            return $exp->gte('Communities.presentation_c', date('Y-m-d'));
                        },
                        function ($exp) {
                            /** @var \Cake\Database\Expression\QueryExpression $exp */

                            return $exp->isNull('Communities.presentation_c');
                        },
                    ],
                ],
                [
                    'Products.id' => ProductsTable::ORGANIZATIONS_SUMMIT,
                    'OR' => [
                        function ($exp) {
                            /** @var \Cake\Database\Expression\QueryExpression $exp */

                            return $exp->gte('Communities.presentation_d', date('Y-m-d'));
                        },
                        function ($exp) {
                            /** @var \Cake\Database\Expression\QueryExpression $exp */

                            return $exp->isNull('Communities.presentation_d');
                        },
                    ],
                ],
                [
                    'Products.id' => ProductsTable::POLICY_DEVELOPMENT,
                    function ($exp) {
                        /** @var \Cake\Database\Expression\QueryExpression $exp */

                        return $exp->lt('Communities.score', 4);
                    },
                ],
            ]])
            ->where(function ($exp) {
                /** @var \Cake\Database\Expression\QueryExpression $exp */

                return $exp->isNull('refunded');
            })
            ->notMatching('Invoices');
    }

    /**
     * Finds purchases that have associated unpaid invoices
     *
     * @param \Cake\ORM\Query $query Query
     * @return \Cake\ORM\Query
     */
    public function findBilledUnpaid(Query $query)
    {
        return $query
            ->where(function ($exp) {
                /** @var \Cake\Database\Expression\QueryExpression $exp */

                return $exp->isNull('refunded');
            })
            ->matching('Invoices', function ($q) {
                /** @var \Cake\ORM\Query $q */

                return $q->where(['paid' => false]);
            });
    }

    /**
     * Finds purchases that have associated paid invoices
     *
     * @param \Cake\ORM\Query $query Query
     * @return \Cake\ORM\Query
     */
    public function findPaid(Query $query)
    {
        return $query
            ->where(function ($exp) {
                /** @var \Cake\Database\Expression\QueryExpression $exp */

                return $exp->isNull('refunded');
            })
            ->matching('Invoices', function ($q) {
                /** @var \Cake\ORM\Query $q */

                return $q->where(['paid' => true]);
            });
    }

    /**
     * Returns the most recent date of a given purchase
     *
     * @param int $productId Product ID
     * @param int $communityId Community ID
     * @return \Cake\I18n\FrozenTime|null
     */
    public function getPurchaseDate($productId, $communityId)
    {
        /** @var \App\Model\Entity\Purchase $result */
        $result = $this->find()
            ->select('created')
            ->where([
                'product_id' => $productId,
                'community_id' => $communityId,
            ])
            ->orderDesc('created')
            ->first();

        return $result ? $result->created : null;
    }

    /**
     * Returns whether or not the specified community has purchased the specified product
     *
     * (and it hasn't been refunded)
     *
     * @param int $productId Product ID
     * @param int $communityId Community ID
     * @return bool
     */
    public function isPurchased($productId, $communityId)
    {
        $result = $this->find()
            ->where([
                'product_id' => $productId,
                'community_id' => $communityId,
                function ($exp) {
                    /** @var \Cake\Database\Expression\QueryExpression $exp */
                    return $exp->isNull('refunded');
                },
            ])
        ->count();

        return $result > 0;
    }
}
