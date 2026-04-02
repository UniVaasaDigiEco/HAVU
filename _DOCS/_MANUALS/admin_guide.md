# HAVU Trail Game — Admin Guide

## Overview

As an admin you can create, edit, and delete routes. Each route is a sequence of GPS checkpoints (nodes) that players walk to in real life. You can add text, images, and videos to each checkpoint.

All admin pages require you to be logged in with an admin account.

---

## Accessing the Admin Panel

1. Go to the app URL and log in with your admin credentials (https://jansoftworks.fi/HavuGamification).
2. You'll be taken directly to the **Admin Dashboard** (`pages/admin/dashboard.php`).
3. From the dashboard you can navigate to: add route, edit route, delete route, and test a route.

---

## Adding a New Route

Navigate to **"Lisää uusi reitti"** from the dashboard.

### Step 1 — Enter route details

On the left side of the screen, fill in:
- **Route name** (required)
- **Description** (optional — shown to players on the route selection page)

The publication date is set to today automatically.

### Step 2 — Add checkpoints on the map

The right side shows an interactive map.

- **Click anywhere on the map** to place a checkpoint marker.
- A node editor panel appears at the bottom right.
- Fill in the **checkpoint title** (required).
- Add **checkpoint content** using the rich text editor — you can write text, add bullet points, embed images, and embed videos.
  - You can click the full-screen icon in the editor to expand it for easier editing.
- Click **"Tallenna"** (Save) to confirm the checkpoint data.

> **Important:** Clicking "Peruuta" (Cancel) does not save the checkpoint content, but the marker stays on the map. You can click it again later to fill in the details, or remove it using the trash icon in the node list.

Repeat for each checkpoint on your route. The first checkpoint becomes the starting point (🚀) and the last becomes the finish (🏁).

### Step 3 — Arrange checkpoints

The left side shows a numbered list of all checkpoints. Use the **arrow buttons** (↑ ↓) to change their order. Players will visit them in this order from top to bottom.

To remove a checkpoint, click the **trash icon** next to it in the list.

To move a checkpoint's position on the map, **drag the marker** to a new location.

### Step 4 — Create the route

Click **"Luo reitti"** (Create route) at the bottom of the page.

The route is saved and immediately visible to players.

---

## Editing a Route

Navigate to **"Muokkaa reittiä"** from the dashboard.

1. Select the route you want to edit from the dropdown at the top of the page.
2. Click **"Lataa reitti"** (Load route).
3. The route loads into the editor — all existing checkpoints appear on the map and in the list.
4. Make your changes:
   - Edit the route name or description on the left.
   - Click a marker on the map to edit that checkpoint's title and content.
   - Add new checkpoints by clicking the map.
   - Remove checkpoints with the trash icon.
   - Reorder checkpoints with the arrow buttons.
   - Drag markers to adjust GPS positions.
5. Click **"Tallenna muutokset"** (Save changes) when done.

> You can only edit routes that belong to your own account.

---

## Deleting a Route

Navigate to **"Poista reitti"** from the dashboard.

1. Select the route to delete from the dropdown.
2. Click **"Poista valittu reitti"** (Delete selected route).
3. A confirmation dialog asks: *"Oletko varma, että haluat poistaa tämän reitin? Tätä toimintoa ei voi peruuttaa."* (Are you sure? This cannot be undone.)
4. Click OK to confirm.

The route and all its checkpoints are permanently deleted. This cannot be reversed.

To go back without deleting, click **"Takaisin hallintapaneeliin"** (Back to dashboard).

---

## Testing a Route

Before sharing a route with players, you can test it yourself from the dashboard.

1. In the **"Reittien testaaminen"** section, select a route from the dropdown.
2. Click **"Pelaa"** (Play).
3. The game opens in a separate view — same experience as players get.

This lets you verify that checkpoints are in the right locations, content appears correctly, and the route is playable end-to-end.

---

## Quick Reference

| Task | Page |
|---|---|
| Overview and navigation | Admin Dashboard |
| Create a new route | Lisää uusi reitti |
| Edit an existing route | Muokkaa reittiä |
| Delete a route | Poista reitti |
| Test a route in-game | Admin Dashboard → Reittien testaaminen |