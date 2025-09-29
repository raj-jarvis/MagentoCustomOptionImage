<?php
// phpcs:ignoreFile

namespace Jarvis\CustomOptionImage\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\Option;
use Jarvis\CustomOptionImage\Model\ImageUploader;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;

class ProductOptionSave
{
    /**
     * @var \Jarvis\CustomOptionImage\Model\ImageUploader
     */
    protected $imageUploader;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param ImageUploader $imageUploader
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     */
    public function __construct(
        ImageUploader $imageUploader,
        RequestInterface $request,
        LoggerInterface $logger
    ) {
        $this->imageUploader = $imageUploader;
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * Plugin to save custom option image during product option save
     *
     * @param object $subject
     * @param array $result
     */
    public function afterSave(Option $subject, $result)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom_option_save.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        // Get product options from request
        $productParams = $this->request->getParam('product', []);
        $options = $productParams['options'] ?? [];
        $imageName = "";
        $logger->info("mmm111");

        foreach ($options as $option) {
            $logger->info("mmm2222");
            if (isset($option['values']) && is_array($option['values'])) {
                $logger->info("mmm333");
                foreach ($option['values'] as $value) {
                    $logger->info("mmm4444");
                    if (isset($value['option_type_id']) && isset($value['image'])) {
                        $logger->info("mmm5555");
                        $optionTypeId = $value['option_type_id'];
                        $imageData = $value['image'];

                        // If image is provided, move it from tmp to final directory
                        if (isset($imageData[0]['file'])) {
                            $logger->info("mmm6666");
                            try {
                                $logger->info("mmm777");
                                $logger->info("mmm7000".$imageData[0]['file']);
                                // Move file from tmp to final directory
                                $imageName = $this->imageUploader->moveFileFromTmp($imageData[0]['file']);
                                $logger->info("mmmm888");
                                //$logger->info("Moved image for option_type_id : ".$optionTypeId);

                                // Update catalog_product_option_type_value with image name
                                $connection = $subject->getConnection();
                                $connection->update(
                                    $subject->getTable('catalog_product_option_type_value'),
                                    ['custom_optimage' => $imageName],
                                    ['option_type_id = ?' => $optionTypeId]
                                );
                                
                            } catch (\Exception $e) {
                                $logger->info("mmmkmkm");
                                $logger->info($e->getMessage());
                            }
                        }
                    } else {
                        if (isset($value['option_type_id'])) {
                            $optionTypeId = $value['option_type_id'];
                            try {
                                $logger->info("mmm999777777777777");
                                $connection = $subject->getConnection();
                                $connection->update(
                                    $subject->getTable('catalog_product_option_type_value'),
                                    ['custom_optimage' => ''],
                                    ['option_type_id = ?' => $optionTypeId]
                                );
                                
                            } catch (\Exception $e) {
                                $logger->info("ghhhhhhhhh");
                                $logger->info($e->getMessage());
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }
}
