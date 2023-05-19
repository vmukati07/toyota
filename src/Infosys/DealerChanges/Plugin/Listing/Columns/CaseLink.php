<?php

/**
 * @package     Infosys/DealerChanges
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\DealerChanges\Plugin\Listing\Columns;

use Signifyd\Connect\Model\CasedataFactory;
use Signifyd\Connect\Model\ResourceModel\Casedata as CasedataResourceModel;
use Infosys\DealerChanges\Helper\Data;

/**
 * Class CaseLink show case link on orger grid
 */
class CaseLink
{

    /**
     * @var CasedataFactory
     */
    protected CasedataFactory $casedataFactory;

    /**
     * @var CasedataResourceModel
     */
    protected CasedataResourceModel $casedataResourceModel;

    /**
     * @var Data
     */
    protected Data $_helper;

    /**
     * CaseLink constructor.
     * @param CasedataFactory $casedataFactory
     * @param CasedataResourceModel $casedataResourceModel
     * @param Data $_helper
     */
    public function __construct(
        CasedataFactory $casedataFactory,
        CasedataResourceModel $casedataResourceModel,
        Data $_helper
    ) {
        $this->casedataResourceModel = $casedataResourceModel;
        $this->casedataFactory = $casedataFactory;
        $this->_helper = $_helper;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function afterPrepareDataSource(
        \Signifyd\Connect\Ui\Component\Listing\Columns\CaseLink $subject,
        array $dataSource
    ) {
        if (isset($dataSource['data']['items'])) {
            $name = $subject->getData('name');
            $isAllowed = $this->_helper->checkSignifydPermission();
            foreach ($dataSource['data']['items'] as &$item) {
                /** @var \Signifyd\Connect\Model\Casedata $case */
                $case = $this->casedataFactory->create();
                $this->casedataResourceModel->load($case, $item['entity_id'], 'order_id');
                if ($name == 'signifyd_guarantee') {
                    $item[$name] = $subject->getNameSignifydGuarantee($case, $name);
                }
                if ($isAllowed == true && empty($case->getCode()) === false) {
                    $url = "https://www.signifyd.com/cases/" . $case->getCode();
                    $item[$name] = "<a href=\"$url\" target=\"_blank\">$item[$name]</a>";
                }
            }
        }
        return $dataSource;
    }
}
