<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $redirect
 * @var string $titleForLayout
 */
?>
<div class="page-header">
    <h1>
        <?= $titleForLayout ?>
    </h1>
</div>

<?php
    echo $this->Form->create(false);
    echo $this->Form->control(
        'community_id',
        [
            'class' => 'form-control',
            'div' => ['class' => 'form-group'],
        ]
    );
    if ($redirect) {
        echo $this->Form->control(
            'redirect',
            [
                'type' => 'hidden',
                'value' => $redirect
            ]
        );
    }
    echo $this->Form->button(
        'Continue',
        ['class' => 'btn btn-primary']
    );
    echo $this->Form->end();
