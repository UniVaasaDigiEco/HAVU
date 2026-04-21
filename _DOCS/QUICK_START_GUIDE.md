# Quick Start Guide - New Route Creation

## Prerequisites
✅ XAMPP/WAMP/MAMP running with PHP 8.2+  
✅ MySQL database configured  
✅ User logged in as admin  
✅ Dependencies installed (`npm install` and `composer install`)

## Step-by-Step: Create Your First Route

### 1. Access the Admin Dashboard
```
Navigate to: http://localhost/HavuGamification/pages/admin/dashboard.php
```

### 2. Click "Add a new route"
This opens `new-route.php`

### 3. Fill in Route Details (Left Panel)

**Required:**
- **Route Title**: e.g., "Campus Walking Tour"

**Optional:**
- **Route Description**: e.g., "A scenic walk through the University of Vaasa campus"
- **Publish immediately**: Toggle ON to make visible to users
- **Publication Date**: Set a future date for scheduled publishing

### 4. Add Nodes to Your Route

#### Method 1: Click on Map
1. Click anywhere on the map
2. A numbered marker appears
3. Node editor opens automatically
4. Fill in:
   - **Node Title**: e.g., "Main Entrance"
   - **Node Content**: e.g., "Welcome to the university! This historic entrance..."
5. Click "Save Node"

#### Method 2: Manual Coordinates
1. Drag an existing marker to a new position
2. Click the marker to edit
3. Update title and content
4. Click "Save Node"

### 5. Add More Nodes
Repeat step 4 to add additional waypoints. Each node will be numbered sequentially.

### 6. Organize Your Route

**Reorder Nodes:**
- Click ↑ (up arrow) to move a node earlier in the sequence
- Click ↓ (down arrow) to move a node later in the sequence

**Edit Node:**
- Click ✏️ (pencil icon) to modify title/content

**Delete Node:**
- Click 🗑️ (trash icon) to remove a node
- Confirm deletion when prompted

**Reposition Node:**
- Drag the marker on the map to adjust coordinates

### 7. Review Your Route
- Check the connecting dashed line shows the correct path
- Verify all nodes are in the correct order
- Ensure all titles and content are filled in

### 8. Submit
Click the green "Create Route" button at the bottom

### 9. Success!
You should see a green success message. Your route is now saved in the database!

---

## Example: Creating a Campus Tour Route

### Route Details
```
Title: "University of Vaasa Campus Tour"
Description: "Explore the main buildings and facilities of our beautiful campus"
Publish: ✓ Yes
Publication Date: [Today's date]
```

### Nodes (Example)

**Node 1: Main Library (Tritonia)**
- Coordinates: Click on library building
- Title: "Tritonia Academic Library"
- Content: "Welcome to Tritonia! This modern library serves three institutions and offers excellent study spaces, research materials, and digital resources."

**Node 2: Main Building (Tervahovi)**
- Coordinates: Click on main building
- Title: "Tervahovi Building"
- Content: "The main academic building housing lecture halls, seminar rooms, and faculty offices. Most of your classes will be held here."

**Node 3: Student Union (Ankkuri)**
- Coordinates: Click on student union
- Title: "Ankkuri Student Center"
- Content: "The heart of student life! Find student services, cafes, event spaces, and student organization offices here."

**Node 4: Technology Building (Technobothnia)**
- Coordinates: Click on tech building
- Title: "Technobothnia"
- Content: "State-of-the-art engineering and technology labs. Home to cutting-edge research in automation and energy systems."

---

## Tips for Success

### 🎯 Route Planning
- Plan your route order before adding nodes
- Start with major landmarks
- Keep walking distance reasonable (< 2km recommended)
- Consider accessibility

### 📝 Writing Node Content
- Keep titles short and clear (< 50 characters)
- Make content engaging and informative
- Include interesting facts or history
- Mention what users should look for
- Typical length: 100-300 characters

### 🗺️ Map Usage
- Zoom in for precise placement (scroll wheel)
- Drag map to pan (click and drag)
- Drag markers to fine-tune positions
- Double-click marker to see popup

