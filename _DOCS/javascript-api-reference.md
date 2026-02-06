# New Route Page - JavaScript API Reference

## Global Variables

### `map`
- **Type**: `L.Map`
- **Description**: Leaflet map instance
- **Initialization**: `initMap()`
- **Default View**: `[63.1055, 21.5929]` (University of Vaasa campus)
- **Zoom Level**: 15

### `markers`
- **Type**: `Array<L.Marker>`
- **Description**: Array storing all map markers
- **Index**: Corresponds to node index in `nodes` array
- **Custom Property**: Each marker has `marker.nodeIndex` property

### `nodes`
- **Type**: `Array<Object>`
- **Description**: Array storing all node data
- **Structure**:
  ```javascript
  {
    lat: number,      // Latitude (decimal)
    lng: number,      // Longitude (decimal)
    title: string,    // Node title
    content: string   // Node popup content
  }
  ```

### `polyline`
- **Type**: `L.Polyline | null`
- **Description**: Line connecting all nodes in sequence
- **Style**: Blue dashed line
- **Updated**: Automatically when nodes change

### `CAMPUS_CENTER`
- **Type**: `Array<number>`
- **Value**: `[63.1055, 21.5929]`
- **Description**: Default map center coordinates

## Functions Reference

### Map Initialization

#### `initMap()`
```javascript
function initMap()
```
**Description**: Initializes the Leaflet map with OpenStreetMap tiles
**Called**: On page load via `DOMContentLoaded` event
**Returns**: `void`
**Side Effects**:
- Creates global `map` variable
- Adds OpenStreetMap tile layer
- Attaches click event handler

---

### Node Management

#### `addNode(lat, lng, title = '', content = '')`
```javascript
function addNode(lat, lng, title = '', content = '')
```
**Description**: Creates a new node and adds it to the map
**Parameters**:
- `lat` (number): Latitude coordinate
- `lng` (number): Longitude coordinate
- `title` (string): Node title (default: "Node N")
- `content` (string): Node content (default: "")

**Returns**: `void`
**Side Effects**:
- Adds node to `nodes` array
- Creates marker on map
- Updates node list display
- Updates polyline
- Opens editor if title is empty

**Example**:
```javascript
// Add node at specific location with title
addNode(63.1055, 21.5929, "Main Building", "Welcome to the main campus building");

// Add node with default title
addNode(63.1055, 21.5929);
```

---

#### `editNode(index)`
```javascript
function editNode(index)
```
**Description**: Opens the node editor for a specific node
**Parameters**:
- `index` (number): Index of node in `nodes` array

**Returns**: `void`
**Side Effects**:
- Populates editor form fields
- Shows editor card
- Scrolls to editor

**Example**:
```javascript
editNode(0); // Edit first node
```

---

#### `saveNodeEdit()`
```javascript
function saveNodeEdit()
```
**Description**: Saves changes from the node editor
**Parameters**: None (reads from form fields)
**Returns**: `void`
**Validation**: Alerts if title is empty
**Side Effects**:
- Updates node in `nodes` array
- Updates marker popup
- Updates node list display
- Closes editor

---

#### `cancelNodeEdit()`
```javascript
function cancelNodeEdit()
```
**Description**: Closes the node editor without saving
**Parameters**: None
**Returns**: `void`
**Side Effects**:
- Hides editor card
- Clears form fields

---

#### `deleteNode(index)`
```javascript
function deleteNode(index)
```
**Description**: Removes a node from the route
**Parameters**:
- `index` (number): Index of node to delete

**Returns**: `void`
**Confirmation**: Shows browser confirm dialog
**Side Effects**:
- Removes marker from map
- Removes node from `nodes` array
- Updates all marker numbers
- Updates node list display
- Updates polyline
- Closes editor if open

**Example**:
```javascript
deleteNode(2); // Delete third node
```

---

