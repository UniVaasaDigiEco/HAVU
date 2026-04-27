(function (window) {
    'use strict';

    var MODAL_ID = 'havu-youtube-embed-modal';
    var INPUT_ID = 'havu-youtube-url';
    var ERROR_ID = 'havu-youtube-error';
    var INSERT_BUTTON_ID = 'havu-youtube-insert';
    var activeContext = null;

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function normalizeHost(hostname) {
        var host = (hostname || '').toLowerCase();
        return host.indexOf('www.') === 0 ? host.slice(4) : host;
    }

    function parseStartOffset(value) {
        var input = String(value || '').trim();
        if (!input) {
            return 0;
        }

        if (/^\d+$/.test(input)) {
            return parseInt(input, 10);
        }

        var match = input.match(/^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$/);
        if (!match) {
            return 0;
        }

        return (parseInt(match[1] || '0', 10) * 3600)
            + (parseInt(match[2] || '0', 10) * 60)
            + parseInt(match[3] || '0', 10);
    }

    function parseYouTubeInput(input) {
        var rawInput = String(input || '').trim();
        if (!rawInput) {
            return null;
        }

        if (/^[A-Za-z0-9_-]{11}$/.test(rawInput)) {
            return { videoId: rawInput, start: 0 };
        }

        var iframeMatch = rawInput.match(/<iframe[^>]+src=["']([^"']+)["']/i);
        if (iframeMatch) {
            rawInput = iframeMatch[1];
        }

        var url;
        try {
            url = new URL(rawInput);
        } catch (_error) {
            return null;
        }

        var host = normalizeHost(url.hostname);
        var pathParts = url.pathname.replace(/^\/+|\/+$/g, '').split('/').filter(Boolean);
        var videoId = null;

        if (host === 'youtu.be') {
            videoId = pathParts[0] || null;
        } else if (host === 'youtube.com' || host === 'm.youtube.com' || host === 'youtube-nocookie.com') {
            videoId = url.searchParams.get('v');

            if (!videoId && pathParts[0] === 'embed' && pathParts[1]) {
                videoId = pathParts[1];
            }

            if (!videoId && pathParts[0] === 'shorts' && pathParts[1]) {
                videoId = pathParts[1];
            }

            if (!videoId && pathParts[0] === 'live' && pathParts[1]) {
                videoId = pathParts[1];
            }
        }

        if (!videoId || !/^[A-Za-z0-9_-]{11}$/.test(videoId)) {
            return null;
        }

        var start = parseStartOffset(url.searchParams.get('start') || url.searchParams.get('t') || '');
        if (!start && url.hash) {
            var fragment = new URLSearchParams(url.hash.replace(/^#/, ''));
            start = parseStartOffset(fragment.get('t') || '');
        }

        return {
            videoId: videoId,
            start: start
        };
    }

    function buildEmbedUrl(videoId, start) {
        var url = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(videoId);
        if (start > 0) {
            url += '?start=' + start;
        }

        return url;
    }

    function buildEmbedHtml(input) {
        var embedData = parseYouTubeInput(input);
        if (!embedData) {
            return null;
        }

        return ''
            + '<div class="node-rich-content__embed" data-embed-provider="youtube">'
            + '<iframe src="' + escapeHtml(buildEmbedUrl(embedData.videoId, embedData.start)) + '"'
            + ' title="YouTube video player"'
            + ' loading="lazy"'
            + ' allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"'
            + ' referrerpolicy="strict-origin-when-cross-origin"'
            + ' allowfullscreen></iframe>'
            + '</div>';
    }

    function normalizeHtml(html) {
        var source = String(html || '').trim();
        if (!source) {
            return '';
        }

        var parser = new DOMParser();
        var documentFragment = parser.parseFromString('<div id="havu-youtube-root">' + source + '</div>', 'text/html');
        var root = documentFragment.getElementById('havu-youtube-root');
        if (!root) {
            return source;
        }

        Array.from(root.querySelectorAll('iframe')).forEach(function (iframe) {
            var embedHtml = buildEmbedHtml(iframe.getAttribute('src') || iframe.outerHTML);
            if (!embedHtml) {
                return;
            }

            var replacementDocument = parser.parseFromString(embedHtml, 'text/html');
            var replacementNode = replacementDocument.body.firstElementChild;
            if (!replacementNode) {
                return;
            }

            var replacementTarget = iframe.parentElement && iframe.parentElement.classList.contains('node-rich-content__embed')
                ? iframe.parentElement
                : iframe;

            replacementTarget.replaceWith(replacementNode);
        });

        return root.innerHTML.trim();
    }

    function wrapRichContent(html) {
        var normalizedHtml = normalizeHtml(html);
        return normalizedHtml ? '<div class="node-rich-content">' + normalizedHtml + '</div>' : '';
    }

    function getTranslations(translations) {
        return Object.assign({
            youtube_embed_tooltip: 'Add YouTube video',
            youtube_modal_title: 'Add YouTube video',
            youtube_modal_label: 'YouTube link',
            youtube_modal_help: 'Paste a normal YouTube link. The video size will scale automatically.',
            youtube_modal_placeholder: 'https://www.youtube.com/watch?v=...',
            youtube_insert: 'Add video',
            youtube_invalid_url: 'Enter a valid YouTube link.',
            youtube_modal_close: 'Close'
        }, translations || {});
    }

    function ensureModal(bootstrapLib, translations) {
        var modalElement = document.getElementById(MODAL_ID);
        if (!modalElement) {
            modalElement = document.createElement('div');
            modalElement.className = 'modal fade';
            modalElement.id = MODAL_ID;
            modalElement.tabIndex = -1;
            modalElement.setAttribute('aria-hidden', 'true');
            document.body.appendChild(modalElement);
        }

        var text = getTranslations(translations);
        modalElement.innerHTML = ''
            + '<div class="modal-dialog modal-dialog-centered">'
            + '  <div class="modal-content">'
            + '    <div class="modal-header">'
            + '      <h5 class="modal-title">' + escapeHtml(text.youtube_modal_title) + '</h5>'
            + '      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="' + escapeHtml(text.youtube_modal_close) + '"></button>'
            + '    </div>'
            + '    <div class="modal-body">'
            + '      <label for="' + INPUT_ID + '" class="form-label">' + escapeHtml(text.youtube_modal_label) + '</label>'
            + '      <input type="url" class="form-control" id="' + INPUT_ID + '" placeholder="' + escapeHtml(text.youtube_modal_placeholder) + '">'
            + '      <div class="invalid-feedback" id="' + ERROR_ID + '"></div>'
            + '      <div class="form-text">' + escapeHtml(text.youtube_modal_help) + '</div>'
            + '    </div>'
            + '    <div class="modal-footer">'
            + '      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' + escapeHtml(text.youtube_modal_close) + '</button>'
            + '      <button type="button" class="btn btn-primary" id="' + INSERT_BUTTON_ID + '">' + escapeHtml(text.youtube_insert) + '</button>'
            + '    </div>'
            + '  </div>'
            + '</div>';

        if (!modalElement.dataset.focusHandlerBound) {
            modalElement.addEventListener('shown.bs.modal', function () {
                var input = document.getElementById(INPUT_ID);
                if (input) {
                    input.focus();
                }
            });
            modalElement.dataset.focusHandlerBound = 'true';
        }

        var inputElement = modalElement.querySelector('#' + INPUT_ID);
        if (inputElement) {
            inputElement.addEventListener('input', function () {
                inputElement.classList.remove('is-invalid');
                modalElement.querySelector('#' + ERROR_ID).textContent = '';
            });

            inputElement.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    modalElement.querySelector('#' + INSERT_BUTTON_ID).click();
                }
            });
        }

        modalElement.querySelector('#' + INSERT_BUTTON_ID).addEventListener('click', function () {
            if (!activeContext || !activeContext.jquery) {
                return;
            }

            var modalInput = modalElement.querySelector('#' + INPUT_ID);
            var embedHtml = buildEmbedHtml(modalInput.value);
            if (!embedHtml) {
                modalInput.classList.add('is-invalid');
                modalElement.querySelector('#' + ERROR_ID).textContent = getTranslations(activeContext.translations).youtube_invalid_url;
                return;
            }

            activeContext.jquery(activeContext.editorSelector).summernote('focus');
            activeContext.jquery(activeContext.editorSelector).summernote('pasteHTML', embedHtml + '<p></p>');
            bootstrapLib.Modal.getOrCreateInstance(modalElement).hide();
        });

        return bootstrapLib.Modal.getOrCreateInstance(modalElement);
    }

    function createSummernoteButton(jquery, bootstrapLib, translations, editorSelector) {
        return jquery.summernote.ui.button({
            contents: '<i class="bi bi-youtube"></i>',
            tooltip: getTranslations(translations).youtube_embed_tooltip,
            click: function () {
                activeContext = {
                    jquery: jquery,
                    editorSelector: editorSelector,
                    translations: translations
                };

                var modal = ensureModal(bootstrapLib, translations);
                var input = document.getElementById(INPUT_ID);
                if (input) {
                    input.value = '';
                    input.classList.remove('is-invalid');
                }
                var errorElement = document.getElementById(ERROR_ID);
                if (errorElement) {
                    errorElement.textContent = '';
                }
                modal.show();
            }
        }).render();
    }

    window.HavuYouTubeEmbed = {
        buildEmbedHtml: buildEmbedHtml,
        createSummernoteButton: createSummernoteButton,
        extractVideoId: function (input) {
            var embedData = parseYouTubeInput(input);
            return embedData ? embedData.videoId : null;
        },
        normalizeHtml: normalizeHtml,
        wrapRichContent: wrapRichContent
    };
}(window));
