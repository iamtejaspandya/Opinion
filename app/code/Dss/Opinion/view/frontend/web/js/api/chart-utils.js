define([
    'Chart'
], function (Chart) {
    'use strict';

    return {
        /**
         * Renders a Chart.js chart.
         *
         * @param {string} chartId - The ID of the canvas element.
         * @param {Array} chartData - Array of values for the dataset.
         * @param {string} chartType - Type of chart (e.g., pie, doughnut, polarArea).
         * @param {Array} labels - Labels for the dataset.
         * @param {Array} backgroundColor - Colors for each section.
         * @returns {Chart} - Chart.js instance.
         */
        renderChart(chartId, chartData, chartType, labels = [], backgroundColor = []) {
            const ctx = document.getElementById(chartId).getContext('2d');
            return new Chart(ctx, {
                type: chartType,
                data: {
                    labels: labels,
                    datasets: [{
                        data: chartData,
                        backgroundColor: backgroundColor
                    }]
                },
                options: {
                    responsive: true
                }
            });
        }
    };
});