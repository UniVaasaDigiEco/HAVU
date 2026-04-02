# Node Challenges — Design Spec

**Date:** 2026-04-02
**Status:** Approved

## Overview

Add optional challenges to route nodes. A challenge is either a multiple-choice question or a free-text answer. Players can see the challenge question while walking toward the node, but cannot answer (or mark the node as visited) until GPS proximity is confirmed.

The game is casual — no scores, no leaderboards — so verification runs entirely client-side. Players may be in forests with flaky connectivity, so no server round-trips are required for challenge verification.

---

## Data Model

### Schema change

Add a single nullable JSON column to the `nodes` table:

```sql
ALTER TABLE nodes ADD COLUMN challenge_data JSON NULL DEFAULT NULL;
```

`NULL` means no challenge — the node behaves exactly as before.

### JSON structure

**Multiple choice:**
```json
{
  "type": "multiple_choice",
  "question": "Mitä puulajia täällä on eniten?",
  "options": ["Mänty", "Kuusi", "Koivu", "Haapa"],
  "correct_index": 0
}
```

**Text answer:**
```json
{
  "type": "text",
  "question": "Mikä on lähimmän lammen nimi?",
  "answer": "Metsälampi"
}
```

Since verification is client-side, `correct_index` and `answer` are included in the data sent to the browser. This is an accepted trade-off given the casual nature of the game.

---

## Admin Editor

### Where

The challenge panel is added to the **node editor card** in both `pages/admin/new-route.php` and `pages/admin/edit-route.php`. The Summernote description editor is unchanged.

### UI

A yellow-bordered "Haaste (valinnainen)" panel appears below the Summernote editor. It contains:

- **Type selector** — three pill buttons: `Ei haastetta` / `Monivalinta` / `Tekstivastaus`
- **Ei haastetta (default):** no fields shown; `challenge_data` saved as `NULL`
- **Monivalinta:** question text field + 2–4 option fields with radio buttons to mark the correct answer + "Lisää vaihtoehto" button (max 4) + ✕ to remove options (min 2)
- **Tekstivastaus:** question text field + correct answer text field + note that fuzzy matching (~70%) is applied automatically

### Data flow

Challenge data is collected from the panel into the existing `nodes` JavaScript array (alongside `title`, `lat`, `lng`, `content`) and submitted as part of `nodes_data` JSON. `actions/create_route.php` and `actions/update-route.php` pass it through to the DB as-is.

The `Node` class and `Route::toJavaScript()` need to include `challenge_data` in their serialization.

---

## Game Page

### Client-side node data

`challenge_data` is included in the `routeNodes` array built from `routeData`. No stripping needed since verification is client-side.

### Popup states

Each node popup has three states:

| State | Challenge inputs | Check-in button |
|---|---|---|
| Out of proximity (or no GPS yet) | Visible, disabled. Badge: "🔒 Ole lähempänä vastataksesi" | Disabled (grey) |
| In proximity, unanswered | Visible, enabled. Badge: "📍 Olet paikalla!" | Disabled (grey) |
| Answered correctly | Collapsed to green "✅ Haaste suoritettu!" confirmation | Enabled (green) |

Nodes with no challenge (`challenge_data` is null) skip straight to "check-in enabled" once in proximity (or immediately if `REQUIRE_GPS_PROXIMITY` is `false`).

### Challenge verification (client-side JS)

**Multiple choice:** compare selected option index against `correct_index`.

**Text answer:** case-insensitive Levenshtein similarity:
```
similarity = 1 - (levenshtein(input, answer) / Math.max(input.length, answer.length))
```
Pass threshold: ≥ 0.70. Both strings lowercased before comparison.

On correct answer: challenge panel collapses to green confirmation, check-in button enables.
On wrong answer: brief shake/error state on the input, player can try again. No attempt limit.

### GPS proximity toggle

Add `REQUIRE_GPS_PROXIMITY` boolean constant to `config/constants.php`. PHP embeds it in the game page as a JS constant. When `false`, proximity checks are skipped (for development/testing). Default: `true`.

---

## Files to Change

| File | Change |
|---|---|
| `_SQL/` | New migration file: `add_challenge_data_to_nodes.sql` |
| `config/constants.php` | Add `REQUIRE_GPS_PROXIMITY` constant |
| `classes/node.class.php` | Add `challenge_data` property, getter, include in `toArray()` |
| `classes/route.class.php` | Ensure `challenge_data` passes through `toJavaScript()` |
| `pages/admin/new-route.php` | Add challenge panel UI + JS to collect challenge data into nodes array |
| `pages/admin/edit-route.php` | Same challenge panel + populate from loaded route data |
| `actions/create_route.php` | Pass `challenge_data` from node JSON to INSERT statement |
| `actions/update-route.php` | Pass `challenge_data` from node JSON to INSERT statement |
| `pages/game.php` | Include `challenge_data` in `routeNodes`, use `REQUIRE_GPS_PROXIMITY`, render challenge UI in popup, implement Levenshtein verify function |

---

## Out of Scope

- Server-side answer verification
- Multiple correct answers
- Attempt limits or cooldowns
- Challenge analytics / tracking
- Challenge types beyond multiple-choice and text
