<?php
declare(strict_types=1);

namespace Lite\CmsFields\Model\Resolver;

use Lite\CmsFields\Model\Page;
use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;

class Identity implements IdentityInterface
{
    /**
     * Cache tags for a resolved page; a missing page gets the bare tag so creating a page invalidates it.
     *
     * @param array $resolvedData
     * @return string[]
     */
    public function getIdentities(array $resolvedData): array
    {
        if (empty($resolvedData['entity_id'])) {
            return [Page::CACHE_TAG];
        }
        return [
            Page::CACHE_TAG . '_' . $resolvedData['entity_id'],
            Page::identifierTag((string)$resolvedData['identifier']),
        ];
    }
}
