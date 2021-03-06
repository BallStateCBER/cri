var surveyInvitationForm = {
    counter: 1,
    already_invited: [],
    uninvited_respondents: [],
    cookieKey: 'invitationFormData',
    cookieExpiration: 365, // in days
    rowLimit: 20,
    surveyId: null,

    init: function (params) {
        this.counter = params.counter;
        this.already_invited = params.already_invited;
        this.uninvited_respondents = params.uninvited_respondents;
        this.surveyId = params.surveyId;

        // Show first row and all rows with values, hide others
        var form = $('#UserClientInviteForm');
        form.find('tbody tr').each(function () {
            var row = $(this);

            if (row.is(':first-child')) {
                surveyInvitationForm.showRow(row);
                return;
            }

            var inputs = row.find('input');
            for (var n = 0; n < inputs.length; n++) {
                if ($(inputs[n]).val()) {
                    surveyInvitationForm.showRow(row);
                    return;
                }
            }

            surveyInvitationForm.removeRow(row);
        });
        this.toggleRemoveButtons();

        // Set up buttons
        $('#add_another').click(function (event) {
            event.preventDefault();
            surveyInvitationForm.showRow();
        });
        $('#sent_invitations_toggler').click(function (event) {
            event.preventDefault();
            $('#sent_invitations').slideToggle();
        });
        $('#suggestions_toggler').click(function (event) {
            event.preventDefault();
            $('#invitation_suggestions').slideToggle();
        });
        form.submit(function (event) {
            return surveyInvitationForm.onSubmit(event);
        });
        form.find('button.remove').click(function () {
            surveyInvitationForm.removeRow($(this).parents('tr'));
        });
        $('#clear-data').click(function (event) {
            event.preventDefault();
            var link = $(this);
            var resultsContainer = $('#clear-data-results');
            surveyInvitationForm.clearData(link, resultsContainer);
        });

        // Set up form protection
        formProtector.protect('UserClientInviteForm', {});

        // Set up email trimming / checking
        form.find('input[type=email]').change(function () {
            var field = $(this);

            // Trim whitespace
            var trimmedInput = field.val().trim();
            field.val(trimmedInput);

            // Check validity of email
            surveyInvitationForm.checkEmail(field);
        });
    },

    /**
     * Sends an AJAX request for clearing saved invitation data
     *
     * @param link
     * @param resultsContainer
     */
    clearData: function (link, resultsContainer) {
        $.ajax({
            url: '/surveys/clear-saved-invitation-data/' + surveyInvitationForm.surveyId,
            dataType: 'json',
            beforeSend: function () {
                link.addClass('disabled');
                var loadingIndicator = $('<img src="/data_center/img/loading_small.gif" class="loading" />');
                link.append(loadingIndicator);
                if (resultsContainer.is(':visible')) {
                    resultsContainer.hide();
                }
            },
            success: function (data) {
                resultsContainer.attr('class', 'text-success');
                resultsContainer.html('<span class="glyphicon glyphicon-ok"></span> Saved data cleared');
                resultsContainer.fadeIn(200);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                resultsContainer.attr('class', 'text-danger');
                resultsContainer.html('<span class="glyphicon glyphicon-warning-sign"></span> Error clearing data');
                resultsContainer.fadeIn(200);
            },
            complete: function () {
                link.removeClass('disabled');
                link.find('.loading').remove();
            }
        });
    },

    /**
     * On submit callback for the invitation form
     *
     * @param event
     * @returns {boolean}
     */
    onSubmit: function (event) {
        var form = $('#UserClientInviteForm');

        // Note redundant emails
        if (form.find('.already_invited').length > 0) {
            alert('Please remove any email addresses that have already been recorded before continuing.');
            event.preventDefault();
            return false;
        }

        // Note any blank fields
        var inputs = form.find('input:visible');
        for (var i = 0; i < inputs.length; i++) {
            if ($(inputs[i]).val() === '') {
                alert('All fields (name, email, and professional title) must be filled out before continuing.');
                event.preventDefault();
                return false;
            }
        }

        return true;
    },

    /**
     * If a row is provided, shows that row. Otherwise, shows next hidden row
     *
     * @param row
     */
    showRow: function (row) {
        var form = $('#UserClientInviteForm');
        row = row ? $(row) : form.find('tbody tr').not(':visible').first();

        row.css('display', 'table-row');
        row.find('input').prop('required', true);

        var visibleCount = form.find('tbody tr:visible').length;

        if (visibleCount >= this.rowLimit) {
            if ($('#limit-warning').length === 0) {
                var warning = $('<p id="limit-warning" class="alert alert-info"></p>');
                warning.html('Sorry, at the moment only ' + this.rowLimit + ' invitations can be sent out at a time.');
                warning.hide();
                form.find('table').after(warning);
                warning.slideDown(500);
            }
            $('#add_another').hide();
        }

        this.toggleRemoveButtons();
    },

    /**
     * Hides a row, clears its input, and places it at the bottom of the table
     *
     * @param row
     */
    removeRow: function (row) {
        row = $(row);
        row.hide();
        row.find('input').val('');
        row.find('input').prop('required', false);

        var form = $('#UserClientInviteForm');
        form.find('tbody').append(row.detach());

        var addAnother = $('#add_another');
        if (!addAnother.is(':visible')) {
            addAnother.show();
        }

        var visibleRowCount = form.find('tbody tr:visible').length;
        if (visibleRowCount < this.rowLimit) {
            $('#limit-warning').slideUp(function () {
                $(this).remove();
            });
        }

        this.toggleRemoveButtons();
    },

    /**
     * Hides all 'remove' buttons if only one row is visible
     */
    toggleRemoveButtons: function () {
        var button = $('button.remove');
        var isVisible = $('#UserClientInviteForm').find('tbody tr:visible').length === 1;
        button.toggle(!isVisible);
    },

    checkEmail: function (field) {
        var email = field.val();
        if (email === '') {
            return;
        }

        var container = field.closest('td');
        container.children('.error-message').remove();
        var errorMsg = null;
        if (this.isInvitedRespondent(email)) {
            errorMsg = $('<div class="error-message already_invited"></div>');
            errorMsg.html('An invitation has already been sent to ' + email);
            container.append(errorMsg);
            return;
        }

        errorMsg = field.parent('td').children('.error-message');
        errorMsg.removeClass('already_invited');
        errorMsg.slideUp(function () {
            $(this).remove();
        });
    },

    /**
     * Returns whether or not the given email address has received an invitation
     *
     * @param email
     * @returns {boolean}
     */
    isInvitedRespondent: function (email) {
        return this.already_invited.indexOf(email) !== -1;
    }
};
