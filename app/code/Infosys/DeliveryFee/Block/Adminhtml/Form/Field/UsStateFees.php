<?php
/**
 * @package Infosys/DeliveryFee
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DeliveryFee\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

/**
 * Responsible for preparing to render and save dynamic rows of States and Feed
 */
class UsStateFees extends AbstractFieldArray
{
	public const STATE_CODE = 'state_code';
	public const FEE = 'fee';

	/** @var UsStateFeesColumn */
	private $stateFeeRenderer;

	/**
	 * @inheritDoc
	 */
	protected function _prepareToRender()
	{
		$this->addColumn(
			self::STATE_CODE,
			[
				'label' => __('State'),
				'class' => 'required-entry',
				'renderer' => $this->getStateFeesRenderer()
			]
		);

		$this->addColumn(
			self::FEE,
			[
				'label' => __('Fee'),
				'class' => 'required-entry validate-currency-dollar'
			]
		);

		$this->_addAfter = false;
		$this->_addButtonLabel = __('Add State Fee');
	}

	/**
	 * @inheritDoc
	 *
	 * @param DataObject $row
	 * @throws LocalizedException
	 */
	protected function _prepareArrayRow(DataObject $row)
	{
		$options = [];

		$stateCode = $row->getStateCode();
		if ($stateCode !== null) {
			$options['option_' . $this->getStateFeesRenderer()->calcOptionHash($stateCode)] = 'selected="selected"';
		}

		$row->setData('option_extra_attrs', $options);
	}

	/**
	 * Return (or create and return) a StateFeesColumn as needed
	 *
	 * @return UsStateFeesColumn
	 * @throws LocalizedException
	 */
	private function getStateFeesRenderer() : UsStateFeesColumn
	{
		if (!$this->stateFeeRenderer) {
			$this->stateFeeRenderer = $this->getLayout()->createBlock(
				UsStateFeesColumn::class,
				'',
				['data' => ['is_render_to_js_template' => true]]
			);
		}

		return $this->stateFeeRenderer;
	}
}
