
/*
 * JavaScript code for Fuze Partner API in-browser demo
 *
 *
 */
$(function () {

    var status = {
        partner: false,
        user: false,
        setPartner: function (partner) {
            if (!partner) {
                // Clearing the Partner keys
                $('#partner-status').addClass('warning').text('Not OK');
                this.partner = false;
                this.setUser(false);
            } else {
                this.partner = true;
                $('#partner-status').removeClass('warning').text('OK, PK: ' + partner);
            }
        },
        setUser: function (user) {
            if (!user) {
                $('#user-status').addClass('warning').text('Not OK');
                this.user = false;
            } else {
                this.user = true;
                $('#user-status').removeClass('warning').text('OK, email: ' + user);
            }
        }
    };

    // Tabulate
    var $tabs = $('#tabs').tabs();

    var $dialog = $('#dialog').dialog({
        autoOpen: false,
        title: 'Dialog Placeholder',
        modal: true,
        height: 530,
        width: 600
    });

    $('.calendar').datepicker({dateFormat: $.datepicker.RFC_2822});

    $('#loading').ajaxStart(function () {
        $(this).show();
    }).ajaxStop(function () {
        $(this).hide();
    });

    var post = function (action, data, callback) {
        data['action'] = action;
        $.ajax({
            url: 'ajax.php',
            dataType: 'json',
            type: 'POST',
            data: data,
            success: callback,
            error: function (xhr, status, err) {
                callback({
                    error: 'HTTP error: ' + status
                });
            }
        });
    };

    var updateStatus = function () {
        post('status', {}, function (resp) { // callback
            if (resp.error) {
                alert("There is something wrong with the backend!\n" + resp.error);
                return;
            }
            if (resp.status.partner) {
                status.setPartner(resp.pkey);
            } else {
                status.setPartner(false);
            }
            if (resp.status.user) {
                status.setUser(resp.user_email);
            } else {
                status.setUser(false);
            }
        });
   };

    $('#goto-signup-link').click(function() {
        // focus the user signup form
        $tabs.tabs('select', '#tab-user');
        $('#user-signup-form input').first().focus();
        return false;
    });

    $('#partner-credentials-form').submit(function () {
        $form = $(this);
        $form.find('input[type=submit]').attr('disabled', 'disabled');
        var data = {
            partner_key: $('#partner-key').val(),
            encryption_key: $('#encryption-key').val()
        };
        $('#encryption-key').val('');
        post('signinpartner', data, function(resp) {
            if (resp.error) {
                status.setPartner(false);
            } else {
                status.setPartner(resp.pk);
            }
            $form.find('input[type=submit]').removeAttr('disabled');
        });
        return false;
    });

    $('#user-credentials-form').submit(function() {
        if (!status.partner) {
            alert('Verify Partner keys first!');
            return false;
        }

        $('#user-credentials-form input[type=submit]').attr('disabled', 'disabled');
        var data = {
            email: $('#user-email').val(),
            password: $('#user-password').val()
        };

        $('#user-password').val('');

        post('signin', data, function (resp) {
            if (resp.error) {
                status.setUser(false);
            } else {
                status.setUser(resp.email);
            }
            $('#user-credentials-form input[type=submit]').removeAttr('disabled');
            updateStatus();
        });

        return false;
    });

    $('#clear-credentials-btn').click(function () {
        post('clearcredentials', {}, function() {
            status.setPartner(false);
            status.setUser(false);
            alert('Credentials cleared');
        });
        return false;
    });

    $('#user-signup-form').submit(function () {
        if (!status.partner) {
            alert('Verify Partner Keys first!');
            $tabs.tabs('select', 'tab-status');
            return false;
        }

        $('#user-signup-form input[type=submit]').attr('disabled', 'disabled');

        var data = {
            email: $('#user-signup-email').val(),
            password: $('#user-signup-password').val(),
            firstname: $('#user-signup-firstname').val(),
            lastname: $('#user-signup-lastname').val(),
            phone: $('#user-signup-phone').val(),
            welcome: $('#user-signup-welcome').is(':checked') ? '1' : ''
        };
        data['packages'] = $('user-signup-packages').val();

        post('signup', data, function (resp) {
            if (!resp || resp.error) {
                alert("User signup failed:\n" + resp.error);
            } else {
                // show modal div here, ask user if they want to use this one
                if ( confirm("Signup went ok.\nWould you like to use the newly signed-up\n" +
                             "user for API requests now?") ) {

                    // Use the email and password to do a signin and get a cookie
                    $tabs.tabs('select', '#tab-status');
                    $('#user-email').val(data.email);
                    $('#user-password').val(data.password);
                    $('#user-credentials-form').submit();
                }
            }
            $('#user-signup-form input[type=submit]').removeAttr('disabled');
        });
        return false;
    });

    $('#user-subscribe-form').submit(function () {
        if (!status.partner || !status.user) {
            alert("Valid partner keys and user credentials are required!");
            return false;
        }
        $form = $(this);
        $form.find('input[type=submit]').attr('disabled', 'disabled');

        var data = {};
        data['packages'] = $('#user-subscribe-packages').val();

        console.log(data);

        post('subscribe', data, function (resp) {
            $form.find('input[type=submit]').removeAttr('disabled');
            if (!resp || resp.error) {
                alert("Schedule failed:\n" + resp.error);
            } else {
                alert("User successfully subscribed to " + resp['package']);
            }
        });
        return false;
    });

    $('#list-packages-link').click(function () {
        post('listpackages', {}, function (resp) {
            if (!resp || !resp.success) {
                alert('getPackages failed: ' + resp.error);
                return;
            }
            var h = '<div><p><h1>Available Packages</h1></p>';
            $.each(resp.packages, function (index, pkg) {
                // TODO: Add a link to automatically subscribe to the package?
                var _is_addon = pkg.is_primary ? '' : ' (addon)';
                h += '<div><p><h2>' + pkg.displayname + _is_addon +  '</h2></p>';
                h += '<p><strong>Description</strong>: ' + pkg.description + '</p>';
                h += '<p><strong>Internal name</strong>: ' + pkg.name + '</p>';
                h += '<p><strong>Phone attendees</strong>: ' + pkg.phoneattendees + '</p>';
                h += '<p><strong>Web attendees</strong>: ' + pkg.webattendees + '</p>';
                h += '<p><strong>Currency</strong>: ' + pkg.currency + '</p>';
                h += '<p><strong>Billing cycle</strong>: ' + pkg.billingcycle + '</p>';
                h += '<p><strong>Price</strong>: ' + pkg.price + '</p>';
                h += '</div>';
            });
            h += '</div>';
            $dialog.dialog('option', 'title', 'Package List');
            $dialog.html(h);
            $dialog.dialog('open');
        });
        return false;
    });

    var onMeetingLaunch = function (launchUrl) {
        var htmlContents = '<div><p>To start the meeting, click ';
        htmlContents += '<a href="' + launchUrl + '" target="_blank">here</a></p></div>';
        $dialog.dialog('option', 'title', 'Meeting Launch!');
        $dialog.html(htmlContents);
        $dialog.dialog('open');
    };

    $('#meeting-schedule-form').submit(function () {
        if (!status.partner || !status.user) {
            alert("Valid partner keys and user credentials are required!");
            return false;
        }

        $('#meeting-schedule-form input[type=submit]').attr('disabled', 'disabled');

        var data = {
            sendemail: $('#meeting-schedule-sendemail').val(),
            includetollfree: $('#meeting-schedule-includetollfree').val(),
            includeinternationaldial: $('#meeting-schedule-includeinternationaldial').val(),
            starttime: $('#meeting-schedule-starttime').val(),
            endtime: $('#meeting-schedule-endtime').val(),
            subject: $('#meeting-schedule-subject').val(),
            invitationtext: $('#meeting-schedule-invitationtext').val(),
            invitees: $('#meeting-schedule-invitees').val(),
            timezone: $('#meeting-schedule-timezone').val(),
            autorecording: $('#meeting-schedule-autorecording').val(),
            webinar: $('#meeting-schedule-webinar').is(':checked') ? '1' : '',
            startnow: $('#meeting-schedule-startnow').is(':checked') ? '1' : ''
        };

        var onMeetingScheduled = function (meetingId) {
            $('#meeting-launch-id').val(meetingId);
            alert("Scheduled meeting " + meetingId +
                  "\nTo start it, submit the \"Launch a meeting\" form.");

        };

        post('schedule', data, function (resp) {
            if (!resp || resp.error) {
                alert("Schedule failed:\n" + resp.error);
            } else {
                if (resp.signedlaunchurl) {
                    onMeetingLaunch(resp.signedlaunchurl);
                } else {
                    onMeetingScheduled(resp.meetingid);
                }
            }
            $('#meeting-schedule-form input[type=submit]').removeAttr('disabled');
        });

        return false;
    });

    $('#meeting-launch-form').submit(function () {
        if (!status.partner || !status.user) {
            alert("Valid partner keys and user credentials are required!");
            return false;
        }

        $('#meeting-launch-form input[type=submit]').attr('disabled', 'disabled');

        var data = {
            meetingid: $('#meeting-launch-id').val()
        };

        post('launch', data, function (resp) {
            if (!resp || resp.error) {
                alert("Launch failed:\n" + resp.error);
            } else {
                onMeetingLaunch(resp.url);
            }
            $('#meeting-launch-form input[type=submit]').removeAttr('disabled');
        });

        return false;
    });

    $('#meeting-list-form').submit(function () {
        if (!status.partner || !status.user) {
            alert("Valid partner keys and user credentials are required!");
            return false;
        }

        var $form = $(this);
        $form.find('input[type=submit]').attr('disabled', 'disabled');

        var data = {
            page: $('#meeting-list-page').val(),
            count: $('#meeting-list-count').val(),
            startdate: $('#meeting-list-startdate').val(),
            enddate: $('#meeting-list-enddate').val()
        };

        post('list', data, function (resp) {
            if (!resp || resp.error) {
                alert("List meetings failed:\n" + resp.error);
            } else {
                $dialog.dialog('option', 'title', 'List Meetings Response');

                var htmlContents = '';
                htmlContents += '<div><p><strong>Total meetings:</strong> ' + resp.total + '</p>';
                htmlContents += '<p><strong>Meetings in this response:</strong> ' + resp.count + '</p>';
                $.each(resp.meetings, function (index, meeting) {
                    htmlContents += '<hr/><p>Meeting ID: ' + meeting.details.meetingid + '<br/>';
                    htmlContents += 'Subject: ' + meeting.details.subject + '<br/>';
                    htmlContents += '</p>';
                });

                htmlContents += '</div>';
                $dialog.html(htmlContents);
                $dialog.dialog('open');
            }
            $form.find('input[type=submit]').removeAttr('disabled');
        });

        return false;
    });

    $('#generic-call-addparam').click(function () {

        var $newParamDiv = $('#generic-call-param-template').clone()
            .removeAttr('id')
            .show();

        // bind a function to remove the row
        $newParamDiv.find('a').click(function () {
            $(this).parent('div').remove();
            return false;
        });

        // add the new element to the DOM tree
        $('#generic-call-parameters').append($newParamDiv);

        return false;
    });

    $('#media-upload-form').submit(function () {

        return false;
    });

    $('#generic-call-form').submit(function () {

        var data = {
            method: $('#generic-call-methodname').val()
        };

        var params = {};

        $('#generic-call-parameters div').each(function (index, pd) {
            var _key = $(this).find('input[name=key]').val();
            if (_key == undefined) {
                return;
            }
            var _val = $(this).find('input[name=value]').val();
            params[_key] = _val;
        });

        data.params = JSON.stringify(params);

        post('generic', data, function (resp) {
            if (!resp || resp.error) {
                alert("Call failed:\n" + resp.error);
                return;
            }

            $dialog.dialog('option', 'title', 'Call Response');

            var h = '<div><strong>Method:</strong> <tt>' + data.method + '</tt>';
            h += '<p><strong>Response Code: </strong> ' + resp.response.code +  '</p>';
            h += '<p><strong>Response Message: </strong> ' + resp.response.message +  '</p>';
            h += '<div><strong>Server response</strong><br/>';
            h += '<pre>' + JSON.stringify(resp.response, null, 2) + '</pre></div>';
            h += '</div>';
            $dialog.html(h);
            $dialog.dialog('open');

        });
        return false;
    });

    $('#media-upload-form').submit(function () {
        if (!status.partner || !status.user) {
            alert("Valid partner keys and user credentials are required!");
            return false;
        }

        var options = {
            type: 'POST',
            url: 'upload.php',
            iframe: true,
            contentType: 'multipart/form-data',
            dataType: 'json',
            success: function (resp, status) {
                if (!resp || resp.error) {
                    alert('Error uploading file: ' + resp.error);
                } else {
                    alert('Media was uploaded, code: ' + resp.code +
                          '\n media ID: ' + resp.mediaId);
                }
            }
        };
        $(this).ajaxSubmit(options);

        return false;
    });

   // Finally, get the status of our session
   updateStatus();

});
