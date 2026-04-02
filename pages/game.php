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
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src 'self' https:; frame-src https://www.youtube.com https://www.youtube-nocookie.com;");

// Hide PHP version for security through obscurity
header_remove('X-Powered-By');

$default_route_public_id = "417bef1b-1b00-46f5-ac85-4774ff20d0ed";

$is_logged_in = !empty($_SESSION['user_public_id']);

$route = null;
if(isset($_GET['route']))
{
    $route_public_id = $_GET['route'];
    try {
        $route = Tools::getRouteByPublicId($route_public_id);
    } catch (Exception $e) {
        echo "<div class='alert alert-danger m-3'>Error: Route not found: ". $e->getMessage() ."<br>Please check the route ID and try again.</div>";
    }
}
else {
    // If no route specified, load the default route
    try {
        $route = Tools::getRouteByPublicId($default_route_public_id);
    } catch (Exception $e) {
        echo "<div class='alert alert-danger m-3'>Error: Default route not found: " . $e->getMessage() . "<br>Please check the default route ID and try again.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAVU Gamification - <?php echo htmlspecialchars($route->getTitle()); ?></title>

    <!-- Bootstrap CSS -->
    <link href="../css/bs-custom.css" rel="stylesheet">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="../node_modules/leaflet/dist/leaflet.css" />
    <!-- jQuery -->
    <script src="../node_modules/jquery/dist/jquery.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Leaflet JS -->
    <script src="../node_modules/leaflet/dist/leaflet.js"></script>

    <script>
        // Configuration
        const PROXIMITY_THRESHOLD = 20; // meters - distance to trigger node popup
        const UPDATE_INTERVAL = 3000; // ms - how often to check GPS position
        const REQUIRE_GPS_PROXIMITY = <?= REQUIRE_GPS_PROXIMITY ? 'true' : 'false' ?>;

        // Session state
        const isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;

        // Load route data from PHP
        const routeData = <?php echo $route->toJavaScript(); ?>;
        if(!routeData){
            alert("Error loading route data. Please check the console for details.");
            throw new Error("Route data is null or undefined");
        }
        console.log("Loaded route data:", routeData);

        // Center of University of Vaasa campus (default)
        let CAMPUS_CENTER = [63.1055, 21.5929];

        // Calculate center from route nodes if available
        if (routeData.nodes && routeData.nodes.length > 0) {
            const lats = routeData.nodes.map(n => parseFloat(n.node.latitude));
            const lngs = routeData.nodes.map(n => parseFloat(n.node.longitude));
            const avgLat = lats.reduce((a, b) => a + b, 0) / lats.length;
            const avgLng = lngs.reduce((a, b) => a + b, 0) / lngs.length;
            CAMPUS_CENTER = [avgLat, avgLng];
        }

        let map = null;

        if(!routeData){
            console.error("Route data is null or undefined. Cannot initialize game.");
             alert("Error loading route data. Please check the console for details.");
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

        // Custom icons
        const unvisitedIcon = L.icon({
            iconUrl: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSI0MiIgdmlld0JveD0iMCAwIDMyIDQyIj48cGF0aCBmaWxsPSIjZGMzNTQ1IiBkPSJNMTYgMEMxMC40OSAwIDYgNC40OSA2IDEwYzAgNy4zNSAxMCAyMiAxMCAyMnMxMC0xNC42NSAxMC0yMmMwLTUuNTEtNC40OS0xMC0xMC0xMHptMCAxNGMtMi4yMSAwLTQtMS43OS00LTRzMS43OS00IDQtNCA0IDEuNzkgNCA0LTEuNzkgNC00IDR6Ii8+PC9zdmc+',
            iconSize: [32, 42],
            iconAnchor: [16, 42],
            popupAnchor: [0, -42]
        });

        const visitedIcon = L.icon({
            iconUrl: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSI0MiIgdmlld0JveD0iMCAwIDMyIDQyIj48cGF0aCBmaWxsPSIjMjhhNzQ1IiBkPSJNMTYgMEMxMC40OSAwIDYgNC40OSA2IDEwYzAgNy4zNSAxMCAyMiAxMCAyMnMxMC0xNC42NSAxMC0yMmMwLTUuNTEtNC40OS0xMC0xMC0xMHptMCAxNGMtMi4yMSAwLTQtMS43OS00LTRzMS43OS00IDQtNCA0IDEuNzkgNCA0LTEuNzkgNC00IDR6Ii8+PC9zdmc+',
            iconSize: [32, 42],
            iconAnchor: [16, 42],
            popupAnchor: [0, -42]
        });

        const startIcon = L.icon({
            iconUrl: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSI0MiIgdmlld0JveD0iMCAwIDMyIDQyIj48cGF0aCBmaWxsPSIjMjhhNzQ1IiBkPSJNMTYgMEMxMC40OSAwIDYgNC40OSA2IDEwYzAgNy4zNSAxMCAyMiAxMCAyMnMxMC0xNC42NSAxMC0yMmMwLTUuNTEtNC40OS0xMC0xMC0xMHptMCAxNGMtMi4yMSAwLTQtMS43OS00LTRzMS43OS00IDQtNCA0IDEuNzkgNCA0LTEuNzkgNC00IDR6Ii8+PC9zdmc+',
            iconSize: [32, 42],
            iconAnchor: [16, 42],
            popupAnchor: [0, -42]
        });

        const finishIcon = L.icon({
            iconUrl: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSI0MiIgdmlld0JveD0iMCAwIDMyIDQyIj48cGF0aCBmaWxsPSIjZmZjMTA3IiBkPSJNMTYgMEMxMC40OSAwIDYgNC40OSA2IDEwYzAgNy4zNSAxMCAyMiAxMCAyMnMxMC0xNC42NSAxMC0yMmMwLTUuNTEtNC40OS0xMC0xMC0xMHptMCAxNGMtMi4yMSAwLTQtMS43OS00LTRzMS43OS00IDQtNCA0IDEuNzkgNCA0LTEuNzkgNC00IDR6Ii8+PC9zdmc+',
            iconSize: [32, 42],
            iconAnchor: [16, 42],
            popupAnchor: [0, -42]
        });

        const userIcon = L.icon({
            iconUrl: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij48Y2lyY2xlIGN4PSIxMiIgY3k9IjEyIiByPSIxMCIgZmlsbD0iIzAwNjZjYyIgb3BhY2l0eT0iMC4zIi8+PGNpcmNsZSBjeD0iMTIiIGN5PSIxMiIgcj0iNiIgZmlsbD0iIzAwNjZjYyIvPjxjaXJjbGUgY3g9IjEyIiBjeT0iMTIiIHI9IjMiIGZpbGw9IndoaXRlIi8+PC9zdmc+',
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });

        // Initialize everything
        $(document).ready(function() {
            // Initialize map
            map = L.map('map', { zoomControl: false, closePopupOnClick: false }).setView(CAMPUS_CENTER, 16);

            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);
            map.attributionControl.setPosition('topleft');

            drawRouteLine();
            initializeMarkers();
            updateProgress();
            initGPS();
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
        }

        // Escape HTML for safe insertion
        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str || '';
            return div.innerHTML;
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

        // Build popup HTML for a node based on its current state
        function buildNodePopup(node) {
            const nodeIndex = routeNodes.indexOf(node);
            const nodeLabel = nodeIndex === 0 ? '🚀 LÄHTÖ' : (nodeIndex === routeNodes.length - 1 ? '🏁 MAALI' : '');
            const canCheckin = !REQUIRE_GPS_PROXIMITY || (node.inProximity && (!node.challenge_data || node.challengeDone));

            let challengeHtml = '';
            if (node.challenge_data && !node.visited) {
                if (node.challengeDone) {
                    challengeHtml = '<div class="alert alert-success py-2 mt-2 mb-1">✅ Haaste suoritettu!</div>';
                } else {
                    const badgeHtml = node.inProximity
                        ? '<span class="badge bg-success mb-2">📍 Olet paikalla!</span>'
                        : '<span class="badge bg-secondary mb-2">🔒 Ole lähempänä vastataksesi</span>';

                    let inputHtml = '';
                    if (node.challenge_data.type === 'multiple_choice') {
                        inputHtml = node.challenge_data.options.map((opt, i) =>
                            `<div class="form-check">
                                <input class="form-check-input" type="radio" name="mc-${node.id}" value="${i}" id="mc-${node.id}-${i}" ${node.inProximity ? '' : 'disabled'}>
                                <label class="form-check-label" for="mc-${node.id}-${i}">${escapeHtml(opt)}</label>
                            </div>`
                        ).join('');
                    } else if (node.challenge_data.type === 'text') {
                        inputHtml = `<input type="text" class="form-control form-control-sm mb-2" id="text-answer-${node.id}" placeholder="Kirjoita vastauksesi..." ${node.inProximity ? '' : 'disabled'}>`;
                    }

                    challengeHtml = `
                        <div class="challenge-area mt-2 border-top pt-2">
                            ${badgeHtml}
                            <p class="fw-semibold mb-2">${escapeHtml(node.challenge_data.question)}</p>
                            ${inputHtml}
                            <button class="btn btn-sm btn-outline-primary" onclick="checkChallengeAnswer(${node.id})" ${node.inProximity ? '' : 'disabled'}>Tarkista vastaus</button>
                            <div class="challenge-error-msg text-danger small mt-1" style="display:none;">Väärä vastaus. Yritä uudelleen.</div>
                        </div>`;
                }
            }

            return `
                <div class="node-popup" id="popup-body-${node.id}">
                    ${nodeLabel ? `<div class="text-center mb-2"><strong>${nodeLabel}</strong></div>` : ''}
                    <h5>${escapeHtml(node.name)}</h5>
                    <div class="mb-2">${node.description || ''}</div>
                    ${challengeHtml}
                    <button class="checkin-btn btn btn-sm ${canCheckin ? 'btn-success' : 'btn-secondary'} mt-2"
                            onclick="markAsVisited(${node.id})"
                            ${canCheckin ? '' : 'disabled'}>
                        Merkkaa käydyksi ✓
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

            const body = document.getElementById('popup-body-' + nodeId);

            if (isCorrect) {
                node.challengeDone = true;
                node.challengeError = false;
                if (body) {
                    const challengeArea = body.querySelector('.challenge-area');
                    if (challengeArea) {
                        challengeArea.innerHTML = '<div class="alert alert-success py-2 mt-2 mb-1">✅ Haaste suoritettu!</div>';
                    }
                    const checkinBtn = body.querySelector('.checkin-btn');
                    if (checkinBtn) {
                        checkinBtn.disabled = false;
                        checkinBtn.className = 'checkin-btn btn btn-sm btn-success mt-2';
                    }
                }
            } else {
                node.challengeError = true;
                if (body) {
                    const errorMsg = body.querySelector('.challenge-error-msg');
                    if (errorMsg) errorMsg.style.display = 'block';
                }
            }
        };

        // Create markers for all nodes
        function initializeMarkers() {
            routeNodes.forEach((node, index) => {
                let icon;
                if (index === 0) {
                    icon = startIcon;
                } else if (index === routeNodes.length - 1) {
                    icon = finishIcon;
                } else {
                    icon = unvisitedIcon;
                }

                const marker = L.marker([node.lat, node.lng], {
                    icon: icon,
                    title: node.name
                }).addTo(map);

                marker.bindPopup(buildNodePopup(node));
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
                        node.inProximity = distance < PROXIMITY_THRESHOLD;

                        // Proximity state changed — update popup if it's open
                        if (node.inProximity !== wasInProximity && markers[node.id] && markers[node.id].isPopupOpen()) {
                            markers[node.id].setPopupContent(buildNodePopup(node));
                        }
                    }
                }
            });

            if (nearestNode && nearestDistance < PROXIMITY_THRESHOLD) {
                // Auto-open popup for the nearest in-range node
                markers[nearestNode.id].openPopup();
            }

            // Update distance info panel
            if (nearestNode) {
                const inRange = nearestDistance < PROXIMITY_THRESHOLD;
                $('#distance-info').html(`
                    <div class="alert ${inRange ? 'alert-success' : 'alert-info'} mb-0 py-2">
                        <strong>${inRange ? '📍 Olet paikalla!' : 'Seuraava:'}</strong><br>
                        <small>${escapeHtml(nearestNode.name)}<br>
                        ${Math.round(nearestDistance)}m päässä</small>
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
                    title: 'Your Location'
                }).addTo(map);

                // Optionally add accuracy circle
                L.circle([lat, lng], {
                    radius: 20,
                    color: '#0066cc',
                    fillColor: '#0066cc',
                    fillOpacity: 0.1,
                    weight: 1
                }).addTo(map);
            }

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
                markers[nodeId].setIcon(visitedIcon);
                updateProgress();

                // Close popup and show acorn animation
                markers[nodeId].closePopup();

                // Create acorn celebration element
                const acornDiv = document.createElement('div');
                acornDiv.className = 'acorn-celebration';
                acornDiv.innerHTML = '<img src="../images/acorn.png" alt="Acorn">';
                document.body.appendChild(acornDiv);

                // Remove acorn after animation completes
                setTimeout(() => {
                    acornDiv.remove();
                }, 2000);

                // Show celebration popup after acorn animation
                setTimeout(() => {
                    const celebrationPopup = `
                        <div class="node-popup text-center">
                            <h5>🎉 Hienoa!</h5>
                            <p>Olet löytänyt rastin: <strong>${node.name}</strong></p>
                        </div>
                    `;
                    markers[nodeId].bindPopup(celebrationPopup).openPopup();
                }, 2100);
            }
        };

        // Update progress bar
        function updateProgress() {
            const totalNodes = routeNodes.length;
            const visitedNodes = routeNodes.filter(n => n.visited).length;
            const percentage = (visitedNodes / totalNodes) * 100;

            $('#progress-text').text(`${visitedNodes}/${totalNodes} rastia`);
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
                setTimeout(() => showCompletionScreen(visitedNodes), 3500);
            }
        }

        function showCompletionScreen(acornCount) {
            const dateStr = new Date().toLocaleDateString('fi-FI', {day: 'numeric', month: 'numeric', year: 'numeric'});

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
                        alert('GPS Error: Sijaintijasi ei voida määrittää. Varmista, että sijaintipalvelut (Sijainti/Location) ovat päällä asetuksistasi, ja että selaimella on lupa käyttää niitä.\n\nVirhe: ' + error.message);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 5000,
                        maximumAge: 0
                    }
                );
            } else {
                alert('GPS palvelut eivät ole tuettuja tällä selaimella. Varmista, että käytät modernia selainta ja että laitteesi tukee GPS:ää.');
                console.log('Geolokaatiota ei ole tuettu tässä selaimessa.');
            }
        }
    </script>
    <link rel="stylesheet" href="../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        #completion-screen {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: linear-gradient(160deg, #1b5e2e 0%, #2e7d42 50%, #1b5e2e 100%);
            overflow-y: auto;
        }
    </style>