#### `moveNodeUp(index)`
```javascript
function moveNodeUp(index)
```
**Description**: Moves a node up in the sequence
**Parameters**:
- `index` (number): Index of node to move

**Returns**: `void`
**Validation**: Does nothing if index is 0
**Side Effects**:
- Swaps nodes in array
- Swaps markers
- Updates marker numbers
- Updates node list
- Updates polyline

**Example**:
```javascript
moveNodeUp(2); // Move third node to second position
```

---

#### `moveNodeDown(index)`
```javascript
function moveNodeDown(index)
```
**Description**: Moves a node down in the sequence
**Parameters**:
- `index` (number): Index of node to move

**Returns**: `void`
**Validation**: Does nothing if index is last
**Side Effects**:
- Swaps nodes in array
- Swaps markers
- Updates marker numbers
- Updates node list
- Updates polyline

**Example**:
```javascript
moveNodeDown(1); // Move second node to third position
```

---

### UI Updates

#### `updateNodesList()`
```javascript
function updateNodesList()
```
**Description**: Refreshes the node list display
**Parameters**: None
**Returns**: `void`
**Side Effects**:
- Updates node count badge
- Regenerates list HTML
- Shows empty state if no nodes

---

#### `updatePolyline()`
```javascript
function updatePolyline()
```
**Description**: Updates the line connecting nodes
**Parameters**: None
**Returns**: `void`
**Side Effects**:
- Removes old polyline if exists
- Creates new polyline if 2+ nodes
- Adds polyline to map

**Style**:
- Color: `#0d6efd` (Bootstrap primary blue)
- Weight: 3
- Opacity: 0.7
- Pattern: Dashed (`10, 5`)

---

### Utility Functions

#### `createNumberedIcon(number)`
```javascript
function createNumberedIcon(number)
```
**Description**: Creates a custom numbered marker icon
**Parameters**:
- `number` (number): Number to display

**Returns**: `L.DivIcon`
**Style**:
- Blue circle with white border
- White text
- 30x30 pixels
- Drop shadow

**Example**:
```javascript
const icon = createNumberedIcon(1);
L.marker([63.1055, 21.5929], { icon: icon }).addTo(map);
```

---

#### `escapeHtml(text)`
```javascript
function escapeHtml(text)
```
**Description**: Sanitizes text for safe HTML display
**Parameters**:
- `text` (string): Text to escape

**Returns**: `string` - Escaped HTML
**Security**: Prevents XSS attacks
**Method**: Uses DOM textContent

**Example**:
```javascript
const safe = escapeHtml("<script>alert('xss')</script>");
// Returns: "&lt;script&gt;alert('xss')&lt;/script&gt;"
```

---

### Event Handlers

#### `onMapClick(e)`
```javascript
function onMapClick(e)
```
**Description**: Handles clicks on the map
**Parameters**:
- `e` (LeafletMouseEvent): Leaflet event object

**Returns**: `void`
**Side Effects**:
- Calls `addNode()` with click coordinates

**Event**: Attached via `map.on('click', onMapClick)`

---

#### Form Submit Handler
```javascript
document.getElementById('routeForm').addEventListener('submit', function(e) { ... })
```
**Description**: Validates and prepares form for submission
**Validation**:
- Checks if at least one node exists
- Alerts user if no nodes

**Side Effects**:
- Serializes nodes to JSON
- Stores in hidden field `nodesData`

**Returns**: `boolean` - true to submit, false to prevent

---

## Marker Events

Each marker has the following events attached:

### `dragend`
**Description**: Fires when marker is dragged
**Handler**: Updates node coordinates
**Side Effects**:
- Updates `nodes[index].lat` and `lng`
- Calls `updateNodesList()`

### `click`
**Description**: Fires when marker is clicked
**Handler**: Opens node editor
**Side Effects**:
- Calls `editNode(marker.nodeIndex)`

---

## Data Flow

