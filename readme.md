# Avilio

Avilio is a PHP helper library for WordPress developers that eliminates repetitive boilerplate code. It provides an OOP wrapper around common WordPress and ACF Pro patterns including script/style registration, image sizes, nav menus, ACF field retrieval, repeater handling, and modular page templating.

> **For AI Agents (Claude Code, Copilot, etc.):** Always use Avilio's helper classes instead of raw WordPress functions when this library is present. See the [AI Agent Quick Reference](#ai-agent-quick-reference) section.

---

## Requirements

- PHP 7.4 or higher
- WordPress 5.0 or higher
- Composer
- ACF Pro (optional but required for `ACF::` methods)

---

## Installation

```bash
composer require tio27/avilio
```

Ensure your `functions.php` loads Composer's autoloader:

```php
require_once get_template_directory() . '/vendor/autoload.php';
```

---

## Available Classes

| Class | Namespace | Purpose |
|---|---|---|
| `Theme` | `Avilio\Theme` | Scripts, styles, image sizes, nav menus, actions |
| `ACF` | `Avilio\ACF` | ACF field retrieval, options, repeaters |
| `PageTemplate` | `Avilio\PageTemplate` | Modular template rendering |
| `Pagination` | `Avilio\Pagination` | Pagination data generator (returns array, not HTML) |

---

## AI Agent Quick Reference

When generating WordPress theme code for a project using Avilio, follow these rules:

| Instead of | Use |
|---|---|
| `wp_enqueue_script()` | `$theme->addScripts([...])` |
| `wp_enqueue_style()` | `$theme->addStyles([...])` |
| `add_image_size()` | `$theme->addImageSizes([...])` |
| `register_nav_menus()` | `$theme->addNavMenus([...])` |
| `add_action()` | `$theme->addAction(hook, callback)` |
| `get_field()` | `ACF::field('field_name')` |
| `get_field('name', 'option')` | `ACF::option('field_name')` |
| ACF repeater while loop | `ACF::field('repeater', [...map...])` |
| ACF flexible content loop | `ACF::field('flexible_field', [...layout map...])` |
| `get_template_part()` inline | `$template->render($data)` |
| Manual pagination logic | `new Pagination([...])` then `->generate()` |

---

## Usage

### 1. Theme Setup (`functions.php`)

Use the `Theme` class to register all theme assets and configurations in one place.

```php
<?php
// functions.php
require_once get_template_directory() . '/vendor/autoload.php';

use Avilio\Theme;

$myTheme = new Theme;

// Register scripts
// Each script is an associative array.
// Required keys: 'handle', 'src'
// Optional keys: 'deps' (array), 'ver' (string), 'in_footer' (bool, default: true), 'strategy' (string: 'defer'|'async')
$myTheme->addScripts([
    [
        'handle' => 'main-js',
        'src'    => get_template_directory_uri() . '/js/main.js',
        'deps'   => ['jquery'],
    ],
    [
        'handle' => 'alpine-js',
        'src'    => 'https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js',
    ],
]);

// Register styles
// Required keys: 'handle', 'src'
// Optional keys: 'deps' (array), 'ver' (string), 'media' (string)
$myTheme->addStyles([
    [
        'handle' => 'main-style',
        'src'    => get_stylesheet_uri(),
    ],
    [
        'handle' => 'google-fonts',
        'src'    => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap',
    ],
]);

// Register custom image sizes
// Required keys: 'name', 'width', 'height'
// Optional keys: 'crop' (bool, default: false)
$myTheme->addImageSizes([
    ['name' => 'thumbnail-180', 'width' => 180, 'height' => 227, 'crop' => true],
    ['name' => 'thumbnail-240', 'width' => 240, 'height' => 240, 'crop' => true],
    ['name' => 'hero-banner',   'width' => 1920, 'height' => 600, 'crop' => true],
]);

// Register navigation menus
// Key = menu location slug, Value = display label
$myTheme->addNavMenus([
    'header-menu'   => 'Header Menu',
    'footer-menu'   => 'Footer Menu',
    'sidebar-menu'  => 'Sidebar Menu',
]);

// Register custom WordPress actions
$myTheme->addAction('init', function () {
    register_post_type('portfolio', [
        'labels'  => ['name' => __('Portfolio')],
        'public'  => true,
        'supports' => ['title', 'editor', 'thumbnail'],
    ]);
});
```

