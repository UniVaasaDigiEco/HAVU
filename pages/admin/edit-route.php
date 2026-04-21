<?php
require_once('../../vendor/autoload.php');
require_once('../../classes/tools.class.php');
require_once('../../classes/security.class.php');
require_once('../../classes/message.class.php');
Security::initSession();

try {
    if (empty($_SESSION['user_public_id'])) {
        throw new Exception('User not authenticated');
    }
    $user = Tools::getUserWithPublicId($_SESSION['user_public_id']);
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

$user_routes = $user->getCreatedRoutes();
$selected_route_public_id = $_GET['route_public_id'] ?? '';
$route_data = null;
$error = null;

if (!empty($selected_route_public_id)) {
    try {
        $selected_route = Tools::getRouteByPublicId($selected_route_public_id);
        $candidate_route_data = $selected_route->toArray();

        if (($candidate_route_data['user_id'] ?? '') !== $_SESSION['user_public_id']) {
            throw new Exception('You can only edit your own routes.');
        }

        $route_data = $candidate_route_data;
    } catch (Exception $e) {
        $error = 'Failed to load route: ' . $e->getMessage();
    }
}

$route_data_json = json_encode($route_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAVU - Muokkaa reittiä</title>
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
</head>
<body class="admin-dashboard">
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-pencil-fill me-2"></i>Muokkaa reittiä</h2>
                    <p class="text-muted mb-0">Valitse reitti ja muokkaa sen tietoja ja rasteja</p>
                </div>
                <a href="dashboard.php" class="btn btn-warning">
                    <i class="bi bi-arrow-left"></i> Takaisin hallintapaneeliin
                </a>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-folder2-open me-2"></i>Valitse muokattava reitti</h5>
        </div>
        <div class="card-body">
            <form id="routeSelectForm" method="GET" class="row g-3 align-items-end">
                <div class="col-lg-8">
                    <label for="route_public_id" class="form-label">Omat reitit</label>
                    <select class="form-select" id="route_public_id" name="route_public_id">
                        <option value="">-- Valitse reitti --</option>
                        <?php foreach ($user_routes as $route): ?>
                            <?php
                            $route_public_id = $route->getPublicId();
                            $is_selected = $route_public_id === $selected_route_public_id ? 'selected' : '';
                            ?>
                            <option value="<?= htmlspecialchars($route_public_id, ENT_QUOTES, 'UTF-8') ?>" <?= $is_selected ?>>
                                <?= htmlspecialchars($route->getTitle(), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-arrow-repeat me-2"></i>Lataa reitti
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php echo Message::displayFlashMessages(); ?>

    <form id="routeForm" method="POST" action="../../actions/update-route.php">
        <input type="hidden" name="route_public_id" value="<?= htmlspecialchars($selected_route_public_id, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="nodes_data" id="nodesData">

        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle-fill me-2"></i>Reitin tiedot</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="route_title" class="form-label">Reitin nimi <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                id="route_title"
                                name="route_title"
                                required
                                value="<?= htmlspecialchars($route_data['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </div>

                        <div class="mb-3">
                            <label for="route_description" class="form-label">Reitin kuvaus</label>
                            <textarea class="form-control" id="route_description" name="route_description" rows="4"><?= htmlspecialchars($route_data['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_published" name="is_published" aria-describedby="public-help" <?= !isset($route_data) || !empty($route_data['is_published']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_published">
                                    Julkinen
                                </label><br>
                                <small id="public-help">Jos haluat, että reitti näkyy ja on pelattavissa kaikille pelaajille, merkkaa reitti julkiseksi</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="publication_date" class="form-label">Julkaisupäivämäärä <span class="text-danger">*</span></label>
                            <input
                                type="date"
                                class="form-control"
                                id="publication_date"
                                name="publication_date"
                                required
                                value="<?= htmlspecialchars($route_data['publication_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-geo-alt-fill me-2"></i>Rasti(t) (<span id="nodeCount">0</span>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="nodesList" class="list-group list-group-flush">
                            <div class="node-list-empty">
                                <i class="bi bi-cursor-fill" style="font-size: 3rem;"></i>
                                <p class="mt-3">Klikkaa paikkaa kartalla, lisätäksesi rastin reitille</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-map me-2"></i>Kartta</h5>
                    </div>
                    <div class="card-body">
                        <div id="map"></div>
                        <div class="mt-2 text-muted small">
                            <i class="bi bi-info-circle"></i> Klikkaa mitä tahansa kohtaa kartalla lisätäksesi rastin. Voit muuttaa rastin paikkaa vetämällä sitä, tai muokata rastin tietoja klikkaamalla rastia.
                        </div>
                    </div>
                </div>

                <div id="nodeEditor" class="card shadow-sm" style="display: none; position: relative;">
                    <div id="uploadOverlay" style="display:none; position:absolute; inset:0; z-index:10; background:rgba(255,255,255,0.88); border-radius:0.375rem; align-items:center; justify-content:center; flex-direction:column;">
                        <div class="spinner-border text-primary" style="width:2.5rem;height:2.5rem;" role="status">
                            <span class="visually-hidden">Ladataan...</span>
                        </div>
                        <p class="mt-3 mb-0 fw-semibold text-primary">Ladataan tiedostoa...</p>
                    </div>
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="bi bi-pencil-fill me-2"></i>Muokkaa rastia</h5>
                    </div>
                    <div class="card-body">
                        <input type="hidden" id="editNodeIndex">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="node_title" class="form-label">Rastin nimi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="node_title">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="node_lat" class="form-label">Leveyspiiri</label>
                                <input type="number" step="0.000001" class="form-control" id="node_lat" readonly>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="node_lng" class="form-label">Pituuspiiri</label>
                                <input type="number" step="0.000001" class="form-control" id="node_lng" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="node_content" class="form-label">Rastin sisältö</label>
                            <textarea id="node_content"></textarea>
                            <small>Lyhyt sisältö rastille. Voit lisätä kuvia ja videoita.</small>
                        </div>

                        <!-- Challenge panel -->
                        <div class="mb-3 p-3 rounded" style="border: 2px solid #ffc107;">
                            <label class="form-label fw-semibold mb-2">Haaste (valinnainen)</label>
                            <div class="d-flex gap-2 mb-3 flex-wrap">
                                <button type="button" class="btn btn-sm btn-warning" id="challengeTypeNone" onclick="setChallengeType('none')">Ei haastetta</button>
                                <button type="button" class="btn btn-sm btn-outline-warning" id="challengeTypeMC" onclick="setChallengeType('multiple_choice')">Monivalinta</button>
                                <button type="button" class="btn btn-sm btn-outline-warning" id="challengeTypeText" onclick="setChallengeType('text')">Tekstivastaus</button>
                            </div>
                            <div id="challengeMCFields" style="display:none;">
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Kysymys</label>
                                    <input type="text" class="form-control form-control-sm" id="challengeQuestion" placeholder="Kirjoita kysymys...">
                                </div>
                                <div id="challengeOptions"></div>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="addOptionBtn" onclick="addChallengeOption()">+ Lisää vaihtoehto</button>
                            </div>
                            <div id="challengeTextFields" style="display:none;">
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Kysymys</label>
                                    <input type="text" class="form-control form-control-sm" id="challengeTextQuestion" placeholder="Kirjoita kysymys...">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label form-label-sm">Oikea vastaus</label>
                                    <input type="text" class="form-control form-control-sm" id="challengeTextAnswer" placeholder="Oikea vastaus...">
                                </div>
                                <small class="text-muted">Vastaukset tarkistetaan automaattisella samanlaistuksella (~70 %).</small>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary" onclick="saveNodeEdit()">
                                <i class="bi bi-check-lg"></i> Tallenna rasti
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="cancelNodeEdit()">
                                Peruuta
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">Valmis päivittämään reitin?</h5>
                                <p class="text-muted mb-0">Tarkista reitin tiedot ja rastit, ja tallenna muutokset</p>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg" id="updateRouteBtn" <?= empty($selected_route_public_id) ? 'disabled' : '' ?>>
                                <i class="bi bi-save-fill me-2"></i> Tallenna muutokset
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
<script src="../../node_modules/summernote/dist/lang/summernote-fi-FI.min.js"></script>
<script src="../../js/challenge-panel.js"></script>
<script>
    let map;
    let markers = [];
    let nodes = [];
    let polyline = null;
    const CAMPUS_CENTER = [63.1055, 21.5929];
    const selectedRoutePublicId = <?= json_encode($selected_route_public_id, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const selectedRouteData = <?= $route_data_json ?: 'null' ?>;

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
                    alert('Lataus epäonnistui: ' + (response.error || 'Tuntematon virhe'));
                }
            },
            error: function(xhr) {
                hideUploadOverlay();
                const msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Yritä uudelleen.';
                alert('Lataus epäonnistui: ' + msg);
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
            lang: 'fi-FI',
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
                        tooltip: 'Lataa videotiedosto',
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
            { 'Kartta': osmLayer, 'Satelliitti': satelliteLayer },
            null,
            { position: 'topleft' }
        ).addTo(map);

        map.on('click', onMapClick);
    }

    function onMapClick(e) {
        if (!selectedRoutePublicId) {
            alert('Ole hyvä ja valitse reitti ensin.');
            return;
        }
        addNode(e.latlng.lat, e.latlng.lng);
    }

    function addNode(lat, lng, title = '', content = '', openEditor = true, challenge_data = null) {
        const nodeIndex = nodes.length;
        const nodeNumber = nodeIndex + 1;

        const node = {
            lat: Number(lat),
            lng: Number(lng),
            title: title || `Node ${nodeNumber}`,
            content: content || '',
            challenge_data: challenge_data
        };

        nodes.push(node);

        const marker = L.marker([lat, lng], {
            draggable: true,
            icon: createNumberedIcon(nodeNumber)
        }).addTo(map);

        marker.nodeIndex = nodeIndex;
        marker.bindPopup(buildPopupContent(node));

        marker.on('dragend', function(event) {
            const newPos = event.target.getLatLng();
            nodes[marker.nodeIndex].lat = newPos.lat;
            nodes[marker.nodeIndex].lng = newPos.lng;
            updateNodesList();
            updatePolyline();
        });

        marker.on('click', function() {
            editNode(marker.nodeIndex);
        });

        markers.push(marker);

        updateNodesList();
        updatePolyline();

        if (openEditor && !title) {
            editNode(nodeIndex);
        }
    }

    function buildPopupContent(node) {
        return `<b>${escapeHtml(node.title)}</b><div class="mt-1">${node.content || '<em>Ei sisältöä</em>'}</div>`;
    }

    function createNumberedIcon(number) {
        return L.divIcon({
            className: 'custom-div-icon',
            html: `<div style="background-color: #0d6efd; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">${number}</div>`,
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });
    }

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

    function stripHtml(html) {
        const div = document.createElement('div');
        div.innerHTML = html;
        return div.textContent || div.innerText || '';
    }

    function updateNodesList() {
        const nodesList = document.getElementById('nodesList');
        const nodeCount = document.getElementById('nodeCount');

        nodeCount.textContent = nodes.length;

        if (nodes.length === 0) {
            nodesList.innerHTML = `
                <div class="node-list-empty">
                    <i class="bi bi-cursor-fill" style="font-size: 3rem;"></i>
                    <p class="mt-3">Klikkaa paikkaa kartalla, lisätäksesi rastin reitille</p>
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
                        <p class="mb-1 small text-muted">${escapeHtml(stripHtml(node.content)) || '<em>Ei sisältöä</em>'}</p>
                        <small class="text-muted">
                            <i class="bi bi-geo-alt"></i> ${Number(node.lat).toFixed(6)}, ${Number(node.lng).toFixed(6)}
                        </small>
                    </div>
                    <div class="d-flex flex-column gap-1">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editNode(${index})" title="Muokkaa">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteNode(${index})" title="Poista">
                            <i class="bi bi-trash"></i>
                        </button>
                        ${index > 0 ? `<button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveNodeUp(${index})" title="Siirrä ylös">
                            <i class="bi bi-arrow-up"></i>
                        </button>` : ''}
                        ${index < nodes.length - 1 ? `<button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveNodeDown(${index})" title="Siirrä alas">
                            <i class="bi bi-arrow-down"></i>
                        </button>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
    }

    function editNode(index) {
        const node = nodes[index];
        const editor = document.getElementById('nodeEditor');

        document.getElementById('editNodeIndex').value = index;
        document.getElementById('node_title').value = node.title;
        document.getElementById('node_lat').value = Number(node.lat).toFixed(6);
        document.getElementById('node_lng').value = Number(node.lng).toFixed(6);
        $('#node_content').summernote('code', node.content || '');
        setChallengeData(node.challenge_data || null);

        editor.style.display = 'block';
        editor.scrollIntoView({ behavior: 'smooth' });
    }

    function saveNodeEdit() {
        const index = parseInt(document.getElementById('editNodeIndex').value, 10);
        const title = document.getElementById('node_title').value.trim();
        const content = $('#node_content').summernote('code');

        if (!title) {
            alert('Rastin nimi on pakollinen');
            return;
        }

        nodes[index].title = title;
        nodes[index].content = content;
        nodes[index].challenge_data = getChallengeData();
        markers[index].setPopupContent(buildPopupContent(nodes[index]));

        updateNodesList();
        cancelNodeEdit();
    }

    function cancelNodeEdit() {
        document.getElementById('nodeEditor').style.display = 'none';
        document.getElementById('editNodeIndex').value = '';
        document.getElementById('node_title').value = '';
        $('#node_content').summernote('code', '');
        resetChallengePanel();
    }

    function deleteNode(index) {
        if (!confirm('Oletko varma, että haluat poistaa tämän rastin?')) {
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

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function clearAllNodes() {
        markers.forEach(marker => map.removeLayer(marker));
        markers = [];
        nodes = [];
        updateNodesList();
        updatePolyline();
    }

    function loadSelectedRouteData() {
        if (!selectedRouteData) return;

        clearAllNodes();

        const routeNodes = (selectedRouteData.nodes || []).slice().sort((a, b) => a.order_number - b.order_number);

        routeNodes.forEach(routeNode => {
            const node = routeNode.node || {};
            addNode(node.latitude, node.longitude, node.title || '', node.content || '', false, node.challenge_data || null);
        });

        if (nodes.length > 0) {
            const bounds = L.latLngBounds(nodes.map(node => [node.lat, node.lng]));
            map.fitBounds(bounds, { padding: [30, 30] });
        }
    }

    document.getElementById('routeForm').addEventListener('submit', function(event) {
        if (!selectedRoutePublicId) {
            event.preventDefault();
            alert('Ole hyvä ja valitse reitti ensin.');
            return false;
        }

        if (nodes.length === 0) {
            event.preventDefault();
            alert('Ole hyvä ja lisää vähintään yksi rasti reitille.');
            return false;
        }

        document.getElementById('nodesData').value = JSON.stringify(nodes);
        return true;
    });

    document.addEventListener('DOMContentLoaded', function() {
        initMap();
        initEditor();

        if (selectedRouteData) {
            loadSelectedRouteData();
        } else {
            const publicationDateInput = document.getElementById('publication_date');
            if (publicationDateInput && !publicationDateInput.value) {
                publicationDateInput.value = new Date().toISOString().split('T')[0];
            }
        }
    });
</script>
</body>
</html>