<?php
/*
Plugin Name: My Books Plugin
Description: A plugin to manage books with custom fields and REST API.
Version: 1.0
Author: Hager Sayed
*/

function create_books_post_type() {
    $labels = array(
        'name'                  => __('Books'),
        'singular_name'         => __('Book'),
        'add_new'               => __('Add New Book'),
        'add_new_item'          => __('Add New Book'),
       
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'supports'           => array('title'),
        'show_in_rest'       => true,
        'menu_icon'          => 'dashicons-book-alt',
        'template'           => array(
            array('core/paragraph', array('placeholder' => 'Enter book description...')),
        ),
        'template_lock'      => 'all',
    );

    register_post_type('books', $args);
}
add_action('init', 'create_books_post_type');

function remove_custom_fields_meta_box() {
    remove_meta_box('postcustom', 'books', 'normal');
}
add_action('add_meta_boxes', 'remove_custom_fields_meta_box');

function add_books_meta_boxes() {
    add_meta_box(
        'books_meta_box',
        'Book Details',
        'display_books_meta_box',
        'books'
    );
}
add_action('add_meta_boxes', 'add_books_meta_boxes');

function display_books_meta_box($post) {
    $author = get_post_meta($post->ID, 'author', true);
    $publication_date = get_post_meta($post->ID, 'publication_date', true);
    ?>
    <style>
        .book-meta-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .book-meta-input {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
    </style>

    <label for="author" class="book-meta-label">Author:</label>
    <input type="text" id="author" name="author" class="book-meta-input" value="<?php echo esc_attr($author); ?>" />
    
    <label for="publication_date" class="book-meta-label">Publication Date:</label>
    <input type="date" id="publication_date" name="publication_date" class="book-meta-input" value="<?php echo esc_attr($publication_date); ?>" />
    <?php
}

function save_books_meta_box($post_id) {
    if (isset($_POST['author'])) {
        update_post_meta($post_id, 'author', sanitize_text_field($_POST['author']));
    }
    if (isset($_POST['publication_date'])) {
        update_post_meta($post_id, 'publication_date', sanitize_text_field($_POST['publication_date']));
    }
}
add_action('save_post', 'save_books_meta_box');

function register_books_api() {
    register_rest_route('custom/v1', '/books', array(
        'methods'  => 'GET',
        'callback' => 'get_books',
    ));
}
add_action('rest_api_init', 'register_books_api');

function get_books() {
    $args = array(
        'post_type'      => 'books',
        'posts_per_page' => -1,
    );
    $query = new WP_Query($args);
    $books = array();

    while ($query->have_posts()) {
        $query->the_post();
        $books[] = array(
            'id'                => get_the_ID(),
            'title'             => get_the_title(),
            'author'            => get_post_meta(get_the_ID(), 'author', true),
            'publication_date'   => get_post_meta(get_the_ID(), 'publication_date', true),
        );
    }

    wp_reset_postdata();
    return rest_ensure_response($books);
}

function books_shortcode($atts) {
    ob_start();
    ?>
    <style>
        #books-list {
            margin-top: 20px;
            font-family: Arial, sans-serif;
        }
        #books-list ul {
            list-style-type: none;
            padding: 0;
        }
        #books-list li {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
    </style>
    <div id="books-list"></div>
    <script>
    jQuery(document).ready(function($) {
        $.ajax({
            url: '<?php echo esc_url(rest_url('custom/v1/books')); ?>',
            method: 'GET',
            success: function(data) {
                var booksHtml = '<ul>';
                data.forEach(function(book) {
                    booksHtml += '<li>' + book.title + ' by ' + book.author + ' (Published: ' + book.publication_date + ')</li>';
                });
                booksHtml += '</ul>';
                $('#books-list').html(booksHtml);
            }
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('books', 'books_shortcode');
