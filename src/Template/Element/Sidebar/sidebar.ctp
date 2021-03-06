<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $accessibleCommunities
 */
    use Cake\Core\Configure;
    if (! isset($authUser)) {
        $authUser = [];
    }
?>

<a href="/" id="sidebar_logo">
    <img src="/img/cri_logo.png" alt="Community Readiness Initiative" />
</a>

<?php if ($authUser): ?>
    <nav class="logged_in">
        <h2 class="logged_in">
            Logged in as
            <?php if (Configure::read('debug')): ?>
                <strong>
                    <?= $authUser['role'] ?>
                </strong>
            <?php endif; ?>
            <?= $authUser['name'] ?>
        </h2>

        <ul>
            <?php if ($authUser['role'] == 'admin'): ?>
                <?= $this->element('Sidebar/admin') ?>
            <?php elseif ($authUser['role'] == 'client'): ?>
                <?= $this->element('Sidebar/client') ?>
            <?php endif; ?>
            <li class="link">
                <?= $this->Html->link(
                    'My Account',
                    [
                        'prefix' => false,
                        'plugin' => false,
                        'controller' => 'Users',
                        'action' => 'myAccount'
                    ]
                ) ?>
            </li>
            <li class="link">
                <?= $this->Html->link(
                    'Logout',
                    [
                        'prefix' => false,
                        'plugin' => false,
                        'controller' => 'Users',
                        'action' => 'logout'
                    ]
                ) ?>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<nav class="public_nav">
    <ul>
        <li class="link">
            <?= $this->Html->link(
                'CRI Home',
                '/'
            ) ?>
        </li>
        <li class="link">
            <?= $this->Html->link(
                'Enroll',
                [
                    'prefix' => false,
                    'plugin' => false,
                    'controller' => 'Pages',
                    'action' => 'enroll'
                ]
            ) ?>
        </li>
        <?php if ($authUser && $authUser['role'] == 'admin' && ! empty($accessibleCommunities)): ?>
            <li>
                <p>
                    Community Performance
                </p>
                <form method="get" id="community-select" action="/communities/view">
                    <select name="community_slug" required>
                        <option value="">
                            Select a community...
                        </option>
                        <?php foreach ($accessibleCommunities as $community): ?>
                            <option value="<?= $community['slug'] ?>">
                                <?= $community['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" value="" />
                </form>
            </li>
        <?php endif; ?>
        <li class="link">
            <?= $this->Html->link(
                'FAQs for Communities',
                [
                    'prefix' => false,
                    'plugin' => false,
                    'controller' => 'Pages',
                    'action' => 'faqCommunity'
                ]
            ) ?>
        </li>
        <li class="link">
            <?= $this->Html->link(
                'Credits and Sources',
                [
                    'prefix' => false,
                    'plugin' => false,
                    'controller' => 'Pages',
                    'action' => 'credits'
                ]
            ) ?>
        </li>
        <?php if (! $authUser): ?>
            <li class="link">
                <?= $this->Html->link(
                    'Login',
                    [
                        'prefix' => false,
                        'plugin' => false,
                        'controller' => 'Users',
                        'action' => 'login'
                    ]
                ) ?>
            </li>
        <?php endif; ?>
    </ul>
</nav>

<?php $this->append('buffered'); ?>
    sidebar.init();
<?php $this->end(); ?>
