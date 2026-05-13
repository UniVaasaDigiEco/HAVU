# HAVU-trailgame

## Live Testing Checklist – Per-Route GPS Threshold

### 1. Admin – Create new route

- [X] Open new-route.php
- [X] Verify the GPS threshold field appears between description and the public/private toggle
- [X] Confirm slider defaults to **25**, number input shows **25**, badge shows **25 m**
- [X] Drag slider to **15** → number and badge update instantly
- [X] Type **50** in the number input → slider jumps to 50
- [X] Type **10** (below min) → on blur it clamps to **15**
- [X] Type **99** (above max) → on blur it clamps to **50**
- [X] Fill in route title, add at least one node, set threshold to **20**, submit
- [X] In phpMyAdmin (or MySQL CLI): `SELECT gps_threshold FROM routes ORDER BY id DESC LIMIT 1;` → expect `20`

### 2. Admin – Create with invalid threshold (edge case)

- [X] Use browser DevTools to change the number input `min/max` to bypass HTML constraints, submit with value `5`
- [X] Expect a flash error message (should say the threshold must be between 15–50)
- [X] Route should **not** be created

### 3. Admin – Edit existing route

- [X] Open edit-route.php, load the route created in step 1
- [X] Confirm slider and number input prefill to **20** (the saved value)
- [X] Change threshold to **35**, save
- [X] Reload the edit page and select the same route → confirm both inputs show **35**
- [X] Check DB: `SELECT gps_threshold FROM routes WHERE ...` → expect `35`

### 4. Admin – Edit with invalid threshold (edge case)

- [X] Same as step 2 but via the edit form with a tampered value
- [X] Expect flash error, route **not** updated

### 5. Game page – Per-route threshold consumed

- [X] Open the game for the edited route in a browser with DevTools console open
- [X] Run: `console.log(routeData.gps_threshold)` → expect `35`
- [X] Run: `console.log(ROUTE_GPS_THRESHOLD)` → expect `35`
- [X] Create a second route with threshold **15**, open its game page, verify `ROUTE_GPS_THRESHOLD` = `15`

### 6. Game page – Proximity logic uses per-route threshold

- [X] With `REQUIRE_GPS_PROXIMITY = true` (default), simulate a GPS position far from a node via DevTools: `checkProximity(0, 0)` → nodes should **not** be in proximity
- [X] Simulate a position right on the node coordinates: `checkProximity(<node_lat>, <node_lng>)` → mark-visited button should appear

### 7. Admin test game page

- [X] Open testGame.php for a route with threshold **15**
- [X] Console: `ROUTE_GPS_THRESHOLD` → expect `15`
- [X] Toggle the GPS restriction off → checkpoint should be markable from anywhere
- [X] Toggle back on → proximity gate re-engaged

### 8. Legacy route (pre-existing data)

- [ ] Open the game for any route created **before** this change (gps_threshold = 25 from DB default)
- [ ] Console: `routeData.gps_threshold` → expect `25`; `ROUTE_GPS_THRESHOLD` → expect `25`

### 9. All three languages

- [ ] Switch locale to **EN** → GPS threshold label and help text visible in the form
- [ ] Switch to **SV** → same
- [ ] Switch to **FI** → same

Continued with Autopilot: Task not yet complete

Provided a 9-section manual testing checklist covering create/edit form UI behavior, server-side validation edge cases, DB persistence verification, per-route threshold consumption in game.php and testGame.php, legacy route fallback, and locale switching.
