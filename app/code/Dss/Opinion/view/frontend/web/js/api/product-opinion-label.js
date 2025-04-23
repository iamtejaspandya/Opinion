define(['jquery'], function ($) {
    'use strict';

    return function (config, element) {
        let $label = $(element);
        let productId = config.productId;
        let url = config.labelUrl;
        let classes = 'liked someliked mixed not-enough no-opinion error';

        function updateLabel(text, className) {
            $label.html(text).removeClass(classes).addClass(className).show();
        }

        if (productId) {
            $.ajax({
                url: url,
                type: 'GET',
                data: { product_id: productId },
                success: function (response) {
                    if (response.success) {
                        updateLabel(response.message, response.class);
                    } else {
                        updateLabel('Be the first to share your opinion!', 'no-opinion');
                    }
                },
                error: function () {
                    updateLabel('Error loading opinions', 'error');
                }
            });
        }
    };
});