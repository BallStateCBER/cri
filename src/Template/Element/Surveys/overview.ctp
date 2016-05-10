<?php
    use Cake\Validation\Validation;
?>

<div class="survey_overview">
    <?php if (! $isOpen): ?>
        <p class="alert alert-info">
            Note: This survey is not yet ready to be administered.
        </p>
    <?php endif; ?>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">
                Invite
            </h3>
        </div>
        <div class="panel-body">
            <p>
                <?= $invitedRespondentCount ?>
                community
                <?= __n("{$survey['type']} has been sent a survey invitation", "{$survey['type']}s have been sent survey invitations", $invitedRespondentCount) ?>
            </p>
            <p>
                <?php if ($invitedRespondentCount > 0): ?>
                    <button class="btn btn-default invitations_toggler">
                        View Invitations
                    </button>
                <?php endif; ?>
                <?= $this->Html->link(
                    'Send Invitations',
                    [
                        'prefix' => 'admin',
                        'controller' => 'Surveys',
                        'action' => 'invite',
                        $survey['id']
                    ],
                    ['class' => 'btn btn-default']
                ) ?>
                <?php if ($invitedRespondentCount > 0): ?>
                    <?= $this->Html->link(
                        'Reminders',
                        [
                            'prefix' => 'admin',
                            'controller' => 'Surveys',
                            'action' => 'remind',
                            $survey['id']
                        ],
                        ['class' => 'btn btn-default']
                    ) ?>
                <?php endif; ?>
            </p>
            <?php if ($invitedRespondentCount > 0): ?>
                <div class="invitations_list">
                    <p>
                        Invitations sent out for this survey:
                    </p>
                    <ul>
                        <?php foreach ($invitations as $invitation): ?>
                            <li>
                                <?= $invitation->name ?: '(No name)' ?>
                                <span class="email">
                                    <?php if (Validation::email($invitation->email)): ?>
                                        <a href="mailto:<?= $invitation->email ?>">
                                            <?= $invitation->email ?>
                                        </a>
                                    <?php else: ?>
                                        <?= $invitation->email ?: '(No email)' ?>
                                    <?php endif; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">
                Collect
            </h3>
        </div>
        <div class="panel-body">
            <p>
                <span class="last_import_time">
                    <?php if ($responsesChecked): ?>
                        Responses were last imported
                        <strong>
                            <?= $this->Time->timeAgoInWords($responsesChecked, ['end' => '+1 year']) ?>
                        </strong>
                    <?php else: ?>
                        Responses have not been imported yet
                    <?php endif; ?>
                </span>
            </p>
            <p>
                <?php if ($isAutomaticallyImported): ?>
                    Responses are automatically imported from
                    SurveyMonkey<?= $autoImportFrequency ? ' approximately '.$autoImportFrequency : '' ?>
                    while this community is in stage <?= $stageForAutoImport ?> of CRI,
                    but you can manually import them at any time.
                <?php else: ?>
                    New responses to this survey are <strong>not</strong> being automatically imported from SurveyMonkey because
                    this community is not currently in stage <?= $stageForAutoImport ?> of CRI.
                <?php endif; ?>
            </p>
            <button class="btn btn-default import_button" data-survey-id="<?= $survey['id'] ?>">
                Import Responses
            </button>
            <p id="import-results"></p>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">
                Review
            </h3>
        </div>
        <div class="panel-body">
            <p>
                <?php
                    if ($percentInvitedResponded < 33) {
                        echo '<span class="text-danger">';
                    } elseif ($percentInvitedResponded < 66) {
                        echo '<span class="text-warning">';
                    } else {
                        echo '<span class="text-success">';
                    }
                    echo $percentInvitedResponded.'%</span>';
                ?>
                of invited respondents have completed this survey
            </p>

            <?php if ($hasUninvitedUnaddressed): ?>
                <p>
                    <span class="text-warning">
                        This survey has uninvited responses that need to be approved or dismissed.
                    </span>
                    <br />
                    These responses will <strong>not</strong> be included in this community's alignment assessment unless if they are approved.
                </p>
            <?php endif; ?>

            <?php if (isset($hasNewResponses) && $hasNewResponses): ?>
                <p>
                    <strong>
                        New responses have been received
                    </strong>
                    since this community's alignment was last set by an administrator.
                </p>
            <?php endif; ?>

            <?php
                $buttonClass = (isset($hasNewResponses) && $hasNewResponses) ? 'primary' : 'default';
                echo $this->Html->link(
                    'Review and Update Alignment',
                    [
                        'prefix' => 'admin',
                        'controller' => 'Responses',
                        'action' => 'view',
                        $survey['id']
                    ],
                    ['class' => 'btn btn-'.$buttonClass]
                );
            ?>

            <?php if ($uninvitedRespondentCount > 0): ?>
                <?= $this->Html->link(
                    'Review / Approve Uninvited Responses',
                    [
                        'prefix' => 'admin',
                        'controller' => 'Respondents',
                        'action' => 'unapproved',
                        $survey['id']
                    ],
                    ['class' => 'btn btn-default']
                ) ?>
            <?php endif; ?>
        </div>
    </div>
</div>