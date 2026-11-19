<?php
declare(strict_types=1);

namespace Lite\CmsFields\Controller\Adminhtml\Page;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Lite_CmsFields::page';

    /**
     * New/edit page form.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->setActiveMenu('Lite_CmsFields::page');
        $page->getConfig()->getTitle()->prepend(
            $this->getRequest()->getParam('id') ? __('Edit Lite CMS Page') : __('New Lite CMS Page')
        );
        return $page;
    }
}
