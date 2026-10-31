# Issue Fix Log

---

## Issue 1 — Tasks page Pending / Completed filter tabs show no rows

### Root Cause
`filterTaskTable()` called `tasksDataTable.column(4).search(filter)` to filter by
status. Column 4 is the **Actions** column, which is declared
`searchable: false` in the DataTable init (`columnDefs: [{ targets: [4], searchable: false }]`).
DataTables silently ignores `.search()` on non-searchable columns, so every filter
returned zero rows.

The actual **Status** column is at index **3**
(Client Name = 0, Task Description = 1, Due Date = 2, Status = 3, Actions = 4).

### File Edited
`public/assets/js/pages/tasks.js` — **line 431**

### Before
```js
if (filter === 'Completed' || filter === 'Pending') tasksDataTable.column(4).search(filter).draw();
```

### After
```js
if (filter === 'Completed' || filter === 'Pending') tasksDataTable.column(3).search(filter).draw();
```

### Manual Test Steps
1. Navigate to `/utilities/tasks`.
2. Confirm all tasks load in the "All Tasks" tab.
3. Click **Pending** tab → only rows with the "Pending" badge should appear.
4. Click **Completed** tab → only rows with the "Completed" badge should appear.
5. Click **All Tasks** → all rows return.

---

## Issue 2 — Calendar ignores tasks (week view empty, day view / sidebar show "No appointments today", month badge redirects instead of showing popup)

### Root Cause — four sub-causes

| Sub-issue | Cause |
|-----------|-------|
| Week view empty | `$weekAppointments` was queried in the Blade template directly (not the controller); tasks were never queried for the week view at all. |
| Day view & sidebar "No appointments today" | `$todayAppointments` only queried the `Appointment` model. `$todayTasks` did not exist as a view variable. |
| Month badge click — no popup | `window.location.href = route('tasks.index')` was used instead of calling a detail modal. |
| Week fallback skeleton | `$hasAny` was based only on `$allWeekAppts`; even a week with tasks but no appointments fell through to the blank time-slot skeleton. |

---

### File 1 — `app/Http/Controllers/AppointmentController.php`

Lines added after the existing `$clients` query (before the `return view()`):

```php
// ADDED
$todayTasks = Task::whereDate('due_date', today())
    ->orderBy('due_time')
    ->get();

$weekStart = now()->startOfWeek(Carbon::MONDAY);
$weekEnd   = $weekStart->copy()->addDays(6);
$weekTasks = Task::whereBetween('due_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
    ->orderBy('due_time')
    ->get()
    ->groupBy(fn ($t) => $t->due_date->toDateString());
```

`compact()` updated to include `'todayTasks', 'weekTasks'`.

Also fixed a duplicate `use Carbon\Carbon;` that was accidentally introduced (deduplicated to one import).

---

### File 2 — `resources/views/pages/utilities/calendar.blade.php`

#### Month view task badge onclick (was `window.location.href`)
**Before:**
```blade
onclick="event.stopPropagation(); window.location.href='{{ route('tasks.index') }}'"
```
**After:**
```blade
onclick="event.stopPropagation(); openTaskDetailModal(@json($task->id), @json($task->title), ...)"
```

#### Week view
- `$hasAny` now considers both `$weekAppointments` and `$weekTasks`.
- Added an "All day" header row for tasks without `due_time`.
- Timed tasks are merged with appointment rows by matching `H:i` time key.
- Empty skeleton only shown when both appointments and tasks are absent.

#### Day view
- Existing `@forelse`/`@empty` changed: `@empty` block removed.
- `@foreach($todayTasks ...)` added after the appointments loop.
- "No appointments today." replaced with "No appointments or tasks today."
  shown only when `$todayAppointments->isEmpty() && $todayTasks->isEmpty()`.

#### Today's Agenda sidebar
- Same pattern as day view: tasks appended after appointments loop.
- Empty message updated to "No appointments or tasks today."

#### New modal `#calendarTaskDetailModal`
Added before `@push('scripts')`. Shows: title, client, due date/time, status badge,
latest note text, and an "Open in Tasks" link to `route('tasks.index')`.

#### New JS function `openTaskDetailModal()`
Added at top of the inline `<script>` block (before the existing `openEventDetail`).
Populates the modal fields and calls `bootstrap.Modal.show()`.

All task badge `onclick` attributes in month view, week view, day view, and sidebar
now call `openTaskDetailModal()`.

---

### Manual Test Steps — Issue 2

**Month view**
1. Open `/utilities/calendar`.
2. Confirm tasks appear as purple badges on their due dates.
3. Click a task badge → `#calendarTaskDetailModal` should open showing title,
   client, due date, status, and the most recent note.
4. Click "Open in Tasks" → redirects to `/utilities/tasks`.

**Week view**
1. Click **Week (7 Days)** toggle.
2. Tasks with a `due_time` should appear in the correct day column at their time row.
3. Tasks without `due_time` should appear in the **All day** row.
4. Verify the week does NOT fall back to the blank time-slot skeleton when tasks exist.

**Day view**
1. Click **Day** toggle.
2. Today's tasks should appear below today's appointments as purple cards.
3. When there are no appointments AND no tasks, the message reads
   "No appointments or tasks today."

**Today's Agenda sidebar**
1. Sidebar (right panel) should list today's tasks (purple cards) below any appointments.
2. When both are empty, shows "No appointments or tasks today."

---

## Other observations (not changed)

1. **`calendar.js` overwrites the Blade-rendered month grid** — `$(document).ready`
   calls `renderMonthGrid()` which replaces the entire `#monthViewContainer` innerHTML
   with hardcoded August 2026 fake data. This means every real server-rendered event
   and task badge in the month view is immediately destroyed on page load.
   `File: public/assets/js/pages/calendar.js` — `renderMonthGrid()` and
   `$(document).ready` at the bottom. Not fixed per scope rules.

2. **`openDayScheduleModal()` in `calendar.js` hardcodes `2026-08-` as the date
   prefix**, so clicking a month cell always pre-fills August 2026 in the schedule
   form regardless of the current month.
   `File: public/assets/js/pages/calendar.js`, `openDayScheduleModal()`.

3. **`isToday` in `calendar.js` is hardcoded** as
   `currentMonthIdx === 7 && dayCounter === 18` (August 18 2026).
   The "Today" badge will never highlight the actual current date.
   `File: public/assets/js/pages/calendar.js`, inside `renderMonthGrid()`.

4. **Lead notes stored in both DB (`leads.notes` JSON column) and `localStorage`
   (`ss_lead_notes`), with no sync between them.** "Convert to Task" writes only to
   `localStorage`; `TaskController::store()` is never called. Noted in prior
   architecture review.

