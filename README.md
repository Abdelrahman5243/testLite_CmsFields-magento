# Lite_CmsFields

Ultra-light headless CMS module for Magento 2.4.6+ (PHP 8.1+). Admins build pages from **sections** and **fields** in the Magento admin; the storefront reads them through **GraphQL**. No Page Builder, no storefront layout/JS/CSS.

## Install

**Manual:** copy the repo contents to `app/code/Lite/CmsFields`.

**Composer (VCS):**

```bash
composer config repositories.lite-cms-fields vcs https://github.com/Abdelrahman5243/test
composer require lite/module-cms-fields:dev-main
```

Then:

```bash
bin/magento module:enable Lite_CmsFields
bin/magento setup:upgrade
bin/magento cache:flush
```

## Admin

**Content → Elements → Lite CMS**. Each page has an identifier (`[a-z0-9_-]+`), a store view (`All Store Views` = default) and a section/field builder with drag-and-drop sorting.

ACL: `Lite_CmsFields::page`, `Lite_CmsFields::page_save`, `Lite_CmsFields::page_delete`.

### Field types

| type | stored value | GraphQL property |
|---|---|---|
| `text`, `textarea`, `select`, `date` | string | `text` |
| `wysiwyg` | HTML (CMS directives are filtered) | `html` |
| `boolean` | bool | `boolean` |
| `image` | uploaded file + alt/title/caption | `image { url alt title caption }` |
| `link` | url, label, target | `link { url label target }` |
| `product` | list of SKUs | `skus` |
| `category` | category ID | `category_id`, `category_uid` |
| `cms_block` | block identifier | `block_identifier` |
| `repeater` | list of items, each a list of fields | `items { fields { ... } }` |

Images are uploaded to `pub/media/lite_cms/tmp` and moved to `pub/media/lite_cms` on save.

## Store views

Each store view can have its own row for the same identifier. Resolution: current store row → `All Store Views` row → `null`. An inactive row returns `null` (it does not fall back), so a page can be hidden per store.

## GraphQL

```graphql
{
  liteCmsPage(identifier: "home") {
    identifier
    sections {
      key
      label
      fields {
        key
        type
        text
        html
        boolean
        image { url alt title caption }
        link { url label target }
        skus
        category_id
        category_uid
        block_identifier
        items { fields { key type text image { url alt } link { url label target } } }
      }
    }
  }
}
```

Send the `Store: <store_code>` header to pick a store view. Each field fills only the property that matches its `type`; the rest are `null`. Missing or inactive pages return `null`.

Responses are cached with tags `lite_cms_page_<id>` and a per-identifier tag, invalidated when a page is saved or deleted.

## Storage

One table, `lite_cms_page`, with the page content as JSON in `content`:

```json
{
  "sections": [
    {
      "key": "hero", "label": "Hero", "sort": 10,
      "fields": [
        {"key": "title", "type": "text", "value": "Hello"},
        {"key": "banner", "type": "image", "value": {"file": "hero.jpg", "alt": "", "title": "", "caption": ""}},
        {"key": "cards", "type": "repeater", "items": [[{"key": "title", "type": "text", "value": "A"}]]}
      ]
    }
  ]
}
```

## Limitations

- `wysiwyg` is an HTML textarea, not a TinyMCE editor.
- `product`, `category` and `cms_block` are plain inputs (no picker, no existence check).
- No scope switcher in the form; create one row per store view with the same identifier.
