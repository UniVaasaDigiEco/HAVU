<?php
require_once('../../vendor/autoload.php');
require_once('../../classes/tools.class.php');
require_once('../../classes/security.class.php');
use Ramsey\Uuid\Uuid;
Security::initSession();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAVU - Create New Route</title>
    <link rel="stylesheet" href="../../css/bs-custom.css">
    <link rel="stylesheet" href="../../node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../node_modules/leaflet/dist/leaflet.css" />
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
    </style>
</head>
<body class="admin-dashboard">
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-map-fill me-2"></i>Create New Route</h2>
                    <p class="text-muted mb-0">Click on the map to add nodes to your route</p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
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
                        <h5 class="mb-0"><i class="bi bi-info-circle-fill me-2"></i>Route Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="route_title" class="form-label">Route Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="route_title" name="route_title" required>
                        </div>

                        <div class="mb-3">
                            <label for="route_description" class="form-label">Route Description</label>
                            <textarea class="form-control" id="route_description" name="route_description" rows="4"></textarea>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_published" name="is_published">
                                <label class="form-check-label" for="is_published">
                                    Publish immediately
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="publication_date" class="form-label">Publication Date</label>
                            <input type="date" class="form-control" id="publication_date" name="publication_date">
                        </div>
                    </div>
                </div>

                <!-- Nodes List -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-geo-alt-fill me-2"></i>Nodes (<span id="nodeCount">0</span>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="nodesList" class="list-group list-group-flush">
                            <div class="node-list-empty">
                                <i class="bi bi-cursor-fill" style="font-size: 3rem;"></i>
                                <p class="mt-3">Click on the map to add your first node</p>
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
                        <h5 class="mb-0"><i class="bi bi-map me-2"></i>Map</h5>
                    </div>
                    <div class="card-body">
                        <div id="map"></div>
                        <div class="mt-2 text-muted small">
                            <i class="bi bi-info-circle"></i> Click anywhere on the map to add a node to your route
                        </div>
                    </div>
                </div>

                <!-- Node Editor -->
                <div id="nodeEditor" class="card shadow-sm" style="display: none;">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="bi bi-pencil-fill me-2"></i>Edit Node</h5>
                    </div>
                    <div class="card-body">
                        <input type="hidden" id="editNodeIndex">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="node_title" class="form-label">Node Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="node_title">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="node_lat" class="form-label">Latitude</label>
                                <input type="number" step="0.000001" class="form-control" id="node_lat" readonly>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="node_lng" class="form-label">Longitude</label>
                                <input type="number" step="0.000001" class="form-control" id="node_lng" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="node_content" class="form-label">Node Content</label>
                            <textarea class="form-control" id="node_content" rows="4" placeholder="Enter the content that will be displayed when users reach this node..."></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary" onclick="saveNodeEdit()">
                                <i class="bi bi-check-lg"></i> Save Node
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="cancelNodeEdit()">
                                Cancel
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
                                <h5 class="mb-1">Ready to create your route?</h5>
                                <p class="text-muted mb-0">Make sure you've added all nodes and filled in the route details</p>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-check-circle-fill me-2"></i> Create Route
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="../../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../node_modules/leaflet/dist/leaflet.js"></script>
<script>
    // Global variables
    let map;
    let markers = [];
    let nodes = [];
    const CAMPUS_CENTER = [63.1055, 21.5929];

    // Initialize map
    function initMap() {
        map = L.map('map').setView(CAMPUS_CENTER, 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // Add click event to map
        map.on('click', onMapClick);
    }

    // Handle map click
    function onMapClick(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;

        // Add node
        addNode(lat, lng);
    }

    // Add a new node
    function addNode(lat, lng, title = '', content = '') {
        const nodeIndex = nodes.length;
        const nodeNumber = nodeIndex + 1;

        // Create node data
        const node = {
            lat: lat,
            lng: lng,
            title: title || `Node ${nodeNumber}`,
            content: content
        };

        nodes.push(node);

        // Create marker
        const marker = L.marker([lat, lng], {
            draggable: true,
            icon: createNumberedIcon(nodeNumber)
        }).addTo(map);

        marker.nodeIndex = nodeIndex;

        // Add popup
        marker.bindPopup(`<b>${node.title}</b><br>${node.content || 'No content yet'}`);

        // Handle marker drag
        marker.on('dragend', function(e) {
            const newPos = e.target.getLatLng();
            nodes[marker.nodeIndex].lat = newPos.lat;
            nodes[marker.nodeIndex].lng = newPos.lng;
            updateNodesList();
        });

        // Handle marker click
        marker.on('click', function() {
            editNode(marker.nodeIndex);
        });

        markers.push(marker);

        // Update UI
        updateNodesList();
        updatePolyline();

        // Show editor for new nodes
        if (!title) {
            editNode(nodeIndex);
        }
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

    // Update nodes list display
    function updateNodesList() {
        const nodesList = document.getElementById('nodesList');
        const nodeCount = document.getElementById('nodeCount');

        nodeCount.textContent = nodes.length;

        if (nodes.length === 0) {
            nodesList.innerHTML = `
                <div class="node-list-empty">
                    <i class="bi bi-cursor-fill" style="font-size: 3rem;"></i>
                    <p class="mt-3">Click on the map to add your first node</p>
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
                        <p class="mb-1 small text-muted">${escapeHtml(node.content) || '<em>No content</em>'}</p>
                        <small class="text-muted">
                            <i class="bi bi-geo-alt"></i> ${node.lat.toFixed(6)}, ${node.lng.toFixed(6)}
                        </small>
                    </div>
                    <div class="d-flex flex-column gap-1">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editNode(${index})" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteNode(${index})" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                        ${index > 0 ? `<button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveNodeUp(${index})" title="Move up">
                            <i class="bi bi-arrow-up"></i>
                        </button>` : ''}
                        ${index < nodes.length - 1 ? `<button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveNodeDown(${index})" title="Move down">
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
        document.getElementById('node_content').value = node.content;
        document.getElementById('node_lat').value = node.lat.toFixed(6);
        document.getElementById('node_lng').value = node.lng.toFixed(6);

        editor.style.display = 'block';
        editor.scrollIntoView({ behavior: 'smooth' });
    }

    // Save node edit
    function saveNodeEdit() {
        const index = parseInt(document.getElementById('editNodeIndex').value);
        const title = document.getElementById('node_title').value.trim();
        const content = document.getElementById('node_content').value.trim();

        if (!title) {
            alert('Node title is required');
            return;
        }

        nodes[index].title = title;
        nodes[index].content = content;

        // Update marker popup
        markers[index].setPopupContent(`<b>${title}</b><br>${content || 'No content yet'}`);

        updateNodesList();
        cancelNodeEdit();
    }

    // Cancel node edit
    function cancelNodeEdit() {
        document.getElementById('nodeEditor').style.display = 'none';
        document.getElementById('editNodeIndex').value = '';
        document.getElementById('node_title').value = '';
        document.getElementById('node_content').value = '';
    }

    // Delete node
    function deleteNode(index) {
        if (!confirm('Are you sure you want to delete this node?')) {
            return;
        }

        // Remove marker
        map.removeLayer(markers[index]);

        // Remove from arrays
        nodes.splice(index, 1);
        markers.splice(index, 1);

        // Update all marker icons with new numbers
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

        // Swap nodes
        [nodes[index], nodes[index - 1]] = [nodes[index - 1], nodes[index]];
        [markers[index], markers[index - 1]] = [markers[index - 1], markers[index]];

        // Update marker icons and indices
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

        // Swap nodes
        [nodes[index], nodes[index + 1]] = [nodes[index + 1], nodes[index]];
        [markers[index], markers[index + 1]] = [markers[index + 1], markers[index]];

        // Update marker icons and indices
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
            alert('Please add at least one node to the route');
            return false;
        }

        // Store nodes data in hidden field
        document.getElementById('nodesData').value = JSON.stringify(nodes);

        return true;
    });

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        initMap();
    });
</script>
</body>
</html>
