<?php
/**
 * @package Infosys/XtentoOrderExport
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\XtentoOrderExport\Plugin\Block\Adminhtml\Destination\Edit\Tab;

use Magento\Config\Model\Config\Source\Yesno;
use Magento\Framework\Registry;

/**
 * Class to add custom fields in destination form
 */
class Configuration
{
    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    private Yesno $yesNo;

    /**
     * @var \Magento\Framework\Registry
     */
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
     * Prepare fields
     *
     * @param \Xtento\OrderExport\Block\Adminhtml\Destination\Edit\Tab\Configuration $subject
     * @param \Closure $proceed
     * @return mixed
     */
    public function aroundGetFormHtml(
        \Xtento\OrderExport\Block\Adminhtml\Destination\Edit\Tab\Configuration $subject,
        \Closure $proceed
    ) {
        $form = $subject->getForm();

        if (is_object($form)) {
            $model = $this->registry->registry('orderexport_destination');
            $baseFieldset = $form->getElement('base_fieldset');
            $yesnoSource = $this->yesNo->toOptionArray();

            $main = $baseFieldset->addField(
                'encrypt_file',
                'select',
                [
                    'name' => 'encrypt_file',
                    'label' => __('Encrypt File'),
                    'title' => __('Encrypt File'),
                    'note' => __('To check the file to be encrypted or not'),
                    'values' => $yesnoSource,
                ]
            );

            // depended fields to add encryption
            $enc_protocol = $baseFieldset->addField(
                'encryption_protocol',
                'select',
                [
                    'name' => 'encryption_protocol',
                    'label' => __('Encryption Protocol'),
                    'id' => 'encryption_protocol',
                    'title' => __('Encryption Protocol'),
                    'class' => 'input-select',
                    'values' =>[ ["value" => 'pgp_encryption', "label" => __("PGP encryption")]]
                 ]
            );
            $public_key = $baseFieldset->addField(
                'enc_public_key',
                'textarea',
                [
                    'name' => 'enc_public_key',
                    'label' => __('Public Key'),
                    'id' => 'enc_public_key',
                    'title' => __('Public Key')
                ]
            );
            $subject->setChild(
                'form_after',
                $subject->getLayout()->createBlock(\Magento\Backend\Block\Widget\Form\Element\Dependence::class)
                    ->addFieldMap($main->getHtmlId(), $main->getName())
                    ->addFieldMap($enc_protocol->getHtmlId(), $enc_protocol->getName())
                    ->addFieldMap($public_key->getHtmlId(), $public_key->getName())
                    ->addFieldDependence($enc_protocol->getName(), $main->getName(), 1)
                    ->addFieldDependence($public_key->getName(), $main->getName(), 1)
            );
            $form->setValues($model->getData());
            $subject->setForm($form);
        }

        return $proceed();
    }
}
