<?php

defined('ABSPATH') || die();


add_action( 'rest_api_init', 'register_custom_api_endpoints' );

function register_custom_api_endpoints() {

    // Register endpoint for all data
    register_rest_route( 'anony', '/woo/home', array(
        'methods' => 'GET',
        'callback' => 'anony_get_home_data',
        'permission_callback' => '__return_true',

    ) );
	
	register_rest_route( 'anony', '/woo/brands', array(
        'methods' => 'GET',
        'callback' => 'anony_get_brands',
        'permission_callback' => '__return_true',

    ) );

}

function anony_get_home_data() {
    $result = array(
        'product_categories' => get_product_categories(),
        'brands' => anony_get_brands(),
        'recent_products' => get_recent_products(),
        'featured_products' => get_featured_products(),
        'best_sellers' => get_best_sellers(),
    );

    return $result;
}

function get_product_categories() {
    $categories = get_terms( 'product_cat', array(
        'parent' => 0,
        'hide_empty' => false,
    ) );

    $result = array();
    foreach ( $categories as $category ) {
        $thumbnail_id = get_term_meta( $category->term_id, 'thumbnail_id', true );
        $thumbnail = wp_get_attachment_url( $thumbnail_id );

        $category_data = array(
            'id' => $category->term_id,
            'name' => $category->name,
            'thumbnail' => $thumbnail,
        );

        $result[] = $category_data;
    }

    return $result;
}

function anony_get_brands() {
    $brands = get_terms( 'pa_brand', array(
        'parent' => 0,
        'hide_empty' => false,
    ) );

    $result = array();
    foreach ( $brands as $brand ) {
        $thumbnail_id = get_term_meta( $brand->term_id, 'image', true );
        $thumbnail = wp_get_attachment_url( $thumbnail_id );

        $brand_data = array(
            'id' => $brand->term_id,
            'name' => $brand->name,
            'thumbnail' => $thumbnail_id,
        );

        $result[] = $brand_data;
    }

    return $result;
}

function get_recent_products() {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 4,
        'orderby' => 'date',
        'order' => 'DESC',
    );

    $products = array();
    $query = new WP_Query( $args );
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            global $product;

            $product_data = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'currency' => get_woocommerce_currency(),
                'category' => wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) ),
                'short_description' => $product->get_short_description(),
            );

            $products[] = $product_data;
        }
    }
    wp_reset_postdata();

    return $products;
}

function get_featured_products() {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 4,
        'orderby' => 'rand',
        'meta_query' => array(
            array(
                'key' => '_featured',
                'value' => 'yes',
            ),
        ),
    );

    $products = array();
    $query = new WP_Query( $args );
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            global $product;

            $product_data = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'currency' => get_woocommerce_currency(),
                'category' => wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) ),
                'short_description' => $product->get_short_description(),
            );

            $products[] = $product_data;
        }
    }
    wp_reset_postdata();

    return $products;
}
function get_best_sellers() {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 4,
        'meta_key' => 'total_sales',
        'orderby' => 'meta_value_num',
    );

    $products = array();
    $query = new WP_Query( $args );
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            global $product;

            $product_data = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'currency' => get_woocommerce_currency(),
                'category' => wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) ),
                'short_description' => $product->get_short_description(),
            );

            $products[] = $product_data;
        }
    }
    wp_reset_postdata();

    return $products;
}
/*
function get_best_sellers() {
    global $wpdb;

    $query = "
        SELECT order_items.order_item_id as product_id, SUM(order_item_meta.meta_value) as quantity
        FROM {$wpdb->prefix}woocommerce_order_items as order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
        LEFT JOIN {$wpdb->prefix}posts as posts ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
        AND posts.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
        AND order_items.order_item_type = 'line_item'
        AND order_item_meta.meta_key = '_qty'
        GROUP BY order_items.order_item_id
        ORDER BY quantity DESC
        LIMIT 10
    ";

    $results = $wpdb->get_results( $query );

    $result = array();
    foreach ( $results as $result ) {
        $product = wc_get_product( $result->product_id );

        $product_data = array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'thumbnail' => $product->get_image(),
        );

        $result[] = $product_data;
    }

    return $result;
}
*/