define([
    'Magento_Ui/js/form/element/abstract',
    'jquery',
    'mage/translate',
    'jquery-ui-modules/sortable'
], function (Abstract, $, $t) {
    'use strict';

    var TYPES = [
            'text', 'textarea', 'wysiwyg', 'image', 'link', 'select',
            'boolean', 'date', 'repeater', 'product', 'category', 'cms_block'
        ],
        LISTS = '.lcb-sections, .lcb-fields, .lcb-items';

    /**
     * @param {String} tag
     * @param {String} [cls]
     * @param {Object} [attrs]
     * @returns {jQuery}
     */
    function el(tag, cls, attrs) {
        return $('<' + tag + '/>').addClass(cls || '').attr(attrs || {});
    }

    /**
     * @param {String} name
     * @param {*} value
     * @param {String} [placeholder]
     * @param {String} [type]
     * @returns {jQuery}
     */
    function input(name, value, placeholder, type) {
        return el('input', 'admin__control-text', {
            type: type || 'text',
            'data-name': name,
            placeholder: placeholder || ''
        }).val(value == null ? '' : value);
    }

    /**
     * @param {String} label
     * @param {String} role
     * @param {String} [cls]
     * @returns {jQuery}
     */
    function button(label, role, cls) {
        return el('button', 'lcb-btn ' + (cls || ''), {
            type: 'button',
            'data-role': role
        }).text(label);
    }

    /**
     * @returns {Array}
     */
    function headControls() {
        return [
            el('span', 'lcb-handle', {
                title: $t('Drag to reorder')
            }).text('☰'),
            button('✕', 'remove', 'lcb-remove').attr('title', $t('Remove'))
        ];
    }

    /**
     * @param {String} text
     * @returns {Array}
     */
    function lines(text) {
        return $.map((text || '').split('\n'), function (line) {
            return $.trim(line) || null;
        });
    }

    return Abstract.extend({
        defaults: {
            elementTmpl: 'Lite_CmsFields/builder',
            uploadUrl: '',
            mediaUrl: ''
        },

        /**
         * Build the editor inside the rendered element.
         *
         * @param {HTMLElement} element
         */
        mount: function (element) {
            var self = this,
                data;

            try {
                data = JSON.parse(this.value() || '{}') || {};
            } catch (e) {
                data = {};
            }

            this.$sections = el('div', 'lcb-sections');
            $.each(data.sections || [], function (i, section) {
                self.$sections.append(self.section(section));
            });

            $(element)
                .append(this.$sections, button($t('Add Section'), 'add-section', 'lcb-add-section'))
                .on('click', '[data-role=add-section]', function () {
                    self.$sections.append(self.section({}));
                    self.refresh();
                })
                .on('click', '[data-role=add-field]', function () {
                    $(this).prev('.lcb-fields').append(self.field({}));
                    self.refresh();
                })
                .on('click', '[data-role=add-item]', function () {
                    var $items = $(this).prev('.lcb-items'),
                        last = self.readFields($items.children('.lcb-item').last().children('.lcb-fields'));

                    $items.append(self.item($.map(last, function (field) {
                        return {
                            key: field.key,
                            type: field.type,
                            options: field.options
                        };
                    })));
                    self.refresh();
                })
                .on('click', '[data-role=remove]', function () {
                    $(this).closest('.lcb-section, .lcb-field, .lcb-item').remove();
                    self.sync();
                })
                .on('click', '[data-role=remove-image]', function () {
                    self.showImage($(this).closest('.lcb-image').data({
                        file: '',
                        tmp: false
                    }));
                    self.sync();
                })
                .on('change', '.lcb-type', function () {
                    var $field = $(this).closest('.lcb-field');

                    $field.children('.lcb-value').empty().append(self.valueUi(this.value, {}));
                    self.refresh();
                })
                .on('change', '[data-name=options]', function () {
                    self.fillOptions($(this).closest('.lcb-value'));
                })
                .on('change', '[data-role=upload]', function () {
                    self.upload(this);
                })
                .on('input change', ':input', function () {
                    self.sync();
                });

            this.$root = $(element);
            this.refresh();
        },

        /**
         * @param {Object} data
         * @returns {jQuery}
         */
        section: function (data) {
            var controls = headControls();

            return el('div', 'lcb-section').append(
                el('div', 'lcb-head').append(
                    controls[0],
                    input('key', data.key, $t('Section key, e.g. hero')),
                    input('label', data.label, $t('Label')),
                    controls[1]
                ),
                this.fields(data.fields),
                button($t('Add Field'), 'add-field')
            );
        },

        /**
         * @param {Array} [list]
         * @returns {jQuery}
         */
        fields: function (list) {
            var self = this,
                $list = el('div', 'lcb-fields');

            $.each(list || [], function (i, field) {
                $list.append(self.field(field));
            });

            return $list;
        },

        /**
         * @param {Object} data
         * @returns {jQuery}
         */
        field: function (data) {
            var controls = headControls(),
                type = $.inArray(data.type, TYPES) < 0 ? 'text' : data.type,
                $type = el('select', 'admin__control-select lcb-type');

            $.each(TYPES, function (i, name) {
                $type.append(el('option').val(name).text(name));
            });
            $type.val(type);

            return el('div', 'lcb-field').append(
                el('div', 'lcb-head').append(
                    controls[0],
                    input('key', data.key, $t('Field key, e.g. title')),
                    $type,
                    controls[1]
                ),
                el('div', 'lcb-value').append(this.valueUi(type, data))
            );
        },

        /**
         * @param {Array} [fields]
         * @returns {jQuery}
         */
        item: function (fields) {
            var controls = headControls();

            return el('div', 'lcb-item').append(
                el('div', 'lcb-head').append(
                    controls[0],
                    el('span', 'lcb-item-title').text($t('Item')),
                    controls[1]
                ),
                this.fields(fields),
                button($t('Add Field'), 'add-field')
            );
        },

        /**
         * Inputs for one field value, by type.
         *
         * @param {String} type
         * @param {Object} data
         * @returns {jQuery|Array}
         */
        valueUi: function (type, data) {
            var self = this,
                value = data.value,
                $node;

            switch (type) {
                case 'textarea':
                case 'wysiwyg':
                    return el('textarea', 'admin__control-textarea', {
                        'data-name': 'value',
                        rows: type === 'wysiwyg' ? 6 : 3,
                        placeholder: type === 'wysiwyg' ? 'HTML' : ''
                    }).val(value || '');

                case 'boolean':
                    return el('label').append(
                        el('input', '', {
                            type: 'checkbox',
                            'data-name': 'value'
                        }).prop('checked', !!value),
                        ' ' + $t('Yes')
                    );

                case 'date':
                    return input('value', value, '', 'date');

                case 'category':
                    return input('value', value, $t('Category ID'), 'number');

                case 'product':
                    return input('value', $.isArray(value) ? value.join(', ') : value, $t('SKUs, comma separated'));

                case 'cms_block':
                    return input('value', value, $t('CMS block identifier'));

                case 'link':
                    value = value || {};
                    $node = el('select', 'admin__control-select lcb-third', {
                        'data-name': 'target'
                    }).append(
                        el('option').val('_self').text($t('Same tab')),
                        el('option').val('_blank').text($t('New tab'))
                    ).val(value.target === '_blank' ? '_blank' : '_self');

                    return [
                        input('url', value.url, $t('URL')).addClass('lcb-third'),
                        input('label', value.label, $t('Label')).addClass('lcb-third'),
                        $node
                    ];

                case 'select':
                    $node = el('div', 'lcb-value');
                    $node.append(
                        el('textarea', 'admin__control-textarea', {
                            'data-name': 'options',
                            rows: 3,
                            placeholder: $t('Options, one per line')
                        }).val((data.options || []).join('\n')),
                        el('select', 'admin__control-select', {
                            'data-name': 'value'
                        }).data('initial', value)
                    );
                    this.fillOptions($node);

                    return $node.children();

                case 'image':
                    value = value || {};
                    $node = el('div', 'lcb-image').data({
                        file: value.file || '',
                        tmp: !!value.tmp
                    }).append(
                        el('img', 'lcb-preview', {
                            alt: ''
                        }),
                        el('span', 'lcb-file'),
                        el('input', '', {
                            type: 'file',
                            accept: 'image/*',
                            'data-role': 'upload'
                        }),
                        button($t('Remove image'), 'remove-image'),
                        input('alt', value.alt, $t('Alt text')),
                        input('title', value.title, $t('Title')),
                        input('caption', value.caption, $t('Caption'))
                    );
                    this.showImage($node);

                    return $node;

                case 'repeater':
                    $node = el('div', 'lcb-items');
                    $.each(data.items || [], function (i, fields) {
                        $node.append(self.item(fields));
                    });

                    return [$node, button($t('Add Item'), 'add-item')];

                default:
                    return input('value', value);
            }
        },

        /**
         * Rebuild the value dropdown of a select field from its options textarea.
         *
         * @param {jQuery} $value
         */
        fillOptions: function ($value) {
            var $select = $value.children('select[data-name=value]'),
                current = $select.val() || $select.data('initial') || '';

            $select.empty().append(el('option').val('').text($t('-- Please select --')));
            $.each(lines($value.children('[data-name=options]').val()), function (i, option) {
                $select.append(el('option').val(option).text(option));
            });
            $select.val(current);

            if ($select.val() === null) {
                $select.val('');
            }
        },

        /**
         * @param {jQuery} $box
         */
        showImage: function ($box) {
            var file = $box.data('file'),
                dir = $box.data('tmp') ? 'lite_cms/tmp/' : 'lite_cms/';

            $box.children('.lcb-preview').toggle(!!file).attr('src', file ? this.mediaUrl + dir + file : null);
            $box.children('.lcb-file').text(file || '');
            $box.children('[data-role=remove-image]').toggle(!!file);
        },

        /**
         * @param {HTMLInputElement} fileInput
         */
        upload: function (fileInput) {
            var self = this,
                $box = $(fileInput).closest('.lcb-image'),
                form = new FormData();

            if (!fileInput.files.length) {
                return;
            }
            form.append('image', fileInput.files[0]);
            form.append('form_key', window.FORM_KEY);

            $.ajax({
                url: this.uploadUrl,
                type: 'POST',
                data: form,
                processData: false,
                contentType: false,
                dataType: 'json',
                showLoader: true
            }).done(function (result) {
                if (!result || result.error || !result.file) {
                    window.alert(result && result.error || $t('Upload failed.'));

                    return;
                }
                self.showImage($box.data({
                    file: result.file,
                    tmp: true
                }));
                self.sync();
            }).fail(function () {
                window.alert($t('Upload failed.'));
            }).always(function () {
                fileInput.value = '';
            });
        },

        /**
         * Enable drag sorting on new lists and write the JSON value.
         */
        refresh: function () {
            var self = this;

            this.$root.find(LISTS).not('.ui-sortable').sortable({
                axis: 'y',
                handle: '.lcb-handle',
                items: '> *',
                tolerance: 'pointer',

                /**
                 * Sort finished.
                 */
                update: function () {
                    self.sync();
                }
            });
            this.sync();
        },

        /**
         * @param {jQuery} $list
         * @returns {Array}
         */
        readFields: function ($list) {
            var self = this;

            return $list.children('.lcb-field').map(function () {
                var $head = $(this).children('.lcb-head'),
                    $value = $(this).children('.lcb-value'),
                    type = $head.children('.lcb-type').val(),
                    out = {
                        key: $.trim($head.children('[data-name=key]').val()),
                        type: type
                    },

                    /**
                     * @param {String} name
                     * @returns {String}
                     */
                    read = function (name) {
                        return $value.find('[data-name=' + name + ']').val() || '';
                    },
                    $image;

                switch (type) {
                    case 'repeater':
                        out.items = $value.children('.lcb-items').children('.lcb-item').map(function () {
                            return [self.readFields($(this).children('.lcb-fields'))];
                        }).get();
                        break;

                    case 'boolean':
                        out.value = $value.find('[data-name=value]').prop('checked');
                        break;

                    case 'image':
                        $image = $value.children('.lcb-image');
                        out.value = {
                            file: $image.data('file') || '',
                            alt: read('alt'),
                            title: read('title'),
                            caption: read('caption')
                        };

                        if ($image.data('tmp')) {
                            out.value.tmp = true;
                        }
                        break;

                    case 'link':
                        out.value = {
                            url: read('url'),
                            label: read('label'),
                            target: read('target')
                        };
                        break;

                    case 'product':
                        out.value = $.map(read('value').split(','), function (sku) {
                            return $.trim(sku) || null;
                        });
                        break;

                    case 'category':
                        out.value = parseInt(read('value'), 10) || null;
                        break;

                    case 'select':
                        out.options = lines(read('options'));
                        out.value = read('value');
                        break;

                    default:
                        out.value = read('value');
                }

                return out;
            }).get();
        },

        /**
         * Serialize the editor into the form value.
         */
        sync: function () {
            var self = this;

            this.value(JSON.stringify({
                sections: this.$sections.children('.lcb-section').map(function (index) {
                    var $head = $(this).children('.lcb-head');

                    return {
                        key: $.trim($head.children('[data-name=key]').val()),
                        label: $head.children('[data-name=label]').val(),
                        sort: (index + 1) * 10,
                        fields: self.readFields($(this).children('.lcb-fields'))
                    };
                }).get()
            }));
        }
    });
});