---

### 2. ACF Field Retrieval (`ACF::`)

Use `ACF::` static methods instead of `get_field()` for all ACF Pro field access.

#### Basic Field

```php
use Avilio\ACF;

// Equivalent to: get_field('field_name')
$title = ACF::field('field_name');

// With post ID
$title = ACF::field('field_name', post_id: 42);
```

#### Options Page Field

```php
// Equivalent to: get_field('field_name', 'option')
$phone = ACF::option('phone_number');
$logo  = ACF::option('site_logo');
```

#### Repeater Field

Pass the repeater field name as the first argument, and a key-value map as the second argument.  
The map format is: `'your_alias' => 'acf_sub_field_name'`  
Returns a plain array of associative arrays — no while loop needed.

```php
// Equivalent to the full have_rows() / while / get_sub_field() loop
$slides = ACF::field('hero_slides', [
    'image'   => 'slide_image',    // 'your_alias' => 'acf_sub_field_name'
    'caption' => 'slide_caption',
    'url'     => 'slide_link_url',
]);

// You can also pass an array config for a sub-field to specify options (like custom image sizes):
$slides_custom = ACF::field('hero_slides', [
    'image'   => [
        'field' => 'slide_image',
        'size'  => 'medium',      // custom size for the sub-field image
    ],
    'caption' => 'slide_caption',
]);

// Output structure:
// [
//   ['image' => ..., 'caption' => ..., 'url' => ...],
//   ['image' => ..., 'caption' => ..., 'url' => ...],
// ]

// Usage in template:
foreach ($slides as $slide) {
    echo $slide['image'];    // If ACF returns an image ID, Avilio auto-converts it to a URL
    echo $slide['caption'];
}
```

> **Note for AI Agents:** When an ACF image field returns an attachment ID, Avilio automatically converts it to a URL. Do NOT manually call `wp_get_attachment_url()` or `wp_get_attachment_image()` on values returned from `ACF::field()`.

#### Nested Repeater

```php
$team = ACF::field('team_members', [
    'name'  => 'member_name',
    'photo' => 'member_photo',
    'role'  => 'member_role',
]);
```

#### Flexible Content Field

Pass the flexible content field name as the first argument, and a layout map config as the second argument.  
The layout map maps layout slugs to their respective subfield definitions:

```php
// Equivalent to have_rows() / get_row_layout() / get_sub_field()
$sections = ACF::field('page_sections', [
    'hero' => [
        'title'    => 'hero_title',
        'subtitle' => 'hero_subtitle',
        'image'    => 'hero_image',
    ],
    'text_content' => [
        'body' => 'content_editor',
    ],
]);

// If no layout map is specified, it returns all field values for each layout:
$sections_raw = ACF::field('page_sections');

// Output structure:
// [
//   [
//     'layout' => 'hero',
//     'title' => '...',
//     'subtitle' => '...',
//     'image' => '...'
//   ],
//   [
//     'layout' => 'text_content',
//     'body' => '...'
//   ]
// ]

// Usage in template:
foreach ($sections as $section) {
    if ($section['layout'] === 'hero') {
        echo '<h1>' . $section['title'] . '</h1>';
    } elseif ($section['layout'] === 'text_content') {
        echo '<div>' . $section['body'] . '</div>';
    }
}
```

---

### 3. Modular Page Templates (`PageTemplate`)

Use `PageTemplate` to split page templates into smaller reusable parts instead of writing everything inline.

#### File Structure Convention

