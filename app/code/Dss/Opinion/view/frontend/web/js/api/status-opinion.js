define([
    'jquery',
    'mage/url'
], function ($, urlBuilder) {
    'use strict';

    return function (config, callback) {
        const url = urlBuilder.build('/opinion/index/status');

        $.ajax({
            url: url,
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