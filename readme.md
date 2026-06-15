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
| `get_template_part()` inline | `$template->render($data)` |

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
// Optional keys: 'deps' (array), 'ver' (string), 'in_footer' (bool, default: true)
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