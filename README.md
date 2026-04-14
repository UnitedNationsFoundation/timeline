# Timeline WordPress Plugin

A WordPress plugin that renders an interactive, carousel-based timeline. Content is organized into **date-grouped slides** with support for text, image, and video content types, a modal popup, and a file download button. All configuration is managed via Advanced Custom Fields (ACF).

---

## Table of Contents

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
- [Refactoring & Optimization Plan](#refactoring--optimization-plan)
  - [Phase 1 — Security (Critical)](#phase-1--security-critical)
  - [Phase 2 — Performance](#phase-2--performance)
  - [Phase 3 — Maintainability](#phase-3--maintainability)
  - [Phase 4 — Stability & Testing](#phase-4--stability--testing)
- [Known Issues](#known-issues)
- [Changelog](#changelog)

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

## Refactoring & Optimization Plan

The following is a prioritized plan for improving security, performance, and long-term maintainability. Issues are grouped by phase so work can be done incrementally without breaking the live plugin.

### Phase 1 — Security (Critical)

These issues should be resolved before any other work. They represent exploitable vulnerabilities.

**1.1 — Add nonce verification to the AJAX endpoint**

`load_posts()` currently accepts unauthenticated POST requests with no CSRF protection.

- In `script_enqueuer()`: add a nonce to the localized `TIMELINE` object
  ```php
  'nonce' => wp_create_nonce('timeline_load_posts')
  ```
- In `functions.js` `preloadItems()`: include the nonce in AJAX data
  ```js
  data: { action: '...', term_id: $term, key: $key, nonce: TIMELINE.nonce }
  ```
- In `load_posts()`: verify before proceeding
  ```php
  check_ajax_referer('timeline_load_posts', 'nonce');
  ```

**1.2 — Sanitize and validate AJAX inputs**

`$_POST['term_id']` is used unsanitized directly in a `WP_Query` `tax_query`. Although WordPress internally sanitizes `term_id`, explicit validation is required.

```php
$term_id = absint($_POST['term_id']);
if ( ! $term_id || ! term_exists($term_id, $this->getTaxonomy()) ) {
    wp_send_json_error('Invalid term'); exit;
}
```

**1.3 — Fix path traversal in content-type include**

`$content_type` is taken from ACF but passed directly to `include()`. Whitelist the allowed values before including:

```php
$allowed = ['text', 'text-image', 'text-video'];
if ( ! in_array($content_type, $allowed, true) ) {
    continue; // or skip the post
}
include('templates/content-type/' . $content_type . '.php');
```

**1.4 — Escape output in templates**

All echoed values in templates should use the appropriate WordPress escaping function:

```php
// taxonomy term name
echo esc_html($date->name);

// URLs
echo esc_url($download_file);

// Image src values from ACF arrays
echo esc_url($title['sizes']['medium']);

// WYSIWYG output (already passes through wp_kses internally via ACF, but explicit is safer)
echo wp_kses_post(get_field('timeline_text'));
```

---

### Phase 2 — Performance

Targeted changes that directly reduce page-load time and Time to Interactive (TTI).

**2.1 — Cache `getDates()` with a transient**

`getDates()` is called on every page load that contains the `[timeline]` shortcode, and again inside each AJAX request via `getByDate()`. This should be cached.

```php
public function getDates() {
    $cached = get_transient('timeline_dates');
    if ( $cached !== false ) return $cached;

    $terms = get_terms([ 'taxonomy' => $this->getTaxonomy(), 'hide_empty' => true, 'orderby' => 'menu_order', 'order' => 'ASC' ]);
    set_transient('timeline_dates', $terms, HOUR_IN_SECONDS);
    return $terms;
}
```

Clear the transient whenever a `timeline_date` term is created, updated, or deleted:

```php
add_action('edited_timeline_date',  [$this, 'flush_dates_cache']);
add_action('created_timeline_date', [$this, 'flush_dates_cache']);
add_action('deleted_timeline_date', [$this, 'flush_dates_cache']);

public function flush_dates_cache() { delete_transient('timeline_dates'); }
```

**2.2 — Cache `getByDate()` per term**

Each `getByDate()` call hits the database. Cache results keyed by `term_id`, cleared when any `timeline_item` is saved or deleted.

```php
public function getByDate($term_id, $limit = -1) {
    $cache_key = 'timeline_posts_' . (int)$term_id;
    $cached    = get_transient($cache_key);
    if ( $cached !== false ) return $cached;

    // ... existing WP_Query ...
    set_transient($cache_key, $query, HOUR_IN_SECONDS);
    return $query;
}
```

**2.3 — Lazy-load images**

Replace all `<img src="...">` in templates with `loading="lazy"` and proper `alt` attributes:

```php
<img src="<?= esc_url($image['url']); ?>" alt="<?= esc_attr($image['alt']); ?>" loading="lazy">
```

**2.4 — Reduce the `background-size` image dimensions**

The registered image size is `2500×2000` pixels, which produces files of 2 MB+ per image. Reduce to a more realistic maximum:

```php
add_image_size('background-size', 1920, 1080, false);
```

Add a `srcset` to the background image markup for responsive delivery.

**2.5 — Add `defer` to non-critical scripts**

Owl Carousel and `functions.js` do not need to block HTML parsing. Add `defer` via the `script_loader_tag` filter:

```php
add_filter('script_loader_tag', function($tag, $handle) {
    $defer = ['timeline_owl', 'timeline_functions'];
    if ( in_array($handle, $defer, true) ) {
        return str_replace(' src=', ' defer src=', $tag);
    }
    return $tag;
}, 10, 2);
```

**2.6 — Introduce a build step for asset minification**

Add a `package.json` with a minimal build pipeline (e.g., `esbuild` + `postcss`) to:
- Minify `style.css` → `style.min.css`
- Minify `functions.js` → `functions.min.js`
- Bundle and tree-shake Owl Carousel (removes ~30% unused code)

Enqueue the `.min.*` variants in production (`WP_DEBUG === false`).

**2.7 — Consolidate Owl Carousel CSS**

Combine `owl.carousel.min.css` and `owl.theme.default.min.css` into a single file to reduce HTTP requests.

**2.8 — Don't autoplay background videos on mobile**

Background videos marked `autoplay muted loop` consume significant bandwidth on mobile connections. Gate autoplay behind a media query check in JavaScript:

```js
if ( window.matchMedia('(min-width: 768px)').matches ) {
    video.play();
}
```

---

### Phase 3 — Maintainability

Structural changes that make the plugin easier to modify, debug, and hand off.

**3.1 — Split the monolithic `settings.php`**

The 735-line file mixes plugin bootstrap, WordPress registration, ACF field definitions, query logic, and AJAX handling. Proposed split:

```
settings.php               ← plugin header + bootstrap only (class instantiation)
includes/
  class-timeline.php       ← main Timeline class (hooks, shortcode, queries)
  acf-fields.php           ← all acf_add_local_field_group() calls
  ajax.php                 ← load_posts() and related AJAX logic
```

Use `require_once` in `settings.php` to pull in each file.

**3.2 — Create a dedicated `acf-fields.php`**

Move all ~500 lines of `acf_add_local_field_group()` definitions into `includes/acf-fields.php`. This makes the main class readable at a glance and makes field changes easier to review in diffs.

**3.3 — Replace `new Timeline()` inside the template**

`templates/timeline.php` instantiates `new Timeline()` directly (line 4). This creates a second instance of the plugin class and bypasses the constructor hooks. Instead, pass the required data as variables when calling `include_once()` in `show_timeline()`:

```php
public function show_timeline($atts) {
    // enqueue assets ...
    $dates              = $this->getDates();
    $preload_number     = $this->getMaxLoadItems();
    include_once('templates/timeline.php');
}
```

In the template, use `$dates` and `$preload_number` directly — no `new Timeline()` needed.

**3.4 — Remove dead code and backup files**

- Delete `assets/css/style.css.back`
- Remove the commented-out `cropDates()` function from `functions.js`
- Remove the commented-out date picker ACF field (lines 238–254 in `settings.php`)
- Either implement or remove `autoplay_youtube_embed_url()` (currently defined but the filter hook is commented out)
- Either use or remove `jquery.mousewheel.min.js`

**3.5 — Replace magic numbers with named constants**

```php
const MAX_LOAD_ITEMS      = 30;
const AUTOPLAY_TIMEOUT_MS = 5000;
const BG_IMAGE_WIDTH      = 1920;
const BG_IMAGE_HEIGHT     = 1080;
```

**3.6 — Use event delegation in `functions.js`**

The current code calls `initTimeline()` after every AJAX load because direct `.click()` bindings don't cover dynamically added elements. Replace with delegated events on a stable ancestor:

```js
$(document).on('click', '.timeline-dots li', function(e) { ... });
```

This also eliminates the `unbind('click')` call in `initTimeline()` which is a code smell.

**3.7 — Add docblocks to public methods**

At minimum, document each public method's parameters, return type, and side effects. This is especially important for `getByDate()` (returns `WP_Query`, not posts array) and `load_posts()` (exits after sending JSON).

---

### Phase 4 — Stability & Testing

**4.1 — Add PHPUnit tests for query methods**

Install `wp-phpunit` and write unit tests for:
- `getDates()` — returns sorted terms, returns empty array when no terms
- `getByDate($term_id)` — returns correct posts, respects `$limit`, handles invalid term_id
- `load_posts()` — returns error status on invalid input, returns HTML on success

**4.2 — Add JavaScript unit tests**

Use Jest to test:
- `moveTimelineMarker()` — correct pixel calculation
- `preloadItems()` — does not double-load already-loaded items
- `stopAllVideos()` — pauses all video elements

**4.3 — Add a `WP_DEBUG` check for ACF dependency**

Display an admin notice (not a fatal error) when ACF is not active:

```php
if ( ! function_exists('acf_add_local_field_group') ) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>Timeline plugin requires Advanced Custom Fields Pro.</p></div>';
    });
    return;
}
```

**4.4 — Update ACF Pro to 6.x**

ACF 5.9.9 (locked in `composer.json`) is end-of-life. Update to `^6.0` and test that all field group definitions are forward-compatible. The primary breaking change is the new field group location rule format.

**4.5 — Add a `CHANGELOG.md`**

Track changes between versions so that deployments can be reviewed at a glance.

---

## Known Issues

| # | Severity | Description |
|---|---|---|
| 1 | Critical | AJAX endpoint has no nonce verification — vulnerable to CSRF |
| 2 | Critical | `$_POST['term_id']` is not validated before use |
| 3 | Critical | `$content_type` is passed to `include()` without whitelist validation (path traversal) |
| 4 | High | All output in templates is unescaped (XSS risk) |
| 5 | High | `getDates()` and `getByDate()` run uncached on every page load |
| 6 | Medium | Background videos autoplay on mobile, consuming excess bandwidth |
| 7 | Medium | `background-size` image dimensions (2500×2000) produce oversized files |
| 8 | Medium | `new Timeline()` is called inside `templates/timeline.php`, creating a second class instance |
| 9 | Low | ACF dependency locked at outdated 5.9.9; should be updated to 6.x |
| 10 | Low | `jquery.mousewheel.min.js` is registered but never enqueued or used |
| 11 | Low | `style.css.back` backup file is committed to version control |
| 12 | Low | No tests of any kind exist for PHP or JavaScript code |

---

## Changelog

### 1.3
- Current version (author: Melisa)
- Interactive timeline with Owl Carousel
- ACF-managed content with three content types (text, text-image, text-video)
- AJAX lazy loading for dates beyond the first 30
- Modal popup and file download button
- Responsive layout with breakpoints at 400px, 500px, 767px, and 800px
