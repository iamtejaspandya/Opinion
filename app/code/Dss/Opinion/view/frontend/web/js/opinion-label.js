define([
    'opinionManager'
], function (opinionManager) {
    'use strict';

    return function (config, element) {
        opinionManager.label(config, element);
    };
});