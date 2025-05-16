define([
    'jquery'
], function ($) {
    'use strict';

    return function deleteOpinion(config, callback) {
        if (!confirm('Are you sure you want to delete this opinion?')) {
            return;
        }

        $.ajax({
            url: config.deleteUrl,
            type: 'POST',
            data: {
                opinion_id: config.opinionId,
                form_key: config.formKey
            },
            success: function (response) {
                if (typeof callback === 'function') {
                    callback(response);
                }
            },
            error: function () {
                if (typeof callback === 'function') {
                    callback({ success: false, message: config.errorMessage });
                }
            }
        });
    };
});