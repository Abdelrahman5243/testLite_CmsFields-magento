<?php
declare(strict_types=1);

namespace Lite\CmsFields\Controller\Adminhtml\Page;

use Lite\CmsFields\Model\PageFactory;
use Lite\CmsFields\Model\ResourceModel\Page as PageResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;

class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Lite_CmsFields::page_delete';

    /**
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param PageResource $resource
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory,
        private readonly PageResource $resource
    ) {
        parent::__construct($context);
    }

    /**
     * Delete a page.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $page = $this->pageFactory->create();
        try {
            $this->resource->load($page, (int)$this->getRequest()->getParam('id'));
            if ($page->getId()) {
                $this->resource->delete($page);
                $this->messageManager->addSuccessMessage(__('The page has been deleted.'));
            }
        } catch (\Throwable $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while deleting the page.'));
        }
        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
