<?php

/**
 * @package     Infosys_XtentoPdfCustomizer
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare (strict_types = 1);

namespace Infosys\XtentoPdfCustomizer\Model\Config;

/**
 * Configuration class
 *
 * Infosys\XtentoPdfCustomizer\Model\Config
 */
class Configuration
{
    /**
     * Get Formatted Telephone Number function
     *
     * @param string $phoneNumber
     * @return string
     */
    public function getFormattedTelephoneNumber($phoneNumber)
    {
        if (isset($phoneNumber)
            && !preg_match("/^\(\d{3}\)\s\d{3}-\d{4}$/", $phoneNumber)) {
            $stringClear = str_replace(' ', '-', $phoneNumber);
            $filterTelephone = preg_replace('/[^A-Za-z0-9 ]/ ', '', $stringClear);

            $formattedTelephoneNumber = "("
            . substr($filterTelephone, -10, -7)
            . ")"
            . " "
            . substr($filterTelephone, -7, -4)
            . "-" . substr($filterTelephone, -4);
        } else {
            $formattedTelephoneNumber = $phoneNumber;
        }

        return $formattedTelephoneNumber;
    }
}
