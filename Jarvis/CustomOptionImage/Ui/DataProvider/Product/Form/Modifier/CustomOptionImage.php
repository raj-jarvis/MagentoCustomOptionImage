<?php

namespace Jarvis\CustomOptionImage\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions as OriginalCustomOptions;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Model\ProductOptions\ConfigInterface;
use Magento\Catalog\Model\Config\Source\Product\Options\Price as ProductOptionsPrice;
use Magento\Framework\UrlInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;

class CustomOptionImage extends OriginalCustomOptions
{
    public const FIELD_IMAGE_UPLOAD_NAME = 'image';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param LocatorInterface $locator
     * @param StoreManagerInterface $storeManager
     * @param ConfigInterface $productOptionsConfig
     * @param ProductOptionsPrice $productOptionsPrice
     * @param UrlInterface $urlBuilder
     * @param ArrayManager $arrayManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        LocatorInterface $locator,
        StoreManagerInterface $storeManager,
        ConfigInterface $productOptionsConfig,
        ProductOptionsPrice $productOptionsPrice,
        UrlInterface $urlBuilder,
        ArrayManager $arrayManager,
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resourceConnection
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->resourceConnection = $resourceConnection;
        parent::__construct(
            $locator,
            $storeManager,
            $productOptionsConfig,
            $productOptionsPrice,
            $urlBuilder,
            $arrayManager
        );
    }

    /**
     * Modifydata
     *
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {

        $product = $this->locator->getProduct();

        $options = $product->getOptions();
        $productId = $product->getId();
        if ($options) {
            foreach ($options as $option) {
                foreach ($option->getValues() as $value) {
                    $imageName = $value->getData('custom_optimage'); // custom column added to option value table
                    if ($imageName) {
                        $value['image'] = [
                            [
                            'name' => $imageName,
                            'url'  => $this->getMediaUrl() . 'custom_option/' . $imageName,
                            ]
                        ];
                    } else {
                        $value['image'] = [];
                    }
                }
            }
        }
        return $data;
    }

    /**
     * ModifyMeta
     *
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $this->meta = $meta;
        $this->createCustomOptionsPanel();
        $this->setColumnOrder('image', 35);
        return $this->meta;
    }

    /**
     * CreateCustomOptionsPanel
     */
    protected function createCustomOptionsPanel()
    {
        $this->meta = array_replace_recursive(
            $this->meta,
            [
                static::GROUP_CUSTOM_OPTIONS_NAME => [
                    'children' => [
                        static::GRID_OPTIONS_NAME => $this->getOptionsGridConfig(10),
                    ],
                ],
            ]
        );

        return $this;
    }

    /**
     * GetOptionsGridConfig
     *
     * @param int $sortOrder
     */
    protected function getOptionsGridConfig($sortOrder)
    {
        return [
            'children' => [
                'record' => [
                    'children' => [
                        static::CONTAINER_OPTION => [
                            'children' => [
                                static::GRID_TYPE_SELECT_NAME => $this->getSelectTypeGridConfig(10),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * GetSelectTypeGridConfig
     *
     * @param int $sortOrder
     */
    protected function getSelectTypeGridConfig($sortOrder)
    {
        $imagesGrid = [
            'children' => [
                'record' => [
                    'children' => [
                        static::FIELD_IMAGE_UPLOAD_NAME => $this->getImgConfig(10),
                    ],
                ],
            ],
        ];

        return $imagesGrid;
    }

    /**
     * GetImgConfig
     *
     * @param int $sortOrder
     */
    private function getImgConfig($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Swatch Image'),
                        'componentType' => 'field',
                        'formElement' => 'fileUploader',
                        'dataScope' => 'image',
                        'dataType' => 'file',
                        'fileInputName' => 'custom_optimage',
                        'sortOrder' => 35,
                        'isMultipleFiles' => false,
                        'elementTmpl' => 'Magento_Ui/js/form/element/file-uploader',
                        'previewTmpl' => 'Magento_Catalog/image-preview',
                        'uploaderConfig' => [
                            'url' => 'br_customimageoption/upload/image'
                        ],
                        'value' => []
                    ],
                ],
            ],
        ];
    }

    /**
     * SetColumnOrder
     *
     * @param string $name
     * @param int $order
     * @return void
     */
    private function setColumnOrder(string $name, int $order)
    {
        $columns = &$this->meta[static::GROUP_CUSTOM_OPTIONS_NAME]['children'][static::GRID_OPTIONS_NAME]
        ['children']['record']['children'][static::CONTAINER_OPTION]['children'][static::GRID_TYPE_SELECT_NAME]
        ['children']['record']['children'];
        $columns[$name]['arguments']['data']['config']['sortOrder'] = $order;
    }

    /**
     * GetMediaUrl
     */
    protected function getMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }
}
