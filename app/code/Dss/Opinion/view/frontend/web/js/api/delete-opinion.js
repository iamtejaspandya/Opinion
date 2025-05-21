define([
    'jquery',
    'Magento_Ui/js/modal/confirm'
], function ($, confirmation) {
    'use strict';

    return function deleteOpinion(config, callback) {
        confirmation({
            title: 'Delete Opinion',
            content: 'Are you sure you want to delete your opinion for <strong>' + config.productName + '</strong>?',
            actions: {
                confirm: function () {
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
                },
                cancel: function () {
                    // No action needed on cancel
                }
            }
        });
    };
});