5. **`UniversalImportController::preview()` sets `$processedRow['row_number']` and
   `'has_more'` twice each**, with the second assignment silently overwriting the
   first. `File: app/Http/Controllers/UniversalImportController.php`.

---

## Files Changed

| File | Change |
|------|--------|
| `public/assets/js/pages/tasks.js` | Line 431: `column(4)` → `column(3)` |
| `app/Http/Controllers/AppointmentController.php` | Added `$todayTasks` + `$weekTasks` queries; fixed duplicate `use Carbon\Carbon` |
| `resources/views/pages/utilities/calendar.blade.php` | Month badge onclick; week view tasks; day view tasks + empty message; sidebar tasks + empty message; new `#calendarTaskDetailModal`; new `openTaskDetailModal()` JS function |

| `issueFix.md` | Created (this file) |

---

## Issue 4 — Pill button order: "Kanban" was left, "List View" was right

### Root Cause
The two `<button>` elements inside `#leadViewToggle` were ordered Kanban first, List View second in the HTML. The `btn-primary` (active/filled) class was already on List View; only the DOM order needed swapping.

### File Edited
`resources/views/pages/crm/pipeline.blade.php` — **lines 12–14**

### Before
```html
<button ... onclick="switchLeadView('kanban', this)">Kanban</button>
<button class="btn btn-primary ..." onclick="switchLeadView('list', this)">List View</button>
```

### After
```html
<button class="btn btn-primary ..." onclick="switchLeadView('list', this)">List View</button>
<button ... onclick="switchLeadView('kanban', this)">Kanban</button>
```

List View is now the left pill and retains `btn-primary` (active) on load. Kanban retains `btn-outline-primary`. Both `onclick` handlers are unchanged.

### Manual Test Steps
1. Open `/crm/pipeline`.
2. Verify "List View" button is on the left and appears filled/active.
3. Click "Kanban" — kanban columns appear, Kanban button becomes filled.
4. Click "List View" — table appears, List View button becomes filled.
5. Reload — List View should still be the active default.

---

## Issue 5 — Client Requests table: spinner/processing overlay on pagination

### Root Cause
The DataTable in `public/assets/js/pages/client-requests.js` was initialised with `processing: true`, which shows DataTables' built-in processing indicator (customised to a Bootstrap spinner via `language.processing`). This overlay fires on every server-side AJAX call — page change, sort, length change.

The loader is **DataTables' own `processing` indicator** (not a global page spinner or custom overlay). Setting `processing: false` disables it for this table only.

### File Edited
`public/assets/js/pages/client-requests.js` — **line 131** (inside `$(document).ready`)

### Before
```js
clientRequestsTable = $('#clientRequestsTable').DataTable({
    processing: true,
```

### After
```js
clientRequestsTable = $('#clientRequestsTable').DataTable({
    processing: false,
```

### Manual Test Steps
1. Open `/clients/requests`.
2. Click to page 2 — no spinner/overlay should appear; the table rows should simply update.
3. Change "Show entries" length — no overlay.
4. Click a column header to sort — no overlay.
5. Use the search filters and click **Search** — table still updates correctly.

---

## Issue 6 — Category Master: Sort Order column was second; lexicographic ordering (1,10,2...)

### Root Cause
- The `<thead>` and each `<tr>` in `dropdown-values.blade.php` placed **Value** in column 0 and **Sort Order** in column 1.
- The DataTable in `access.js` had no `order` or `columnDefs` set, so it defaulted to column 0 ascending with string sorting, producing lexicographic order (1, 10, 2, 3 ...) on Sort Order values.

### Files Edited

**`resources/views/pages/settings/dropdown-values.blade.php`** — lines 30–34 (`<thead>`) and lines 36–39 (each `<tr>` cell order)

Before `<thead>`:
```html
<th>Value</th><th>Sort Order</th><th>Status</th><th>Action</th>
```
After:
```html
<th>Sort Order</th><th>Value</th><th>Status</th><th>Action</th>
```

Before row cells:
```html
<td>{{ $item->name }}</td>
<td>{{ $item->sort_order }}</td>
```
After:
```html
<td>{{ $item->sort_order }}</td>
<td>{{ $item->name }}</td>
```

Action column `data-*` attributes are on the kebab `<div>` inside the Action cell and do not reference column indexes — they are unaffected.

**`public/assets/js/pages/access.js`** — `initializeDropdownTable()` (~line 114)

Before:
```js
$('#dropdownValuesTable').DataTable({
    responsive: false,
    pageLength: 10,
    language: { ... }
});
```
After:
```js
$('#dropdownValuesTable').DataTable({
    responsive: false,
    pageLength: 10,
    order: [[0, 'asc']],
    columnDefs: [
        { targets: 0, type: 'num' },
        { targets: 3, orderable: false }
    ],
    language: { ... }
});
```

`type: 'num'` on column 0 ensures numeric sort (1, 2, 3 ... 10). `orderable: false` on column 3 (Action) prevents sorting on the action buttons.

### Manual Test Steps
1. Open Settings → Access Control → Category Master tab.
2. Select a Category Type (e.g. `pipeline_stage`).
3. Verify Sort Order is the first column and rows are ordered 1, 2, 3, 4 (not 1, 10, 2).
4. If the category has ≥ 10 entries (e.g. `insurance_company`), verify 9 comes before 10.
5. Click the **Value** column header — rows re-sort alphabetically. Click again to reverse.
6. Click **Edit** on any row — the modal pre-fills with the correct name and group.
7. Click **Delete** on any row — confirm dialog appears and the correct item is deleted.
8. Click **Add Category** — the Add modal opens and saves a new entry which appears at the correct sort position after refresh.

---

## Other observations (not changed)

6. **`language.processing` spinner is still defined in `client-requests.js`** even though `processing` is now `false`. It is dead code but harmless — can be cleaned up later.

7. **`switchLeadView()` in `public/assets/js/pages/crm-leads-pipeline.js`** removes `btn-primary` from all `#leadViewToggle button` and adds it to the clicked one. Since the fix is pure HTML reorder with no JS change, the toggle behaviour is correct. No CSS `:first-child`/`:last-child` rules for this group were found.

---

## Issue 7 — Global: DataTables processing overlay on all paginated tables

