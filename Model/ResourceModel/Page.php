<?php
declare(strict_types=1);

namespace Lite\CmsFields\Model\ResourceModel;

use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class Page extends AbstractDb
{
    /**
     * @param Context $context
     * @param ImageUploader $imageUploader
     * @param string|null $connectionName
     */
    public function __construct(
        Context $context,
        private readonly ImageUploader $imageUploader,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('lite_cms_page', 'entity_id');
    }

    /**
     * @inheritdoc
     */
    protected function _initUniqueFields()
    {
        $this->_uniqueFields = [
            ['field' => ['identifier', 'store_id'], 'title' => __('A page with this identifier for this store view')],
        ];
        return $this;
    }

    /**
     * Row for the given store, falling back to the default (store 0) row.
     *
     * @param string $identifier
     * @param int $storeId
     * @return array
     */
    public function getRowForStore(string $identifier, int $storeId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('identifier = ?', $identifier)
            ->where('store_id IN (?)', [0, $storeId])
            ->order('store_id DESC')
            ->limit(1);

        return $connection->fetchRow($select) ?: [];
    }

    /**
     * Validate identifier and content JSON, and normalize the JSON before saving.
     *
     * @param AbstractModel $object
     * @return $this
     * @throws LocalizedException
     */
    protected function _beforeSave(AbstractModel $object)
    {
        $identifier = trim((string)$object->getData('identifier'));
        if (!preg_match('/^[a-z0-9_-]+$/', $identifier)) {
            throw new LocalizedException(
                __('Identifier is required and may contain only lowercase letters, digits, "_" and "-".')
            );
        }

        $content = trim((string)$object->getData('content'));
        $data = $content === '' ? ['sections' => []] : json_decode($content, true);
        if (!is_array($data) || !is_array($data['sections'] ?? null)) {
            throw new LocalizedException(__('Content must be valid JSON with a "sections" list.'));
        }
        $this->assertUniqueKeys($data['sections'], (string)__('Section'));
        foreach ($data['sections'] as &$section) {
            $section['fields'] = $this->prepareFields($section['fields'] ?? []);
        }
        unset($section);

        $object->setData('identifier', $identifier);
        $object->setData('content', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return parent::_beforeSave($object);
    }

    /**
     * Check field keys and move freshly uploaded images out of the tmp directory, including repeater items.
     *
     * @param mixed $fields
     * @return array
     * @throws LocalizedException
     */
    private function prepareFields($fields): array
    {
        $this->assertUniqueKeys($fields, (string)__('Field'));
        foreach ($fields as &$field) {
            $type = $field['type'] ?? '';
            if ($type === 'repeater') {
                $field['items'] = array_map([$this, 'prepareFields'], array_values((array)($field['items'] ?? [])));
            } elseif ($type === 'image' && !empty($field['value']['tmp'])) {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                $path = $this->imageUploader->moveFileFromTmp(basename((string)$field['value']['file']), true);
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                $field['value']['file'] = basename($path);
                unset($field['value']['tmp']);
            }
        }
        unset($field);

        return array_values($fields);
    }

    /**
     * Ensure every row has a key and keys are not repeated.
     *
     * @param mixed $rows
     * @param string $what
     * @return void
     * @throws LocalizedException
     */
    private function assertUniqueKeys($rows, string $what): void
    {
        if (!is_array($rows)) {
            throw new LocalizedException(__('%1 list is invalid.', $what));
        }
        $seen = [];
        foreach ($rows as $row) {
            $key = is_array($row) ? (string)($row['key'] ?? '') : '';
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $key)) {
                throw new LocalizedException(
                    __('%1 key "%2" is empty or invalid. Use letters, digits, "_" and "-".', $what, $key)
                );
            }
            if (isset($seen[$key])) {
                throw new LocalizedException(__('%1 key "%2" is used more than once.', $what, $key));
            }
            $seen[$key] = true;
        }
    }
}
