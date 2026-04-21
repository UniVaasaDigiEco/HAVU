# HAVU Gamification — User Manual

## Overview

HAVU Gamification is a location-based outdoor game platform. Admins create geo-tagged routes with content nodes, and players follow those routes in real life using their phone's GPS.

---

## For Players

### Playing a Route (pages/game.php)

1. Open the game via a link or from the Admin Dashboard's "Route Testing" section.
2. Allow location access when the browser asks — GPS is required.
3. A map loads showing your route as a dashed line with colored markers:
   - Green = Start node
   - Gold = Finish node
   - Red = Unvisited nodes
   - Blue dot = Your current position
4. Walk toward a node. When you are within 50 meters, a popup opens automatically with the node's content.
5. Click "Mark as Visited" in the popup to collect the node and earn an acorn.
6. The progress bar at the bottom tracks how many nodes you've completed.
7. The info panel (tap the route title button at the top) shows your acorn count and the distance to the next node.
8. When all nodes are visited, a congratulations message appears.

  ---
## For Administrators

**All admin pages require you to be logged in.**

### Dashboard (pages/admin/dashboard.php)

The main hub. From here you can:
- Navigate to route management actions (create, edit, delete).
- Test a route: Select a route from the dropdown and click Play to open it in the game view.

---
### Create a Route (pages/admin/new-route.php)

1. Enter a Route Title (required) and optional description.
2. The publication date is set to today automatically.
3. Click anywhere on the map to add nodes. A node editor panel appears.
4. For each node, fill in:
   - Node Title (required)
   - Node Content — text shown to players when they reach this location
5. Use the arrow buttons in the node list to reorder nodes.
6. Use the trash icon to remove a node.
7. Drag a marker on the map to fine-tune its position.
8. Click Create Route when done.

  ---
### Edit a Route (pages/admin/edit-route.php)

1. Select a route from the "Select Route to Edit" dropdown and click Load Route.
2. The existing nodes and route details load into the editor.
3. Modify the title, description, publication date, or nodes as needed (same controls as creating).
4. Click Update Route to save changes.

  ---
### Delete a Route (pages/admin/delete-route.php)

1. Select the route to remove from the dropdown.
2. Click Delete Selected Route and confirm the prompt.
3. This action cannot be undone.
4. Click Back to Dashboard to return without deleting.
