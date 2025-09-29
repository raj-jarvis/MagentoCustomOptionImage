<?php
namespace Jarvis\CustomOptionImage\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\App\RequestInterface;

class SaveCustomOptionImage implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        Filesystem $filesystem,
        UploaderFactory $uploaderFactory,
        RequestInterface $request
    ) {
        $this->filesystem = $filesystem;
        $this->uploaderFactory = $uploaderFactory;
        $this->request = $request;
    }

    /**
     * Execute Function
     *
     * @param object $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quoteItem = $observer->getEvent()->getQuoteItem();
        $product = $observer->getEvent()->getProduct();
        $files = $this->request->getFiles();

        if (!isset($files['options']) || !isset($files['options']['name'])) {
            return;
        }

        $mediaDir = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath('custom_option/');

        foreach ($files['options']['name'] as $optionId => $file) {
            if (!isset($file['file']) || $files['options']['error'][$optionId]['file'] !== UPLOAD_ERR_OK) {
                continue;
            }

            try {
                $uploader = $this->uploaderFactory->create(['fileId' => "options[{$optionId}][file]"]);
                $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png']);
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(true);
                $result = $uploader->save($mediaDir);

                if ($result['file']) {
                    $quoteItem->addOption([
                        'product_id' => $product->getId(),
                        'code' => 'option_' . $optionId,
                        'value' => $result['file']
                    ]);

                    // Save to custom table
                    $connection = $product->getResource()->getConnection();
                    $table = $connection->getTableName('br_customimageoption_image');
                    $connection->insert($table, [
                        'option_id' => $optionId,
                        'image_path' => $result['file']
                    ]);
                }
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('File upload failed: %1', $e->getMessage())
                );
            }
        }
    }
}
