define(['jquery'], function ($) {
    'use strict';

    return function (config, callback) {
        $.ajax({
            url: config.statusUrl,
            type: 'GET',
            data: { product_id: config.productId },
            showLoader: true,
            success: function (response) {
                if (callback && typeof callback === 'function') {
                    callback(response);
                }
            },
            error: function () {
                console.error("Error fetching opinion status.");
            }
        });
    };
});