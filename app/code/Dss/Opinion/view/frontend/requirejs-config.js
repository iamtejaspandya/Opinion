var config = {
    map: {
        '*': {
            'opinionManager': 'Dss_Opinion/js/api/opinion-manager',
            'opinionLabelOnly': 'Dss_Opinion/js/opinion-label',
            'opinionHandler': 'Dss_Opinion/js/opinion',
            'opinionActions': 'Dss_Opinion/js/actions',
            'opinionChartsComponent': 'Dss_Opinion/js/opinion-charts'
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