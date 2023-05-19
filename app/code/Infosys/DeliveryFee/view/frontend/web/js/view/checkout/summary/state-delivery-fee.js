/**
 * @package     Infosys/DeliveryFee
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2022. All Rights Reserved.
 */
define(
    [
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/totals'
    ],

    function (
        Component,
        quote,
        priceUtils,
        totals
    ) {
        "use strict";

        return Component.extend({
            defaults: {
                isFullTaxSummaryDisplayed: window.checkoutConfig.isFullTaxSummaryDisplayed || false,
                template: 'Infosys_DeliveryFee/checkout/summary/state-delivery-fee'
            },

            totals: quote.getTotals(),
            isTaxDisplayedInGrandTotal: window.checkoutConfig.includeTaxInGrandTotal || false,

            /**
             * Return true if we are in fullMode and the total includes a state delivery fee
             */
            isDisplayed: function() {
                return this.isFullMode() && this.totalIncludesStateDeliveryFee();
            },

            /**
             * Return true if totals isn't null, and totals contains a state_delivery_fee segment
             */
            totalIncludesStateDeliveryFee: function() {
                return this.totals() &&
                    totals.getSegment('state_delivery_fee') &&
                    totals.getSegment('state_delivery_fee').value > 0;
            },

            /**
             * Return the value of the state_delivery_fee total
             */
            getValue: function() {
                let price = 0;

                if (this.totalIncludesStateDeliveryFee()) {
                    price = totals.getSegment('state_delivery_fee').value;
                }

                return this.getFormattedPrice(price);
            },

            /**
             * Return the value of the base_state_delivery_fee total
             */
            getBaseValue: function() {
                let price = 0;

                if (this.totalIncludesStateDeliveryFee()) {
                    price = this.totals().base_state_delivery_fee;
                }

                return priceUtils.formatPrice(price, quote.getBasePriceFormat());
            }
        });
    }
);
