define([
    'Chart'
], function (Chart) {
    'use strict';

    return {
        renderChart(chartId, chartData, chartType) {
            const ctx = document.getElementById(chartId).getContext('2d');
            return new Chart(ctx, {
                type: chartType,
                data: {
                    labels: ['Liked', 'Disliked'],
                    datasets: [{
                        data: chartData,
                        backgroundColor: ['#4caf50', '#f44336'],
                    }]
                },
                options: {
                    responsive: true
                }
            });
        }
    };
});