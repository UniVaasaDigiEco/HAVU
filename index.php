<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAVU Gamification - Campus Route</title>

    <!-- Bootstrap CSS -->
    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="node_modules/leaflet/dist/leaflet.css" />

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        #map {
            height: 100vh;
            width: 100%;
        }

        .info-panel {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            max-width: 300px;
        }

        .gps-status {
            position: absolute;
            top: 10px;
            left: 60px;
            z-index: 1000;
            background: white;
            padding: 10px 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .gps-active {
            color: #28a745;
        }

        .gps-inactive {
            color: #dc3545;
        }

        .node-popup {
            max-width: 300px;
        }

        .node-popup h5 {
            margin-top: 0;
            color: #0066cc;
        }

        .progress-container {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            min-width: 300px;
        }

        .visited-marker {
            filter: hue-rotate(120deg);
        }

        .pulse-marker {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.1);
                opacity: 0.8;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <!-- GPS Status Indicator -->
    <div class="gps-status">
        <span id="gps-icon" class="gps-inactive">📍</span>
        <span id="gps-text">GPS: Inactive</span>
    </div>

    <!-- Info Panel -->
    <div class="info-panel">
        <h5>📍 Campus Route</h5>
        <p class="mb-2"><small>Walk the route and discover interesting locations at the University of Vaasa campus!</small></p>
        <div id="distance-info"></div>
    </div>

    <!-- Progress Indicator -->
    <div class="progress-container">
        <div class="d-flex justify-content-between mb-2">
            <span><strong>Progress</strong></span>
            <span id="progress-text">0/0 nodes</span>
        </div>
        <div class="progress">
            <div id="progress-bar" class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
        </div>
    </div>

    <!-- Map Container -->
    <div id="map"></div>

    <!-- jQuery -->
    <script src="node_modules/jquery/dist/jquery.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Leaflet JS -->
    <script src="node_modules/leaflet/dist/leaflet.js"></script>

    <script>
        // Configuration
        const PROXIMITY_THRESHOLD = 50; // meters - distance to trigger node popup
        const UPDATE_INTERVAL = 3000; // ms - how often to check GPS position

        // Center of University of Vaasa campus
        const CAMPUS_CENTER = [63.1055, 21.5929];

        // Define the route nodes (waypoints) around University of Vaasa campus
        const routeNodes = [
            {
                id: 1,
                name: "Ankkuri - Palosaari", //63.104877198359176, 21.591938317666084
                lat: 63.1049,
                lng: 21.5919,
                description: "Welcome to Ankkuri at Palosaari! This is the heart of student life at the University of Vaasa. Ankkuri houses student services, recreational facilities, and various student organization spaces. It's a vibrant hub where students gather, socialize, and access support services throughout their academic journey.",
                visited: false
            },
            {
                id: 2,
                name: "Technobothnia",
                lat: 63.1045,
                lng: 21.5945,
                description: "Technobothnia is the state-of-the-art technology and engineering building at the University of Vaasa. This modern facility houses cutting-edge laboratories, research facilities, and collaborative workspaces. It's a center for innovation where students and researchers work on advanced projects in technology, automation, and energy systems.",
                visited: false
            },
            {
                id: 3,
                name: "Tritonia Academic Library", //63.10569950452311, 21.594013438314224
                lat: 63.10569950452311,
                lng: 21.594013438314224,
                description: "Tritonia is the modern academic library serving the University of Vaasa, Hanken School of Economics, and Novia University of Applied Sciences. This collaborative facility offers extensive study spaces, digital resources, and a vast collection of academic materials. It's a favorite spot for students to study, conduct research, and access learning resources.",
                visited: false
            },
            {
                id: 4,
                name: "University of Vaasa, Tervahovi Building", //63.10552634759802, 21.593174837423316
                lat: 63.10552634759802,
                lng: 21.593174837423316,
                description: "Tervahovi is one of the main academic buildings at the University of Vaasa, primarily serving the faculties of business studies and humanities. This building features modern lecture halls, seminar rooms, and faculty offices. It's a central location for classes, academic meetings, and student activities throughout the academic year.",
                visited: false
            }
        ];

        // Initialize map
        const map = L.map('map').setView(CAMPUS_CENTER, 16);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

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
            const φ1 = lat1 * Math.PI / 180;
            const φ2 = lat2 * Math.PI / 180;
            const Δφ = (lat2 - lat1) * Math.PI / 180;
            const Δλ = (lon2 - lon1) * Math.PI / 180;

            const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                      Math.cos(φ1) * Math.cos(φ2) *
                      Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
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

                // Show celebration
                markers[nodeId].closePopup();
                setTimeout(() => {
                    const celebrationPopup = `
                        <div class="node-popup text-center">
                            <h5>🎉 Great Job!</h5>
                            <p>You've discovered: <strong>${node.name}</strong></p>
                        </div>
                    `;
                    markers[nodeId].bindPopup(celebrationPopup).openPopup();
                }, 100);
            }
        };

        // Update progress bar
        function updateProgress() {
            const totalNodes = routeNodes.length;
            const visitedNodes = routeNodes.filter(n => n.visited).length;
            const percentage = (visitedNodes / totalNodes) * 100;

            $('#progress-text').text(`${visitedNodes}/${totalNodes} nodes`);
            $('#progress-bar').css('width', percentage + '%');

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

        // Initialize everything
        $(document).ready(function() {
            drawRouteLine();
            initializeMarkers();
            updateProgress();
            initGPS();

            // Add scale control
            L.control.scale().addTo(map);
        });
    </script>
</body>
</html>