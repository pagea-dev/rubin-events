# Rubin Events — TYPO3 Event Management Extension

A simple event management extension for TYPO3 v13 and v14. Create, display and archive club events with optional map location (OpenStreetMap).

---

## Requirements

| Component | Version |
|---|---|
| TYPO3 | `^13.4 \|\| ^14.0` |
| PHP | ^8.1 |
| fe_users | Included with TYPO3 core |

---

## Installation

```bash
composer require pagea-dev/rubin-events
```

Then set up the extension and flush caches:

```bash
vendor/bin/typo3 extension:setup
vendor/bin/typo3 cache:flush
```

With DDEV:

```bash
ddev composer require pagea-dev/rubin-events
ddev typo3 cache:flush
```

---

## Setup

### 1. Include the TYPO3 Set

Add the set `pagea-dev/rubin-events` to your site configuration:

```yaml
# config/sites/<site>/config.yaml
dependencies:
  - pagea-dev/rubin-events
```

Alternatively, add it in the TYPO3 backend under **Site Management → Sites → [Edit site] → Sets**.

### 2. Create a storage folder

Create a page of type **Folder** (SysFolder) where event records will be stored. The UID of this page is used as the **Storage PID**.

### 3. Configure the Extension Manager

In the backend under **Admin Tools → Extensions → Rubin Events**, configure the following defaults:

| Setting | Description | Default |
|---|---|---|
| Default Zoom Level | Initial map zoom (1 = world, 18 = street level) | `12` |
| Default Latitude | Map center latitude on first load | `51.5` |
| Default Longitude | Map center longitude on first load | `9.5` |
| Storage PID | Default folder PID for new backend records | `14` |
| Load Swiper | Ship the bundled Swiper element with the slider list style | on |

These values are used as fallback by the Map Picker when no coordinates have been saved yet.

---

## Adding Plugins

Three plugins are available, each inserted as a **content element** on a page:

### Event List (`list`)

Displays upcoming events.

**FlexForm settings:**

| Field | Description |
|---|---|
| Storage PID | Folder page from which events are loaded |
| List page (pidList) | Where the detail view returns to — see below |
| List style | Rendering variant (Swiper slider, default list, Bootstrap list) |
| "More" button behavior | Open a modal or link to the detail page — see *Modal* |
| Detail page (pidShow) | Only shown when "more" button is set to redirect |
| Limit | Maximum number of events shown (1–100, default: 10) |

**List page:** every detail link this plugin renders carries the list page along
(`…&tx_rubinevents_eventshow[pidList]=12`), and the detail view uses it for its back button. That way
one detail page can serve several lists sitting on different pages, each returning to its own list.

Leave the field empty and the plugin hands over **the page it is placed on**, so the visitor returns
to where they came from without any configuration. Set it explicitly only when the back button should
lead somewhere else — note that the slider variant also renders its "all events" button from this
field, and only when it is set.

**List style values:**

| Value | Description |
|---|---|
| `0` | Swiper slider (carousel) |
| `1` | Default tile list (default) |
| `2` | Bootstrap list |

### Event Show (`show`)

Displays a single event in detail.

**FlexForm settings:**

| Field | Description |
|---|---|
| Storage PID | Folder page from which events are loaded |
| List page (pidList) | Fallback target for the back button, used when the linking plugin does not pass one |

If no event is found, the controller automatically redirects to the back page.

### Event Archive (`archive`)

Displays past events.

**FlexForm settings:**

| Field | Description |
|---|---|
| List style | Default tile list or Bootstrap list (slider not available) |
| Storage PID | Folder page from which events are loaded |
| Back page (pidList) | Target page for the back button |
| Limit | Maximum number of events shown |

---

## Creating Events

Events are created as records inside the configured SysFolder. In the backend go to **Web → List → [select SysFolder] → New record → Rubin Event**.

### Fields

#### Tab: General

| Field | Required | Description |
|---|---|---|
| **Start date** | Yes | Date and time the event begins |
| **End date** | No | Date and time the event ends |
| **Title** | Yes | Name of the event |
| **Teaser** | No | Short description shown as preview text in list views |
| **Description** | No | Full description; rendered with line breaks in the detail view |
| **Location** | No | Free-text location, e.g. "Club house, Main Street 1" |
| **Map location** | No | Coordinates set via the Map Picker (OpenStreetMap) |
| **Creator** | No | Frontend user (fe_user) who created the event |
| **Contacts** | No | One or more fe_users shown as contacts (name + email) in the detail view |

