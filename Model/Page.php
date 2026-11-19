<?php
declare(strict_types=1);

namespace Lite\CmsFields\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class Page extends AbstractModel implements IdentityInterface
{
    public const CACHE_TAG = 'lite_cms_page';

    /**
     * @var string
     */
    protected $_eventPrefix = 'lite_cms_page';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Page::class);
    }

    /**
     * Cache tag shared by every row (any store) of one identifier.
     *
     * @param string $identifier
     * @return string
     */
    public static function identifierTag(string $identifier): string
    {
        return self::CACHE_TAG . '_i_' . bin2hex($identifier);
    }

    /**
     * Tags to invalidate on save/delete.
     *
     * The bare tag marks cached "page not found" responses; the identifier tags cover store fallback.
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        $tags = [self::CACHE_TAG];
        if ($this->getId()) {
            $tags[] = self::CACHE_TAG . '_' . $this->getId();
        }
        foreach ([$this->getData('identifier'), $this->getOrigData('identifier')] as $identifier) {
            if ($identifier !== null && $identifier !== '') {
                $tags[] = self::identifierTag((string)$identifier);
            }
        }
        return array_values(array_unique($tags));
    }
}
