<?php
namespace Jarvis\CustomOptionImage\Controller\Adminhtml\Upload;

use Magento\Backend\App\Action;
use Jarvis\CustomOptionImage\Model\ImageUploader;
use Magento\Framework\Controller\ResultFactory;

class Image extends Action
{
    /**
     * @var \Jarvis\CustomOptionImage\Model\ImageUploader
     */
    protected $imageUploader;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Jarvis\CustomOptionImage\Model\ImageUploader $imageUploader
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        ImageUploader $imageUploader
    ) {
        parent::__construct($context);
        $this->imageUploader = $imageUploader;
    }

    /**
     * Isallowed Products
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Catalog::products');
    }

    /**
     * Execute Function
     */
    public function execute()
    {
        $uploader = $this->imageUploader; // your custom uploader
        $imageId = 'custom_optimage'; // same as fileInputName

        $file = $this->getRequest()->getFiles($imageId);
        try {
            $result = $uploader->saveFileToTmpDir($imageId);
            $result['tmp_name'] = $result['name'];
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
