<?php

/**
 * @package     Infosys/DealerChanges
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DealerChanges\Plugin\Backend\Block\Dashboard;

use Infosys\DealerChanges\Model\DashboardLinks\Permissions;
use Magento\Backend\Block\Dashboard\Grids as CoreGrids;

/**
 * Class to customize the dashboard grids
 */
class Grids extends CoreGrids
{
    /**
     * @var Permissions
     */
    protected Permissions $permissions;

    /**
     * Construct function
     *
     * @param Permissions $permissions
     */
    public function __construct(
        Permissions $permissions
    ) {
        $this->permissions = $permissions;
    }

    /**
     * BeforeToHtml function to customize the dashboard grid
     *
     * @param CoreGrids $subject
     * @return void
     */
    public function beforeToHtml(
        CoreGrids $subject
    ) {
        if ($this->permissions->checkYotpoReviewsPermission() == true) {
            $subject->addTab(
                'yotpo_reviews',
                [
                    'label' => __('Yotpo Reviews'),
                    'url' => $subject->getUrl('yotpo_yotpo/*/YotpoReviews', ['_current' => true]),
                    'class' => 'ajax'
                ]
            );
        }
    }
}
