/**
 * @package Infosys/RollingSitemap
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright � 2022. All Rights Reserved.
 */
define(
    [
        'jquery'
    ],

    function ($) {

        'use strict';

        return function (target) {
            $.validator.addMethod(
                'validate-cron-expr',

                function (value) {
                    let cronregex = new RegExp(/^(\*|([0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])|\*\/([0-9]|1[0-9]|2[0-9]|3[0-9]|4[0-9]|5[0-9])) (\*|([0-9]|1[0-9]|2[0-3])|\*\/([0-9]|1[0-9]|2[0-3])) (\*|([1-9]|1[0-9]|2[0-9]|3[0-1])|\*\/([1-9]|1[0-9]|2[0-9]|3[0-1])) (\*|([1-9]|1[0-2])|\*\/([1-9]|1[0-2])) (\*|([0-6])|\*\/([0-6]))$/);

                    return cronregex.test(value);
                },

                $.mage.__('Please enter a valid cron expression')
            );

            return target;
        };
    }
);