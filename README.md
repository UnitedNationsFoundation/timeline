# Timeline WordPress Plugin

A WordPress plugin that renders an interactive, carousel-based timeline. Content is organized into **date-grouped slides** with support for text, image, and video content types, a modal popup, and a file download button. All configuration is managed via Advanced Custom Fields (ACF).

---

## Table of Contents

- [Timeline WordPress Plugin](#timeline-wordpress-plugin)
  - [Table of Contents](#table-of-contents)
  - [Requirements](#requirements)
  - [Installation](#installation)
  - [Usage](#usage)
  - [Content Management](#content-management)
    - [Timeline Items (Posts)](#timeline-items-posts)
    - [Timeline Settings (Options Page)](#timeline-settings-options-page)
    - [Dates (Taxonomy)](#dates-taxonomy)
    - [Content Types](#content-types)
  - [Architecture](#architecture)
    - [File Structure](#file-structure)
    - [Data Flow](#data-flow)
    - [Asset Loading](#asset-loading)
    - [AJAX Endpoint](#ajax-endpoint)
  - [Changelog](#changelog)
    - [1.3](#13)

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | >= 8.0 |
| WordPress | >= 5.9 |
| Advanced Custom Fields Pro | >= 5.9.9 (6.x recommended) |
| jQuery | Included with WordPress |

ACF Pro must be installed and activated **before** this plugin. The plugin silently no-ops if ACF is missing but will not function correctly.

---

## Installation

1. Copy the `timeline/` directory into `wp-content/plugins/`.
2. Ensure ACF Pro is installed and active (see `composer.json` for the package source).
3. Activate **Timeline** in the WordPress plugin dashboard.
4. Navigate to **Timeline Settings** in the admin sidebar and fill in the global options (title image, popup content, download file).
5. Create at least one **Date** term under `Timeline > Dates`, then create **Timeline Items** assigned to that date.
6. Embed the timeline on any page with the `[timeline]` shortcode.

---

## Usage

Place the shortcode anywhere in a page or post:

```
[timeline]
```

No shortcode attributes are currently supported. All configuration is done via the WordPress admin.

---

## Content Management

### Timeline Items (Posts)

Timeline Items are a custom post type (`timeline_item`). Each item becomes one **slide** within a date's carousel. Items are ordered by **menu order** (drag-and-drop in the admin list view).

Each item has two ACF field sections:

**Background**

| Field | Type | Notes |
|---|---|---|
| Background Type | Select | `image`, `color`, or `video` |
| Background Image | Image | Shown when type = image |
| Background Color | Color Picker | Shown when type = color |
| Background Video (file) | File | Shown when type = video (self-hosted) |
| Background Video URL | URL | Shown when type = video (external URL) |
| Overlay | True/False | Enables a color overlay on top of the background |
| Overlay Color | Color Picker | Overlay color |
| Overlay Opacity | Number (0–99) | Overlay opacity % |

**Content**

| Field | Type | Notes |
|---|---|---|
| Content Type | Select | `text`, `text-image`, or `text-video` — controls which template is rendered |
| Image | Image | Shown when content type = text-image |
| Video (file) | File | Shown when content type = text-video (self-hosted) |
| Video URL | URL | Shown when content type = text-video (external URL) |
| Text Position | Select | 7 layout options for text placement |
| Text Background Color | Color Picker | Background behind the text block |
| Text Background Opacity | Number | Opacity of text background |
| Text | WYSIWYG | Main slide copy |

### Timeline Settings (Options Page)

Found under **Timeline Settings** in the admin sidebar. These fields are global and apply to every timeline on the site.

| Field | Type | Purpose |
|---|---|---|
| Title | Image | Large decorative title image shown at the bottom of the timeline |
| Popup Button Text | Text | Label on the button that opens the modal |
| Popup Button Icon | Image | Icon displayed on the popup button |
| Popup Title | Text | Heading inside the modal |
| Popup Text | WYSIWYG | Body content inside the modal |
| Download Button Text | Text | Label on the PDF/file download button |
| Download Button Icon | File | Icon displayed on the download button |
| Download File | URL | Link to the file that is downloaded |

### Dates (Taxonomy)

Dates are a hierarchical taxonomy (`timeline_date`) attached to `timeline_item`. Each term becomes one **dot** on the timeline navigation bar. Terms are ordered by **menu order** — set this in `Appearance > Menus` or via a menu-order plugin.

> The term **name** is the label shown on the navigation dot, so use human-readable date strings (e.g., `1920s`, `2001`, `Today`).

### Content Types

Three PHP templates in `templates/content-type/` correspond to the **Content Type** ACF field:

| Value | Template | Description |
|---|---|---|
| `text` | `text.php` | Text-only slide |
| `text-image` | `text-image.php` | Text alongside a static image |
| `text-video` | `text-video.php` | Text alongside a video (file or URL) |

---

## Architecture

### File Structure

```
timeline/
├── settings.php              # Main plugin file — class Timeline, all hooks
├── composer.json             # PHP dependency config (ACF Pro)
├── auth.json                 # ACF Pro credentials (do not commit)
├── templates/
│   ├── timeline.php          # Root shortcode template (navigation + carousel container)
│   ├── popup.php             # Modal markup
│   ├── carousel-buttons.php  # Play / pause controls
│   └── content-type/
│       ├── text.php          # Text slide
│       ├── text-image.php    # Text + image slide
│       └── text-video.php    # Text + video slide
└── assets/
    ├── css/
    │   └── style.css         # All plugin styles (~18 KB)
    ├── js/
    │   ├── functions.js      # Timeline JS logic (~4.4 KB)
    │   └── jquery.mousewheel.min.js  # Mouse-wheel polyfill (currently unused)
    └── owlcarousel/
        ├── owl.carousel.min.js       # Owl Carousel 2 (~43 KB)
        ├── owl.carousel.min.css
        └── owl.theme.default.min.css
```

### Data Flow

```
[timeline] shortcode
    │
    ▼
show_timeline()
    │
    ├── Enqueues CSS + JS (only on pages that use the shortcode)
    │
    └── Includes templates/timeline.php
            │
            ├── getDates()        → get_terms('timeline_date')
            │                       Returns all non-empty date terms, sorted by menu_order
            │
            ├── For each date (up to max_load_items = 30):
            │       getByDate($term_id)  → WP_Query on timeline_item CPT
            │       Renders content-type template for each post
            │
            └── For remaining dates (> 30):
                    Renders a loading placeholder div
                    JavaScript calls load_posts() via AJAX when the dot is clicked
```

**AJAX lazy load** (`load_posts` action):
- Triggered by `preloadItems()` in `functions.js` on page load for all dots
- POST data: `term_id`, `key`
- Returns JSON: `{ status, content (HTML string), classes }`
- Injects HTML into the carousel container and initializes Owl Carousel on it

### Asset Loading

Assets are **registered on `init`** but only **enqueued when the `[timeline]` shortcode is rendered**, preventing unnecessary loading on pages that don't use the timeline.

| Handle | File | Size | Dependencies |
|---|---|---|---|
| `timeline_owl` | owlcarousel/owl.carousel.min.js | 43 KB | jquery |
| `timeline_functions` | js/functions.js | 4.4 KB | jquery |
| `timeline_owl_stylesheet` | owlcarousel/owl.carousel.min.css | 3.1 KB | — |
| `timeline_owltheme_stylesheet` | owlcarousel/owl.theme.default.min.css | 1.0 KB | — |
| `timeline_stylesheet` | css/style.css | 18 KB | — |

A `TIMELINE` JavaScript object is localized onto `timeline_functions`:

```js
TIMELINE.ajax_url  // wp-admin/admin-ajax.php
TIMELINE.loading   // Loading spinner HTML string
TIMELINE.max_items // 30
```

### AJAX Endpoint

| Property | Value |
|---|---|
| Action | `load_timeline_posts` |
| Method | POST |
| Authentication | None required (also registered for logged-out users) |
| Parameters | `term_id` (int), `key` (int) |
| Response | JSON `{ status: 'ok'|'err', content: string, classes: string }` |

---

## Changelog

### 1.3
- Current version (author: Melisa)
- Interactive timeline with Owl Carousel
- ACF-managed content with three content types (text, text-image, text-video)
- AJAX lazy loading for dates beyond the first 30
- Modal popup and file download button
- Responsive layout with breakpoints at 400px, 500px, 767px, and 800px
