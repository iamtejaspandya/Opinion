define([
    'jquery',
    'mage/url'
], function ($, urlBuilder) {
    'use strict';

    return function (config, element) {
        let $label = $(element);
        let productId = config.productId;
        let classes = 'one-opinion all-liked liked someliked mixed not-enough no-opinion error';
        const url = urlBuilder.build('/opinion/index/productopinionlabel');

        function updateLabel(text, className) {
            $label.html(text).removeClass(classes).addClass(className).show();
        }

        if (productId) {
            $.ajax({
                url: url,
                type: 'GET',
                data: { product_id: productId },
                success: function (response) {
                    if (response.error) {
                        updateLabel(response.message, response.class);
                    } else if (response.success) {
                        updateLabel(response.message, response.class);
                    } else {
                        updateLabel('Unexpected response.', 'error');
                    }
                },
                error: function () {
                    updateLabel('Error loading opinions', 'error');
                }
            });
        }
    };
});