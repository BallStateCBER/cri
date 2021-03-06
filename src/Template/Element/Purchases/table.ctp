<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Purchase[]|\Cake\Collection\CollectionInterface $purchases
 * @var array $sources
 */
?>
<?= $this->element('pagination') ?>

<table class="table" id="purchases_index">
    <thead>
    <tr>
        <th>
            Date
        </th>
        <th>
            Community
        </th>
        <th>
            Product
        </th>
        <th>
            Report Refund
        </th>
        <th>
            Details
        </th>
    </tr>
    </thead>
    <tbody>
        <?php foreach ($purchases as $purchase): ?>
            <tr>
                <td>
                    <?= $this->Time->format($purchase->created, 'M/d/YYYY', false, 'America/New_York'); ?>
                </td>
                <td>
                    <?php if ($purchase->community): ?>
                        <?= $this->Html->link(
                            $purchase->community['name'],
                            [
                                'prefix' => 'admin',
                                'controller' => 'Purchases',
                                'action' => 'view',
                                $purchase->community['slug']
                            ]
                        ) ?>
                    <?php else: ?>
                        <span class="text-danger">
                            Unknown community (#<?= $purchase->community_id ?>)
                        </span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($purchase->product['description']): ?>
                        <?= $purchase->product['description'] ?>
                    <?php else: ?>
                        <span class="text-danger">
                            Unknown product (#<?= $purchase->product_id ?>)
                        </span>
                    <?php endif; ?>

                    <?php if ($purchase->amount): ?>
                        ($<?= number_format($purchase->amount) ?>)
                        <?php if ($purchase->amount != $purchase->product['price']): ?>
                            <br />
                            <span class="label label-warning">
                                Amount paid differs from current price
                            </span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-danger">
                            Unknown amount
                            (Expected to be $<?= number_format($purchase->product['price']) ?>)
                        </span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($purchase->refunded): ?>
                        <button class="refunded btn btn-default btn-block">
                            Refunded
                        </button>
                    <?php else: ?>
                        <?= $this->Form->postLink(
                            'Refund',
                            [
                                'prefix' => 'admin',
                                'action' => 'refund',
                                $purchase->id
                            ],
                            [
                                'class' => 'btn btn-default btn-block',
                                'escape' => false,
                                'confirm' => 'Are you sure you want to mark this payment as having been refunded?'
                            ]
                        ) ?>
                    <?php endif; ?>
                </td>
                <td>
                    <button class="details btn btn-default btn-block">
                        Details
                    </button>
                </td>
            </tr>
            <tr class="details">
                <td colspan="5">
                    <ul>
                        <li>
                            <?php if ($purchase->admin_added): ?>
                                Purchase record added by
                                <?php if ($purchase->user): ?>
                                    <?= $purchase->user['name'] ?>
                                <?php else: ?>
                                    <span class="text-danger">
                                        an unknown admin (#<?= $purchase->user_id ?>)
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                Purchase made online by
                                <?php if ($purchase->user): ?>
                                    <?= $purchase->user['name'] ?>
                                <?php else: ?>
                                    <span class="text-danger">
                                        an unknown admin (#<?= $purchase->user_id ?>)
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </li>
                        <li>
                            Funding source:
                            <?php if ($purchase->source): ?>
                                <?= $sources[$purchase->source] ?>
                            <?php else: ?>
                                <span class="text-danger">
                                    unknown
                                </span>
                            <?php endif; ?>
                        </li>
                        <?php if ($purchase->refunded): ?>
                            <li>
                                Marked refunded by
                                <?php if ($purchase->refunder['name']): ?>
                                    <?= $purchase->refunder['name'] ?>
                                <?php else: ?>
                                    <span class="text-danger">
                                        an unknown admin (#<?= $purchase->refunder_id ?>)
                                    </span>
                                <?php endif; ?>
                                on
                                <?= $this->Time->format(
                                    $purchase->refunded,
                                    'MMMM d, Y',
                                    false,
                                    'America/New_York'
                                ); ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($purchase->notes): ?>
                            <li>
                                <?= nl2br($purchase->notes) ?>
                            </li>
                        <?php endif; ?>
                    </ul>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?= $this->element('pagination') ?>

<?php $this->element('script', ['script' => 'admin/purchases-index']); ?>
<?php $this->append('buffered'); ?>
    adminPurchasesIndex.init();
<?php $this->end(); ?>
