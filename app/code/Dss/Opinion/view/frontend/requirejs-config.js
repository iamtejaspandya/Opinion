var config = {
    map: {
        '*': {
            'opinionHandler': 'Dss_Opinion/js/opinion',
            'opinionStatus': 'Dss_Opinion/js/api/status-opinion',
            'opinionSave': 'Dss_Opinion/js/api/save-opinion',
            'productOpinionLabel': 'Dss_Opinion/js/api/product-opinion-label',
            'opinionChartComponent': 'Dss_Opinion/js/opinion-chart',
            'currentOpinionChartComponent': 'Dss_Opinion/js/current-opinion-chart',
            'opinionActions': 'Dss_Opinion/js/actions'
        }
    },

    paths: {
        'Chart': 'Dss_Opinion/js/api/chart.min',
        'chart-utils': 'Dss_Opinion/js/api/chart-utils'
    },

    shim: {
        'Chart': {
            exports: 'Chart'
        }
    }
};