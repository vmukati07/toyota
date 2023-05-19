<?php
/**
 * @package Infosys/DeliveryFee
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DeliveryFee\Block\Adminhtml\Form\Field;

use Infosys\DeliveryFee\Model\Config\Region\UsStateInformationProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

/**
 * Responsible for providing an HTML select element populated with US states as options
 */
class UsStateFeesColumn extends Select
{
	/** @var UsStateInformationProvider */
	private UsStateInformationProvider $usStateInformationProvider;

	/**
	 * @param UsStateInformationProvider $usStateInformationProvider
	 * @param Context $context
	 * @param array $data
	 */
	public function __construct(
		UsStateInformationProvider $usStateInformationProvider,
		Context $context,
		array $data = []
	) {
		$this->usStateInformationProvider = $usStateInformationProvider;

		parent::__construct($context, $data);
	}

	/**
	 * Set the HTML element's name
	 *
	 * @param $name
	 * @return UsStateFeesColumn
	 */
	public function setInputName($name) : UsStateFeesColumn
	{
		return $this->setName($name);
	}

	/**
	 * Set the HTML element's id
	 *
	 * @param $id
	 * @return UsStateFeesColumn
	 */
	public function setInputId($id) : UsStateFeesColumn
	{
		return $this->setId($id);
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 * @throws NoSuchEntityException
	 */
	public function _toHtml() : string
	{
		if (!$this->getOptions()) {
			$this->setOptions($this->usStateInformationProvider->toOptionArray());
		}

		return parent::_toHtml();
	}
}
