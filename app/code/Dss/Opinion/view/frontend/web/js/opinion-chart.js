define([
    'jquery',
    'chart-utils'
], function ($, chartUtils) {
    'use strict';

    return function (config) {
        let chart;
        const chartTypeKey = 'dss_opinion_selected_chart_type';
        let currentType = localStorage.getItem(chartTypeKey) || 'pie';

        function initChart() {
            const canvas = $('#opinion-chart');
            if (canvas.length && canvas.is(':visible')) {
                canvas.attr('width', canvas.parent().width());
                chart = chartUtils.renderChart(
                    'opinion-chart',
                    [config.chartData.likes, config.chartData.dislikes],
                    currentType,
                    ['Liked', 'Disliked'],
                    config.chartData.colors
                );
            }
        }

        $(document).ready(function () {
            initChart();

            $(document).on('chartTypeChanged', function (e, type) {
                if (chart) chart.destroy();
                currentType = type;
                localStorage.setItem(chartTypeKey, currentType);
                initChart();
            });
        });
    };
});