<?php

/**
 * Created by PhpStorm.
 * User: mytory
 * Date: 10/30/16
 * Time: 1:41 PM
 */
class MytoryBoardAdmin
{
    function __construct()
    {
        add_action('admin_menu', array($this, 'addMenuPage'));
        add_action('admin_enqueue_scripts', array($this, 'adminScripts'));
        add_action('wp_ajax_mytoryBoardSearchPost', array($this, 'searchPost'));
        add_action('admin_init', array($this, 'options'));
    }

    function addMenuPage()
    {
        add_menu_page('고정글', '고정글', 'edit_others_posts', 'mytory-board-sticky-posts', array($this, 'stickyPosts'), '', 6);
    }

    function adminScripts()
    {
        $screen = get_current_screen();
        if ($screen->id == 'toplevel_page_mytory-board-sticky-posts') {
            wp_enqueue_script('mytory-board-sticky-posts', plugin_dir_url(__FILE__) . 'sticky-posts.js',
                array('jquery-ui-autocomplete', 'underscore'), false, true);
        }
    }

    function stickyPosts()
    {
        $result_message = "";
        if (!empty($_POST)) {
            wp_verify_nonce($_POST['_wpnonce'], 'mytory-board-sticky-posts');
            $diff = array_diff(get_option('sticky_posts'), explode(',', $_POST['sticky_posts']));
            if (update_option('sticky_posts', explode(',', $_POST['sticky_posts']))) {
                $result_message = '저장했습니다.';
            } else if (empty($diff)) {
                $result_message = '추가/제거한 글이 없어서 저장하지 않았습니다.';
            } else {
                $result_message = '저장중 오류가 있었습니다.';
            }
        }
        include __DIR__ . '/sticky-posts.php';
    }

    function searchPost()
    {
        global $wp_query;
        $args = array(
            'post_type' => 'any',
            's' => $_GET['term'],
            'posts_per_page' => 50,
        );
        if (!empty($_GET['selected'])) {
            $args['post__not_in'] = explode(',', $_GET['selected']);
        }
        $wp_query = new WP_Query($args);

        $posts_for_autocomplete = array();
        while (have_posts()): the_post();
            $posts_for_autocomplete[] = array(
                'id' => get_the_ID(),
                'value' => get_the_title() . ' (' . get_the_date() . ')',
            );
        endwhile;
        if (empty($posts_for_autocomplete)) {
            $posts_for_autocomplete = array(
                array(
                    'id' => '',
                    'value' => '검색 결과가 없습니다.'
                )
            );
        }
        echo json_encode($posts_for_autocomplete);
        wp_die();
    }

    function options()
    {
    }
}

new MytoryBoardAdmin();