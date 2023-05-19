<?php
/**
 * @package     Infosys/VehicleSearch
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\VehicleSearch\Plugin\Model\Adapter\Index;

use Magento\Elasticsearch\Model\Adapter\Index\Builder as BaseBuilder;

class Builder
{
    /**
     * Adding new setting to the builder for normalizer
     *
     * @param BaseBuilder $subject
     * @param array $settings
     * @return array
     */
    public function afterBuild(BaseBuilder $subject, $settings)
    {
        $settings['analysis']['normalizer']['vehice_normalizer'] = [
            "type" => "custom",
            "char_filter" => [],
            "filter" =>  ["lowercase","uppercase"]
        ];
        return $settings;
    }
}