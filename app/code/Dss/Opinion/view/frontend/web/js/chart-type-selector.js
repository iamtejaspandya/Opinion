define([
    'jquery'
], function ($) {
    'use strict';

    return function () {
        $(function () {
            const $chartWrapper = $('.opinion-stats');
            const chartTypes = ['pie', 'doughnut', 'polarArea'];
            const chartTypeKey = 'dss_opinion_selected_chart_type';

            if ($chartWrapper.length) {
                const $selectorWrapper = $('<div>', { class: 'chart-type-selector-wrapper' });
                const $label = $('<label>', { for: 'chart-type', text: 'Chart Type' });
                const $select = $('<select>', { id: 'chart-type', name: 'chart-type' });

                chartTypes.forEach(function (type) {
                    const $option = $('<option>', {
                        value: type,
                        text: type.charAt(0).toUpperCase() + type.slice(1)
                    });
                    $select.append($option);
                });

                const savedType = localStorage.getItem(chartTypeKey);
                if (savedType && chartTypes.includes(savedType)) {
                    $select.val(savedType);
                    $(document).trigger('chartTypeChanged', [savedType]);
                }

                $select.on('change', function (event) {
                    const selectedChartType = event.target.value;
                    localStorage.setItem(chartTypeKey, selectedChartType);
                    $(document).trigger('chartTypeChanged', [selectedChartType]);
                });

                $selectorWrapper.append($label).append($select);
                $chartWrapper.prepend($selectorWrapper);
            }
        });
    };
});