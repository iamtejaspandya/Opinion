define([
    'jquery',
    'mage/url',
    'Magento_Ui/js/modal/confirm'
], function ($, urlBuilder, confirmation) {
    'use strict';

    return {
        /**
         * Saves a customer's opinion (like or dislike) for a product.
         *
         * @param {Object} config - Configuration object.
         * @param {string} config.formKey - Magento form key for CSRF protection.
         * @param {number} config.productId - ID of the product.
         * @param {string} config.productName - Name of the product.
         * @param {number} opinionValue - Customer's opinion value (1 = liked, 0 = disliked).
         * @param {Function} callback - Callback function to handle the response.
         */
        save: function (config, opinionValue, callback) {
            const url = urlBuilder.build('opinion/index/save');

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    form_key: config.formKey,
                    product_id: config.productId,
                    product_name: config.productName,
                    opinion: opinionValue
                },
                showLoader: true,
                success: function (response) {
                    if (typeof callback === 'function') {
                        callback(response);
                    }
                },
                error: function () {
                    console.error("Error saving opinion.");
                }
            });
        },

        /**
         * Fetches a customer's existing opinion status for a product.
         *
         * @param {Object} config - Configuration object.
         * @param {number} config.productId - ID of the product.
         * @param {Function} callback - Callback function to handle the response.
         */
        status: function (config, callback) {
            const url = urlBuilder.build('opinion/index/status');

            $.ajax({
                url: url,
                type: 'GET',
                data: { product_id: config.productId },
                showLoader: true,
                success: function (response) {
                    if (typeof callback === 'function') {
                        callback(response);
                    }
                },
                error: function () {
                    console.error("Error fetching opinion status.");
                }
            });
        },

        /**
         * Updates the product opinion label based on aggregated data.
         *
         * @param {Object} config - Configuration object.
         * @param {number} config.productId - ID of the product.
         * @param {HTMLElement|string|jQuery} element - DOM element or selector where the label should be updated.
         */
        label: function (config, element) {
            const $label = $(element);
            const productId = config.productId;
            const classes = 'one-opinion all-liked liked someliked mixed not-enough no-opinion error';
            const url = urlBuilder.build('opinion/index/productopinionlabel');

            function updateLabel(text, className) {
                $label.html(text).removeClass(classes).addClass(className).show();
            }

            if (productId) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    data: { product_id: productId },
                    success: function (response) {
                        if (response.error || response.success) {
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
        },

        /**
         * Shows a confirmation dialog and deletes a customer's opinion for a product.
         *
         * @param {Object} config - Configuration object.
         * @param {number} config.opinionId - ID of the opinion to delete.
         * @param {string} config.formKey - Magento form key for CSRF protection.
         * @param {string} config.productName - Name of the product.
         * @param {string} config.errorMessage - Fallback message on error.
         * @param {Function} callback - Callback function to handle the response.
         */
        delete: function (config, callback) {
            const url = urlBuilder.build('opinion/index/delete');

            confirmation({
                title: 'Delete Opinion',
                content: 'Are you sure you want to delete your opinion for <strong>' + config.productName + '</strong>?',
                actions: {
                    confirm: function () {
                        $.ajax({
                            url: url,
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
                        // No action needed
                    }
                }
            });
        }
    };
});