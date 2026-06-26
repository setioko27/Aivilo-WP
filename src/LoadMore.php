<?php
namespace Avilio;

class LoadMore
{
    private string $action;
    private string $postType;
    private int $postsPerPage;
    private string $templatePart;
    private array $extraArgs;
    private string $target;
    private bool $enqueueJS;

    public function __construct(array $args)
    {
        $this->action = $args['action'] ?? 'avilio_load_more';
        $this->postType = $args['post_type'] ?? 'post';
        $this->postsPerPage = $args['posts_per_page'] ?? (int) get_option('posts_per_page');
        $this->templatePart = $args['template_part'] ?? 'template-parts/content';
        $this->extraArgs = $args['extra_args'] ?? [];
        $this->target = $args['target'] ?? '';
        $this->enqueueJS = $args['enqueue_js'] ?? true;

        $this->registerHooks();
    }

    private function registerHooks(): void
    {
        add_action("wp_ajax_{$this->action}", [$this, 'handleAJAX']);
        add_action("wp_ajax_nopriv_{$this->action}", [$this, 'handleAJAX']);

        if ($this->enqueueJS) {
            add_action('wp_footer', [self::class, 'print_js']);
        }
    }

    public function button_attrs(): string
    {
        $nonce = wp_create_nonce($this->action);
        
        $queryArgs = array_merge([
            'post_type'      => $this->postType,
            'posts_per_page' => $this->postsPerPage,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'no_found_rows'  => false,
        ], $this->extraArgs);

        $query = new \WP_Query($queryArgs);
        $maxPages = $query->max_num_pages;

        $attrs = [
            'data-load-more' => esc_attr($this->action),
            'data-action'    => esc_attr($this->action),
            'data-nonce'     => esc_attr($nonce),
            'data-page'      => 1,
            'data-max-pages' => esc_attr($maxPages),
        ];

        if ($this->target) {
            $attrs['data-target'] = esc_attr($this->target);
        }

        $htmlAttrs = [];
        foreach ($attrs as $key => $val) {
            $htmlAttrs[] = "{$key}=\"{$val}\"";
        }

        return implode(' ', $htmlAttrs);
    }

    public function handleAJAX(): void
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], $this->action)) {
            wp_send_json_error('Security verification failed.', 403);
            wp_die();
        }

        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;

        $queryArgs = array_merge([
            'post_type'      => $this->postType,
            'posts_per_page' => $this->postsPerPage,
            'paged'          => $page,
            'post_status'    => 'publish',
        ], $this->extraArgs);

        if (isset($_POST['filters']) && is_array($_POST['filters'])) {
            $filters = array_map('sanitize_text_field', $_POST['filters']);
            $queryArgs = array_merge($queryArgs, $filters);
        }

        $query = new \WP_Query($queryArgs);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                get_template_part($this->templatePart);
            }
            wp_reset_postdata();
        }

        wp_die();
    }

    public static function print_js(): void
    {
        static $printed = false;
        if ($printed) {
            return;
        }
        $printed = true;
        ?>
        <script type="text/javascript">
        if (typeof avilioLoadMoreInit === 'undefined') {
            window.avilioLoadMoreInit = true;
            window.avilioAjaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
            
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('[data-load-more]');
                if (!btn) return;
                
                e.preventDefault();
                
                const action = btn.dataset.action;
                const nonce = btn.dataset.nonce;
                const page = parseInt(btn.dataset.page);
                const maxPages = parseInt(btn.dataset.maxPages);
                const targetSelector = btn.dataset.target;
                
                if (!targetSelector) {
                    return; // Let custom JS handle it if no target selector is defined
                }
                
                const targetContainer = document.querySelector(targetSelector);
                if (!targetContainer) {
                    console.error('Avilio LoadMore: Target container not found: ' + targetSelector);
                    return;
                }
                
                const originalText = btn.innerHTML;
                btn.innerHTML = 'Loading...';
                btn.disabled = true;
                
                const formData = new FormData();
                formData.append('action', action);
                formData.append('nonce', nonce);
                formData.append('page', page + 1);
                
                fetch(window.avilioAjaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.text())
                .then(html => {
                    if (html.trim()) {
                        targetContainer.insertAdjacentHTML('beforeend', html);
                        btn.dataset.page = page + 1;
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        
                        if (page + 1 >= maxPages) {
                            btn.remove();
                        }
                    } else {
                        btn.remove();
                    }
                })
                .catch(err => {
                    console.error(err);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            });
        }
        </script>
        <?php
    }
}
