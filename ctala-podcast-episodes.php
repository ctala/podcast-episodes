<?php
/*
Copyright 2023 cristiantala
Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at
http://www.apache.org/licenses/LICENSE-2.0
Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
Plugin Name: Podcast Episodes
Description: Un plugin personalizado que agrega un Custom Post Type y una taxonomía para episodios de podcast.
Version: 1.0
Author: Cristian Tala Sánchez
*/

//Custom Post Type 'podcast_episodes'
function podcast_episodes_post_type()
{
    $labels = array(
        'name' => __('Episodios de Podcast'),
        'singular_name' => __('Episodio de Podcast'),
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'taxonomies' => array('podcast'),
        'show_in_rest' => true,
    );

    register_post_type('podcast_episodes', $args);
}

add_action('init', 'podcast_episodes_post_type');

//Taxonomía 'Podcast'
function podcast_taxonomy()
{
    $labels = array(
        'name' => __('Podcasts'),
        'singular_name' => __('Podcast'),
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
        // Asegúrate de incluir esta línea para habilitar el soporte de la descripción en el editor de bloques
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'podcast'),
    );

    register_taxonomy('podcast', 'podcast_episodes', $args);
}

add_action('init', 'podcast_taxonomy');

//Añadir campo personalizado 'url_youtube'
function podcast_episodes_meta_box()
{
    add_meta_box(
        'podcast_episodes_youtube',
        'URL de YouTube',
        'podcast_episodes_youtube_meta_box_callback',
        'podcast_episodes',
        'side',
        'default'
    );
}

add_action('add_meta_boxes', 'podcast_episodes_meta_box');

function podcast_episodes_youtube_meta_box_callback($post)
{
    wp_nonce_field('podcast_episodes_youtube_save', 'podcast_episodes_youtube_nonce');

    $value = get_post_meta($post->ID, 'url_youtube', true);

    echo '<label for="podcast_episodes_youtube_field">';
    echo 'URL de YouTube:';
    echo '</label> ';
    echo '<input type="url" id="podcast_episodes_youtube_field" name="podcast_episodes_youtube_field" value="' . esc_attr($value) . '" size="25" />';
}

function save_podcast_episodes_youtube_meta_box($post_id)
{
    if (!isset($_POST['podcast_episodes_youtube_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['podcast_episodes_youtube_nonce'], 'podcast_episodes_youtube_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (!isset($_POST['podcast_episodes_youtube_field'])) {
        return;
    }

    $data = sanitize_text_field($_POST['podcast_episodes_youtube_field']);

    update_post_meta($post_id, 'url_youtube', $data);
}

add_action('save_post', 'save_podcast_episodes_youtube_meta_box');

// Incluir el video de YouTube en el header del template
// Usa el formateador en caso de que incluyan el link de distintas maneras
function podcast_episodes_youtube_header()
{
    if (is_singular('podcast_episodes')) {
        global $post;
        $youtube_url = get_post_meta($post->ID, 'url_youtube', true);

        if ($youtube_url) {
            $video_id = getYoutubeVideoId($youtube_url);
            $formatted_url = "https://www.youtube.com/embed/$video_id";
            echo '<div class="podcast-episodes-youtube">';
            echo '<iframe width="560" height="315" src="' . $formatted_url . '" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
            echo '</div>';
        }
    }
}

add_action('wp_head', 'podcast_episodes_youtube_header');
add_action('wp_footer', 'podcast_episodes_taxonomy_below_title');


// Agregar estilos para el video de YouTube en el header
function podcast_episodes_styles()
{
    echo '<style>
.podcast-episodes-youtube {
position: relative;
padding-bottom: 56.25%;
height: 0;
overflow: hidden;
}
.podcast-episodes-youtube iframe {
position: absolute;
top: 0;
left: 0;
width: 100%;
height: 100%;
}
</style>';
}

add_action('wp_head', 'podcast_episodes_styles');




function podcast_episodes_taxonomy_below_title()
{
    if (is_singular('podcast_episodes')) {
        global $post;

        $terms = wp_get_post_terms($post->ID, 'podcast');

        if (!empty($terms) && !is_wp_error($terms)) {
            echo '<div class="podcast-episode-taxonomy">';
            echo 'Podcast: ';

            $term_links = array();

            foreach ($terms as $term) {
                $term_link = get_term_link($term, 'podcast');
                $term_links[] = '<a href="' . esc_url($term_link) . '">' . esc_html($term->name) . '</a>';
            }

            echo implode(', ', $term_links);
            echo '</div>';
        }
    }
}


function theme_slug_filter_the_title($title)
{
    return $title;
}
add_filter('the_title', 'theme_slug_filter_the_title');



function getYoutubeVideoId($url)
{
    $pattern = '/^(?:https?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))((\w|-){11})(?:\S+)?$/';
    if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
    } else {
        return false;
    }
}
?>