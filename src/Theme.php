<?php
namespace Avilio;

class Theme
{
    private const DEFAULT_PRIORITY = 10;
    private const DEFAULT_ARGS = 1;
    private const DEFAULT_MEDIA = 'all';
    private const DEFAULT_VERSION = '1.0.0';
    
    private array $defaultLogoSettings = [
        'height' => 250,
        'width' => 250,
        'flex-width' => true,
        'flex-height' => true,
        'unlink-homepage-logo' => true,
    ];

    private array $defaultHtmlSupport = [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption'
    ];

    public function __construct()
    {
        $this->initializeThemeSupport()
             ->initializeStyles();
    }

    private function initializeThemeSupport(): self
    {
        return $this->addSupport('title-tag')
                    ->addSupport('custom-logo', $this->defaultLogoSettings)
                    ->addSupport('post-thumbnails')
                    ->addSupport('html5', $this->defaultHtmlSupport);
    }

    private function initializeStyles(): self
    {
        return $this->addStyles([
            'handle' => "theme-style",
            "src" => get_stylesheet_uri()
        ]);
    }

    

    public function addAction(string $hook, callable $function, int $priority = self::DEFAULT_PRIORITY, int $accepted_args = self::DEFAULT_ARGS): void
    {
        add_action($hook, $function, $priority, $accepted_args);
    }

    public function addFilter(string $hook, callable $function, int $priority = self::DEFAULT_PRIORITY, int $accepted_args = self::DEFAULT_ARGS): void
    {
        add_filter($hook, $function, $priority, $accepted_args);
    }

    private function actionAfterSetup(callable $function): void
    {
        $this->addAction("after_setup_theme", $function);
    }

    private function actionEnqueueScripts(callable $function): void
    {
        $this->addAction("wp_enqueue_scripts", $function);
    }

    public function addSupport(string $feature, ?array $options = null): self
    {
        $this->actionAfterSetup(function () use ($feature, $options) {
            if ($options) {
                add_theme_support($feature, $options);
            } else {
                add_theme_support($feature);
            }
        });
        return $this;
    }

    public function removeSupport(string $feature): self
    {
        $this->actionAfterSetup(fn() => remove_theme_support($feature));
        return $this;
    }

    public function loadTextDomain(string $domain, $path = false): self
    {
        $this->actionAfterSetup(fn() => load_theme_textdomain($domain, $path));
        return $this;
    }

    public function addImageSizes(array $sizes = []): self
    {
        $this->actionAfterSetup(function() use ($sizes) {
            foreach ($sizes as $size) {
                if (empty($size['name'])) continue;
                
                add_image_size(
                    $size['name'],
                    $size['width'] ?? 0,
                    $size['height'] ?? 0,
                    $size['crop'] ?? false
                );
            }
        });
        return $this;
    }

    public function removeImageSize(string $name): self
    {
        $this->actionAfterSetup(fn() => remove_image_size($name));
        return $this;
    }

    public function addStyles(array $styles): self
    {
        $this->actionEnqueueScripts(function() use ($styles) {
            if (!isset($styles['handle']) || !isset($styles['src'])) {
                $this->processMultipleStyles($styles);
                return;
            }
            
            $this->enqueueStyle($styles);
        });
        return $this;
    }

    private function processMultipleStyles(array $styles): void
    {
        foreach ($styles as $style) {
            if (!isset($style['handle']) || !isset($style['src'])) continue;
            $this->enqueueStyle($style);
        }
    }

    private function enqueueStyle(array $style): void
    {
        wp_enqueue_style(
            $style['handle'],
            $style['src'],
            $style['deps'] ?? [],
            $style['ver'] ?? false,
            $style['media'] ?? self::DEFAULT_MEDIA
        );
    }

    public function addScripts(array $scripts): self
    {
        $this->actionEnqueueScripts(function() use ($scripts) {
            if (!isset($scripts['handle']) || !isset($scripts['src'])) {
                $this->processMultipleScripts($scripts);
                return;
            }
            
            $this->enqueueScript($scripts);
        });
        return $this;
    }

    private function processMultipleScripts(array $scripts): void
    {
        foreach ($scripts as $script) {
            if (!isset($script['handle']) || !isset($script['src'])) continue;
            $this->enqueueScript($script);
        }
    }

    private function enqueueScript(array $script): void
    {
        $strategy = $script['strategy'] ?? null;
        $in_footer = $script['in_footer'] ?? true;
        
        $args = $in_footer;
        if ($strategy) {
            $args = [
                'strategy' => $strategy,
                'in_footer' => $in_footer,
            ];
        }

        wp_enqueue_script(
            $script['handle'],
            $script['src'],
            $script['deps'] ?? [],
            $script['ver'] ?? false,
            $args
        );
    }

    public function addAdminAsset(string $type, string $handle, string $src, $deps = false, string $ver = self::DEFAULT_VERSION): self
    {
        $this->addAction('admin_enqueue_scripts', function() use ($type, $handle, $src, $deps, $ver) {
            $function = "wp_enqueue_$type";
            $function($handle, $src, $deps, $ver);
        });
        return $this;
    }

    public function addAdminStyle(string $handle, string $src, $deps = false, string $ver = self::DEFAULT_VERSION): self
    {
        return $this->addAdminAsset('style', $handle, $src, $deps, $ver);
    }

    public function addAdminScript(string $handle, string $src, $deps = false, string $ver = self::DEFAULT_VERSION): self
    {
        return $this->addAdminAsset('script', $handle, $src, $deps, $ver);
    }

    public function removeAsset(string $type, string $handle): self
    {
        $this->actionEnqueueScripts(function() use ($type, $handle) {
            call_user_func("wp_dequeue_$type", $handle);
            call_user_func("wp_deregister_$type", $handle);
        });
        return $this;
    }

    public function removeStyle(string $handle): self
    {
        return $this->removeAsset('style', $handle);
    }

    public function removeScript(string $handle): self
    {
        return $this->removeAsset('script', $handle);
    }

    public function addNavMenus(array $locations): self
    {
        $this->actionAfterSetup(fn() => register_nav_menus($locations));
        return $this;
    }

    public function addNavMenu(string $location, string $description): self
    {
        $this->actionAfterSetup(fn() => register_nav_menu($location, $description));
        return $this;
    }

    public function removeNavMenu(string $location): self
    {
        $this->actionAfterSetup(fn() => unregister_nav_menu($location));
        return $this;
    }
}
