<?php

use Avilio\ACF;

function get_menu_by_location($location, $args = [])
{
    $locations = get_nav_menu_locations();
    if(isset($locations[$location])){
        $object = wp_get_nav_menu_object($locations[$location]);
        $navbar_items = wp_get_nav_menu_items($object->name, $args);

        return ["name" => $object->name, 'menu' => build_tree($navbar_items)];
    }
    return false;
}

function build_tree($elements, $parentId = 0)
{
    $branch = [];
    foreach ($elements as $element) {
        if ($element->menu_item_parent == $parentId) {
            $children = build_tree($elements, $element->ID);
            if ($children)
                $element->menu_children = $children;

            $branch[$element->ID] = $element;
            unset($element);
        }
    }
    return $branch;
}

function view_url($text)
{
    $url = parse_url($text);
    $res = $text;
    if (!isset($url["scheme"])):
        $res = "http://{$text}";
    endif;
 
    if (filter_var($res, FILTER_VALIDATE_URL) === FALSE):
        $res = "#{$res}";
    endif;
    echo $res;
}
function view($var,$empty_val="--"){
    if($var){
        return $var;
    }else{
        return $empty_val;
    }
}


function get_featured_url($id, $size = "full")
{
    
    if (has_post_thumbnail($id)) {
        $img = wp_get_attachment_image_src(get_post_thumbnail_id($id), $size);
        return $img[0];
    } else {
        $image = ACF::option("default_image", ['size' => $size]);
        return $image;
    }
}

function get_image($image, $size = "full",$default='default_image')
{
    
    if(get_field($default,'option')){
        $id = $image ? $image : get_field($default, 'option');
    }else{
        $id = $image;
    }

    if (is_numeric($id)) {
        $img = wp_get_attachment_image_src($id, $size);
        return $img[0];
    } else {
        return $id;
    }
}


function get_post_list($args, $return = [])
{
    $data = [];
    if ($args) {
        $query = new WP_Query($args);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $item = [];
                foreach ($return as $key => $value) {
                    switch ($value) {
                        case 'title':
                            $item[$key] = get_the_title();
                            break;
                        case 'link':
                            $item[$key] = get_the_permalink();
                            break;
                        case (strpos($value, 'featured_image') === 0):
                            $size = substr($value, 15);
                            $item[$key] = get_featured_url(get_the_ID(), $size);
                            break;
                        case (strpos($value, 'acf_') === 0):
                            $field_name = substr($value, 4);
                            $item[$key] = ACF::field($field_name);
                            break;
                        default:
                            $item[$key] = $value;
                            break;
                    }
                }
                $data[] = $item;
            }
            wp_reset_postdata();
        }
    }
    return $data;
}
function format_number($value, $length = 2)
{
    return str_pad($value, $length, '0', STR_PAD_LEFT);
}
function print_array($array)
{
    echo '<pre>';
    echo json_encode($array, JSON_PRETTY_PRINT);
    echo '</pre>';
}