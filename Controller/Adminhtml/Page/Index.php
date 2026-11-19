<?php
declare(strict_types=1);

namespace Lite\CmsFields\Controller\Adminhtml\Page;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Lite_CmsFields::page';

    /**
     * Page grid.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->setActiveMenu('Lite_CmsFields::page');
        $page->getConfig()->getTitle()->prepend(__('Lite CMS'));
        return $page;
    }
}
