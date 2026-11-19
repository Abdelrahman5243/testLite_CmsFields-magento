<?php
declare(strict_types=1);

namespace Lite\CmsFields\Model\Page;

use Lite\CmsFields\Model\ResourceModel\Page\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var array|null
     */
    private $loadedData;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param StoreManagerInterface $storeManager
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    /**
     * Form data keyed by entity ID; data from a failed save takes precedence so edits are not lost.
     *
     * @return array
     */
    public function getData(): array
    {
        if ($this->loadedData === null) {
            $this->loadedData = [];
            foreach ($this->collection->getItems() as $page) {
                $this->loadedData[$page->getId()] = $page->getData();
            }
            $persisted = $this->dataPersistor->get('lite_cms_page');
            if (!empty($persisted)) {
                $this->loadedData[$persisted['entity_id'] ?? ''] = $persisted;
                $this->dataPersistor->clear('lite_cms_page');
            }
        }
        return $this->loadedData;
    }

    /**
     * Give the builder the media base URL for image previews.
     *
     * @return array
     */
    public function getMeta(): array
    {
        $meta = parent::getMeta();
        $meta['general']['children']['content']['arguments']['data']['config']['mediaUrl'] =
            $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        return $meta;
    }
}
