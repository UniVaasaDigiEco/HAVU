<?php
require_once('../classes/tools.class.php');
require_once('../classes/security.class.php');
require_once('../classes/route.class.php');
require_once('../config/constants.php');

Security::initSession();
// Security headers to protect against common web attacks

// Prevent clickjacking - stops your site from being embedded in iframes
header('X-Frame-Options: DENY');

// Prevent MIME-sniffing - forces browser to respect declared content types
header('X-Content-Type-Options: nosniff');

// Enable browser's XSS filter (helps older browsers)
header('X-XSS-Protection: 1; mode=block');

// Control referrer information - prevents leaking sensitive URL data
header('Referrer-Policy: strict-origin-when-cross-origin');

// Control browser features - only allow geolocation on your domain
header('Permissions-Policy: geolocation=(self)');

// Force HTTPS for future visits (only set when on HTTPS)
// Since your server redirects to HTTPS, this ensures browsers remember to use it
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Content Security Policy - most powerful XSS protection
// Defines where resources can be loaded from
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://www.google.com https://www.gstatic.com; style-src 'self' 'unsafe-inline' https://www.gstatic.com; img-src 'self' data: https:; connect-src 'self' https:; frame-src https://www.youtube.com https://www.youtube-nocookie.com https://www.thinglink.com https://thinglink.com https://www.google.com https://recaptcha.google.com;");

// Hide PHP version for security through obscurity
header_remove('X-Powered-By');

$default_route_public_id = "417bef1b-1b00-46f5-ac85-4774ff20d0ed";

$is_logged_in = !empty($_SESSION['user_public_id']);
$route_public_id = isset($_GET['route']) ? trim((string)$_GET['route']) : '';
$date_locale = [
    'fi' => 'fi-FI',
    'en' => 'en-US',
    'sv' => 'sv-SE',
][current_locale()] ?? 'fi-FI';

$route = null;
$route_error_message = null;
if ($route_public_id !== '')
{
    try {
        $route = Tools::getRouteByPublicId($route_public_id);
    } catch (Exception $e) {
        $route_error_message = t('game.route_not_found', ['message' => $e->getMessage()]);
    }
} else {
    // If no route specified, load the default route
    try {
        $route = Tools::getRouteByPublicId($default_route_public_id);
    } catch (Exception $e) {
        $route_error_message = t('game.default_route_not_found', ['message' => $e->getMessage()]);
    }
}

$show_entry_choice_modal = !$is_logged_in && $route_public_id !== '';
$route_return_url = ROOT_DIR . 'pages/game.php?route=' . urlencode($route_public_id);
$entry_choice_login_url = ROOT_DIR . 'login.php?return_to=' . urlencode($route_return_url);
$entry_choice_register_url = ROOT_DIR . 'register.php?return_to=' . urlencode($route_return_url);