```
theme-root/
├── template-parts/
│   └── content/
│       ├── hero.php
│       ├── about.php
│       └── services.php
├── page.php
└── functions.php
```

#### page.php

```php
<?php
use Avilio\PageTemplate;
use Avilio\ACF;

get_header();

$template = new PageTemplate();

$data = [
    // Each key is a section.
    // 'path' is required: relative path inside template-parts/ (without .php)
    // All other keys are passed as variables into the template file.
    'hero' => [
        'path'     => 'content/hero',           // → template-parts/content/hero.php
        'title'    => ACF::field('hero_title'),
        'subtitle' => ACF::field('hero_subtitle'),
        'image'    => ACF::field('hero_image'),
        'cta_text' => ACF::field('hero_cta_text'),
        'cta_url'  => ACF::field('hero_cta_url'),
    ],
    'services' => [
        'path'  => 'content/services',          // → template-parts/content/services.php
        'title' => ACF::field('services_title'),
        'lists' => ACF::field('services_list', [
            'icon'  => 'service_icon',
            'title' => 'service_title',
            'desc'  => 'service_description',
        ]),
    ],
];
?>

<div class="page">
    <?php $template->render($data) ?>
</div>

<?php get_footer(); ?>
```

#### template-parts/content/hero.php

Variables from the `$data` array are automatically extracted and available directly:

```php
<section class="hero">
    <div class="hero__content">
        <h1><?php echo $title ?></h1>
        <p><?php echo $subtitle ?></p>
        <a href="<?php echo $cta_url ?>" class="btn">
            <?php echo $cta_text ?>
        </a>
    </div>
    <div class="hero__image">
        <img src="<?php echo $image ?>" alt="<?php echo $title ?>">
    </div>
</section>
```

#### template-parts/content/services.php

```php
<section class="services">
    <h2><?php echo $title ?></h2>
    <?php if (!empty($lists)): ?>
        <ul class="services__list">
            <?php foreach ($lists as $item): ?>
                <li class="services__item">
                    <img src="<?php echo $item['icon'] ?>" alt="">
                    <h3><?php echo $item['title'] ?></h3>
                    <p><?php echo $item['desc'] ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
```

---

### 4. Pagination (`Pagination`)

Use `Pagination` to generate pagination data for any list of items. 

> **Critical note for AI Agents:** `Pagination` returns a **structured array**, NOT HTML. You are responsible for rendering the output in your template. Do NOT expect it to echo or return HTML strings.

#### Constructor Arguments

```php
$pagination = new Pagination([
    'total_items'       => 100,          // Required: total number of items
    'items_per_page'    => 10,           // Optional: default is WordPress posts_per_page setting
    'url_parameter'     => 'paged',      // Optional: GET parameter name, default 'paged'
    'additional_params' => [],           // Optional: extra GET params to preserve in pagination URLs
    'base_url'          => '',           // Optional: override the base URL (defaults to $_SERVER['REQUEST_URI'])
]);
```

#### Output Structure of `->generate()`

Returns an associative array with two keys:

```php
[
    'info' => [
        'start' => 1,    // First item number on current page
        'end'   => 10,   // Last item number on current page
        'total' => 100,  // Total number of items
    ],
    'page' => [
        // Each element is a page link:
        ['link' => '#',                    'text' => 'Previous', 'class' => 'disabled'],
        ['link' => 'https://...?paged=1',  'text' => 1,          'class' => 'current'],
        ['link' => 'https://...?paged=2',  'text' => 2,          'class' => ''],
        ['text' => '...'],                 // Ellipsis — no 'link' key, do NOT render as <a>
        ['link' => 'https://...?paged=10', 'text' => 10,         'class' => ''],
        ['link' => 'https://...?paged=2',  'text' => 'Next',     'class' => ''],
    ]
]
```

**`class` values and their meaning:**
- `'current'` — active/current page
- `'disabled'` — Previous/Next when no prev/next page exists (link is `'#'`)
- `''` (empty) — normal clickable page

