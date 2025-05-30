define([
    'jquery',
    'mage/url'
], function ($, urlBuilder) {
    'use strict';

    function decodeHtmlEntities(str) {
        var txt = document.createElement("textarea");
        txt.innerHTML = str;
        return txt.value;
    }

    return function (config, element) {
        const $input = $(element);
        const $suggestions = $('#opinion-suggestions');
        const $productIdsInput = $('#product_ids_input');
        const url = urlBuilder.build('/opinion/index/suggest');

        function fetchSuggestions(query) {
            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                data: { opinion_query: encodeURIComponent(query) },
                success: function (response) {
                    $suggestions.empty();

                    if (response.length) {
                        const productIds = [];

                        response.forEach(function (item) {
                            productIds.push(item.id);

                            $('<li>')
                                .addClass('suggestion-item')
                                .text(decodeHtmlEntities(item.name))
                                .attr('data-product-id', item.id)
                                .appendTo($suggestions);
                        });

                        $productIdsInput.val(productIds.join(','));
                    } else {
                        $productIdsInput.val('');
                        $suggestions.hide();
                    }
                }
            });
        }

        $input.on('keyup', function () {
            const query = $input.val();

            if (query.length < 3) {
                $suggestions.hide();
                $suggestions.empty();
                $productIdsInput.val('');
                return;
            }

            $productIdsInput.val('');
            fetchSuggestions(query);
            $suggestions.show();
        });

        $input.on('focus', function () {
            if ($suggestions.children().length) {
                $suggestions.show();
            }
        });

        $(document).on('click', '#opinion-suggestions li.suggestion-item', function () {
            const selectedText = $(this).text();
            const productId = $(this).data('product-id');

            $input.val(selectedText);
            $productIdsInput.val(productId);
            $suggestions.hide();

            setTimeout(function () {
                $input.closest('form').submit();
            }, 200);
        });

        $(document).on('click', function (event) {
            if (
                !$input.is(event.target) &&
                $input.has(event.target).length === 0 &&
                !$suggestions.is(event.target) &&
                $suggestions.has(event.target).length === 0
            ) {
                $suggestions.hide();
            }
        });

        $(document).ready(function () {
            const initialQuery = $input.val().trim();
            const productIds = $productIdsInput.val().trim();

            if (initialQuery.length >= 3 && !productIds) {
                fetchSuggestions(initialQuery);
                $suggestions.hide();
            }
        });
    };
});