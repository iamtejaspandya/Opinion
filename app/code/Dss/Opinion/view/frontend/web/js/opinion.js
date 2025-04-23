define([
    'jquery',
    'opinionStatus',
    'opinionSave',
    'productOpinionLabel'
], function ($, loadStatus, saveOpinion, productOpinionLabel) {
    'use strict';

    return function (config) {
        $(document).ready(function () {
            loadOpinion();
        });

        function loadOpinion() {
            loadStatus(config, function (response) {
                let opinionText = response.opinion === 1 ? config.likeMessage :
                                  response.opinion === 0 ? config.dislikeMessage :
                                  config.defaultMessage;

                let newHtml = `
                    <span id="opinion-status" class="opinion-status ${response.opinion !== null ? (response.opinion ? 'liked' : 'disliked') : 'neutral'}">
                        ${opinionText}
                    </span>
                    <div id="opinion-button-container" class="opinion-button-container">
                        ${response.opinion === 1 ?
                            `<button class="action secondary dislike-button" id="dislike-button" data-opinion="0">${config.dislikeLabel}</button>` :
                            `<button class="action primary like-button" id="like-button" data-opinion="1">${config.likeLabel}</button>`
                        }
                        ${response.opinion === null ?
                            `<button class="action secondary dislike-button" id="dislike-button" data-opinion="0">${config.dislikeLabel}</button>` : ''}
                    </div>
                `;

                if ($('#opinion-wrapper #opinion-container').length === 0) {
                    $('#opinion-wrapper').html('<div id="opinion-container" class="opinion-container"></div>');
                }
                $('#opinion-container').html(newHtml);

                bindOpinionButtonClick();
                productOpinionLabel(config, '#product-opinion-label');
            });
        }

        function bindOpinionButtonClick() {
            $('#opinion-button-container').off('click', 'button').on('click', 'button', function () {
                let opinionValue = $(this).data('opinion');
                saveOpinion(config, opinionValue, function (response) {
                    if (response.redirect && response.redirect_url) {
                        window.location.href = response.redirect_url;
                    } else {
                        let messageType = response.success ? "success" : "error";
                        $('#opinion-message').html(`<div class="message ${messageType}">${response.message}</div>`);
                        loadOpinion();
                    }
                });
            });
        }
    };
});