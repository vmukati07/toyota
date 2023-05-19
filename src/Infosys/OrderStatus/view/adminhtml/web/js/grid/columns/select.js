define([
    'underscore',
    'Magento_Ui/js/grid/columns/select'
], function(_, Column) {
    'use strict';
    return Column.extend({
        defaults: {
            bodyTmpl: 'Infosys_OrderStatus/ui/grid/cells/status'
        },
        getOrderStatusColor: function(row) {
            if (row.status == 'pending') {
                return 'order-pending';
            } else if (row.status == 'processing') {
                return 'order-processing';
            } else if (row.status == 'complete') {
                return 'order-complete';
            } else if (row.status == 'closed') {
                return 'order-closed';
            } else if (row.status == 'canceled') {
                return 'order-canceled';
            } else if (row.status == 'holded') {
                return 'order-holded';
            }
            return '#303030';
        }
    });
});