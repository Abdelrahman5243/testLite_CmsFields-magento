<?php
declare(strict_types=1);

namespace Lite\CmsFields\Model\Resolver;

use Lite\CmsFields\Model\ResourceModel\Page as PageResource;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;

class Page implements ResolverInterface
{
    /**
     * @param PageResource $resource
     * @param FilterProvider $filterProvider
     */
    public function __construct(
        private readonly PageResource $resource,
        private readonly FilterProvider $filterProvider
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $identifier = trim((string)($args['identifier'] ?? ''));
        if ($identifier === '') {
            throw new GraphQlInputException(__('"identifier" must not be empty.'));
        }

        /** @var StoreInterface $store */
        $store = $context->getExtensionAttributes()->getStore();
        $row = $this->resource->getRowForStore($identifier, (int)$store->getId());
        if (!$row || !(int)$row['is_active']) {
            return null;
        }

        $content = json_decode((string)$row['content'], true);
        $sections = is_array($content['sections'] ?? null) ? $content['sections'] : [];
        usort($sections, static fn (array $a, array $b): int => ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0));

        return [
            'entity_id' => (int)$row['entity_id'],
            'identifier' => $row['identifier'],
            'sections' => array_map(
                fn (array $section): array => [
                    'key' => $section['key'] ?? null,
                    'label' => $section['label'] ?? null,
                    'fields' => $this->fields($section['fields'] ?? [], $store),
                ],
                $sections
            ),
        ];
    }

    /**
     * Convert stored fields to the LiteCmsField shape.
     *
     * @param array $fields
     * @param StoreInterface $store
     * @return array
     */
    private function fields(array $fields, StoreInterface $store): array
    {
        $result = [];
        foreach ($fields as $field) {
            $type = (string)($field['type'] ?? 'text');
            $value = $field['value'] ?? null;
            $out = ['key' => $field['key'] ?? null, 'type' => $type];
            switch ($type) {
                case 'wysiwyg':
                    $out['html'] = $this->filterProvider->getPageFilter()
                        ->setStoreId((int)$store->getId())
                        ->filter((string)$value);
                    break;
                case 'boolean':
                    $out['boolean'] = (bool)$value;
                    break;
                case 'image':
                    if (!empty($value['file'])) {
                        $out['image'] = [
                            'url' => $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA)
                                . 'lite_cms/' . ltrim((string)$value['file'], '/'),
                            'alt' => $value['alt'] ?? '',
                            'title' => $value['title'] ?? '',
                            'caption' => $value['caption'] ?? '',
                        ];
                    }
                    break;
                case 'link':
                    $out['link'] = is_array($value) ? $value : null;
                    break;
                case 'product':
                    $out['skus'] = array_values((array)$value);
                    break;
                case 'category':
                    if ($value) {
                        $out['category_id'] = (int)$value;
                        // phpcs:ignore Magento2.Functions.DiscouragedFunction
                        $out['category_uid'] = base64_encode((string)(int)$value);
                    }
                    break;
                case 'cms_block':
                    $out['block_identifier'] = (string)$value;
                    break;
                case 'repeater':
                    $out['items'] = array_map(
                        fn (array $item): array => ['fields' => $this->fields($item, $store)],
                        array_values((array)($field['items'] ?? []))
                    );
                    break;
                default:
                    $out['text'] = is_scalar($value) ? (string)$value : null;
            }
            $result[] = $out;
        }
        return $result;
    }
}
