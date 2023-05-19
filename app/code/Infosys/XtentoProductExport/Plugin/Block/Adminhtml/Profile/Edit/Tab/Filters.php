<?php
/**
 * @package Infosys/XtentoProductExport
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\XtentoProductExport\Plugin\Block\Adminhtml\Profile\Edit\Tab;

use Magento\Checkout\CustomerData\AbstractItem;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Framework\Registry;

/**
 * Class to add include in feed option
 */
class Filters
{
    private Yesno $yesNo;

    private Registry $registry;

    /**
     * Initialize dependencies
     *
     * @param Yesno $yesNo
     * @param Registry $registry
     */
    public function __construct(
        Yesno $yesNo,
        Registry $registry
    ) {
        $this->yesNo = $yesNo;
        $this->registry = $registry;
    }

    /**
     * Prepare field
     *
     * @param \Magento\User\Block\User\Edit\Tab\Main $subject
     * @param \Closure $proceed
     * @return mixed
     */
    public function aroundGetFormHtml(
        \Xtento\ProductExport\Block\Adminhtml\Profile\Edit\Tab\Filters $subject,
        \Closure $proceed
    ) {
        $form = $subject->getForm();

        if (is_object($form)) {
            $profileData = $this->registry->registry('productexport_profile');
            $include_in_feed = $profileData->getData('export_filter_include_in_feed');
            $image_filter = $profileData->getData('export_filter_product_feed_image');

            $fieldset = $form->getElement('item_filters');
            $field = $fieldset->addField(
                'export_filter_include_in_feed',
                'select',
                [
                    'label' => __('Include in feed'),
                    'name' => 'export_filter_include_in_feed',
                    'values' => $this->yesNo->toOptionArray(),
                    'note' => __('If set to yes, only products which has 
                        include_in_feeds attribute value as yes will be exported.')
                ]
            );

            $imagefield = $fieldset->addField(
                'export_filter_product_feed_image',
                'select',
                [
                    'label' => __('Product Image Filter'),
                    'name' => 'export_filter_product_feed_image',
                    'values' => $this->yesNo->toOptionArray(),
                    'note' => __('If set to yes, only products which has 
                        product image will be exported.')
                ]
            );

            $field->setValue($include_in_feed);
            $imagefield->setValue($image_filter);
            $subject->setForm($form);
        }

        return $proceed();
    }
}
