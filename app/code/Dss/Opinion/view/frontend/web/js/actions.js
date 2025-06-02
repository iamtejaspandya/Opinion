define([
    'jquery',
    'opinionManager'
], function ($, opinionManager) {
    'use strict';

    return function (config) {
        $(document).ready(function () {
            $(document).off('click', `#${config.elementId} .change-opinion`).on('click', `#${config.elementId} .change-opinion`, function () {
                const $button = $(this);
                const $row = $button.closest('tr');
                const opinionValue = $button.data('opinion');

                opinionManager.save(config, opinionValue, function (response) {
                    if (response.redirect && response.redirect_url) {
                        window.location.href = response.redirect_url;
                    } else if (response.success) {
                        const newOpinionText = opinionValue === 1 ? 'Liked' : 'Disliked';
                        const newButtonText = opinionValue === 1 ? 'Change to Dislike' : 'Change to Like';
                        const newOpinionValue = opinionValue === 1 ? 0 : 1;
                        const newButtonClass = opinionValue === 1 ? 'secondary' : 'primary';
                        const newClass = opinionValue === 1 ? 'liked' : 'disliked';
                        const oldClass = opinionValue === 1 ? 'disliked' : 'liked';

                        $row.find('.product-opinion').text(newOpinionText);

                        $button.text(newButtonText)
                               .removeClass('primary secondary liked disliked')
                               .addClass(newButtonClass)
                               .addClass(newClass)
                               .data('opinion', newOpinionValue);

                        $row.find('.product-image, .product-name, .product-opinion, .product-opinion-actions')
                            .removeClass(oldClass)
                            .addClass(newClass);
                    }
                });
            });

            $(document).off('click', `#${config.elementId} .delete-opinion`).on('click', `#${config.elementId} .delete-opinion`, function () {
                const $button = $(this);
                const $row = $button.closest('tr');

                opinionManager.delete({
                    ...config,
                    opinionId: $button.data('opinion-id')
                }, function (response) {
                    if (response.redirect && response.redirect_url) {
                        window.location.href = response.redirect_url;
                    } else {
                        alert(response.message || config.errorMessage);
                    }
                });
            });
        });
    };
});