define([
    'jquery',
    'chart-utils'
], function ($, chartUtils) {
    'use strict';

    return function (config) {
        const chartTypeKey = 'dss_opinion_selected_chart_type';
        let currentType = localStorage.getItem(chartTypeKey) || 'pie';
        const charts = [];

        function initChart(canvasId, data, labels, colors) {
            const canvas = $('#' + canvasId);
            if (canvas.length && canvas.is(':visible')) {
                canvas.attr('width', canvas.parent().width());
                return chartUtils.renderChart(canvasId, data, currentType, labels, colors);
            }

            return null;
        }

        function renderAllCharts() {
            charts.forEach(c => c && c.destroy());
            charts.length = 0;

            (config.charts || []).forEach(chart => {
                const chartInstance = initChart(
                    chart.canvasId,
                    chart.data,
                    chart.labels,
                    chart.colors
                );
                if (chartInstance) {
                    charts.push(chartInstance);
                }
            });
        }

        $(document).ready(function () {
            renderAllCharts();

            $(document).on('chartTypeChanged', function (e, type) {
                currentType = type;
                localStorage.setItem(chartTypeKey, currentType);
                renderAllCharts();
            });
        });
    };
});