<?php
declare(strict_types=1);

namespace Lite\CmsFields\Controller\Adminhtml\Page;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;

class Upload extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Lite_CmsFields::page_save';

    /**
     * @param Context $context
     * @param ImageUploader $imageUploader
     */
    public function __construct(
        Context $context,
        private readonly ImageUploader $imageUploader
    ) {
        parent::__construct($context);
    }

    /**
     * Upload an image to the temporary directory.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $saved = $this->imageUploader->saveFileToTmpDir('image');
            $result = ['file' => ltrim((string)$saved['file'], '/'), 'url' => $saved['url']];
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage()];
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