#### Tab: Language / Access / Extended

Standard TYPO3 tabs for translation, visibility and access control.

---

## Map Picker (Map Location)

The **Map location** field uses a custom backend form element (`rubinEventsMapPicker`) that renders an interactive OpenStreetMap inside the backend form.

**How to use:**

1. **Click anywhere on the map** to set a location — a marker appears immediately.
2. The saved coordinates are shown above the map as `Lat: X.X, Lon: Y.Y`.
3. Use the **"Clear location"** button to remove the marker and empty the field.

**Stored format:** `lat,lon` as a plain string, e.g. `51.8745,9.3512`

**Frontend output:** The detail view (`Show`) renders an OpenStreetMap link (`?mlat=...&mlon=...`) when a location is set.

**Default map center** (when no location is saved yet): read from the Extension Manager configuration (see *Setup → Configure the Extension Manager* above).

The map is powered by [Leaflet.js](https://leafletjs.com/), bundled locally inside the extension at `Resources/Public/JavaScript/Lib/Leaflet/` — no external runtime dependency is added to the backend.

---

## Modal (compact detail view)

Every list view renders a **"More info"** button per event. What it does is decided by the
**"More" button behavior** setting:

| Value | Behavior |
|---|---|
| `0` | Opens a modal with a compact detail view (default) |
| `1` | Links to the configured detail page (`pidShow`) |

The modal shows title, date, location, teaser, description and — when the event has coordinates —
the OpenStreetMap and Google Maps links. Contacts and the interactive map stay reserved for the full
detail view.

It is built on the native `<dialog>` element and driven by
`Resources/Public/JavaScript/EventModal.js` — plain vanilla JS, no framework, no Bootstrap. ESC and
focus handling come from the browser; the script adds backdrop click, the close button and the
scroll lock. The asset is pulled in by the partial via `f:asset.script`, so it only lands on pages
that actually render a modal.

**No request is made when opening.** Each trigger carries its data in attributes
(`data-title`, `data-date`, `data-date-end`, `data-location`, `data-teaser`, `data-description`,
`data-lat`, `data-lon`), and `Partials/Event/Modal.html` is an empty shell that gets filled on
click. Values are written with `textContent`, so event data can never inject markup. Empty fields
hide their whole row.

One shell is rendered per plugin, and a trigger always fills the shell inside its own
`.rubin-events` wrapper — several event plugins on one page do not interfere with each other.

The **archive plugin** has no behavior setting and no detail page, so its buttons always open the
modal.

Styling lives in `rubinevents.scss`. A minimal critical stylesheet is inlined by the partial, so the
modal is usable (width, backdrop, hidden fields, line breaks) even without the extension SCSS.

---

## Slider (Swiper)

List style **Slider** (`0`) renders the events as a [Swiper](https://swiperjs.com/) carousel, using
Swiper's custom element (`<swiper-container>` / `<swiper-slide>`). Version 14.1.0 is bundled locally
in `Resources/Public/JavaScript/Lib/Swiper/`.

Whether the extension ships that file is controlled by the **Load Swiper** checkbox in the extension
configuration (*Admin Tools > Extensions > Rubin Events*, on by default). Switch it off when your
site package already delivers Swiper — the markup stays the same, only the library is not loaded a
second time. No other slider library is included either way.

The asset is pulled in by the partial itself via `f:asset.script`, so it only lands on pages that
actually render a slider, and no TypoScript setup is required.

---

## Template Overrides

Templates, partials and layouts can be overridden via site settings:

```yaml
# config/sites/<site>/settings.yaml
rubinevents.templateRootPathOverride: 'EXT:my_extension/Resources/Private/Templates/RubinEvents/'
rubinevents.partialRootPathOverride:  'EXT:my_extension/Resources/Private/Partials/RubinEvents/'
rubinevents.layoutRootPathOverride:   'EXT:my_extension/Resources/Private/Layouts/RubinEvents/'
```

Empty values (default) mean the extension's own templates are used.

**Available templates:**

| File | Description |
|---|---|
| `Templates/Event/List.html` | List view (dispatches to list style partial) |
| `Templates/Event/Show.html` | Detail view |
| `Templates/Event/Archive.html` | Archive view |
| `Partials/Event/List.html` | Default tile partial |
| `Partials/Event/BsList.html` | Bootstrap list partial |
| `Partials/Event/Slider.html` | Swiper slider partial |
| `Partials/Event/Show.html` | Detail partial |
| `Partials/Event/Map.html` | Leaflet map incl. external map links |
| `Partials/Event/MoreButton.html` | "More info" trigger — modal button or detail link |
| `Partials/Event/Modal.html` | Modal shell for the compact detail view |

---

## Map in the Detail View

When an event has coordinates set (see *Map Picker*), the detail view renders `Partials/Event/Map.html`: an interactive Leaflet map centered on the event, followed by two buttons that open the location in **OpenStreetMap** and in **Google Maps**.

Leaflet's CSS and JS are pulled in by the partial itself via `f:asset.css` / `f:asset.script`, so they are only loaded on pages that actually show a map — no TypoScript setup required. Leaflet is bundled locally in `Resources/Public/JavaScript/Lib/Leaflet/`; only the map tiles are fetched from `tile.openstreetmap.org`, which `Configuration/ContentSecurityPolicies.php` allows for both backend and frontend scope.

Scroll wheel zoom is disabled until the map has focus, so scrolling past the map does not trap the page.

The map container needs an explicit height — it is styled in `Resources/Private/Scss/rubinevents.scss` (`.map-canvas`, default `350px`). If you do not load the extension's SCSS, set a height yourself, otherwise the map stays invisible.

---

## Backend Module

**Web → Events** lists every event record, regardless of which folder it is stored in, split into
upcoming and past. Each row links straight into the record editor and returns to the module
afterwards. The doc header carries a *New event* button, which creates the record in the storage
folder from the extension configuration — if no storage PID is set there, the button is hidden and
the module says so.

### Settings indicator

At the top of the module an infobox reports the state of the extension configuration:

| Colour | Meaning |
|---|---|
| Green | No setting is wrong |
| Yellow | Some settings are wrong |
| Red | No setting is usable |

Checked are `storagePid` (must resolve to an existing page), `defaultZoom` (1–18), `defaultLat`
(−90…90) and `defaultLon` (−180…180); each invalid one is listed with its current value and what is
expected. `useSwiper` is left out — a checkbox has no invalid value, and counting it would make the
red state unreachable.

### Example import

*Import examples* in the top right of the doc header creates a complete demo dataset:

1. a storage folder **"Rubin Events – Beispieldaten"** at the top level of the page tree,
2. seven contacts (`fe_users`) with their photos as file references,
3. eight events inside that folder, each with one to three of those contacts.

It needs no configuration — the folder comes with it, which is the point on a fresh installation
where nothing is set up yet. Clicking again does nothing but say the content is already there;
delete the folder to import a second time.

The content lives in `Resources/Private/ExampleContent/`:

```
ExampleContent/
  dump.sql        the records
  images/         the contact photos, copied into fileadmin/rubin_events_examples/ on import
```

Everything in the dump is invented — names, addresses, `@example.org` mail addresses (reserved for
documentation by RFC 2606) and coordinates refer to no real person, club or place.

A plain dump cannot know the uids it will get, so the statements use placeholders that
`ExampleContentImporter` resolves:

| Placeholder | Resolved to |
|---|---|
| `###PID###` | uid of the created storage folder |
| `###FEUSER:<username>###` | uid of that fe_user, imported earlier in the same run |
| `###FILE:<filename>###` | sys_file uid of that file from `images/` |
| `###DATE:<offset>\|<HH:MM>###` | timestamp relative to today, e.g. `-28 days\|09:00` |

The date placeholder is what keeps the set useful: the events stay spread around the import date
instead of drifting into the past. Extending the example content means editing `dump.sql` only, no
PHP change.

Because raw INSERTs bypass `DataHandler`, the importer updates the reference index for the records
it wrote — otherwise the file references would show up as broken in the backend.

The module is registered through `Configuration/Backend/Modules.php` using the route form
(`routes` → controller method) rather than Extbase `controllerActions`, and the controller is a plain
backend controller. Registration, `ModuleTemplateFactory`, `ButtonBar` and the `Module` Fluid layout
work the same way in TYPO3 v13 and v14, so the module needs no version switches.

---

## Dashboard Widget

The extension registers a backend dashboard widget **"Upcoming Events"** that lists the next events and provides a **"Create Event"** button. The widget is available immediately after installation via **Dashboard → Add widget → Rubin Events**.

---

## Custom ViewHelper

`r:format.localizedDate` — formats a date/time value in the configured site language.

Fluid namespace: `xmlns:r="http://typo3.org/ns/PageaDev/RubinEvents/ViewHelpers"`

---

## License

GPL-3.0-or-later — see [LICENSE](LICENSE)