</head>
<body>

    <!-- Route Completion Screen -->
    <div id="completion-screen">
        <div class="d-flex flex-column align-items-center justify-content-center min-vh-100 p-4 text-white text-center">
            <div style="max-width: 460px; width: 100%;">

                <img src="../images/acorn.png" alt="Acorn"
                     style="width: 90px; height: 90px; margin-bottom: 1.5rem; filter: drop-shadow(0 6px 16px rgba(0,0,0,0.5));">

                <h1 class="display-5 fw-bold mb-1">Onneksi olkoon! 🎉</h1>
                <p class="lead mb-1 text-white-50">Suoritit reitin</p>
                <h2 class="fw-bold mb-4" id="completion-route-name"></h2>

                <div class="d-flex justify-content-center gap-3 mb-5">
                    <div class="bg-white bg-opacity-10 rounded-3 p-3" style="min-width: 130px;">
                        <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                            <img src="../images/acorn.png" alt="Acorn" style="width: 22px; height: 22px;">
                            <span class="fs-2 fw-bold" id="completion-acorn-count">0</span>
                        </div>
                        <div class="text-white-50 small">Tammenterhoa

                        </div>
                    </div>
                    <div class="bg-white bg-opacity-10 rounded-3 p-3" style="min-width: 130px;">
                        <div class="fs-4 fw-bold mb-1" id="completion-date"></div>
                        <div class="text-white-50 small">Suorituspäivä</div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button onclick="window.location.reload()" class="btn btn-light btn-lg fw-bold">
                        <i class="bi bi-arrow-repeat me-2"></i>Pelaa uudelleen
                    </button>
                    <a href="routes.php" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-map me-2"></i>Valitse reitti
                    </a>
                    <a href="player/dashboard.php" class="btn btn-outline-light btn-lg"
                       id="completion-btn-dashboard" style="display: none;">
                        <i class="bi bi-person-fill me-2"></i>Oma profiili
                    </a>
                </div>

            </div>
        </div>
    </div>

    <!-- Info Panel Toggle Button -->
    <button class="btn btn-primary info-panel-toggle" id="info-panel-toggle" onclick="showInfoPanel()">
        📍 <?php echo htmlspecialchars($route->getTitle()); ?> <span class="ms-1">⬇️</span>
    </button>

    <!-- Info Panel -->
    <div class="info-panel" id="info-panel">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h5 class="mb-0">📍 <?php echo htmlspecialchars($route->getTitle()); ?></h5>
            <button type="button" class="btn-close ms-2" onclick="hideInfoPanel()" aria-label="Sulje"></button>
        </div>
        <p class="mb-2"><small><?php echo htmlspecialchars($route->getDescription()); ?></small></p>
        <div class="mb-2">
            <img src="../images/acorn.png" alt="Acorns" style="width: 24px; height: 24px; vertical-align: middle; margin-right: 8px;">
            <strong style="font-size: 1.1em; vertical-align: middle;"><span id="acorn-count">0</span></strong>
        </div>
        <div id="distance-info"></div>
    </div>

    <!-- Progress Indicator -->
    <div class="progress-container">
        <div class="d-flex justify-content-between mb-2">
            <span><strong>Eteneminen</strong></span>
            <span id="progress-text">0/0 rastia</span>
        </div>
        <div class="progress">
            <div id="progress-bar" class="progress-bar bg-success" role="progressbar" style="width: 0"></div>
        </div>
    </div>

    <!-- Map Container -->
    <div id="map"></div>
</body>
</html>