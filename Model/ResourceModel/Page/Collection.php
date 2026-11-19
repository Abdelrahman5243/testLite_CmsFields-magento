<?php
declare(strict_types=1);

namespace Lite\CmsFields\Model\ResourceModel\Page;

use Lite\CmsFields\Model\Page;
use Lite\CmsFields\Model\ResourceModel\Page as PageResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(Page::class, PageResource::class);
    }
}
