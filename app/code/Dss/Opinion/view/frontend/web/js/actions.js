define([
    'jquery',
    'opinionSave'
], function ($, saveOpinion) {
    'use strict';

    return function (config) {
        $(document).ready(function () {
            $(document).off('click', `#${config.elementId} button`).on('click', `#${config.elementId} button`, function () {
                const $button = $(this);
                const $row = $button.closest('tr');
                const opinionValue = $button.data('opinion');

                saveOpinion(config, opinionValue, function (response) {
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
                               .removeClass('primary secondary')
                               .addClass(newButtonClass, newClass)
                               .data('opinion', newOpinionValue);

                        $row.find('.product-image, .product-name, .product-opinion, .product-opinion-actions')
                            .removeClass(oldClass)
                            .addClass(newClass);
                    }
                });
            });
        });
    };
});