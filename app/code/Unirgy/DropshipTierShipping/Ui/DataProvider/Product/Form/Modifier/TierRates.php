<?php

namespace Unirgy\DropshipTierShipping\Ui\DataProvider\Product\Form\Modifier {

    use Magento\Ui\Component\Container;
    use Magento\Ui\Component\Form\Element\DataType\Number;
    use Magento\Ui\Component\Form\Element\DataType\Price;
    use Magento\Ui\Component\Form\Element\DataType\Text;
    use Magento\Ui\Component\Form\Element\Input;
    use Magento\Ui\Component\Form\Element\Select;
    use Magento\Ui\Component\Form\Element\MultiSelect;
    use Magento\Ui\Component\Form\Field;
    use Unirgy\DropshipTierShipping\Model\ResourceModel\DeliveryType\Collection as DeliveryTypeCollection;
    use Magento\Customer\Api\Data\GroupInterface;
    use Magento\Customer\Api\GroupManagementInterface;
    use Magento\Customer\Api\GroupRepositoryInterface;
    use Magento\Framework\Api\SearchCriteriaBuilder;
    use Magento\Framework\Module\Manager as ModuleManager;

    $hlp = \Magento\Framework\App\ObjectManager::getInstance()->get('\Unirgy\Dropship\Helper\Data');
    if (!$hlp->compareMageVer('2.1')) {
        class TierRates
        {
        }
    } else {

        class TierRates extends \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier
        {
            protected $locator;

            protected $arrayManager;

            protected $meta = [];

            protected $dtCollection;

            protected $moduleManager;

            protected $groupManagement;

            protected $searchCriteriaBuilder;

            protected $groupRepository;

            protected $_tsHlp;
            protected $_hlp;
            protected $_scSrc;

            public function __construct(
                \Magento\Catalog\Model\Locator\LocatorInterface $locator,
                \Magento\Framework\Stdlib\ArrayManager $arrayManager,
                DeliveryTypeCollection $deliveryTypeCollection,
                GroupRepositoryInterface $groupRepository,
                GroupManagementInterface $groupManagement,
                SearchCriteriaBuilder $searchCriteriaBuilder,
                ModuleManager $moduleManager,
                \Unirgy\Dropship\Helper\Data $udropshipHelper,
                \Unirgy\DropshipTierShipping\Helper\Data $tiershipHelper,
                \Unirgy\DropshipShippingClass\Model\Source $shipclassSource
            )
            {
                $this->locator = $locator;
                $this->arrayManager = $arrayManager;
                $this->dtCollection = $deliveryTypeCollection;
                $this->groupRepository = $groupRepository;
                $this->groupManagement = $groupManagement;
                $this->searchCriteriaBuilder = $searchCriteriaBuilder;
                $this->moduleManager = $moduleManager;
                $this->_tsHlp = $tiershipHelper;
                $this->_hlp = $udropshipHelper;
                $this->_scSrc = $shipclassSource;
            }

            public function modifyData(array $data)
            {
                return $data;
            }

            public function modifyMeta(array $meta)
            {
                $this->meta = $meta;

                $this->customizeTierRates();

                return $this->meta;
            }

            protected function customizeTierRates()
            {
                $tierRatesPath = $this->arrayManager->findPath(
                    'udtiership_rates',
                    $this->meta,
                    null,
                    'children'
                );

                if ($tierRatesPath) {
                    $this->meta = $this->arrayManager->merge(
                        $tierRatesPath,
                        $this->meta,
                        $this->getTierRatesStructure($tierRatesPath)
                    );
                }

                return $this;
            }

            protected function getTierRatesStructure($tierRatesPath)
            {
                return [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => 'dynamicRows',
                                'label' => __('Tier Shipping Rates'),
                                'renderDefaultRecord' => false,
                                'recordTemplate' => 'record',
                                'dataScope' => '',
                                'dndConfig' => [
                                    'enabled' => false,
                                ],
                                'disabled' => false,
                                'sortOrder' =>
                                    $this->arrayManager->get($tierRatesPath . '/arguments/data/config/sortOrder', $this->meta),
                            ],
                        ],
                    ],
                    'children' => [
                        'record' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'componentType' => Container::NAME,
                                        'isTemplate' => true,
                                        'is_collection' => true,
                                        'component' => 'Magento_Ui/js/dynamic-rows/record',
                                        'dataScope' => '',
                                    ],
                                ],
                            ],
                            'children' => [
                                'delivery_type_id' => [
                                    'arguments' => [
                                        'data' => [
                                            'config' => [
                                                'dataType' => Select::NAME,
                                                'formElement' => Select::NAME,
                                                'componentType' => Field::NAME,
                                                'dataScope' => 'delivery_type_id',
                                                'label' => __('Delivery Type'),
                                                'options' => $this->getDeliveryTypes(),
                                            ],
                                        ],
                                    ],
                                ],
                                /*
                                'container_customer' => [
                                    'arguments' => [
                                        'data' => [
                                            'config' => [
                                                'formElement' => Fieldset::NAME,
                                                'componentType' => Fieldset::NAME,
                                                'label' => __(''),
                                                'dataScope' => '',
                                            ],
                                        ],
                                    ],
                                    'children' => [
                                        'customer_shipclass_id' => [
                                            'arguments' => [
                                                'data' => [
                                                    'config' => [
                                                        'formElement' => MultiSelect::NAME,
                                                        'componentType' => Field::NAME,
                                                        'dataType' => MultiSelect::NAME,
                                                        'dataScope' => 'customer_shipclass_id',
                                                        'label' => __('Customer Shipclass'),
                                                        'options' => $this->getCustomerShipclasses(),
                                                    ],
                                                ],
                                            ],
                                        ],
                                        'customer_group_id' => [
                                            'arguments' => [
                                                'data' => [
                                                    'config' => [
                                                        'formElement' => MultiSelect::NAME,
                                                        'componentType' => Field::NAME,
                                                        'dataType' => MultiSelect::NAME,
                                                        'dataScope' => 'customer_group_id',
                                                        'label' => __('Customer Group'),
                                                        'options' => $this->getCustomerGroups(),
                                                        'visible' => $this->isUseCustomerGroup()
                                                    ],
                                                ],
                                            ],
                                        ]
                                        ]
                                ],
                                */
                                'customer_shipclass_id' => [
                                    'arguments' => [
                                        'data' => [
                                            'config' => [
                                                'formElement' => MultiSelect::NAME,
                                                'componentType' => Field::NAME,
                                                'dataType' => MultiSelect::NAME,
                                                'dataScope' => 'customer_shipclass_id',
                                                'label' => __('Customer Shipclass'),
                                                'options' => $this->getCustomerShipclasses(),
                                            ],
                                        ],
                                    ],
                                ],
                                'customer_group_id' => [
                                    'arguments' => [
                                        'data' => [
                                            'config' => [
                                                'formElement' => MultiSelect::NAME,
                                                'componentType' => Field::NAME,
                                                'dataType' => MultiSelect::NAME,
                                                'dataScope' => 'customer_group_id',
                                                'label' => __('Customer Group'),
                                                'options' => $this->getCustomerGroups(),
                                                'visible' => $this->isUseCustomerGroup()
                                            ],
                                        ],
                                    ],
                                ],
                                'cost' => [
                                    'arguments' => [
                                        'data' => [
                                            'config' => [
                                                'componentType' => Field::NAME,
                                                'formElement' => Input::NAME,
                                                'dataType' => Price::NAME,
                                                'label' => __('Cost for the first item'),
                                                'enableLabel' => true,
                                                'dataScope' => 'cost',
                                                'addbefore' => $this->locator->getStore()
                                                    ->getBaseCurrency()
                                                    ->getCurrencySymbol(),
                                            ],
                                        ],
                                    ],
                                ],
                                'additional' => [
                                    'arguments' => [
                                        'data' => [
                                            'config' => [
                                                'componentType' => Field::NAME,
                                                'formElement' => Input::NAME,
                                                'dataType' => Price::NAME,
                                                'label' => __('Additional item cost'),
                                                'enableLabel' => true,
                                                'dataScope' => 'additional',
                                                'visible' => $this->isShowAdditionalColumn(),
                                                'addbefore' => $this->locator->getStore()
                                                    ->getBaseCurrency()
                                                    ->getCurrencySymbol(),
                                            ],
                                        ],
                                    ],
                                ],
                                'handling' => [
                                    'arguments' => [
                                        'data' => [
                                            'config' => [
                                                'componentType' => Field::NAME,
                                                'formElement' => Input::NAME,
                                                'dataType' => Price::NAME,
                                                'label' => __('Tier handling fee'),
                                                'enableLabel' => true,
                                                'dataScope' => 'handling',
                                                'visible' => $this->isShowHandlingColumn(),
                                                'addbefore' => $this->locator->getStore()
                                                    ->getBaseCurrency()
                                                    ->getCurrencySymbol(),
                                            ],
                                        ],
                                    ],
                                ],
                                'sort_order' => [
                                    'arguments' => [
                                        'data' => [
                                            'config' => [
                                                'formElement' => Input::NAME,
                                                'componentType' => Field::NAME,
                                                'dataType' => Number::NAME,
                                                'label' => __('Sort Order'),
                                                'dataScope' => 'sort_order',
                                            ],
                                        ],
                                    ],
                                ],
                                'actionDelete' => [
                                    'arguments' => [
                                        'data' => [
                                            'config' => [
                                                'componentType' => 'actionDelete',
                                                'dataType' => Text::NAME,
                                                'label' => '',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];
            }

            protected function getDeliveryTypes()
            {
                return $this->dtCollection->toOptionArray();
            }

            protected function getCustomerGroups()
            {
                if (!$this->moduleManager->isEnabled('Magento_Customer')) {
                    return [];
                }
                $customerGroups = [
                    [
                        'label' => __('ALL GROUPS'),
                        'value' => GroupInterface::CUST_GROUP_ALL,
                    ]
                ];

                /** @var GroupInterface[] $groups */
                $groups = $this->groupRepository->getList($this->searchCriteriaBuilder->create());
                foreach ($groups->getItems() as $group) {
                    $customerGroups[] = [
                        'label' => $group->getCode(),
                        'value' => $group->getId(),
                    ];
                }

                return $customerGroups;
            }

            protected function isUseCustomerGroup()
            {
                return $this->_hlp->getScopeFlag('carriers/udtiership/use_customer_group');
            }

            public function isShowAdditionalColumn()
            {
                return $this->_tsHlp->isUseAdditional($this->getCalculationMethod()) && $this->_tsHlp->isV2Rates() && !$this->_tsHlp->isV2SimpleConditionalRates();
            }

            public function isShowHandlingColumn()
            {
                return $this->_tsHlp->isUseHandling($this->getHandlingApply()) && $this->_tsHlp->isV2Rates() && !$this->_tsHlp->isV2SimpleRates() && !$this->_tsHlp->isV2SimpleConditionalRates();
            }

            public function getHandlingApply()
            {
                return $this->_hlp->getScopeConfig('carriers/udtiership/handling_apply_method');
            }

            public function getCalculationMethod()
            {
                return $this->_hlp->getScopeConfig('carriers/udtiership/calculation_method');
            }

            public function getCustomerShipclasses()
            {
                $options = $this->_scSrc->setPath('customer_ship_class')->toOptionArray();
                array_unshift($options, ['value' => '*', 'label' => __('*** All ***')]);
                foreach ($options as &$opt) {
                    $opt['value'] = (string)$opt['value'];
                }
                unset($opt);
                return $options;
            }
        }
    }
}