### ✅ Quality Checklist
Before submitting, verify:
- [ ] Route has a descriptive title
- [ ] All nodes have meaningful titles
- [ ] All nodes have content (not empty)
- [ ] Nodes are in logical order
- [ ] Markers are accurately positioned
- [ ] Route forms a sensible path
- [ ] Publication settings are correct

---

## Troubleshooting

### "Please add at least one node to the route"
**Solution**: You must add at least one node before submitting. Click on the map to add your first node.

### "Node title is required"
**Solution**: Every node must have a title. Fill in the "Node Title" field in the editor.

### "Route title is required"
**Solution**: The route must have a title. Fill in the "Route Title" field in the left panel.

### Map not showing
**Solution**: 
1. Check internet connection (map tiles load from internet)
2. Clear browser cache
3. Try different browser
4. Check browser console for errors (F12)

### Markers disappearing
**Solution**: 
1. Don't refresh the page (unsaved data will be lost)
2. Make sure you clicked "Save Node" after editing
3. Check that nodes array is populating (browser console: `console.log(nodes)`)

### Can't drag marker
**Solution**: 
1. Markers should be draggable by default
2. Try clicking and holding for 1 second before dragging
3. Check browser console for JavaScript errors

### Changes not saving
**Solution**: 
1. Make sure you clicked "Create Route" at the bottom
2. Check for error messages at the top of the page
3. Verify database connection is working
4. Check PHP error logs

---

## Keyboard Shortcuts

While in node editor:
- **Tab**: Move between fields
- **Escape**: Cancel editing (must add manual handler)
- **Enter**: In title field, moves to content (default browser behavior)

---

## What Happens After Creation?

### Database Records Created
1. **Route record**: Stores route title, description, publication status
2. **Node records**: One for each waypoint with coordinates and content  
3. **Cross-reference records**: Links nodes to route with order numbers

### Where to Find Your Route
- View all routes: `dashboard.php` (when list feature is implemented)
- Edit route: `edit-route.php?id=[route_id]` (when implemented)
- User view: `game.php` (when route selection is implemented)

### Publishing
- **Published immediately**: Available to users right away
- **Scheduled**: Will become available on publication date
- **Unpublished**: Only visible to admins (for testing)

---

## Advanced Features (Future)

### Coming Soon
- 📸 Add photos to nodes
- 📋 Import nodes from CSV
- 🗺️ GPS coordinate input
- 🎨 Custom marker colors
- 📏 Show route distance
- ⏱️ Estimated completion time
- 🏷️ Tags and categories
- 👥 Collaborative editing
- 📊 Route analytics

---

## Need Help?

### Documentation
- `_DOCS/new-route-page-guide.md` - Full feature documentation
- `_DOCS/javascript-api-reference.md` - JavaScript API details
- `_DOCS/IMPLEMENTATION_SUMMARY.md` - Technical implementation

### Code Files
- `pages/admin/new-route.php` - Main file
- `classes/route.class.php` - Route class reference
- `classes/node.class.php` - Node class reference

### Database
- `_SQL/jansoftw_havu_structure.sql` - Database schema
- `_SQL/migration_add_user_type.sql` - User type migration

---

## Quick Reference Card

| Action | How To |
|--------|--------|
| Add node | Click map |
| Edit node | Click marker OR click pencil icon |
| Delete node | Click trash icon |
| Move node up | Click up arrow |
| Move node down | Click down arrow |
| Reposition node | Drag marker |
| View popup | Double-click marker |
| Zoom map | Scroll wheel |
| Pan map | Drag background |
| Save changes | Click "Save Node" |
| Cancel edit | Click "Cancel" |
| Submit route | Click "Create Route" |

---

## Best Practices Summary

✅ **DO:**
- Plan route before creating
- Use descriptive titles
- Add meaningful content
- Test the route walking path
- Verify coordinates are accurate
- Save frequently (after each node)

❌ **DON'T:**
- Leave content fields empty
- Create routes without testing
- Place nodes too far apart
- Use vague titles like "Node 1"
- Forget to set publication status
- Refresh page with unsaved changes

---

**Ready to create your first route? Let's go! 🚀**