### Adding a Node
1. User clicks map → `onMapClick(e)`
2. `addNode(lat, lng)` called
3. Node object created and added to `nodes[]`
4. Marker created with custom icon
5. Marker added to `markers[]` and map
6. Popup attached to marker
7. `updateNodesList()` refreshes UI
8. `updatePolyline()` draws connecting line
9. `editNode(index)` opens editor

### Editing a Node
1. User clicks marker or edit button → `editNode(index)`
2. Form fields populated
3. Editor shown and scrolled into view
4. User modifies title/content
5. User clicks "Save" → `saveNodeEdit()`
6. Node updated in `nodes[]`
7. Marker popup updated
8. `updateNodesList()` refreshes UI
9. Editor closed

### Deleting a Node
1. User clicks delete button → `deleteNode(index)`
2. Confirmation dialog shown
3. If confirmed:
   - Marker removed from map
   - Node removed from `nodes[]`
   - Marker removed from `markers[]`
   - All marker numbers updated
   - `updateNodesList()` refreshes UI
   - `updatePolyline()` redraws line

### Submitting Form
1. User clicks "Create Route"
2. Form submit event fires
3. Validation checks node count
4. If valid:
   - Nodes serialized to JSON
   - JSON stored in `nodesData` hidden field
   - Form submits to server
5. If invalid:
   - Alert shown
   - Submission prevented

---

## Integration with PHP

### Data Serialization
JavaScript sends node data as JSON string in hidden field:
```javascript
document.getElementById('nodesData').value = JSON.stringify(nodes);
```

PHP receives and decodes:
```php
$nodes = json_decode($_POST['nodes_data'] ?? '[]', true);
```

### Data Structure Match
JavaScript node object matches PHP expectations:
```javascript
// JavaScript
{ lat: 63.1055, lng: 21.5929, title: "Node 1", content: "..." }

// PHP processes as
$node['lat']     → latitude  (DECIMAL)
$node['lng']     → longitude (DECIMAL)
$node['title']   → title     (TEXT)
$node['content'] → content   (TEXT)
```

---

## Best Practices

### Performance
- Minimize DOM updates by batching changes
- Use event delegation where possible
- Update polyline only when necessary

### Security
- Always use `escapeHtml()` for user input display
- Validate data before submission
- Use prepared statements on server

### User Experience
- Auto-open editor for new nodes
- Provide visual feedback (hover states)
- Show empty states with helpful messages
- Confirm destructive actions (delete)

### Maintainability
- Keep functions focused and single-purpose
- Use descriptive variable names
- Comment complex logic
- Follow consistent naming conventions

---

## Debugging Tips

### Common Issues

**Map not loading:**
- Check Leaflet CSS is loaded
- Verify map container has height
- Check browser console for errors

**Markers not appearing:**
- Verify coordinates are valid
- Check marker array population
- Inspect map object in console

**Polyline not updating:**
- Ensure `updatePolyline()` is called
- Check nodes array has 2+ items
- Verify coordinates are numbers

**Form not submitting:**
- Check validation in submit handler
- Verify nodes array is not empty
- Check browser console for errors

### Console Commands
```javascript
// View all nodes
console.log(nodes);

// View all markers
console.log(markers);

// Get map bounds
console.log(map.getBounds());

// Count nodes
console.log(`Total nodes: ${nodes.length}`);
```

---

## Browser Compatibility

| Feature | Chrome | Firefox | Safari | Edge |
|---------|--------|---------|--------|------|
| Leaflet | ✅ | ✅ | ✅ | ✅ |
| Arrow functions | ✅ | ✅ | ✅ | ✅ |
| Template literals | ✅ | ✅ | ✅ | ✅ |
| Destructuring | ✅ | ✅ | ✅ | ✅ |
| Array methods | ✅ | ✅ | ✅ | ✅ |

**Minimum Versions:**
- Chrome 51+
- Firefox 54+
- Safari 10+
- Edge 15+
