/**
 * Slova_Blog — Preview button of the post form.
 *
 * Sends what is in the form right now to slova_blog/post/preview and opens the returned
 * storefront link in a new tab. The tab is opened on the click itself (then pointed at the
 * link), so popup blockers let it through. When the server saved a draft, the form gets
 * the new post id and image names, so the next Save updates that post instead of
 * creating another one.
 *
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */
define([
    'jquery',
    'uiRegistry',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, registry, alert, $t) {
    'use strict';

    function syncEditors() {
        // Copy what is in each WYSIWYG editor into its form field, in case the editor
        // has not reported its last change yet.
        var tiny = window.tinymce || window.tinyMCE,
            editors;

        if (!tiny) {
            return;
        }
        // TinyMCE 6+ (Magento 2.4.8): get() without an id lists every editor.
        editors = typeof tiny.get === 'function' ? tiny.get() : tiny.editors;
        $.each(editors || [], function (i, editor) {
            var field;

            if (!editor || typeof editor.getContent !== 'function') {
                return;
            }
            field = registry.get('wysiwygId = ' + editor.id);
            if (field && typeof field.value === 'function') {
                field.value(editor.getContent());
            }
        });
    }

    function updateForm(provider, response) {
        // After a draft was saved: the next Save must update that post, with the moved images.
        try {
            if (response.post_id) {
                provider.set('data.post_id', String(response.post_id));
            }
            $.each(response.images || {}, function (field, value) {
                provider.set('data.' + field, value);
            });
        } catch (e) {
            if (window.console) {
                window.console.error(e);
            }
        }
    }

    function fail(tab, message) {
        if (tab) {
            tab.close();
        }
        alert({content: message || $t('The preview could not be created.')});
    }

    return function (config, element) {
        $(element).on('click', function (event) {
            var provider = registry.get(config.provider),
                tab;

            event.preventDefault();
            if (!provider) {
                return;
            }
            syncEditors();
            tab = window.open('', '_blank');
            if (tab) {
                tab.document.title = $t('Preview');
                tab.document.body.innerHTML = '<p style="font:16px sans-serif;padding:2rem">' +
                    $t('Preparing the preview...') + '</p>';
            }

            $.ajax({
                url: config.url,
                type: 'POST',
                dataType: 'json',
                showLoader: true,
                data: {
                    'form_key': window.FORM_KEY,
                    post: JSON.stringify(provider.get('data'))
                }
            }).done(function (response) {
                if (!response || !response.url) {
                    fail(tab, response && response.error);
                    return;
                }
                if (tab) {
                    tab.location.href = response.url;
                } else {
                    window.open(response.url, '_blank');
                }
                updateForm(provider, response);
            }).fail(function () {
                fail(tab);
            });
        });
    };
});
