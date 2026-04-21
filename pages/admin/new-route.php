<?php
require_once('../../vendor/autoload.php');
require_once('../../classes/tools.class.php');
require_once('../../classes/security.class.php');
use Ramsey\Uuid\Uuid;
Security::initSession();

$summernote_locale = [
    'fi' => 'fi-FI',
    'en' => 'en-US',
    'sv' => 'sv-SE',
][current_locale()] ?? 'fi-FI';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('admin_new_route.title'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="../../css/bs-custom.css">
    <link rel="stylesheet" href="../../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../node_modules/leaflet/dist/leaflet.css">
    <link rel="stylesheet" href="../../node_modules/summernote/dist/summernote-bs5.min.css">
    <style>
        #map {
            height: 500px;
            width: 100%;
            border-radius: 0.375rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        }

        .node-item {
            border-left: 3px solid #0d6efd;
            transition: all 0.3s ease;
        }

        .node-item:hover {
            border-left-color: #0a58ca;
            box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.1);
        }

        .node-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #0d6efd;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
        }

        .leaflet-popup-content {
            margin: 13px 19px;
            line-height: 1.4;
        }

        .node-list-empty {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .note-editor.note-frame {
            border-radius: 0.375rem;
        }
    </style>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>" async defer></script>
</head>
<body class="admin-dashboard">
<?php require_once '../../includes/_language_switcher.php'; ?>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-map-fill me-2"></i><?= htmlspecialchars(t('admin_new_route.heading'), ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="text-muted mb-0"><?= htmlspecialchars(t('admin_new_route.subheading'), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <a href="dashboard.php" class="btn btn-warning">
                    <i class="bi bi-arrow-left"></i> <?= htmlspecialchars(t('common.back_to_dashboard'), ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>
        </div>
    </div>

    <form id="routeForm" method="POST" action="../../actions/create_route.php">
        <input type="hidden" name="action" value="create_route">
        <input type="hidden" name="nodes_data" id="nodesData">

        <div class="row">
            <!-- Left Column - Route Details -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle-fill me-2"></i><?= htmlspecialchars(t('route_editor.route_details'), ENT_QUOTES, 'UTF-8') ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="route_title" class="form-label"><?= htmlspecialchars(t('common.route_name'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="route_title" name="route_title" aria-describedby="route_help" required>
                            <small id="route_help"><?= htmlspecialchars(t('route_editor.route_title_help'), ENT_QUOTES, 'UTF-8') ?></small>
                        </div>

                        <div class="mb-3">
                            <label for="route_description" class="form-label"><?= htmlspecialchars(t('common.route_description'), ENT_QUOTES, 'UTF-8') ?></label>
                            <textarea class="form-control" id="route_description" aria-describedby="description_help" name="route_description" rows="4"></textarea>
                            <small id="description_help"><?= htmlspecialchars(t('route_editor.route_description_help'), ENT_QUOTES, 'UTF-8') ?></small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_published" name="is_published" aria-describedby="public-help" checked>
                                <label class="form-check-label" for="is_published">
                                    <?= htmlspecialchars(t('common.public'), ENT_QUOTES, 'UTF-8') ?>
                                </label><br>
                                <small id="public-help"><?= htmlspecialchars(t('route_editor.public_help'), ENT_QUOTES, 'UTF-8') ?></small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="publication_date" class="form-label"><?= htmlspecialchars(t('common.publication_date_short'), ENT_QUOTES, 'UTF-8') ?></label>
                            <input type="date" class="form-control" id="publication_date" name="publication_date" readonly>
                            <small><?= htmlspecialchars(t('route_editor.publication_date_help'), ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                    </div>
                </div>

                <!-- Nodes List -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-geo-alt-fill me-2"></i><?= htmlspecialchars(t('common.nodes'), ENT_QUOTES, 'UTF-8') ?> (<span id="nodeCount">0</span>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="nodesList" class="list-group list-group-flush">
                            <div class="node-list-empty">
                                <i class="bi bi-cursor-fill" style="font-size: 3rem;"></i>
                                <p class="mt-3"><?= htmlspecialchars(t('route_editor.nodes_empty'), ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Map and Node Editor -->
            <div class="col-lg-8">
                <!-- Map -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-map me-2"></i><?= htmlspecialchars(t('common.map'), ENT_QUOTES, 'UTF-8') ?></h5>
                    </div>
                    <div class="card-body">
                        <!-- Location search -->
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" id="locationSearch" class="form-control" placeholder="<?= htmlspecialchars(t('route_editor.search_location_placeholder'), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
                                <button class="btn btn-outline-secondary" type="button" id="locationSearchBtn">
                                    <?= htmlspecialchars(t('common.search'), ENT_QUOTES, 'UTF-8') ?>
                                </button>
                            </div>
                            <div id="searchResults" class="list-group mt-1" style="display:none; position:absolute; z-index:1000; max-width:600px;"></div>
                        </div>
                        <div id="map"></div>
                        <div class="mt-2 text-muted small">
                            <i class="bi bi-info-circle"></i> <?= htmlspecialchars(t('route_editor.map_help_new'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                </div>

                <!-- Node Editor -->
                <div id="nodeEditor" class="card shadow-sm" style="display: none; position: relative;">
                    <div id="uploadOverlay" style="display:none; position:absolute; inset:0; z-index:10; background:rgba(255,255,255,0.88); border-radius:0.375rem; align-items:center; justify-content:center; flex-direction:column;">
                            <div class="spinner-border text-primary" style="width:2.5rem;height:2.5rem;" role="status">
                            <span class="visually-hidden"><?= htmlspecialchars(t('common.loading'), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <p class="mt-3 mb-0 fw-semibold text-primary"><?= htmlspecialchars(t('common.loading_file'), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="bi bi-pencil-fill me-2"></i><?= htmlspecialchars(t('route_editor.node_edit'), ENT_QUOTES, 'UTF-8') ?></h5>
                    </div>
                    <div class="card-body">
                        <input type="hidden" id="editNodeIndex">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="node_title" class="form-label"><?= htmlspecialchars(t('common.node_name'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="node_title">
                                <small><?= htmlspecialchars(t('route_editor.node_name_help'), ENT_QUOTES, 'UTF-8') ?></small>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="node_lat" class="form-label"><?= htmlspecialchars(t('route_editor.latitude'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="number" step="0.000001" class="form-control" id="node_lat" readonly>
                                <small><?= htmlspecialchars(t('route_editor.latitude_help'), ENT_QUOTES, 'UTF-8') ?></small>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="node_lng" class="form-label"><?= htmlspecialchars(t('route_editor.longitude'), ENT_QUOTES, 'UTF-8') ?></label>
                                <input type="number" step="0.000001" class="form-control" id="node_lng" readonly>
                                <small><?= htmlspecialchars(t('route_editor.longitude_help'), ENT_QUOTES, 'UTF-8') ?></small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="node_content" class="form-label"><?= htmlspecialchars(t('common.node_content'), ENT_QUOTES, 'UTF-8') ?></label>
                            <textarea id="node_content"></textarea>
                            <small><?= htmlspecialchars(t('route_editor.node_content_help'), ENT_QUOTES, 'UTF-8') ?></small>
                        </div>

                        <!-- Challenge panel -->
                        <div class="mb-3 p-3 rounded" style="border: 2px solid #ffc107;">
                            <label class="form-label fw-semibold mb-2"><?= htmlspecialchars(t('route_editor.challenge'), ENT_QUOTES, 'UTF-8') ?></label>
                            <div class="d-flex gap-2 mb-3 flex-wrap">
                                <button type="button" class="btn btn-sm btn-warning" id="challengeTypeNone" onclick="setChallengeType('none')"><?= htmlspecialchars(t('route_editor.challenge_none'), ENT_QUOTES, 'UTF-8') ?></button>
                                <button type="button" class="btn btn-sm btn-outline-warning" id="challengeTypeMC" onclick="setChallengeType('multiple_choice')"><?= htmlspecialchars(t('route_editor.challenge_multiple_choice'), ENT_QUOTES, 'UTF-8') ?></button>
                                <button type="button" class="btn btn-sm btn-outline-warning" id="challengeTypeText" onclick="setChallengeType('text')"><?= htmlspecialchars(t('route_editor.challenge_text'), ENT_QUOTES, 'UTF-8') ?></button>
                            </div>
                            <div id="challengeMCFields" style="display:none;">
                                <div class="mb-2">
                                    <label class="form-label form-label-sm"><?= htmlspecialchars(t('route_editor.challenge_question'), ENT_QUOTES, 'UTF-8') ?></label>
                                    <input type="text" class="form-control form-control-sm" id="challengeQuestion" placeholder="<?= htmlspecialchars(t('route_editor.challenge_question_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div id="challengeOptions"></div>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="addOptionBtn" onclick="addChallengeOption()"><?= htmlspecialchars(t('route_editor.challenge_add_option'), ENT_QUOTES, 'UTF-8') ?></button>
                            </div>
                            <div id="challengeTextFields" style="display:none;">
                                <div class="mb-2">
                                    <label class="form-label form-label-sm"><?= htmlspecialchars(t('route_editor.challenge_question'), ENT_QUOTES, 'UTF-8') ?></label>
                                    <input type="text" class="form-control form-control-sm" id="challengeTextQuestion" placeholder="<?= htmlspecialchars(t('route_editor.challenge_question_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label form-label-sm"><?= htmlspecialchars(t('route_editor.challenge_correct_answer'), ENT_QUOTES, 'UTF-8') ?></label>
                                    <input type="text" class="form-control form-control-sm" id="challengeTextAnswer" placeholder="<?= htmlspecialchars(t('route_editor.challenge_correct_answer_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <small class="text-muted"><?= htmlspecialchars(t('route_editor.challenge_similarity_hint'), ENT_QUOTES, 'UTF-8') ?></small>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary" onclick="saveNodeEdit()">
                                <i class="bi bi-check-lg"></i> <?= htmlspecialchars(t('route_editor.save_node'), ENT_QUOTES, 'UTF-8') ?>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="cancelNodeEdit()">
                                <?= htmlspecialchars(t('common.cancel'), ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="row mt-4">
            <div class="col">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1"><?= htmlspecialchars(t('route_editor.create_ready'), ENT_QUOTES, 'UTF-8') ?></h5>
                                <p class="text-muted mb-0"><?= htmlspecialchars(t('route_editor.create_ready_help'), ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars(t('route_editor.create_route'), ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="../../node_modules/jquery/dist/jquery.min.js"></script>
<script src="../../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../node_modules/leaflet/dist/leaflet.js"></script>
<script src="../../node_modules/summernote/dist/summernote-bs5.min.js"></script>
<script src="../../node_modules/summernote/dist/lang/summernote-<?= htmlspecialchars($summernote_locale, ENT_QUOTES, 'UTF-8') ?>.min.js"></script>
<script src="../../js/challenge-panel.js"></script>
<script>
    // Global variables
    let map;
    let markers = [];
    let nodes = [];
    const CAMPUS_CENTER = [63.1055, 21.5929];
    const translations = <?= HavuLocale::jsonNamespace('common', 'route_editor') ?>;
    const commonTranslations = translations.common;
    const routeEditorTranslations = translations.route_editor;
    const activeLocale = <?= json_encode(current_locale(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    window.challengePanelTranslations = routeEditorTranslations;

    function translate(template, params = {}) {
        return Object.entries(params).reduce(
            (value, [key, replacement]) => value.replaceAll(`:${key}`, replacement),
            template
        );
    }

    // Upload image or video file and insert into editor
    function uploadFile(file, type) {
        showUploadOverlay();
        const formData = new FormData();
        formData.append('file', file);

        $.ajax({
            url: '../../actions/upload-media.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                hideUploadOverlay();
                if (response.url) {
                    if (type === 'image') {
                        $('#node_content').summernote('focus');
                        $('#node_content').summernote('insertImage', response.url);
                    } else {
                        const videoHtml = `<video controls style="max-width:100%"><source src="${response.url}" type="${file.type}"></video><p></p>`;
                        $('#node_content').summernote('pasteHTML', videoHtml);
                    }
                } else {
                    alert(translate(routeEditorTranslations.upload_failed, {
                        message: response.error || commonTranslations.unknown_error
                    }));
                }
            },
            error: function(xhr) {
                hideUploadOverlay();
                const msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : commonTranslations.try_again;
                alert(translate(routeEditorTranslations.upload_failed, { message: msg }));
            }
        });
    }

    function showUploadOverlay() {
        $('#uploadOverlay').css({display: 'flex', opacity: 0}).animate({opacity: 1}, 150);
    }

    function hideUploadOverlay() {
        $('#uploadOverlay').animate({opacity: 0}, 300, function() {
            $(this).css('display', 'none');
        });
    }

    // Initialize Summernote editor
    function initEditor() {
        $('#node_content').summernote({
            lang: <?= json_encode($summernote_locale, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
            height: 220,
            toolbar: [
                ['style',  ['style']],
                ['font',   ['bold', 'italic', 'underline', 'clear']],
                ['para',   ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture', 'video', 'videoUpload']],
                ['view',   ['fullscreen', 'codeview']],
            ],
            buttons: {
                videoUpload: function(context) {
                    const ui = $.summernote.ui;
                    return ui.button({
                        contents: '<i class="bi bi-camera-video-fill"></i>',
                        tooltip: routeEditorTranslations.video_upload_tooltip,
                        click: function() {
                            const input = $('<input type="file" accept="video/mp4,video/webm,video/quicktime,video/x-m4v">');
                            input.on('change', function() {
                                if (this.files[0]) {
                                    uploadFile(this.files[0], 'video');
                                }
                            });
                            input.trigger('click');
                        }
                    }).render();
                }
            },
            callbacks: {
                onImageUpload: function(files) {
                    uploadFile(files[0], 'image');
                }
            }
        });
    }

    // Initialize map — center on user's location, fall back to campus
    function initMap() {
        map = L.map('map', { zoomControl: true }).setView(CAMPUS_CENTER, 15);

        const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        });

        const satelliteLayer = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles © Esri — Source: Esri, Maxar, Earthstar Geographics',
            maxZoom: 19
        });

        osmLayer.addTo(map);

        L.control.layers(
            { [commonTranslations.map]: osmLayer, [commonTranslations.satellite]: satelliteLayer },
            null,
            { position: 'topleft' }
        ).addTo(map);

        map.on('click', onMapClick);

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    map.setView([pos.coords.latitude, pos.coords.longitude], 15);
                },
                function() { /* denied or unavailable — stay on campus center */ }
            );
        }
    }

    // Location search via Nominatim
    let searchMarker = null;

    function searchLocation() {
        const query = document.getElementById('locationSearch').value.trim();
        if (!query) return;

        const btn = document.getElementById('locationSearchBtn');
        btn.disabled = true;
        btn.textContent = routeEditorTranslations.searching;

        fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=json&limit=5&countrycodes=fi`, {
            headers: { 'Accept-Language': activeLocale }
        })
        .then(r => r.json())
        .then(results => {
            const container = document.getElementById('searchResults');
            if (!results.length) {
                container.innerHTML = `<div class="list-group-item text-muted">${commonTranslations.no_results}</div>`;
                container.style.display = 'block';
                return;
            }
            container.innerHTML = results.map((r, i) =>
                `<button type="button" class="list-group-item list-group-item-action" data-idx="${i}"
                    data-lat="${r.lat}" data-lon="${r.lon}">
                    <i class="bi bi-geo-alt me-1"></i>${r.display_name}
                </button>`
            ).join('');
            container.style.display = 'block';
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = commonTranslations.search;
        });
    }

    function setupSearch() {
        document.getElementById('locationSearchBtn').addEventListener('click', searchLocation);
        document.getElementById('locationSearch').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); searchLocation(); }
        });

        document.getElementById('searchResults').addEventListener('click', function(e) {
            const btn = e.target.closest('button[data-lat]');
            if (!btn) return;

            const lat = parseFloat(btn.dataset.lat);
            const lon = parseFloat(btn.dataset.lon);

            map.setView([lat, lon], 16);

            if (searchMarker) map.removeLayer(searchMarker);
            searchMarker = L.marker([lat, lon], { opacity: 0.6 }).addTo(map)
                .bindPopup(btn.textContent.trim()).openPopup();

            document.getElementById('searchResults').style.display = 'none';
            document.getElementById('locationSearch').value = '';
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#locationSearch') && !e.target.closest('#locationSearchBtn') && !e.target.closest('#searchResults')) {
                document.getElementById('searchResults').style.display = 'none';
            }
        });
    }

    // Handle map click
    function onMapClick(e) {
        addNode(e.latlng.lat, e.latlng.lng);
    }

    // Add a new node
    function addNode(lat, lng, title = '', content = '', challenge_data = null) {
        const nodeIndex = nodes.length;
        const nodeNumber = nodeIndex + 1;

        const node = {
            lat: lat,
            lng: lng,
            title: title || translate(routeEditorTranslations.default_node_title, { number: String(nodeNumber) }),
            content: content,
            challenge_data: challenge_data
        };

        nodes.push(node);

        const marker = L.marker([lat, lng], {
            draggable: true,
            icon: createNumberedIcon(nodeNumber)
        }).addTo(map);

        marker.nodeIndex = nodeIndex;
        marker.bindPopup(buildPopupContent(node));

        marker.on('dragend', function(e) {
            const newPos = e.target.getLatLng();
            nodes[marker.nodeIndex].lat = newPos.lat;
            nodes[marker.nodeIndex].lng = newPos.lng;
            updateNodesList();
        });

        marker.on('click', function() {
            editNode(marker.nodeIndex);
        });

        markers.push(marker);

        updateNodesList();
        updatePolyline();

        if (!title) {
            editNode(nodeIndex);
        }
    }

    function buildPopupContent(node) {
        return `<b>${escapeHtml(node.title)}</b><div class="mt-1">${node.content || `<em>${routeEditorTranslations.node_no_content}</em>`}</div>`;
    }

    // Create numbered icon for markers
    function createNumberedIcon(number) {
        return L.divIcon({
            className: 'custom-div-icon',
            html: `<div style="background-color: #0d6efd; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">${number}</div>`,
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });
    }

    // Update polyline connecting nodes
    let polyline = null;
    function updatePolyline() {
        if (polyline) {
            map.removeLayer(polyline);
        }

        if (nodes.length > 1) {
            const latlngs = nodes.map(node => [node.lat, node.lng]);
            polyline = L.polyline(latlngs, {
                color: '#0d6efd',
                weight: 3,
                opacity: 0.7,
                dashArray: '10, 5'
            }).addTo(map);
        }
    }

    // Strip HTML tags for plain-text previews
    function stripHtml(html) {
        const div = document.createElement('div');
        div.innerHTML = html;
        return div.textContent || div.innerText || '';
    }

    // Update nodes list display
    function updateNodesList() {
        const nodesList = document.getElementById('nodesList');
        const nodeCount = document.getElementById('nodeCount');

        nodeCount.textContent = nodes.length;

        if (nodes.length === 0) {
            nodesList.innerHTML = `
                <div class="node-list-empty">
                    <i class="bi bi-cursor-fill" style="font-size: 3rem;"></i>
                    <p class="mt-3">${routeEditorTranslations.nodes_empty}</p>
                </div>
            `;
            return;
        }

        nodesList.innerHTML = nodes.map((node, index) => `
            <div class="list-group-item node-item">
                <div class="d-flex align-items-start gap-3">
                    <div class="node-number">${index + 1}</div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${escapeHtml(node.title)}</h6>
                        <p class="mb-1 small text-muted">${escapeHtml(stripHtml(node.content)) || `<em>${routeEditorTranslations.node_no_content}</em>`}</p>
                        <small class="text-muted">
                            <i class="bi bi-geo-alt"></i> ${node.lat.toFixed(6)}, ${node.lng.toFixed(6)}
                        </small>
                    </div>
                    <div class="d-flex flex-column gap-1">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editNode(${index})" title="${routeEditorTranslations.node_action_edit}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteNode(${index})" title="${routeEditorTranslations.node_action_delete}">
                            <i class="bi bi-trash"></i>
                        </button>
                        ${index > 0 ? `<button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveNodeUp(${index})" title="${routeEditorTranslations.node_action_move_up}">
                            <i class="bi bi-arrow-up"></i>
                        </button>` : ''}
                        ${index < nodes.length - 1 ? `<button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveNodeDown(${index})" title="${routeEditorTranslations.node_action_move_down}">
                            <i class="bi bi-arrow-down"></i>
                        </button>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Edit node
    function editNode(index) {
        const node = nodes[index];
        const editor = document.getElementById('nodeEditor');

        document.getElementById('editNodeIndex').value = index;
        document.getElementById('node_title').value = node.title;
        document.getElementById('node_lat').value = node.lat.toFixed(6);
        document.getElementById('node_lng').value = node.lng.toFixed(6);
        $('#node_content').summernote('code', node.content || '');
        setChallengeData(node.challenge_data || null);

        editor.style.display = 'block';
        editor.scrollIntoView({ behavior: 'smooth' });
    }

    // Save node edit
    function saveNodeEdit() {
        const index = parseInt(document.getElementById('editNodeIndex').value);
        const title = document.getElementById('node_title').value.trim();
        const content = $('#node_content').summernote('code');

        if (!title) {
            alert(routeEditorTranslations.node_name_required);
            return;
        }

        nodes[index].title = title;
        nodes[index].content = content;
        nodes[index].challenge_data = getChallengeData();

        markers[index].setPopupContent(buildPopupContent(nodes[index]));

        updateNodesList();
        cancelNodeEdit();
    }

    // Cancel node edit
    function cancelNodeEdit() {
        document.getElementById('nodeEditor').style.display = 'none';
        document.getElementById('editNodeIndex').value = '';
        document.getElementById('node_title').value = '';
        $('#node_content').summernote('code', '');
        resetChallengePanel();
    }

    // Delete node
    function deleteNode(index) {
        if (!confirm(routeEditorTranslations.confirm_delete_node)) {
            return;
        }

        map.removeLayer(markers[index]);
        nodes.splice(index, 1);
        markers.splice(index, 1);

        markers.forEach((marker, i) => {
            marker.setIcon(createNumberedIcon(i + 1));
            marker.nodeIndex = i;
        });

        updateNodesList();
        updatePolyline();
        cancelNodeEdit();
    }

    // Move node up
    function moveNodeUp(index) {
        if (index === 0) return;

        [nodes[index], nodes[index - 1]] = [nodes[index - 1], nodes[index]];
        [markers[index], markers[index - 1]] = [markers[index - 1], markers[index]];

        markers[index].setIcon(createNumberedIcon(index + 1));
        markers[index].nodeIndex = index;
        markers[index - 1].setIcon(createNumberedIcon(index));
        markers[index - 1].nodeIndex = index - 1;

        updateNodesList();
        updatePolyline();
    }

    // Move node down
    function moveNodeDown(index) {
        if (index === nodes.length - 1) return;

        [nodes[index], nodes[index + 1]] = [nodes[index + 1], nodes[index]];
        [markers[index], markers[index + 1]] = [markers[index + 1], markers[index]];

        markers[index].setIcon(createNumberedIcon(index + 1));
        markers[index].nodeIndex = index;
        markers[index + 1].setIcon(createNumberedIcon(index + 2));
        markers[index + 1].nodeIndex = index + 1;

        updateNodesList();
        updatePolyline();
    }

    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Handle form submission
    document.getElementById('routeForm').addEventListener('submit', function(e) {
        if (nodes.length === 0) {
            e.preventDefault();
            alert(routeEditorTranslations.create_need_node);
            return false;
        }

        document.getElementById('nodesData').value = JSON.stringify(nodes);
        return true;
    });

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        initMap();
        initEditor();
        setupSearch();

        const today = new Date().toISOString().split('T')[0];
        document.getElementById('publication_date').value = today;
    });
</script>
<?php require_once '../../includes/_feedback_widget.php'; ?>
</body>
</html>
