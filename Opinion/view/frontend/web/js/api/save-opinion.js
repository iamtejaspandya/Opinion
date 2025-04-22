define(['jquery'], function ($) {
    'use strict';

    return function (config, opinionValue, callback) {
        $.ajax({
            url: config.saveUrl,
            type: 'POST',
            data: {
                form_key: config.formKey,
                product_id: config.productId,
                product_name: config.productName,
                opinion: opinionValue
            },
            showLoader: true,
            success: function (response) {
                if (callback && typeof callback === 'function') {
                    callback(response);
                }
            },
            error: function () {
                console.error("Error saving opinion.");
            }
        });
    };
});