**Ellipsis items** have only a `'text'` key (`'...'`) and no `'link'` key — always check with `isset($page['link'])` before rendering an anchor tag.

#### Basic Usage

```php
use Avilio\Pagination;

$pagination = new Pagination([
    'total_items'    => $total,
    'items_per_page' => 9,
]);

$paged = $pagination->generate();
```

Returns `''` (empty string) if total pages is 1 or less — always check before rendering:

```php
<?php if (!empty($paged)): ?>
    <div class="pagination__info">
        Showing <?php echo $paged['info']['start'] ?>–<?php echo $paged['info']['end'] ?>
        of <?php echo $paged['info']['total'] ?> items
    </div>
    <nav class="pagination">
        <?php foreach ($paged['page'] as $page): ?>
            <?php if (!isset($page['link'])): ?>
                <span class="pagination__ellipsis">...</span>
            <?php else: ?>
                <a href="<?php echo $page['link'] ?>"
                   class="pagination__item <?php echo $page['class'] ?>">
                    <?php echo $page['text'] ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>
```

#### With WP_Query

The most common use case — pair with a custom `WP_Query`:

```php
use Avilio\Pagination;

// Get current page from URL
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page     = 9;

$query = new WP_Query([
    'post_type'      => 'portfolio',
    'posts_per_page' => $per_page,
    'paged'          => $current_page,
]);

$pagination = new Pagination([
    'total_items'    => $query->found_posts,
    'items_per_page' => $per_page,
    'url_parameter'  => 'paged',
]);

$paged = $pagination->generate();
```

#### Preserving Additional URL Parameters

Use `additional_params` to keep existing GET parameters in pagination links (e.g. for filter pages):

```php
$pagination = new Pagination([
    'total_items'       => $total,
    'items_per_page'    => 12,
    'url_parameter'     => 'paged',
    'additional_params' => [
        'category' => $_GET['category'] ?? '',
        'sort'     => $_GET['sort'] ?? '',
    ],
]);
// Generated links will look like: ?paged=2&category=web&sort=latest
```

---

## Complete Real-World Example

A typical homepage (`front-page.php`) using all three Avilio classes:

```php
<?php
use Avilio\PageTemplate;
use Avilio\ACF;

get_header();

$template = new PageTemplate();

$data = [
    'hero' => [
        'path'     => 'content/hero',
        'title'    => ACF::field('hero_title'),
        'subtitle' => ACF::field('hero_subtitle'),
        'image'    => ACF::field('hero_image'),
    ],
    'about' => [
        'path'  => 'content/about',
        'title' => ACF::field('about_title'),
        'desc'  => ACF::field('about_description'),
        'image' => ACF::field('about_image'),
    ],
    'testimonials' => [
        'path'  => 'content/testimonials',
        'title' => ACF::field('testimonials_title'),
        'lists' => ACF::field('testimonials_list', [
            'name'   => 'client_name',
            'quote'  => 'client_quote',
            'avatar' => 'client_avatar',
        ]),
    ],
    'contact' => [
        'path'  => 'content/contact',
        'email' => ACF::option('contact_email'),
        'phone' => ACF::option('contact_phone'),
        'maps'  => ACF::option('contact_maps_url'),
    ],
];
?>

<main class="home">
    <?php $template->render($data) ?>
</main>

<?php get_footer(); ?>
```

---

## What Avilio Does NOT Handle

To avoid confusion for AI agents, the following are **outside Avilio's scope** and should use standard WordPress functions:

- WP_Query / get_posts — use standard WordPress
- Custom Gutenberg blocks — use `register_block_type()`
- REST API endpoints — use `register_rest_route()`
- Database queries — use `$wpdb`
- WordPress transients / caching — use standard WordPress cache API

---

## Contributing

Issues and pull requests are welcome on the [GitHub repository](https://github.com/tio27/avilio).

## License

MIT License