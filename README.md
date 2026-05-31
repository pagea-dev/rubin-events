# Rubin Events — TYPO3 Event Management Extension

A simple event management extension for TYPO3 v14. Create, display and archive club events with optional map location (OpenStreetMap).

---

## Requirements

| Component | Version |
|---|---|
| TYPO3 | ^14.0 |
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
| Detail page (pidList) | Target page for the "more" link / detail view |
| List style | Rendering variant (default list, Bootstrap list, TinySlider) |
| "More" button behavior | Inline display or redirect to detail page |
| Detail page (pidShow) | Only shown when "more" button is set to redirect |
| Limit | Maximum number of events shown (1–100, default: 10) |

**List style values:**

| Value | Description |
|---|---|
| `0` | TinySlider (carousel) |
| `1` | Default tile list (default) |
| `2` | Bootstrap list |

### Event Show (`show`)

Displays a single event in detail.

**FlexForm settings:**

| Field | Description |
|---|---|
| Storage PID | Folder page from which events are loaded |
| Back page (pidList) | Target page for the back button |

If no event is found, the controller automatically redirects to `pidList`.

### Event Archive (`archive`)

Displays past events.

**FlexForm settings:**

| Field | Description |
|---|---|
| List style | Default tile list or Bootstrap list (TinySlider not available) |
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

## TinySlider

When list style **TinySlider** (`0`) is used, asset loading must be enabled. Set `rubinevents.useTinyslider: 1` in your site settings (enabled by default). TypoScript will then include `tiny-slider.js` and `tiny-slider.min.css` automatically. Otherwise, if you already implement TinySlider, you have to disable it, so it doesn't load twice.

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
| `Templates/Event/List.fluid.html` | List view (dispatches to list style partial) |
| `Templates/Event/Show.fluid.html` | Detail view |
| `Templates/Event/Archive.fluid.html` | Archive view |
| `Partials/Event/List.fluid.html` | Default tile partial |
| `Partials/Event/BsList.fluid.html` | Bootstrap list partial |
| `Partials/Event/TinySlider.fluid.html` | TinySlider partial |
| `Partials/Event/Show.fluid.html` | Detail partial |

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
