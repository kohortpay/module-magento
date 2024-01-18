define(
    [
        'Magento_Checkout/js/view/payment/default'
    ],
    function (Component) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Kohortpay_Payment/payment/kohortpay'
            },
            getDescription: function () {
                return 'Pay with Kohortpay';
            },
        });
    }
);