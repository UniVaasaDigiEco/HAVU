<?php
require_once('../classes/tools.class.php');
require_once('../classes/security.class.php');
require_once('../classes/route.class.php');

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
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src 'self' https:;");

// Hide PHP version for security through obscurity
header_remove('X-Powered-By');

$default_route_public_id = "417bef1b-1b00-46f5-ac85-4774ff20d0ed";

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
        const PROXIMITY_THRESHOLD = 50; // meters - distance to trigger node popup
        const UPDATE_INTERVAL = 3000; // ms - how often to check GPS position

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
            visited: false,
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
            map = L.map('map').setView(CAMPUS_CENTER, 16);

            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);

            drawRouteLine();
            initializeMarkers();
            updateProgress();
            initGPS();

            // Add scale control
            L.control.scale().addTo(map);
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

        // Create markers for all nodes
        function initializeMarkers() {
            routeNodes.forEach((node, index) => {
                // Determine which icon to use
                let icon;
                if (index === 0) {
                    icon = startIcon; // First node - green
                } else if (index === routeNodes.length - 1) {
                    icon = finishIcon; // Last node - gold
                } else {
                    icon = unvisitedIcon; // Middle nodes - red
                }

                const marker = L.marker([node.lat, node.lng], {
                    icon: icon,
                    title: node.name
                }).addTo(map);

                const nodeLabel = index === 0 ? '🚀 START' : (index === routeNodes.length - 1 ? '🏁 FINISH' : '');
                const popupContent = `
                    <div class="node-popup">
                        ${nodeLabel ? `<div class="text-center mb-2"><strong>${nodeLabel}</strong></div>` : ''}
                        <h5>${node.name}</h5>
                        <p>${node.description}</p>
                        <button class="btn btn-sm btn-primary" onclick="markAsVisited(${node.id})">
                            Mark as Visited ✓
                        </button>
                    </div>
                `;

                marker.bindPopup(popupContent);
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

        // Check proximity to nodes
        function checkProximity(userLat, userLng) {
            let nearestNode = null;
            let nearestDistance = Infinity;

            routeNodes.forEach(node => {
                if (!node.visited) {
                    const distance = calculateDistance(userLat, userLng, node.lat, node.lng);

                    if (distance < PROXIMITY_THRESHOLD && distance < nearestDistance) {
                        nearestNode = node;
                        nearestDistance = distance;
                    }
                }
            });

            // Update distance info
            if (nearestNode) {
                $('#distance-info').html(`
                    <div class="alert alert-success mb-0 py-2">
                        <strong>📍 Nearby!</strong><br>
                        <small>${nearestNode.name}<br>
                        ${Math.round(nearestDistance)}m away</small>
                    </div>
                `);

                // Auto-open popup when very close
                if (nearestDistance < PROXIMITY_THRESHOLD) {
                    markers[nearestNode.id].openPopup();
                }
            } else {
                // Find closest unvisited node
                let closestNode = null;
                let closestDistance = Infinity;

                routeNodes.forEach(node => {
                    if (!node.visited) {
                        const distance = calculateDistance(userLat, userLng, node.lat, node.lng);
                        if (distance < closestDistance) {
                            closestNode = node;
                            closestDistance = distance;
                        }
                    }
                });

                if (closestNode) {
                    $('#distance-info').html(`
                        <div class="alert alert-info mb-0 py-2">
                            <strong>Next:</strong><br>
                            <small>${closestNode.name}<br>
                            ${Math.round(closestDistance)}m away</small>
                        </div>
                    `);
                }
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

        // Mark node as visited
        window.markAsVisited = function(nodeId) {
            const node = routeNodes.find(n => n.id === nodeId);
            if (node && !node.visited) {
                node.visited = true;
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
                            <h5>🎉 Great Job!</h5>
                            <p>You've discovered: <strong>${node.name}</strong></p>
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

            $('#progress-text').text(`${visitedNodes}/${totalNodes} nodes`);
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
                setTimeout(() => {
                    alert('🎉 Congratulations! You\'ve completed the entire campus route!');
                }, 500);
            }
        }

        // Initialize GPS tracking
        function initGPS() {
            if ('geolocation' in navigator) {
                navigator.geolocation.watchPosition(
                    (position) => {
                        $('#gps-icon').removeClass('gps-inactive').addClass('gps-active');
                        $('#gps-text').text('GPS: Active');

                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;

                        updateUserPosition(lat, lng);
                    },
                    (error) => {
                        console.error('GPS Error:', error);
                        $('#gps-icon').removeClass('gps-active').addClass('gps-inactive');
                        $('#gps-text').text('GPS: Error');
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 5000,
                        maximumAge: 0
                    }
                );
            } else {
                $('#gps-text').text('GPS: Not Available');
                console.log('Geolocation is not supported by this browser.');
            }
        }
    </script>
</head>
<body>
    <!-- GPS Status Indicator -->
    <div class="gps-status">
        <span id="gps-icon" class="gps-inactive">📍</span>
        <span id="gps-text">GPS: Inactive</span>
    </div>

    <!-- Info Panel -->
    <div class="info-panel">
        <h5>📍 <?php echo htmlspecialchars($route->getTitle()); ?></h5>
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
            <span><strong>Progress</strong></span>
            <span id="progress-text">0/0 nodes</span>
        </div>
        <div class="progress">
            <div id="progress-bar" class="progress-bar bg-success" role="progressbar" style="width: 0"></div>
        </div>
    </div>

    <!-- Map Container -->
    <div id="map"></div>
</body>
</html>