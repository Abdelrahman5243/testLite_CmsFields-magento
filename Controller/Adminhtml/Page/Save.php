<?php
declare(strict_types=1);

namespace Lite\CmsFields\Controller\Adminhtml\Page;

use Lite\CmsFields\Model\PageFactory;
use Lite\CmsFields\Model\ResourceModel\Page as PageResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Lite_CmsFields::page_save';

    /**
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param PageResource $resource
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory,
        private readonly PageResource $resource,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
    }

    /**
     * Save a page and return to its edit form.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        $data = (array)$this->getRequest()->getPostValue();
        $id = (int)($data['entity_id'] ?? 0);
        $page = $this->pageFactory->create();

        try {
            if ($id) {
                $this->resource->load($page, $id);
                if (!$page->getId()) {
                    throw new LocalizedException(__('This page no longer exists.'));
                }
            }
            $page->addData([
                'identifier' => $data['identifier'] ?? '',
                'store_id' => (int)($data['store_id'] ?? 0),
                'is_active' => (int)($data['is_active'] ?? 0),
                'content' => $data['content'] ?? '',
            ]);
            $this->resource->save($page);
            $this->messageManager->addSuccessMessage(__('The page has been saved.'));
            return $redirect->setPath('*/*/edit', ['id' => $page->getId()]);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Throwable $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the page.'));
        }

        $this->dataPersistor->set('lite_cms_page', $data);

        return $id ? $redirect->setPath('*/*/edit', ['id' => $id]) : $redirect->setPath('*/*/edit');
    }
}
