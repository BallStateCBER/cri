<div class="page-header">
    <h1>
        <?= $titleForLayout ?>
    </h1>
</div>

<?= $this->element('Communities/admin_header', [
    'adminHeader' => $adminHeader,
    'communityId' => $community->id,
    'surveyId' => null
]) ?>
<div id="presentations-form">
    <?= $this->Form->create($community) ?>
    <?php foreach (['a', 'b', 'c'] as $letter): ?>
        <?php
            $class = 'well';
            if ($community->{'presentation_' . $letter . '_scheduled'}) {
                $class .= ' show-date';
            }
        ?>
        <section class="<?= $class ?>">
            <h2>
                Presentation <?= strtoupper($letter) ?>
            </h2>
            <div class="checkbox">
                <?= $this->Form->radio(
                    'presentation_' . $letter . '_scheduled',
                    [
                        0 => 'Not scheduled yet',
                        1 => 'Scheduled'
                    ]
                ) ?>
            </div>
            <?= $this->Form->input(
                'presentation_' . $letter,
                ['label' => false]
            ) ?>
        </section>
    <?php endforeach; ?>
    <?= $this->Form->button(
        'Update',
        ['class' => 'btn btn-primary']
    ) ?>
    <?= $this->Form->end() ?>
</div>

<?php $this->append('buffered'); ?>
    presentationsForm.init();
<?php $this->end();