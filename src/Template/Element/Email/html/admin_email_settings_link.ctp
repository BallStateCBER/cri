<?php
/**
 * @var \App\View\AppView $this
 */
/**
 * @var \App\View\AppView $this
 */
?>
<p>
    You can edit your subscription to administrator alert emails by visiting
    <?= $this->Html->link(
        'My Account',
        [
            'controller' => 'Users',
            'action' => 'myAccount',
            '_full' => true,
            'plugin' => null
        ]
    ) ?>.
</p>
