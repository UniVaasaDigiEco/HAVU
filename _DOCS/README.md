# HAVU Gamification - Campus Route Geocaching App

A geocaching-style map application that creates an interactive campus tour at the University of Vaasa.

## Features

- 📍 **GPS Tracking**: Real-time location tracking using phone GPS
- 🗺️ **Interactive Map**: Leaflet-powered map with OpenStreetMap tiles
- 📌 **Route Waypoints**: 6 nodes/pins forming a route around campus
- 🎯 **Proximity Detection**: Automatically detects when user is near a node (50m threshold)
- 💬 **Auto-popup**: Information popups open when user approaches nodes
- ✅ **Progress Tracking**: Visual progress bar showing visited nodes
- 🎨 **Visual Feedback**: Different icons for visited/unvisited nodes
- 📏 **Distance Display**: Shows distance to nearest unvisited node

## Campus Route Nodes

1. **Main Building - Palosaari** (63.1055, 21.5929)
2. **Tritonia Academic Library** (63.1045, 21.5945)
3. **Technobothnia** (63.1065, 21.5950)
4. **Fabriikki** (63.1070, 21.5915)
5. **Student Union Building** (63.1050, 21.5910)
6. **Campus Park Area** (63.1060, 21.5935)

## Setup

1. Make sure XAMPP is installed and running
2. Navigate to the project directory
3. Dependencies are already installed (bootstrap, jquery, leaflet)
4. Open in browser: `http://localhost/HavuGamification/`

## Usage

1. **Allow GPS Access**: When prompted, allow the browser to access your location
2. **GPS Indicator**: Top-left shows GPS status (Active/Inactive)
3. **Info Panel**: Top-right shows nearby node information
4. **Walk the Route**: Follow the blue dashed line connecting all nodes
5. **Discover Nodes**: When within 50 meters, popup opens automatically
6. **Mark as Visited**: Click the "Mark as Visited" button in popups
7. **Track Progress**: Bottom progress bar shows completion percentage
8. **Complete Route**: Visit all 6 nodes to complete the campus tour!

## Technical Details

- **Framework**: Leaflet.js for mapping
- **UI**: Bootstrap 5 for styling
- **GPS**: HTML5 Geolocation API
- **Distance Calculation**: Haversine formula
- **Proximity Threshold**: 50 meters (configurable)
- **Update Interval**: 3 seconds (configurable)

## Configuration

You can modify these settings in `index.php`:

```javascript
const PROXIMITY_THRESHOLD = 50; // meters - distance to trigger popup
const UPDATE_INTERVAL = 3000; // ms - GPS update frequency
```

## Adding New Nodes

To add more waypoints to the route, edit the `routeNodes` array in `index.php`:

```javascript
{
    id: 7,
    name: "New Location",
    lat: 63.1234,
    lng: 21.5678,
    description: "Information about this location...",
    visited: false
}
```

## Browser Compatibility

- Requires browser with Geolocation API support
- Works best on mobile devices with GPS
- Desktop browsers can use Wi-Fi positioning (less accurate)

## Authors

Jyri Nieminen & Vaiva Stanisauskaite

## License

ISC