if (!$route) {
    ?>
    <!DOCTYPE html>
    <html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars(t('common.app_name'), ENT_QUOTES, 'UTF-8') ?></title>
        <link rel="icon" type="image/x-icon" href="../favicon.ico">
        <link href="../css/bs-custom.css" rel="stylesheet">
    </head>
    <body class="bg-light">
    <?php require_once '../includes/_language_switcher.php'; ?>
    <div class="container py-5">
        <div class="alert alert-danger"><?= $route_error_message ?></div>
        <a href="../index.php" class="btn btn-primary"><?= htmlspecialchars(t('common.back_to_home'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('common.app_name'), ENT_QUOTES, 'UTF-8') ?> - <?php echo htmlspecialchars($route->getTitle()); ?></title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">

    <!-- Bootstrap CSS -->
    <link href="../css/bs-custom.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/responsive-embeds.css">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="../node_modules/leaflet/dist/leaflet.css" />
    <!-- jQuery -->
    <script src="../node_modules/jquery/dist/jquery.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Leaflet JS -->
    <script src="../node_modules/leaflet/dist/leaflet.js"></script>
    <script src="../js/youtube-embed.js"></script>
    <script src="../js/message-widget.js"></script>

    <script>
        // Configuration
        const PROXIMITY_THRESHOLD = <?= json_encode(PROXIMITY_THRESHOLD, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>; // global fallback (meters)
        const MOBILE_BREAKPOINT = 767.98;
        const REQUIRE_GPS_PROXIMITY = <?= REQUIRE_GPS_PROXIMITY ? 'true' : 'false' ?>;
        const translations = <?= HavuLocale::jsonNamespace('common', 'game') ?>;
        const commonTranslations = translations.common;
        const gameTranslations = translations.game;
        const activeLocale = <?= json_encode($date_locale, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const phoneViewportQuery = window.matchMedia(`(max-width: ${MOBILE_BREAKPOINT}px)`);

        function translate(template, params = {}) {
            return Object.entries(params).reduce(
                (value, [key, replacement]) => value.replaceAll(`:${key}`, replacement),
                template
            );
        }

        // Session state
        const isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;
        const requiresEntryChoice = <?= $show_entry_choice_modal ? 'true' : 'false' ?>;

        // Load route data from PHP
        const routeData = <?php echo $route->toJavaScript(); ?>;
        if(!routeData){
            alert(gameTranslations.route_data_error);
            throw new Error("Route data is null or undefined");
        }
        console.log("Loaded route data:", routeData);

        // Use per-route GPS threshold, with fallback to global constant
        const ROUTE_GPS_THRESHOLD = (routeData.gps_threshold && routeData.gps_threshold >= 15 && routeData.gps_threshold <= 50)
            ? routeData.gps_threshold
            : PROXIMITY_THRESHOLD;

        // Default map center — falls back to configured default, overridden by route node average
        let DEFAULT_MAP_CENTER = <?= json_encode(DEFAULT_MAP_CENTER) ?>;

        // Calculate center from route nodes if available
        if (routeData.nodes && routeData.nodes.length > 0) {
            const lats = routeData.nodes.map(n => parseFloat(n.node.latitude));
            const lngs = routeData.nodes.map(n => parseFloat(n.node.longitude));
            const avgLat = lats.reduce((a, b) => a + b, 0) / lats.length;
            const avgLng = lngs.reduce((a, b) => a + b, 0) / lngs.length;
            DEFAULT_MAP_CENTER = [avgLat, avgLng];
        }

        let map = null;

        if(!routeData){
            console.error("Route data is null or undefined. Cannot initialize game.");
             alert(gameTranslations.route_data_error);
             throw new Error("Route data is null or undefined");
        }
        // Transform route data into the format expected by the game
        const routeNodes = routeData.nodes.map((nodeData, _index) => ({
            id: nodeData.node.id,
            name: nodeData.node.title,
            lat: parseFloat(nodeData.node.latitude),
            lng: parseFloat(nodeData.node.longitude),
            description: nodeData.node.content,
            challenge_data: nodeData.node.challenge_data || null,
            visited: false,
            inProximity: !REQUIRE_GPS_PROXIMITY,
            challengeDone: false,
            challengeError: false,
            order_number: nodeData.order_number
        }));

        // Store markers
        const markers = {};
        let userMarker = null;
        let userPosition = null;
        let routeLine = null;
        let routeLineVisible = false; // Default OFF
        let activeNodeId = null;
        const PLAYER_SAFETY_NOTICE_KEY = `player-safety-notice-dismissed-${routeData.public_id || 'default'}`;

        // Numbered marker badges: blue for unvisited, green for visited.
        function createNodeIcon(number, visited = false) {
            const colorClass = visited ? 'route-node-marker--visited' : 'route-node-marker--unvisited';
            return L.divIcon({
                className: 'route-node-marker-wrapper',
                html: `<div class="route-node-marker-badge ${colorClass}">${number}</div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 16],
                popupAnchor: [0, -16]
            });
        }

        const userIcon = L.icon({
            iconUrl: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij48Y2lyY2xlIGN4PSIxMiIgY3k9IjEyIiByPSIxMCIgZmlsbD0iIzAwNjZjYyIgb3BhY2l0eT0iMC4zIi8+PGNpcmNsZSBjeD0iMTIiIGN5PSIxMiIgcj0iNiIgZmlsbD0iIzAwNjZjYyIvPjxjaXJjbGUgY3g9IjEyIiBjeT0iMTIiIHI9IjMiIGZpbGw9IndoaXRlIi8+PC9zdmc+',
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });

        let gameInitialized = false;

        function setEntryChoiceUiLocked(locked) {
            const infoPanelToggle = document.getElementById('info-panel-toggle');
            const infoPanel = document.getElementById('info-panel');

            if (infoPanelToggle) {
                infoPanelToggle.style.display = locked ? 'none' : '';
                infoPanelToggle.setAttribute('aria-hidden', locked ? 'true' : 'false');
            }

            if (infoPanel) {
                if (locked) {
                    infoPanel.classList.remove('visible');
                }
                infoPanel.style.display = locked ? 'none' : '';
                infoPanel.setAttribute('aria-hidden', locked ? 'true' : 'false');
            }
        }

        function hasAnyVisitedCheckpoints() {
            return routeNodes.some(node => !!node.visited);
        }

        function shouldShowPlayerSafetyNotice() {
            try {
                return !hasAnyVisitedCheckpoints() && sessionStorage.getItem(PLAYER_SAFETY_NOTICE_KEY) !== '1';
            } catch (error) {
                return !hasAnyVisitedCheckpoints();
            }
        }

        function showPlayerSafetyNotice() {
            const modalElement = document.getElementById('playerSafetyNoticeModal');
            if (!modalElement) {
                return;
            }

            const modal = new bootstrap.Modal(modalElement, {
                backdrop: 'static',
                keyboard: false
            });

            modalElement.addEventListener('hide.bs.modal', function handleSafetyNoticeClose() {
                try {
                    sessionStorage.setItem(PLAYER_SAFETY_NOTICE_KEY, '1');
                } catch (error) {
                    // Ignore storage failures.
                }

                modalElement.removeEventListener('hide.bs.modal', handleSafetyNoticeClose);
            });

            modal.show();
        }

        function initializeGame() {
            if (gameInitialized) {
                return;
            }
            gameInitialized = true;

            // Ensure HUD controls are available once gameplay starts.
            setEntryChoiceUiLocked(false);

            // Initialize map
            map = L.map('map', { zoomControl: false, closePopupOnClick: false }).setView(DEFAULT_MAP_CENTER, 16);

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
            map.attributionControl.setPosition('topleft');

            L.control.layers(
                { [commonTranslations.map]: osmLayer, [commonTranslations.satellite]: satelliteLayer },
                null,
                { position: 'topleft' }
            ).addTo(map);

            drawRouteLine();
            initializeRouteLineToggle();
            initializeMarkers();
            syncMarkerPresentationBindings();
            updateProgressBarHeightVariable();
            bindRecenterMapButton();
            updateProgress();
            initGPS();

            window.addEventListener('resize', handleViewportChange);
            if (phoneViewportQuery.addEventListener) {
                phoneViewportQuery.addEventListener('change', handleViewportChange);
            } else {
                phoneViewportQuery.addListener(handleViewportChange);
            }

            if (window.visualViewport) {
                window.visualViewport.addEventListener('resize', handleViewportChange);
            }

            const syncLayout = function() {
                updateProgressBarHeightVariable();
                map.invalidateSize();
            };

            syncLayout();
            requestAnimationFrame(syncLayout);
            setTimeout(syncLayout, 250);
            window.addEventListener('load', syncLayout, { once: true });
            window.addEventListener('pageshow', syncLayout);

            if (shouldShowPlayerSafetyNotice()) {
                setTimeout(showPlayerSafetyNotice, 250);
            }
        }

        function bindInfoPanelActions() {
            const messageCreatorBtn = document.getElementById('message-creator-btn');
            const reportBugBtn = document.getElementById('report-bug-btn');

            if (messageCreatorBtn) {
                messageCreatorBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    hideInfoPanel();
                    openMessageModal(routeData.id, routeData.title);
                });
            }

            if (reportBugBtn) {
                reportBugBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    hideInfoPanel();
                    openFeedbackModal('bug');
                });
            }
        }

        // Initialize everything
        $(document).ready(function() {
            bindInfoPanelActions();

            if (!requiresEntryChoice) {
                initializeGame();
                return;
            }

            setEntryChoiceUiLocked(true);

            const entryChoiceModalElement = document.getElementById('route-entry-choice-modal');
            const playAnonymouslyButton = document.getElementById('play-anonymously-btn');

            if (!entryChoiceModalElement || !playAnonymouslyButton) {
                setEntryChoiceUiLocked(false);
                initializeGame();
                return;
            }

            const entryChoiceModal = new bootstrap.Modal(entryChoiceModalElement, {
                backdrop: 'static',
                keyboard: false
            });

            playAnonymouslyButton.addEventListener('click', function() {
                this.disabled = true;
                setEntryChoiceUiLocked(false);
                initializeGame();
                entryChoiceModal.hide();
            });

            entryChoiceModal.show();
        });

        // Draw route line
        function drawRouteLine() {
            const latlngs = routeNodes.map(node => [node.lat, node.lng]);

            if (routeLine) {
                map.removeLayer(routeLine);
            }

            routeLine = L.polyline(latlngs, {
                color: '#0066cc',
                weight: 3,
                opacity: 0.6,
                dashArray: '10, 10'
            }).addTo(map);

            if (!routeLineVisible) {
                map.removeLayer(routeLine);
            }
        }

        function setRouteLineVisibility(visible) {
            routeLineVisible = visible;

            if (!routeLine) {
                return;
            }

            if (visible) {
                if (!map.hasLayer(routeLine)) {
                    map.addLayer(routeLine);
                }
            } else if (map.hasLayer(routeLine)) {
                map.removeLayer(routeLine);
            }
        }

        function initializeRouteLineToggle() {
            const toggle = document.getElementById('route-line-toggle');
            if (!toggle) {
                return;
            }
            // Only allow toggle if allowed by route
            if (routeData.allow_route_line) {
                toggle.checked = routeLineVisible;
                toggle.disabled = false;
                toggle.style.display = '';
                toggle.addEventListener('change', function () {
                    setRouteLineVisibility(this.checked);
                });
            }
        }

        // Escape HTML for safe insertion
        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str || '';
            return div.innerHTML;
        }

        function triggerCheckpointPopupVibration() {
            if (!('vibrate' in navigator)) {
                return;
            }

            // Short, subtle haptic pulse when a nearby checkpoint popup is auto-opened.
            navigator.vibrate(120);
        }

        // Levenshtein similarity: 1 - (distance / max(len_a, len_b))
        function levenshteinSimilarity(a, b) {
            if (!a.length || !b.length) return 0;
            const m = a.length, n = b.length;
            const dp = [];
            for (let i = 0; i <= m; i++) {
                dp[i] = [i];
                for (let j = 1; j <= n; j++) dp[i][j] = i === 0 ? j : 0;
            }
            for (let i = 1; i <= m; i++) {
                for (let j = 1; j <= n; j++) {
                    dp[i][j] = a[i-1] === b[j-1]
                        ? dp[i-1][j-1]
                        : 1 + Math.min(dp[i-1][j-1], dp[i-1][j], dp[i][j-1]);
                }
            }
            return 1 - dp[m][n] / Math.max(m, n);
        }

        function isPhoneViewport() {
            return phoneViewportQuery.matches;
        }

        function getNodeLabel(node) {
            const nodeIndex = routeNodes.indexOf(node);
            return nodeIndex === 0 ? gameTranslations.start_label : (nodeIndex === routeNodes.length - 1 ? gameTranslations.finish_label : '');
        }

        // Build node HTML for both desktop popups and the phone bottom sheet
        function buildNodeContent(node) {
            const nodeLabel = getNodeLabel(node);
            const canCheckin = !REQUIRE_GPS_PROXIMITY || (node.inProximity && (!node.challenge_data || node.challengeDone));

            let challengeHtml = '';
            if (node.challenge_data) {
                if (node.challengeDone) {
                    challengeHtml = `<div class="alert alert-success py-2 mt-2 mb-1">${gameTranslations.challenge_completed}</div>`;
                } else {
                    const badgeHtml = node.inProximity
                        ? `<span class="badge bg-success mb-2">${gameTranslations.in_range}</span>`
                        : `<span class="badge bg-secondary mb-2">${gameTranslations.move_closer}</span>`;

                    let inputHtml = '';
                    if (node.challenge_data.type === 'multiple_choice') {
                        inputHtml = node.challenge_data.options.map((opt, i) =>
                            `<div class="form-check">
                                <input class="form-check-input" type="radio" name="mc-${node.id}" value="${i}" id="mc-${node.id}-${i}" ${node.inProximity ? '' : 'disabled'}>
                                <label class="form-check-label" for="mc-${node.id}-${i}">${escapeHtml(opt)}</label>
                            </div>`
                        ).join('');
                    } else if (node.challenge_data.type === 'text') {
                        inputHtml = `<input type="text" class="form-control form-control-sm mb-2" id="text-answer-${node.id}" placeholder="${gameTranslations.text_answer_placeholder}" ${node.inProximity ? '' : 'disabled'}>`;
                    }

                    challengeHtml = `
                        <div class="challenge-area mt-2 border-top pt-2">
                            ${badgeHtml}
                            <p class="fw-semibold mb-2">${escapeHtml(node.challenge_data.question)}</p>
                            ${inputHtml}
                            <button class="btn btn-sm btn-outline-primary" onclick="checkChallengeAnswer(${node.id})" ${node.inProximity ? '' : 'disabled'}>${gameTranslations.check_answer}</button>
                            <div class="challenge-error-msg text-danger small mt-1" style="display:none;">${gameTranslations.wrong_answer}</div>
                        </div>`;
                }
            }

            return `
                <div class="node-popup" id="node-body-${node.id}">
                    ${nodeLabel ? `<div class="text-center mb-2"><strong>${nodeLabel}</strong></div>` : ''}
                    <h5>${escapeHtml(node.name)}</h5>
                    <div class="mb-2">${window.HavuYouTubeEmbed.wrapRichContent(node.description)}</div>
                    ${challengeHtml}
                    <button class="checkin-btn btn btn-sm ${canCheckin ? 'btn-success' : 'btn-secondary'} mt-2"
                            onclick="markAsVisited(${node.id})"
                            ${canCheckin ? '' : 'disabled'}>
                        ${gameTranslations.mark_visited}
                    </button>
                </div>`;
        }

        // Verify challenge answer for a node
        window.checkChallengeAnswer = function(nodeId) {
            const node = routeNodes.find(n => n.id === nodeId);
            if (!node || !node.challenge_data || node.challengeDone) return;

            let isCorrect = false;

            if (node.challenge_data.type === 'multiple_choice') {
                const checked = document.querySelector(`input[name="mc-${nodeId}"]:checked`);
                if (!checked) return;
                isCorrect = parseInt(checked.value, 10) === node.challenge_data.correct_index;
            } else if (node.challenge_data.type === 'text') {
                const input = document.getElementById(`text-answer-${nodeId}`);
                if (!input || !input.value.trim()) return;
                isCorrect = levenshteinSimilarity(
                    input.value.trim().toLowerCase(),
                    node.challenge_data.answer.trim().toLowerCase()
                ) >= 0.70;
            }

            const body = document.getElementById('node-body-' + nodeId);

            if (isCorrect) {
                node.challengeDone = true;
                node.challengeError = false;
                refreshNodePresentation(nodeId);
                window.markAsVisited(nodeId);
            } else {
                node.challengeError = true;
                if (body) {
                    const errorMsg = body.querySelector('.challenge-error-msg');
                    if (errorMsg) errorMsg.style.display = 'block';
                }
            }
        };

        function getMobileNodeSheet() {
            return document.getElementById('mobile-node-sheet');
        }

        function isMobileNodeSheetVisible() {
            const sheet = getMobileNodeSheet();
            return !!sheet && sheet.classList.contains('visible');
        }

        function renderMobileNodeSheet(nodeId) {
            const node = routeNodes.find(n => n.id === nodeId);
            const sheetBody = document.getElementById('mobile-node-sheet-body');

            if (!node || !sheetBody) {
                return;
            }

            sheetBody.innerHTML = buildNodeContent(node);
        }

        function openMobileNodeSheet(nodeId) {
            const sheet = getMobileNodeSheet();
            if (!sheet) {
                return;
            }

            activeNodeId = nodeId;
            renderMobileNodeSheet(nodeId);
            sheet.classList.add('visible');
        }

        function closeMobileNodeSheet(clearActiveNode = true) {
            const sheet = getMobileNodeSheet();
            if (!sheet) {
                return;
            }

            sheet.classList.remove('visible');

            if (clearActiveNode) {
                activeNodeId = null;
            }
        }

        function syncMarkerPresentationBindings() {
            routeNodes.forEach(node => {
                const marker = markers[node.id];
                if (!marker) {
                    return;
                }

                if (isPhoneViewport()) {
                    if (marker.getPopup()) {
                        marker.unbindPopup();
                    }
                } else {
                    const content = buildNodeContent(node);
                    if (marker.getPopup()) {
                        marker.setPopupContent(content);
                    } else {
                        marker.bindPopup(content);
                    }
                }
            });
        }

        function refreshNodePresentation(nodeId) {
            const marker = markers[nodeId];
            const node = routeNodes.find(n => n.id === nodeId);

            if (!marker || !node) {
                return;
            }

            if (isPhoneViewport()) {
                if (activeNodeId === nodeId && isMobileNodeSheetVisible()) {
                    renderMobileNodeSheet(nodeId);
                }
            } else {
                const content = buildNodeContent(node);
                if (marker.getPopup()) {
                    marker.setPopupContent(content);
                } else {
                    marker.bindPopup(content);
                }
            }
        }

        function openNodePresentation(nodeId) {
            const node = routeNodes.find(n => n.id === nodeId);
            const marker = markers[nodeId];

            if (!node || !marker) {
                return;
            }

            activeNodeId = nodeId;

            if (isPhoneViewport()) {
                map.closePopup();
                openMobileNodeSheet(nodeId);
                return;
            }

            refreshNodePresentation(nodeId);
            marker.openPopup();
        }

        function closeNodePresentation(clearActiveNode = true) {
            map.closePopup();
            closeMobileNodeSheet(clearActiveNode);

            if (clearActiveNode && !isPhoneViewport()) {
                activeNodeId = null;
            }
        }

        function updateProgressBarHeightVariable() {
            const progressContainer = document.querySelector('.progress-container');
            if (!progressContainer) {
                return;
            }

            document.documentElement.style.setProperty('--progress-bar-height', `${progressContainer.offsetHeight}px`);
        }

        function updateRecenterButtonState() {
            const button = document.getElementById('recenter-map-btn');
            if (!button) {
                return;
            }

            const hasUserPosition = !!userPosition;
            button.disabled = !hasUserPosition;
            button.setAttribute('aria-disabled', hasUserPosition ? 'false' : 'true');
        }

        function bindRecenterMapButton() {
            const button = document.getElementById('recenter-map-btn');
            if (!button) {
                return;
            }

            button.addEventListener('click', function() {
                if (!map || !userPosition) {
                    return;
                }

                map.setView([userPosition.lat, userPosition.lng], Math.max(map.getZoom(), 16), { animate: true });
            });

            updateRecenterButtonState();
        }

        function handleViewportChange() {
            const activeElement = document.activeElement;
            const isTypingInChallengeInput = !!activeElement
                && ['INPUT', 'TEXTAREA', 'SELECT'].includes(activeElement.tagName)
                && (
                    !!activeElement.closest('#mobile-node-sheet')
                    || !!activeElement.closest('.leaflet-popup-content')
                );

            // Mobile keyboards fire resize events; avoid rebuilding node UI while the user is typing.
            if (isTypingInChallengeInput) {
                updateProgressBarHeightVariable();
                return;
            }

            const selectedNodeId = activeNodeId;
            const hadVisibleMobileSheet = isMobileNodeSheetVisible();

            updateProgressBarHeightVariable();
            syncMarkerPresentationBindings();

            if (isPhoneViewport()) {
                map.closePopup();
                if (selectedNodeId !== null) {
                    openMobileNodeSheet(selectedNodeId);
                }
                return;
            }

            closeMobileNodeSheet(false);
            if (selectedNodeId !== null) {
                refreshNodePresentation(selectedNodeId);
                if (hadVisibleMobileSheet && markers[selectedNodeId]) {
                    markers[selectedNodeId].openPopup();
                }
            }
        }

        // Create markers for all nodes
        function initializeMarkers() {
            routeNodes.forEach((node, index) => {
                const marker = L.marker([node.lat, node.lng], {
                    icon: createNodeIcon(index + 1, node.visited),
                    title: node.name
                }).addTo(map);

                if (!isPhoneViewport()) {
                    marker.bindPopup(buildNodeContent(node));
                }

                marker.on('click', function() {
                    if (isPhoneViewport()) {
                        openNodePresentation(node.id);
                    } else {
                        activeNodeId = node.id;
                        refreshNodePresentation(node.id);
                    }
                });

                marker.on('popupclose', function() {
                    if (!isPhoneViewport() && activeNodeId === node.id) {
                        activeNodeId = null;
                    }
                });

                markers[node.id] = marker;
            });
        }

        // Calculate distance between two coordinates (Haversine formula)
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371e3; // Earth's radius in meters
            const phi1 = lat1 * Math.PI / 180;
            const phi2 = lat2 * Math.PI / 180;
            const dPhi = (lat2 - lat1) * Math.PI / 180;
            const dLambda = (lon2 - lon1) * Math.PI / 180;

            const a = Math.sin(dPhi / 2) * Math.sin(dPhi / 2) +
                Math.cos(phi1) * Math.cos(phi2) *
                Math.sin(dLambda / 2) * Math.sin(dLambda / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

            return R * c; // Distance in meters
        }

        // Check proximity to nodes and update per-node state
        function checkProximity(userLat, userLng) {
            let nearestNode = null;
            let nearestDistance = Infinity;

            routeNodes.forEach(node => {
                if (!node.visited) {
                    const distance = calculateDistance(userLat, userLng, node.lat, node.lng);

                    if (distance < nearestDistance) {
                        nearestNode = node;
                        nearestDistance = distance;
                    }

                    if (REQUIRE_GPS_PROXIMITY) {
                        const wasInProximity = node.inProximity;
                        node.inProximity = distance < ROUTE_GPS_THRESHOLD;

                        if (node.inProximity !== wasInProximity) {
                            refreshNodePresentation(node.id);
                        }
                    }
                }
            });

            if (nearestNode && nearestDistance < ROUTE_GPS_THRESHOLD) {
                const nearestMarker = markers[nearestNode.id];
                const desktopPopupOpen = nearestMarker && nearestMarker.getPopup() && nearestMarker.isPopupOpen();
                const phoneSheetOpen = activeNodeId === nearestNode.id && isMobileNodeSheetVisible();

                if (!desktopPopupOpen && !phoneSheetOpen) {
                    triggerCheckpointPopupVibration();
                    openNodePresentation(nearestNode.id);
                }
            }

            // Update distance info panel
            if (nearestNode) {
                const inRange = nearestDistance < ROUTE_GPS_THRESHOLD;
                $('#distance-info').html(`
                    <div class="alert ${inRange ? 'alert-success' : 'alert-info'} mb-0 py-2">
                        <strong>${inRange ? gameTranslations.in_range : gameTranslations.next}</strong><br>
                        <small>${escapeHtml(nearestNode.name)}<br>
                        ${translate(commonTranslations.distance_meters, { count: String(Math.round(nearestDistance)) })}</small>
                    </div>
                `);
            }
        }

        // Update user position on map
        function updateUserPosition(lat, lng) {
            userPosition = { lat, lng };

            if (userMarker) {
                userMarker.setLatLng([lat, lng]);
            } else {
                userMarker = L.marker([lat, lng], {
                    icon: userIcon,
                    title: commonTranslations.your_location
                }).addTo(map);
            }

            updateRecenterButtonState();
            checkProximity(lat, lng);
        }

        // Send visit to server for logged-in players
        function trackVisit(nodeId) {
            if (!isLoggedIn) return;
            $.ajax({
                url: '../actions/track-visit.php',
                type: 'POST',
                data: { node_id: nodeId, route_public_id: routeData.public_id },
                error: function() {
                    console.warn('Visit tracking failed for node', nodeId);
                }
            });
        }

        // Mark node as visited
        window.markAsVisited = function(nodeId) {
            const node = routeNodes.find(n => n.id === nodeId);
            if (node && !node.visited) {
                // Guard: proximity and challenge must be satisfied
                if (REQUIRE_GPS_PROXIMITY && !node.inProximity) return;
                if (node.challenge_data && !node.challengeDone) return;

                node.visited = true;
                trackVisit(nodeId);
                try {
                    sessionStorage.setItem(PLAYER_SAFETY_NOTICE_KEY, '1');
                } catch (error) {
                    // Ignore storage failures.
                }
                const markerIndex = routeNodes.findIndex(n => n.id === nodeId);
                const markerNumber = markerIndex >= 0 ? markerIndex + 1 : '?';
                markers[nodeId].setIcon(createNodeIcon(markerNumber, true));
                updateProgress();

                // Close the current presentation and show acorn animation
                closeNodePresentation();

                // Create acorn celebration element
                const acornDiv = document.createElement('div');
                acornDiv.className = 'acorn-celebration';
                acornDiv.innerHTML = `
                    <img src="../images/acorn.png" alt="Acorn">
                    <div class="acorn-celebration__text">${escapeHtml(gameTranslations.checkpoint_found_message || 'Congratulations!\nYou found a checkpoint!')}</div>
                `;
                document.body.appendChild(acornDiv);

                // Remove acorn after animation completes
                setTimeout(() => {
                    acornDiv.remove();
                }, 4000);
            }
        };

        // Update progress bar
        function updateProgress() {
            const totalNodes = routeNodes.length;
            const visitedNodes = routeNodes.filter(n => n.visited).length;
            const percentage = (visitedNodes / totalNodes) * 100;

            $('#progress-text').text(translate(gameTranslations.progress_text, { visited: String(visitedNodes), total: String(totalNodes) }));
            $('#progress-bar').css('width', percentage + '%');

            // Update acorn count with animation
            const $acornCount = $('#acorn-count');
            const oldCount = parseInt($acornCount.text()) || 0;
            if (visitedNodes > oldCount) {
                $acornCount.addClass('bump');
                setTimeout(() => $acornCount.removeClass('bump'), 500);
            }
            $acornCount.text(visitedNodes);

            if (visitedNodes === totalNodes) {
                setTimeout(() => showCompletionScreen(visitedNodes), 4000);
            }
        }

        function showCompletionScreen(acornCount) {
            const dateStr = new Date().toLocaleDateString(activeLocale, {day: 'numeric', month: 'numeric', year: 'numeric'});

            document.getElementById('completion-route-name').textContent = routeData.title;
            document.getElementById('completion-acorn-count').textContent = acornCount;
            document.getElementById('completion-date').textContent = dateStr;

            if (isLoggedIn) {
                document.getElementById('completion-btn-dashboard').style.display = '';
            }

            document.getElementById('completion-screen').style.display = 'block';
        }

        // Info panel visibility
        function showInfoPanel() {
            $('#info-panel').addClass('visible');
            $('#info-panel-toggle').addClass('hidden');
        }

        function hideInfoPanel() {
            $('#info-panel').removeClass('visible');
            $('#info-panel-toggle').removeClass('hidden');
        }

        // Initialize GPS tracking
        function initGPS() {
            if ('geolocation' in navigator) {
                navigator.geolocation.watchPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;

                        updateUserPosition(lat, lng);
                    },
                    (error) => {
                        console.error('GPS Error:', error);
                        alert(translate(gameTranslations.gps_error, { message: error.message }));
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 5000,
                        maximumAge: 0
                    }
                );
            } else {
                alert(gameTranslations.gps_unsupported);
                console.log('Geolokaatiota ei ole tuettu tässä selaimessa.');
            }
        }
    </script>
    <link rel="stylesheet" href="../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .route-node-marker-wrapper {
            background: transparent;
            border: 0;
        }

        .route-node-marker-badge {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 0.9rem;
            border: 2px solid #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.28);
        }

        .route-node-marker--unvisited {
            background: #0d6efd;
        }

        .route-node-marker--visited {
            background: #198754;
        }

        .acorn-celebration {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.65rem;
            text-align: center;
            animation: acornCelebrationFade 2s ease-in-out forwards;
        }

        .acorn-celebration img {
            animation: acornPulseOnly 2s ease-in-out forwards;
        }

        @keyframes acornCelebrationFade {
            0% {
                opacity: 0;
            }

            15% {
                opacity: 1;
            }

            85% {
                opacity: 1;
            }

            100% {
                opacity: 0;
            }
        }

        @keyframes acornPulseOnly {
            0% {
                transform: scale(0.5);
            }

            15% {
                transform: scale(1);
            }

            25% {
                transform: scale(1.2);
            }

            35% {
                transform: scale(1);
            }

            45% {
                transform: scale(1.15);
            }

            55% {
                transform: scale(1);
            }

            100% {
                transform: scale(0.8);
            }
        }

        .acorn-celebration__text {
            color: ghostwhite;
            background: rgba(0, 0, 0, 0.6) !important;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 700;
            font-size: 1.5rem;
            line-height: 1.25;
            white-space: pre-line;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.55);
            max-width: min(90vw, 320px);
        }

        .progress-container__content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .progress-container__status {
            flex: 1 1 auto;
            min-width: 0;
        }

        .progress-container__meta {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.4rem;
        }

        .progress-container__meta span:last-child {
            white-space: nowrap;
        }

        .progress-container__recenter {
            flex: 0 0 auto;
            white-space: nowrap;
        }

        @media (max-width: 575.98px) {
            .progress-container__recenter .btn {
                padding: 0.35rem 0.5rem;
            }

            .progress-container__recenter .btn .progress-container__recenter-label {
                display: none;
            }
        }

        @media (max-width: 767.98px) {
            .mobile-node-sheet__card {
                pointer-events: none;
            }

            .mobile-node-sheet.visible .mobile-node-sheet__card {
                pointer-events: auto;
            }
        }

        #completion-screen {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: linear-gradient(160deg, #1b5e2e 0%, #2e7d42 50%, #1b5e2e 100%);
            overflow-y: auto;
        }
    </style>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>" async defer></script>
</head>
<body>
    <?php if ($show_entry_choice_modal): ?>
    <div class="modal fade" id="route-entry-choice-modal" tabindex="-1" aria-labelledby="routeEntryChoiceModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="routeEntryChoiceModalLabel">
                        <i class="bi bi-signpost-split-fill me-2"></i><?= htmlspecialchars(t('game.entry_choice_title'), ENT_QUOTES, 'UTF-8') ?>
                    </h5>
                </div>
                <div class="modal-body">
                    <p class="mb-3"><?= htmlspecialchars(t('game.entry_choice_intro'), ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="alert alert-info small mb-3" role="note">
                        <div class="fw-semibold mb-1">
                            <i class="bi bi-lightbulb-fill me-1"></i><?= htmlspecialchars(t('game.entry_choice_helper_title'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div><?= htmlspecialchars(t('game.entry_choice_helper_body'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="<?= htmlspecialchars($entry_choice_login_url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right me-1"></i><?= htmlspecialchars(t('game.entry_choice_login'), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <a href="<?= htmlspecialchars($entry_choice_register_url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary">
                            <i class="bi bi-person-plus-fill me-1"></i><?= htmlspecialchars(t('game.entry_choice_register'), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <button type="button" class="btn btn-secondary" id="play-anonymously-btn">
                            <i class="bi bi-person me-1"></i><?= htmlspecialchars(t('game.entry_choice_anonymous'), ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="modal fade" id="playerSafetyNoticeModal" tabindex="-1" aria-labelledby="playerSafetyNoticeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="playerSafetyNoticeModalLabel">
                        <i class="bi bi-shield-exclamation me-2"></i><?= htmlspecialchars(t('game.safety_notice_title'), ENT_QUOTES, 'UTF-8') ?>
                    </h5>
                </div>
                <div class="modal-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars(t('game.safety_notice'), ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        <?= htmlspecialchars(t('game.safety_notice_ok'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Route Completion Screen -->
    <div id="completion-screen">
        <div class="d-flex flex-column align-items-center justify-content-center min-vh-100 p-4 text-white text-center">
            <div style="max-width: 460px; width: 100%;">

                <img src="../images/acorn.png" alt="Acorn"
                     style="width: 90px; height: 90px; margin-bottom: 1.5rem; filter: drop-shadow(0 6px 16px rgba(0,0,0,0.5));">

                <h1 class="display-5 fw-bold mb-1"><?= htmlspecialchars(t('game.completion_title'), ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="lead mb-1 text-white-50"><?= htmlspecialchars(t('game.completion_subtitle'), ENT_QUOTES, 'UTF-8') ?></p>
                <h2 class="fw-bold mb-4" id="completion-route-name"></h2>

                <div class="d-flex justify-content-center gap-3 mb-5">
                    <div class="bg-white bg-opacity-10 rounded-3 p-3" style="min-width: 130px;">
                        <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                            <img src="../images/acorn.png" alt="Acorn" style="width: 22px; height: 22px;">
                            <span class="fs-2 fw-bold" id="completion-acorn-count">0</span>
                        </div>
                        <div class="text-white-50 small"><?= htmlspecialchars(t('game.completion_acorns'), ENT_QUOTES, 'UTF-8') ?>

                        </div>
                    </div>
                    <div class="bg-white bg-opacity-10 rounded-3 p-3" style="min-width: 130px;">
                        <div class="fs-4 fw-bold mb-1" id="completion-date"></div>
                        <div class="text-white-50 small"><?= htmlspecialchars(t('game.completion_date'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button onclick="window.location.reload()" class="btn btn-light btn-lg fw-bold">
                        <i class="bi bi-arrow-repeat me-2"></i><?= htmlspecialchars(t('common.play_again'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                    <a href="../index.php" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-house-door me-2"></i><?= htmlspecialchars(t('common.back_to_home'), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <a href="player/dashboard.php" class="btn btn-outline-light btn-lg"
                       id="completion-btn-dashboard" style="display: none;">
                        <i class="bi bi-person-fill me-2"></i><?= htmlspecialchars(t('common.my_profile'), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </div>

            </div>
        </div>
    </div>

    <!-- Info Panel Toggle Button -->
    <button class="btn btn-primary info-panel-toggle" id="info-panel-toggle" onclick="showInfoPanel()" aria-label="Asetukset ja tila" title="Asetukset ja tila"<?= $show_entry_choice_modal ? ' style="display:none" aria-hidden="true"' : '' ?>>
        <i class="bi bi-gear-fill" aria-hidden="true"></i>
    </button>

    <!-- Info Panel -->
    <div class="info-panel" id="info-panel"<?= $show_entry_choice_modal ? ' style="display:none" aria-hidden="true"' : '' ?>>
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h5 class="mb-0">📍 <?php echo htmlspecialchars($route->getTitle()); ?></h5>
            <button type="button" class="btn-close ms-2" onclick="hideInfoPanel()" aria-label="<?= htmlspecialchars(t('common.close'), ENT_QUOTES, 'UTF-8') ?>"></button>
        </div>
        <p class="mb-2"><small><?php echo htmlspecialchars($route->getDescription()); ?></small></p>
        <div class="mb-2">
            <img src="../images/acorn.png" alt="Acorns" style="width: 24px; height: 24px; vertical-align: middle; margin-right: 8px;">
            <strong style="font-size: 1.1em; vertical-align: middle;"><span id="acorn-count">0</span></strong>
        </div>
        <div id="distance-info"></div>
        <div class="info-panel-footer mt-3 pt-2 border-top">
            <div class="form-check form-switch mb-3">
                <?php if (!empty($route->toArray()['allow_route_line'])): ?>
                <input class="form-check-input" type="checkbox" role="switch" id="route-line-toggle">
                <label class="form-check-label" for="route-line-toggle">
                    <?= htmlspecialchars(t('game.route_line_toggle_label'), ENT_QUOTES, 'UTF-8') ?>
                </label>
                <?php endif; ?>
            </div>
            <div class="info-panel-actions">
                <a href="#" class="btn btn-sm btn-outline-secondary" id="message-creator-btn">
                    <i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars(t('common.message_creator'), ENT_QUOTES, 'UTF-8') ?>
                </a>
                <a href="#" class="btn btn-sm btn-outline-secondary" id="report-bug-btn">
                    <i class="bi bi-bug-fill me-1"></i><?= htmlspecialchars(t('feedback.type_bug'), ENT_QUOTES, 'UTF-8') ?>
                </a>
                <?php if ($is_logged_in): ?>
                    <a href="player/dashboard.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars(t('common.my_profile'), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endif; ?>
                <?php
                $language_switcher_mode = 'inline';
                require_once '../includes/_language_switcher.php';
                ?>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="hideInfoPanel()">
                    <i class="bi bi-x-lg me-1"></i><?= htmlspecialchars(t('common.close'), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </div>
        </div>
    </div>

    <div class="mobile-node-sheet" id="mobile-node-sheet">
        <div class="mobile-node-sheet__card">
            <div class="mobile-node-sheet__header">
                <div class="mobile-node-sheet__handle" aria-hidden="true"></div>
                <button type="button"
                        class="btn-close mobile-node-sheet__close"
                        onclick="closeMobileNodeSheet()"
                        aria-label="<?= htmlspecialchars(t('common.close'), ENT_QUOTES, 'UTF-8') ?>"></button>
            </div>
            <div class="mobile-node-sheet__body" id="mobile-node-sheet-body"></div>
        </div>
    </div>

    <!-- Progress Indicator -->
    <div class="progress-container">
        <div class="progress-container__content">
            <div class="progress-container__status">
                <div class="progress-container__meta">
                    <span><strong><?= htmlspecialchars(t('game.progress_label'), ENT_QUOTES, 'UTF-8') ?></strong></span>
                    <span id="progress-text"><?= htmlspecialchars(t('game.progress_text', ['visited' => 0, 'total' => 0]), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="progress">
                    <div id="progress-bar" class="progress-bar bg-success" role="progressbar" style="width: 0"></div>
                </div>
            </div>
            <div class="progress-container__recenter">
                <button type="button" class="btn btn-sm btn-outline-primary" id="recenter-map-btn" title="<?= htmlspecialchars(t('game.recenter_map'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(t('game.recenter_map'), ENT_QUOTES, 'UTF-8') ?>" disabled>
                    <i class="bi bi-crosshair"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Map Container -->
    <div id="map"></div>
    <?php
    $feedback_widget_no_float = true;
    require_once '../includes/_feedback_widget.php';
    ?>
    <?php
    // Set up message widget with current route
    $message_route_id = $route->getId();
    $message_route_name = $route->getTitle();
    require_once '../includes/_message-widget.php';
    ?>
</body>
</html>
