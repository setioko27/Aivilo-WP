<?php
namespace Avilio;

class Pagination {
    private $total_items;
    private $items_per_page;
    private $current_page;
    private $total_pages;
    private $url_parameter;
    private $additional_params;

    public function __construct($args) {
        $this->total_items = $args['total_items'] ?? 0;
        $this->items_per_page = $args['items_per_page'] ?? get_option('posts_per_page');
        $this->url_parameter = $args['url_parameter'] ?? 'paged';
        $this->additional_params = $args['additional_params'] ?? [];
        $this->current_page = $this->get_current_page();
        $this->total_pages = ceil($this->total_items / $this->items_per_page);
    }

    private function get_current_page() {
        return isset($_GET[$this->url_parameter]) ? max(1, intval($_GET[$this->url_parameter])) : 1;
    }


    public function generate() {
        if ($this->total_pages <= 1) return '';

        $output = [];
        // Info
        $start = ($this->current_page - 1) * $this->items_per_page + 1;
        $end = min($this->current_page * $this->items_per_page, $this->total_items);
        $output['info'] = ['start'=>$start,'end'=>$end,'total'=>$this->total_items];
        $output['page'] = [];
        
        $prev_link = "#";
        if ($this->current_page > 1) {
            $prev_link = $this->current_page - 1;
        }
        $output['page'][] = $this->get_page_link($prev_link,'Previous');

        $output['page'] = array_merge($output['page'],$this->get_page_numbers());

        $next_link = "#";
        if ($this->current_page < $this->total_pages) {
            $next_link =  $this->current_page + 1;
        }
        $output['page'][] = $this->get_page_link($next_link,'Next');

        return $output;
    }

    private function get_page_numbers() {
        $pages = [];

        if ($this->total_pages <= 5) {
            for ($i = 1; $i <= $this->total_pages; $i++) {
                $pages[] = $this->get_page_link($i);
            }
            return $pages;
        }

        $pages[]= $this->get_page_link(1);

        if ($this->current_page > 3) {
            $pages[] = ['text'=>'...'];
        }
        $start = ($this->current_page <= 2) ? 2 : (($this->current_page < $this->total_pages) ? max(2, $this->current_page - 1) : $this->total_pages - 2);
        $end = ($this->current_page <= 2) ? 3 : min($this->total_pages - 1, $this->current_page + 1);
        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $this->get_page_link($i);
        }
        if ($this->current_page < $this->total_pages - 2) {
            $pages[] = ['text'=>'...'];
        }
        $pages[] = $this->get_page_link($this->total_pages);

        return $pages;
    }

    private function get_page_link($page_number,$text=null) {
        $text = $text ?: $page_number;
        $class = $this->current_page == $page_number ? 'current' : ($page_number === "#"? 'disabled': '');
        $url = $page_number;
        if($url !== "#"){
            $url = add_query_arg(array_merge(
                [$this->url_parameter => $page_number],
                $this->additional_params
            ), get_permalink());
        }
        
        return ['link' => $url,'text' => $text,'class'=>$class];
    }
}