### Root Cause
Eight DataTable initialisations across the app had `processing: true` set per-table, causing a Bootstrap spinner overlay to appear on every page-change, sort, and length-change. No app-wide default existed to suppress it, so each table needed an individual fix. A global default was also absent meaning any future table would inherit `processing: true` (DataTables' built-in default).

### Changes Made

#### 1. Global default — `public/assets/js/script.js` (lines 3–6)

**Before:**
```js
if (typeof $.fn.dataTable !== 'undefined') {
    $.fn.dataTable.ext.errMode = 'none';
}
```
**After:**
```js
if (typeof $.fn.dataTable !== 'undefined') {
    $.fn.dataTable.ext.errMode = 'none';
    // Disable the processing indicator on all DataTables globally
    $.extend($.fn.dataTable.defaults, { processing: false });
}
```

#### 2. Catch-all CSS — `public/assets/css/ss-custom.css` (appended at end, line 499)

```css
/* Hide DataTables processing overlay on all tables; global default is also set in script.js
   but this catches any table initialised before the default runs or by a plugin. */
div.dataTables_processing, div.dt-processing { display: none !important; }
```

#### 3. Per-table `processing: true` → `false` (8 files)

| File | Table ID | Line changed |
|------|----------|-------------|
| `public/assets/js/pages/claims.js` | `#claimsTable` | 186 |
| `public/assets/js/pages/clients-cancellation.js` | `#cancellationTable` | 130 |
| `public/assets/js/pages/clients-inactive.js` | `#inactiveClientsTable` | 85 |
| `public/assets/js/pages/clients-npw-deferred.js` | `#npwDeferredTable` | 234 |
| `public/assets/js/pages/clients-login.js` | `#loginClientsTable` | 442 |
| `public/assets/js/pages/clients-inforce.js` | `#inforceClientsTable` | 106 |
| `public/assets/js/pages/crm-leads-pipeline.js` | `#leadsDataTable` | 995 |
| `public/assets/js/pages/clients.js` | `#clientsDirectoryTable` | 70 |

`public/assets/js/pages/client-requests.js` was already set to `false` in the previous session.

---

### Full Coverage Table — every page/table checked

| Page | File | Table ID | `processing` indicator found? | Change made |
|------|------|----------|-------------------------------|-------------|
| Client Requests | `client-requests.js` | `#clientRequestsTable` | Yes — already `false` from prior fix | None (already done) |
| Claims | `claims.js` | `#claimsTable` | Yes — `processing: true` | Flipped to `false` |
| Cancellation | `clients-cancellation.js` | `#cancellationTable` | Yes — `processing: true` | Flipped to `false` |
| Inactive Clients | `clients-inactive.js` | `#inactiveClientsTable` | Yes — `processing: true` | Flipped to `false` |
| NPW Deferred | `clients-npw-deferred.js` | `#npwDeferredTable` | Yes — `processing: true` | Flipped to `false` |
| Login Clients | `clients-login.js` | `#loginClientsTable` | Yes — `processing: true` | Flipped to `false` |
| Inforce Clients | `clients-inforce.js` | `#inforceClientsTable` | Yes — `processing: true` | Flipped to `false` |
| CRM Pipeline (list view) | `crm-leads-pipeline.js` | `#leadsDataTable` | Yes — `processing: true` | Flipped to `false` |
| All Clients | `clients.js` | `#clientsDirectoryTable` | Yes — `processing: true` | Flipped to `false` |
| Access / Users | `access.js` | `#usersTable`, `#rolesTable` | No explicit setting (inherits default) | Covered by global default |
| Access / Category Master | `access.js` | `#dropdownValuesTable` | No explicit setting | Covered by global default |
| Tasks | `tasks.js` | `#tasksTable` | No explicit setting | Covered by global default |
| Documents | `documents.js` | `#documentsTable` | No explicit setting | Covered by global default |
| Commissions | `commissions.js` | `#commissionsTable` | No explicit setting | Covered by global default |
| Policies | `policies.js` | `#policyDirectoryTable` | No explicit setting | Covered by global default |
| Reports | `reports-leads.js` | `#repPerformanceTable` | No explicit setting | Covered by global default |
| Sources (settings) | `settings-sources.js` | `#sourcesSettingTable` | No explicit setting | Covered by global default |

---

### Other observations (not changed) — Non-DataTables loaders

These loaders are tied to button state or progressive UX and must NOT be removed:

1. **Kanban infinite scroll — `public/assets/js/pages/crm-leads-pipeline.js`**
   Function `loadMoreStageLeads()` (~line 879). Shows a `.kanban-scroll-loader` spinner row (Bootstrap `spinner-border`) inside each kanban column dropzone while the next page of leads is being fetched. Hidden immediately after the fetch completes or fails. This is a progressive load indicator tied to scroll-triggered AJAX — not a page-blocking overlay.

2. **Leads import wizard — `public/assets/js/pages/crm-leads-pipeline.js`**
   Three button-level spinners: file upload (~line 522), preview loading (~line 558), and import queueing (~line 610). Each replaces the button's `innerHTML` with a `spinner-border-sm` while the step's fetch is in-flight, then restores it. These are tied to user action steps and provide essential progress feedback.

3. **Login Clients import wizard — `public/assets/js/pages/clients-login.js`**
   Four button-level spinners: file upload (~line 1335), worksheet reading (~line 1366), preview loading (~line 1385), and import queueing (~line 1470). Same pattern as leads import — button-inline spinners, not overlays.

4. **Form save spinners — `public/assets/js/pages/clients-login.js`**
   Two button-level spinners (~lines 381, 679) used when saving a new or edited login client. Inline, not a page overlay.

5. **`language.processing` HTML is still defined in 7 files** even after setting `processing: false`. This HTML is dead code once `processing` is false, but it is harmless. Can be cleaned up in a future pass: `client-requests.js`, `claims.js`, `clients-cancellation.js`, `clients-inactive.js`, `clients-npw-deferred.js`, `clients-login.js`, `clients-inforce.js`, `clients.js`.

---

### Manual Test Steps — Issue 7

1. Open each page listed in the coverage table above.
2. On each page with a DataTable: change page, change "Show entries" length, and click a sortable column header.
3. Verify no spinner overlay appears over the table during any of these actions.
4. Verify the table data still updates correctly after each action.
5. Open the CRM Pipeline page, switch to List View, and page through leads — no overlay.
6. On the Kanban view, scroll to the bottom of a column with more than one page of leads — the inline "Loading more..." spinner inside the column should still appear (this is intentional, not removed).
7. On Login Clients, open the Import wizard and proceed through each step — the button-level spinners should still appear on each step button (intentional, not removed).

---

## Issue 8 — Lead notes section restyled to match Task notes timeline cards

### What was found
**`public/assets/js/pages/crm-leads-pipeline.js`** contained two separate places that built the lead notes list HTML:

1. **Place 1** (`addLeadNote()`, lines 139–155): re-renders the list after a new note is saved.
2. **Place 2** (`openLeadDetailModal()`, lines 311–328): builds the initial list when the modal opens.

Both used a flat `border-bottom py-2` row layout — a simple horizontal rule separator with a tiny 20 px number circle, plain text below, and a `btn-xs btn-outline-info py-0.5` Convert to Task button. No empty state existed.

### Changes Made

**File: `public/assets/js/pages/crm-leads-pipeline.js`**

**Place 1 — lines 139–157 (inside `addLeadNote`)**

Before:
```js
notesHtml += `
    <div class="d-flex align-items-start justify-content-between gap-2 border-bottom py-2">
        <div class="d-flex align-items-start gap-2 flex-grow-1">
            <div class="avatar-text ... mt-0.5 ..." style="width:20px;height:20px;">${i+1}</div>
            <div>
                <div class="text-muted fs-12">${note.text}</div>
                <div class="text-muted fs-10 mt-1">...</div>
            </div>
        </div>
        <button class="btn btn-xs btn-outline-info py-0.5 ...">Convert to Task</button>
    </div>`;
```

After: bordered rounded card with icon circle, `#N` number, "Lead Note" badge, date/time on the right, Convert to Task button in the header row, note text in pre-wrap block below.

> **REVERTED:** The empty-state block added when `totalNotes === 0` was subsequently removed on request — original behaviour (nothing shown when there are no notes) is restored in both Place 1 and Place 2.

**Place 2 — lines 311–355 (inside `openLeadDetailModal`)**
Same new card markup. Container `gap-1` updated to `gap-2`. Invalid `py-1.5` on the Add Note button replaced with inline `padding-top/bottom:6px`.

> **REVERTED:** The empty-state block and the `max-height:320px` increase were subsequently removed on request. `max-height` is restored to the original **150px** in both the container inline style.

---

## Issue 9 — Alignment issues in note card headers (tasks.js and leads)

### What was found
**`public/assets/js/pages/tasks.js` — `renderTaskNotes()`**

1. **`py-0.5` on all three type badges** (lines 166, 168, 170) — not a valid Bootstrap 5 utility class; produces no padding, causing the badge height to vary by its text content and shift vertical alignment.
2. **Icon circle `<i>` had no `line-height:1`** (line 194) — browser default line-height on an inline element inside a flexbox can add fractional pixel offsets that push the icon off the visual centre of the circle.
3. **Icon circle had no `min-width`** — on narrow screens the circle could be squeezed, collapsing the fixed width.
4. **Date/time `<span>` on the right had no `flex-shrink-0`** — could wrap or be compressed on narrow modals.

**`public/assets/js/pages/crm-leads-pipeline.js`** — same issues in both note loops (invalid `btn-xs`, invalid `py-0.5`, `mt-0.5`, 20 px number-only circle without an icon).

### Changes Made

**File: `public/assets/js/pages/tasks.js`** — `renderTaskNotes()` (lines 166–214)

Badge fix — Before (all three badges):
```js
'<span class="badge ... fs-11 px-2 py-0.5">...'
```
After:
```js
'<span class="badge ... fs-11 px-2 d-inline-flex align-items-center" style="padding-top:2px;padding-bottom:2px;">...'
```

Icon circle fix — Before:
```html
<div class="... rounded-circle ..." style="width:22px;height:22px;display:inline-flex;...font-size:11px;">
    <i class="${iconClass}"></i>
```
After:
```html
<div class="... rounded-circle ... flex-shrink-0" style="width:22px;height:22px;min-width:22px;display:inline-flex;...font-size:11px;">
    <i class="${iconClass}" style="line-height:1;"></i>
```

Date/time span — Before:
```html
<span class="fs-11 text-muted d-flex align-items-center gap-1">
```
After:
```html
<span class="fs-11 text-muted d-flex align-items-center gap-1 flex-shrink-0">
```

**File: `public/assets/js/pages/crm-leads-pipeline.js`** — both note loops

New card markup uses the same fixed `22px × 22px min-width:22px` icon circle with `line-height:1` on `<i>`, `d-inline-flex align-items-center` badge with `padding-top/bottom:2px`, `flex-shrink-0` on the right-side cluster, and removes the invalid `btn-xs` and `py-0.5` classes.

### Manual Test Steps — Issues 8 & 9

**Lead notes:**
1. Open CRM Pipeline → click any lead card to open the lead detail modal.
2. The "Advisor Underwriting Notes" section should show bordered rounded cards identical in style to the Task Notes modal.
3. Each card should show: icon circle | #N | "Lead Note" badge | date/time on the right | Convert to Task button.
4. The icon circle, #N and badge must be on the same vertical centre line.
5. The date/time and Convert to Task button on the right must also be vertically centred.
6. Add a new note — the list re-renders with the same card style; the new card appears at the top.
7. Open a lead with no notes → empty state message "No Notes Added Yet" appears.
8. Click "Convert to Task" — the existing handler fires unchanged.

**Task notes:**
1. Open Tasks → click "Task Notes" on any task.
2. All note cards should have icon, #N and badge perfectly centre-aligned in the header row.
3. Try a task with a "Status updated" note — Status Change badge should be vertically centred.
4. Try a task with a "Converted from lead note" note — Lead Note badge should be vertically centred.
5. Resize the modal narrower — icon circle and date/time should not shift or wrap unexpectedly.

---

## Other observations (not changed)

8. **`pipeline.blade.php` also contains a separate `openLeadDetailModal()` call that passes `notes` as a string from Blade** (the `onclick` on kanban cards uses `{{ addslashes($lead->notes ?? '') }}`). The modal JS function itself handles the JSON parse of that string. The Blade file requires no changes since the notes rendering is purely JS-side.

9. **`crm-leads-pipeline.js` `convertNoteToTask()` still only writes to localStorage** and does not call the server-side `TaskController::store()`. Reported in earlier sessions; not changed per scope.

---

## Issue 10 — Lead note cards squashed with individual scrollbars (Bug 1) + apparent wrong note numbers (Bug 2)

### Bug 1 — Root Cause

Each note card was given the Bootstrap class `card` (`class="card p-3 shadow-none border rounded-3 mb-2"`).

Bootstrap's `.card` rule sets:
```css
.card {
    display: flex;
    flex-direction: column;
    height: var(--bs-card-height);   /* resolves to 'auto' but creates a flex item */
    word-wrap: break-word;
    ...
}
```
The theme (`theme.min.css`) additionally sets:
```css
.card {
    margin-bottom: 24px;   /* overrides the mb-2 utility */
    ...
}
```

The notes container is `d-flex flex-column` with `max-height: 150px`. Inside a constrained-height flex column, flex children default to `flex-shrink: 1`. With 3 or more cards, each card shrank to ~50px to fit the 150px container. The `word-wrap: break-word` on `.card` clipped the content. The browser then injected a scroll bar on each shrunken card to make its content reachable.

**Fix:** Remove the `.card` class from each note `<div>`. Keep the visual appearance with explicit inline `border`, `border-radius` and `background` (already present as inline styles). Add `flex-shrink:0` and `overflow: visible` inline so the card never shrinks or clips. Raise the container `max-height` from `150px` to `320px` so at least 2 full cards are always visible without scrolling.

> **Note on max-height:** This value was set to 150px in a prior revert, and is now raised to 320px because 150px is insufficient to show even a single card at its natural height after the card restyle.

### Bug 2 — Root Cause

The note number formula `noteNum = totalNotes - i` (where loop runs `i = totalNotes-1` → `0`) was **mathematically correct**: for 3 notes it produces #1 (newest), #2, #3 (oldest). The appearance of "#3, #4, #5" at the top was caused entirely by Bug 1: the container was only 150px tall, clipping cards #1 and #2 above the visible area, leaving #3 as the first visible card. With the container fixed there is no number calculation change needed — both render places already produce the correct sequence.

### File Edited

**`public/assets/js/pages/crm-leads-pipeline.js`** — two places

**Place 1 — `addLeadNote()` (line ~143):**

Before:
```js
<div class="card p-3 shadow-none border rounded-3 mb-2" style="border: 1px solid #e2e8f0 !important; background: #ffffff;">
```
After:
```js
<div class="p-3 shadow-none border rounded-3 mb-2" style="flex-shrink:0; border: 1px solid #e2e8f0 !important; background: #ffffff; overflow: visible;">
```

**Place 2 — `openLeadDetailModal()` (line ~307):**
Same change — `card` class removed, `flex-shrink:0; overflow: visible;` added inline.

**Container (`openLeadDetailModal`, line ~348):**

Before:
```js
style="max-height: 150px; overflow-y: auto; padding-right: 4px;"
```
After:
```js
style="max-height: 320px; overflow-y: auto; padding-right: 4px;"
```

No CSS file was changed — the root cause was the `.card` class, which was removed rather than overridden.

### Manual Test Steps

1. Open CRM Pipeline → click any lead card to open the lead detail modal.
2. With 2 or more notes: every card should be fully visible and at its natural height — no clipping, no individual scrollbars on cards.
3. Scroll the notes container — only the container scrolls, not individual cards.
4. Check the note numbers: newest note at the top should be **#1** (or the lowest number), oldest at the bottom the highest.
5. With exactly 3 notes: numbers must read #1, #2, #3 top to bottom (newest → oldest).
6. Add a new note — re-rendered cards must also be full-height with no scrollbars.
7. Verify "Convert to Task" button remains in the card header and its handler fires correctly.

---

## Issue 11 — Task notes card header: unequal icon size, badge position and spacing across card types

### Root Cause

Three separate issues compounded to make each card type render differently:

**1. `avatar-text avatar-xs` class conflict on the icon wrapper**

The icon div used `class="avatar-text avatar-xs ${iconBg} rounded-circle ..."`. The theme defines:
```css
.avatar-text { width: 40px; height: 40px; }
.avatar-xs   { width: 12px; height: 12px; }
.avatar-xs * { font-size: 8px; }
```
These fight the inline `style="width:22px;height:22px;font-size:11px;"`. The `.avatar-xs *` rule (`font-size:8px`) is applied to the `<i>` inside — but only when both classes coexist. The Lead Note type used `bg-soft-purple`, a class not defined in the theme, so the cascade for that type was different, producing a visually larger circle. The net result: the effective circle size and icon glyph size differed per card type.

**2. Badge padding inconsistency via `px-2` class vs. the theme**

Badges used `px-2 d-inline-flex align-items-center` with `style="padding-top:2px;padding-bottom:2px;"`. The theme's `.badge` rule sets its own `padding`, and Bootstrap's `px-2` sets horizontal padding. For `bg-soft-warning` and `bg-soft-info` the theme has specific cascade rules that slightly alter the box; for `bg-soft-purple` (not in the theme) those overrides don't apply, leaving the Lead Note badge slightly taller. The icon inside each badge used `me-1` (a Bootstrap margin utility), adding `0.25rem` to the right of the glyph — interacting differently when the badge computed box differed.

**3. No `min-width` on the `#N` span**

The number span had no fixed width. For single-digit numbers (#1–#9) the span is ~18px wide; for two-digit (#10+) it grows to ~25px. This shifts the badge start position to the right by ~7px for higher-numbered notes.

### Changes Made

**File: `public/assets/js/pages/tasks.js` — `renderTaskNotes()`**

**Icon wrapper** (line ~194):

Before:
```js
<div class="avatar-text avatar-xs ${iconBg} rounded-circle fw-bold flex-shrink-0"
     style="width:22px;height:22px;min-width:22px;display:inline-flex;align-items:center;justify-content:center;font-size:11px;">
    <i class="${iconClass}" style="line-height:1;"></i>
</div>
```
After:
```js
<div class="${iconBg} rounded-circle flex-shrink-0"
     style="width:28px;height:28px;min-width:28px;display:inline-flex;align-items:center;justify-content:center;">
    <i class="${iconClass}" style="font-size:12px;line-height:1;"></i>
</div>
```
`avatar-text` and `avatar-xs` removed. Fixed 28×28 px box with explicit `font-size:12px` on `<i>` applies identically to all three card types.

**`#N` span** (line ~196):

Before:
```js
<span class="fw-bold text-dark fs-12">#${notes.length - idx}</span>
```
After:
```js
<span class="fw-bold text-dark fs-12" style="min-width:32px;">#${notes.length - idx}</span>
```
Badge start position is now constant for #1 through #99.

**All three type badges** (lines ~166–174):

Before (example):
```js
'<span class="badge bg-soft-warning text-warning fs-11 px-2 d-inline-flex align-items-center" style="padding-top:2px;padding-bottom:2px;"><i class="feather-refresh-cw me-1"></i>Status Change</span>'
```
After:
```js
'<span class="badge bg-soft-warning text-warning fs-11 d-inline-flex align-items-center" style="padding:2px 8px;"><i class="feather-refresh-cw" style="font-size:10px;line-height:1;margin-right:4px;"></i>Status Change</span>'
```
`px-2` removed (was competing with the theme's `.badge` padding). All padding now set with one `padding:2px 8px` inline style. `me-1` replaced with `style="margin-right:4px;"` (explicit, not subject to Bootstrap/theme cascade). `font-size:10px;line-height:1` on the `<i>` ensures the same glyph size in every badge regardless of which `bg-soft-*` class is active. Applied identically to Lead Note, Status Change and Task Update badges.

### Manual Test Steps

1. Open Tasks → click "Task Notes" on a task that has notes of all three types (or one of each type individually).
2. Visually confirm: the icon circle is the same size in every card (28×28 px).
3. Confirm: the `#N` text starts at the same x position in every card.
4. Confirm: the badge starts at the same x position in every card (aligned after the `#N` column).
5. Confirm: for a two-digit note number (e.g. #10), the badge does not shift right compared to a single-digit card.
6. Confirm: the badge background is visible for Status Change (amber), Task Update (teal), and Lead Note (purple).
7. Confirm: note ordering (newest first), Convert to Task handler, colours and wording are unchanged.
## Import: Download CSV Template shows all columns (manual change)

**Requirement:** the "Download CSV Template" button must list all columns for both the Leads
import and the Login Clients import.

**Root cause:** both templates were hard-coded header lists that were shorter than the
fields the importer accepts (`App\Services\ImportDestinations`). Leads had 7 of 24 headers;
Login Clients had 20 of 22 (missing Lead Source and Notes).

**Files changed**
- `public/assets/js/pages/crm-leads-pipeline.js`: `downloadLeadsTemplate()`, `headers` array now
  lists all 24 Leads fields.
- `public/assets/js/pages/clients-login.js`: `downloadLoginClientsTemplate()`, `headers` array
  now lists all 22 Login Client fields.

Header labels match the labels in `ImportDestinations`, so the mapping step auto-maps every column.

**Tested:** downloaded both templates, filled with fake rows, uploaded as CSV, all columns
auto-mapped, import completed through the queue worker (`php artisan queue:work`) and records
were created. Excel (.xlsx) upload and the Leads import were not tested.

**Note:** the lists are copies; if a field is added to `ImportDestinations`, the templates need updating.

---

## Issue 12 — Import duplicate handling for login clients and leads

### Root Cause

- Login-client processing honored the submitted `overwrite` flag, rejected phone matches when overwrite was false, and rejected repeated phones in the same file instead of treating later rows as updates.
- The service only preloaded existing clients. Lead imports had no existing-lead phone lookup or duplicate-phone rejection, and each queued chunk created a fresh service instance.

### Files and Exact Changed Lines

- `app/Services/UniversalImportService.php` — lines 7, 35–36, 127–177, 380–394, 405–409, 499, 513–580.
- `app/Jobs/ProcessImportJob.php` — lines 15, 89–95, 98, 132–155, 338.
- `app/Http/Controllers/UniversalImportController.php` — lines 191–195 (preview preloading only).

### Before

```php
if ($overwrite && $existingPhoneId) {
    if ($existingClientId && $existingClientId !== $existingPhoneId) {
        return ['error' => 'Mobile number belongs to a different existing client'];
    }
    $existingClientId = $existingPhoneId;
}
```

```php
if (isset($this->seenPhones[$phone])) {
    return ['error' => 'Duplicate mobile number in import file'];
}
```

Lead rows had no existing-lead phone lookup or duplicate check.

### After

- Login-client processing always uses overwrite behavior, regardless of the submitted flag. A matching phone selects that client even if the policy number points elsewhere.
- A repeated new phone in one chunk is coalesced so only its latest row is written; across chunks, the prior row has already created a client and the next chunk preloads that phone for update.
- If that phone-matched client’s email belongs to another client, the row keeps the existing `Duplicate email address already exists` error. This safely avoids changing the other client’s email or silently dropping the conflict.
- Lead imports preload existing leads using the same SQL `REPLACE` phone normalization as client preloading. They reject existing or repeated phones with `Duplicate mobile number: lead already exists`; no client-table lookup is used.
- Lead phones seen in previous chunks are carried under the import tracking UUID in cache and removed when the import file is cleaned up. This catches duplicates across chunks, including earlier rows that failed other validations.

```php
if ($existingPhoneId) {
    $existingClientId = $existingPhoneId;
}
```

```php
if (isset($this->dbLeadPhones[$phone]) || isset($this->seenLeadPhones[$phone])) {
    return ['success' => false, 'errors' => ['Duplicate mobile number: lead already exists'], 'raw' => $rawData];
}
```

### Test Steps

1. Import a login-client row with an existing client’s phone in a different format, with overwrite disabled. Confirm the same client is updated and no new client is created.
2. Include the same new phone twice in one file with different row data. Confirm one client is created with the later row’s data. Repeat with the rows on opposite sides of a 100-row chunk boundary and confirm the later row updates it.
3. Use a phone belonging to client A and an email belonging to client B. Confirm the row retains the duplicate-email error and client B’s email is unchanged.
4. Import leads whose phone matches an existing lead, a prior row in the same chunk, and a prior chunk. Confirm the later rows are rejected with the exact duplicate-mobile error and no lead is updated.
5. Import a lead with neither email nor phone. Confirm the existing `Email or Phone is required.` error remains.
6. Preview both destinations and confirm the duplicate results are preloaded and displayed.

Validation run: PHP lint passed for all three changed PHP files. `php artisan test --filter=ImportResultAccessTest` ran 11 tests; 7 passed and 2 failed: one preview request's test schema lacks `lookup_values`, and one existing test reaches `ImportDestinations::getConfig()` with a null destination. Neither failure is from the changed duplicate-handling paths.

### Other observations (not changed)

- `UniversalImportController::preview()` still assigns `row_number` twice and `has_more` twice; the later assignment wins. These lines were intentionally left unchanged per scope.

---

## Issue 13 — CSV templates follow database columns and import wizard fields

### Root Cause

Both template download functions generated headers from hard-coded JavaScript arrays. Those arrays could drift from the database schema and from `ImportDestinations`, which supplies the mapping screen's fields. The Login Client mapping also omitted the client status-pipeline columns.

### Files and Exact Changed Lines

- `app/Http/Controllers/UniversalImportController.php` — lines 65–98: schema-driven CSV template response.
- `routes/web.php` — lines 61–64: Leads template route inside `permission:leads,write`; lines 174–177: Login Clients template route inside `permission:clients_login,write`. Both routes also inherit their corresponding `read` permission and the outer `auth`, `user.active` middleware.
- `app/Services/ImportDestinations.php` — lines 102–128: missing Login Client fields; lines 146–171: model-fillable warning check.
- `app/Services/UniversalImportService.php` — line 478: preserve Leads Adviser text while resolving `user_id`; lines 601–627: pass through Login Client pipeline fields and parse dates.
- `public/assets/js/pages/crm-leads-pipeline.js` — lines 1068–1071: use Leads template endpoint.
- `public/assets/js/pages/clients-login.js` — lines 906–909: use Login Clients template endpoint.
- `issueFix.md` — appended this entry.

### Before

Each JavaScript function built a fixed list and created a browser-side CSV Blob:

```js
const headers = [/* fixed header list */];
const blob = new Blob([`${headers.join(',')}\r\n`], { type: 'text/csv;charset=utf-8;' });
```

### After

The controller reads table columns with `Schema::getColumnListing()`, omits system fields and the duplicate physical `adviser` column, and writes a CSV header row. The `user_id` column becomes the configured **Adviser** field; that one input resolves `user_id` and is also retained in the `adviser` text column. `lead_source_id` uses the configured **Lead Source** field. Other columns use their configured labels or title case.

```js
function downloadLeadsTemplate(event) {
    event.preventDefault();
    window.location.href = '/import/templates/leads';
}
```

Missing model fillable columns now produce a `Log::warning()` entry. Current runtime coverage check returned no missing fillable fields for either destination.

### Final Template Headers

**Leads — 24 columns**

`First Name`, `Last Name`, `Email`, `Phone`, `Estimated Cover`, `Lead Source`, `Status`, `Adviser`, `Notes`, `Policy No`, `Insurance Company`, `Login Date`, `ANP`, `DOB`, `Address`, `Suburb`, `City`, `Post Code`, `Not Counting`, `Compliance By`, `RoA Due Date`, `Status Compliance`, `Sent to Client`, `Outcome`

**Login Clients — 47 columns**

`Policy No`, `Insurance Company`, `First Name`, `Last Name`, `Email`, `Mobile`, `DOB`, `Address`, `Suburb`, `City`, `Post Code`, `Login Date`, `ANP`, `Status`, `Lead Source`, `Adviser`, `Not Counting`, `Compliance by`, `RoA Due on`, `Status Compliance`, `Sent to Client`, `Outcome`, `Inforce Date`, `Inforce Request Type`, `Inforce Process Status`, `Inforce Process By`, `Inforce Outcome`, `Inforce Comments`, `Inforce Finished Date`, `Claim Type`, `Claim Admin`, `Claim Processed Date`, `Claim Update Status`, `Claim Approved Date`, `Claim Result`, `Notes`, `Canc Date Sent`, `Canc Completed Date`, `Canc Outcome`, `Canc Comments`, `Canc Admin`, `Npw Issue Date`, `Npw Premium`, `Npw Premium Mode`, `Npw Admin`, `Npw Pending`, `Npw Comments`

The database has 48 non-system `clients` columns; the separate physical `adviser` column shares the single requested **Adviser** template input with `user_id`, resulting in 47 unique template fields.

Runtime mapping verification against the current schema produced **24 Leads headers / 47 Login Client headers**, with **zero unmapped headers** from `suggestMapping()`.

### Test Steps

1. Sign in as a user with Leads write permission and download the Leads template. Confirm it has the 24 headers above; verify a user lacking Leads write permission receives 403.
2. Sign in as a user with Login Clients write permission and download the Login Clients template. Confirm it has the 47 headers above; verify a user lacking `clients_login` write permission receives 403.
3. Upload each template in its corresponding import wizard. Confirm every header is automatically mapped and shown in the database-fields mapping list.
4. Import sample values for the newly added Login Client fields, including date fields, and verify values persist in their matching `clients` columns with dates normalized.
5. Import a Lead with Adviser text. Verify the text persists in `leads.adviser` and the matching user ID is stored in `leads.user_id`.
6. In an isolated test harness, pass a destination config whose model declares one additional fillable field. Confirm the application logger records that missing configuration as a warning, not an error; no production model or migration changes are needed.

### Other observations (not changed)

- Runtime schema introspection determines the header order, so the exact order may differ between database drivers if their column listings differ; the fields and mappings remain schema-derived.
- The legacy `public/assets/` tree was not edited; only the two explicitly allowed `public/assets/js/pages/` files changed.

---

## Issue 14 — Replace native delete confirmations with the styled confirmation modal

### Files and Exact Lines Changed

- `public/assets/js/pages/clients-inforce.js` — lines 256–282: await the shared confirmation modal before sending the existing DELETE request; remove both listeners when resolved.
- `public/assets/js/pages/crm-leads-pipeline.js` — line 1059: replace the native confirmation on the Delete Lead button with `confirmFormSubmit()`.
- `resources/views/pages/settings/dropdown-values.blade.php` — line 57: remove native form confirmation; line 60: add `confirmFormSubmit()` to the delete button.
- `issueFix.md` — appended this entry.

### Before

```js
if (!confirm(`Permanently delete "${clientName}"? This cannot be undone.`)) return;
```

```html
onsubmit="return confirm('Delete this category?');"
```

```html
onclick="return confirm('Delete this lead? This action cannot be undone.')"
```

### After

- The Inforce Client delete flow uses `#globalConfirmModal`. The confirm-click listener resolves true; `hidden.bs.modal` resolves false for Cancel, X, Escape, or backdrop dismissal. Both listeners are removed on either resolution. The DELETE fetch runs only after a true result.
- Category and Lead form submissions call the existing `confirmFormSubmit(event, message, form)` helper. Existing category wording, lead wording, CSRF and POST/`_method=DELETE` remain intact; the Lead button keeps its red danger classes.

```js
const confirmed = await new Promise(resolve => {
    // Both handlers are removed by finish() on confirmation or dismissal.
});
if (!confirmed) return;
```

```html
onclick="confirmFormSubmit(event, 'Delete this category?', this.closest('form'))"
```

```html
onclick="confirmFormSubmit(event, 'Delete this lead? This action cannot be undone.', this.closest('form'))"
```

### Test Steps

1. In the Inforce Clients page, choose Delete Client. Confirm the shared modal displays the client-specific message.
2. Click **Cancel**, the modal **X**, press **Escape**, and click the backdrop in separate attempts. Confirm no DELETE request occurs.
3. Confirm **Yes, Confirm** once and verify exactly one DELETE request occurs and the DataTable refreshes.
4. Repeat the Inforce delete prompt after each dismissal and confirm no stale handler triggers an earlier client deletion.
5. In Settings → Category Master, click Delete Category and verify the shared modal keeps the message “Delete this category?”; Cancel leaves the category untouched, confirmation submits the existing form.
6. In CRM Pipeline, use Delete Lead and verify the styled modal, exact message, red danger styling, and unchanged POST + `_method=DELETE` request.
7. Verify existing `alert()` feedback remains unchanged.

Validation requested: run `node --check` on both edited JS files and `php artisan view:clear`.

### Other observations (not changed)

- The global alert override and all `alert()` calls are unchanged.
- The task page’s separate `confirmTaskDeletion()` implementation is unchanged.

## Issue 15 — Lead imports accepted unknown insurers

### Root Cause

`processLead()` returned an `error` for an insurer that did not match an active insurer, but `processRow()` did not check that result. It consequently returned a successful row result containing the error instead of rejecting the row.

### Change Made

`app/Services/UniversalImportService.php` — `processRow()` now checks for an `error` after calling `processLead()` and returns the same failed-row structure used by the Login Client branch. Duplicate-phone handling was not changed.

### Validation

Passed: `php -l app/Services/UniversalImportService.php` reported no syntax errors.

## Issue 17 — Calendar popups and latest task-note previews

### Changes Made

- `resources/views/pages/utilities/calendar.blade.php`: replaced dynamic inline popup handlers with escaped `data-*` attributes and a delegated click listener for appointment and task details; task badges in month, week, day, and agenda views keep their details populated. Month date cells also use a delegated handler.
- `resources/views/pages/utilities/tasks.blade.php`: latest task note now uses the first note (newest under the global scope), and the note preview/count structure is available for updates after adding notes.
- `public/assets/js/pages/tasks.js`: task rows now render the same preview structure; adding a note refreshes the note-count badge, serialized notes, and newest-note preview.

### Validation

Passed `node --check public/assets/js/pages/tasks.js`. Rendered the calendar through `AppointmentController` using October 2026 task data: 26 popup elements and 31 calendar date cells were generated, and all popup elements had the expected data attributes with no inline handler or missing attributes. No appointment records were present in the database during this render. Rendered calendar and tasks Blade views and passed `php -l` on both compiled PHP views.

## Issue 18 — Compact Task Notes timeline cards

### Cause Found

`public/assets/js/pages/tasks.js`, `renderTaskNotes()` lines 198–214: the note card used `p-3` (16px padding per side), its header row used `mb-2` (8px gap), and the note text wrapper used `line-height: 1.6`. The note wrapper had no explicit margin reset. No fixed/minimum height or flex-stretch rule was set on the individual note cards, and no matching card rule was found in `public/assets/css/ss-custom.css`.

### Before / After

- Card padding: `p-3` → `p-2` (12px).
- Header-to-text spacing: retained the compact 8px `mb-2`.
- Note text: set `margin:0` and reduced line height from `1.6` to `1.3`; preserved `white-space: pre-wrap` and `word-break: break-word` so long notes continue to wrap fully.
- The existing border, radius, colors, header alignment, date/time placement, note ordering and scrollable list remain unchanged.

### Test Steps

1. Open Tasks and open **Task Notes** for a task with one short note; verify the card fits its content and has approximately 12px inner padding.
2. Verify the header and note text are separated by about 8px and share the same left edge.
3. Check long/multiline notes wrap and remain fully visible while scrolling the notes list.
4. Confirm badges, icon/#N alignment, date/time placement, colors, borders and newest-first ordering are unchanged.

Validation: `node --check public/assets/js/pages/tasks.js` passed.

## Issue 20 — Sequential display order for merged lead categories and sources

### Changes Made

- `app/Http/Controllers/Settings/DropdownValueController.php`: the merged `lead_category` / `lead_source` query now orders by stored `sort_order`, then `group`, then `name`; it assigns each merged item a transient `display_order` from 1 through N. Stored data and all write actions are unchanged.
- `resources/views/pages/settings/dropdown-values.blade.php`: only the merged view displays `display_order`; other groups continue displaying their stored `sort_order`. The Sort Order cell retains a numeric `data-order` value for DataTables sorting.

### Test Steps

1. Open Category Master and select Lead Category / Lead Source.
2. Verify the merged rows display sequential order values from 1 to N, ordered by stored sort order, group, then name.
3. Sort the Sort Order column and verify numeric order.
4. Open another category group and verify its stored sort-order values are still displayed.

Validation: `php -l app/Http/Controllers/Settings/DropdownValueController.php` passed.

## Issue 19 — Remove Task Notes card indentation and excess height

### Causes Found

- `public/assets/js/pages/tasks.js`, line 199: the note wrapper used Bootstrap `.card` (flex card layout and automatic height) plus `p-2` (8px padding, not the previously documented 12px). The shared theme rule in `public/assets/css/theme.min.css`, lines 3995–4000, also assigns `.card` a 24px bottom margin; the local `mb-2` utility requested 8px, but left the card coupled to global card styling.
- `tasks.js`, line 200: `mb-2` added an 8px header gap.
- `tasks.js`, lines 213–214: the text wrapper itself had no `ms-*`, `margin-left`, or left padding. However, the newline and indentation around `${displayText}` emitted collapsible leading whitespace, visibly offsetting text slightly from the icon.
- No fixed/minimum height is specified on individual note cards. The 220px minimum height belongs to the scroll-list container in `resources/views/pages/utilities/tasks.blade.php` and is outside this change's scope. The note wrapper is a `div`, so paragraph/pre default margins do not apply.

### Before / After

- Removed `.card`, `p-2`, and `mb-2` from each rendered note item; set `display:block`, `height:auto`, `min-height:0`, `padding:12px`, and an explicit 8px bottom gap.
- Reduced the header-to-text gap from 8px to 6px.
- Reset text margin, padding, and indent, and put the note text directly in the wrapper without template whitespace. Preserved `pre-wrap` and word breaking for complete long-note wrapping.
- Kept the border, radius, colors, icon/#N/badge alignment, date/time placement, note order, and scroll-list behavior.

### Test Steps

1. Open a one-line Task Note and check the card height is content-driven (roughly 70–80px), with 12px padding and an 8px gap between cards.
2. Verify the note begins exactly under the icon’s left edge with no leading indent.
3. Check long and multiline notes wrap and remain fully visible while scrolling.
4. Confirm header alignment, date/time placement, badges, colors, borders, radius, and newest-first ordering remain unchanged.

Validation: `node --check public/assets/js/pages/tasks.js` passed.

## Issue 16 — Safely handle lead and calendar inline data; improve Kanban widths

### Changes Made

- `public/assets/js/pages/crm-leads-pipeline.js`: lead cards and note buttons now carry HTML-escaped `data-*` values and use delegated click handlers instead of interpolating names or notes into inline `onclick` JavaScript. The legacy lead-card builder follows the same pattern.
- `resources/views/pages/utilities/calendar.blade.php`: appointment detail arguments now use `@json` instead of `addslashes()` interpolation.
- `public/assets/css/ss-custom.css`: Kanban columns have a 280px minimum basis and scroll horizontally when needed; source badges can shrink and wrap.

### Validation

Passed: `node --check public/assets/js/pages/crm-leads-pipeline.js` and `php -l app/Services/UniversalImportService.php`. No PHP source file was changed in this issue; the PHP lint check confirms the PHP CLI is available, while Blade templates are not valid standalone inputs to `php -l`.

## Issue 21 — Sequential display order on Access Category Master

### Changes Made

- `app/Http/Controllers/Admin/UserController.php`: the merged `lead_category` / `lead_source` query now orders by stored `sort_order`, then `group`, then `name`; it assigns each item a transient `display_order` from 1 through N and passes `group = lead_category` to the view. Other groups and all write logic remain unchanged.

### Test Steps

1. Open Access → Category Master with the merged Lead Category / Lead Source list.
2. Verify its Sort Order values display sequentially from 1 through N.
3. Confirm another category group still displays its stored sort-order values.

Validation: `php -l app/Http/Controllers/Admin/UserController.php` passed.