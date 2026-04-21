# New Route Creation Page

## Overview
The `new-route.php` page provides an interactive interface for administrators to create new routes with multiple nodes (waypoints) for the HAVU Gamification system.

## Features

### 1. Interactive Map
- **Leaflet.js Integration**: Uses OpenStreetMap tiles for map display
- **Click to Add**: Click anywhere on the map to add a new node
- **Draggable Markers**: Markers can be dragged to adjust node positions
- **Visual Route Path**: A dashed line connects all nodes in sequence
- **Numbered Markers**: Each node displays its order number

### 2. Route Details Form
- **Route Title**: Required field for the route name
- **Route Description**: Optional detailed description
- **Publish Status**: Toggle to publish immediately or save as draft
- **Publication Date**: Optional scheduled publication date

### 3. Node Management
- **Node List**: Displays all nodes in order with title, content, and coordinates
- **Edit Nodes**: Click any node to edit its title and content
- **Delete Nodes**: Remove unwanted nodes
- **Reorder Nodes**: Move nodes up or down to change route sequence
- **Drag to Reposition**: Drag markers on the map to adjust coordinates

### 4. Node Editor
- **Title**: Required field for the node name
- **Content**: Popup content displayed when users reach the node
- **Coordinates**: Automatically filled based on map position (read-only)

## Usage Instructions

### Creating a New Route

1. **Access the Page**
   - Navigate to Admin Dashboard
   - Click "Add a new route"

2. **Fill Route Details**
   - Enter a route title (required)
   - Add a description (optional)
   - Set publication status and date

3. **Add Nodes**
   - Click on the map where you want to place a node
   - The node editor will appear automatically
   - Fill in the node title and content
   - Click "Save Node"

4. **Manage Nodes**
   - Edit: Click the pencil icon or click the marker
   - Delete: Click the trash icon
   - Reorder: Use up/down arrow buttons
   - Reposition: Drag the marker on the map

5. **Submit**
   - Review all nodes and route details
   - Click "Create Route" to save

## Technical Details

### Database Structure
The route creation process inserts data into three tables:

**routes Table:**
- `id`: Auto-increment primary key
- `public_id`: UUID (binary(16))
- `is_published`: Boolean (0 or 1)
- `publication_date`: Date (nullable)
- `created_by`: User ID of creator
- `user_id`: Owner user ID
- `title`: Route title
- `description`: Route description
- `created_at`: Timestamp
- `updated_at`: Timestamp

**nodes Table:**
- `id`: Auto-increment primary key
- `public_id`: UUID (binary(16))
- `is_published`: Boolean (0 or 1)
- `publication_date`: Date (nullable)
- `created_by`: User ID of creator
- `title`: Node title
- `content`: Node popup content
- `latitude`: Decimal(9,6)
- `longitude`: Decimal(9,6)
- `created_at`: Timestamp
- `updated_at`: Timestamp

**node_route_cross Table:**
- `id`: Auto-increment primary key
- `node_id`: Foreign key to nodes
- `route_id`: Foreign key to routes
- `order_number`: Integer defining sequence

### PHP Classes Used
- **User**: Manages user authentication and authorization
- **Tools**: Database connection and utility functions
- **Ramsey\Uuid\Uuid**: UUID generation for public IDs

### JavaScript Functions

**Map Functions:**
- `initMap()`: Initializes the Leaflet map
- `onMapClick(e)`: Handles map click events
- `addNode(lat, lng, title, content)`: Adds a new node
- `createNumberedIcon(number)`: Creates custom numbered markers
- `updatePolyline()`: Updates the connecting line between nodes

**Node Management:**
- `editNode(index)`: Opens editor for a specific node
- `saveNodeEdit()`: Saves changes to a node
- `cancelNodeEdit()`: Closes editor without saving
- `deleteNode(index)`: Removes a node
- `moveNodeUp(index)`: Moves node up in sequence
- `moveNodeDown(index)`: Moves node down in sequence

**UI Updates:**
- `updateNodesList()`: Refreshes the node list display
- `escapeHtml(text)`: Sanitizes text for display

### Form Submission
- Validates at least one node exists
- Serializes node data to JSON
- Posts to same page with `action=create_route`
- Uses database transactions for data integrity
- Rolls back on error

## Security Features
- Session-based authentication
- Admin-only access
- SQL prepared statements (prevents SQL injection)
- HTML escaping (prevents XSS)
- CSRF protection via same-origin submission

## Dependencies
- Bootstrap 5 (UI framework)
- Bootstrap Icons (icons)
- Leaflet.js (mapping)
- OpenStreetMap (map tiles)
- Ramsey UUID (UUID generation)

## Error Handling
- User authentication check
- Database transaction rollback on error
- Client-side validation (required fields, minimum nodes)
- Server-side validation (title, nodes array)
- Exception catching with user-friendly messages

## Future Enhancements
- Import nodes from CSV/JSON
- Copy nodes from existing routes
- Preview route before saving
- Add images to nodes
- Route templates
- Bulk node editing
