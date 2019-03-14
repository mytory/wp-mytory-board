<?php

/**
 * @package           MytoryBoard
 * Plugin Name:       Mytory Board
 * Description:       한국형 게시판 Mytory Board
 * Author:            mytory
 * Author URI:        http://mytory.net
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */
class MytoryBoard
{
    public $defaultBoardId = 58; // set yours.
    public $canAnonymousWriting = true;
    public $canSetNameByPost = true;

    function __construct()
    {
        add_action('init', array($this, 'registerMytoryBoardPost'));
        add_action('init', array($this, 'registerMytoryBoard'));
        register_activation_hook(__FILE__, [$this, 'flushRewriteRules']);
        register_activation_hook(__FILE__, array($this, 'addRole'));
        register_deactivation_hook(__FILE__, array($this, 'removeRole'));
        register_deactivation_hook(__FILE__, 'flush_rewrite_rules');
        add_action('wp_enqueue_scripts', array($this, 'scripts'));
        add_action('save_post_mytory_board_post', array($this, 'savePost'), 10, 3);
        add_action('wp_head', array($this, 'globalJsVariable'));
        add_action('wp_ajax_increase_pageview', array($this, 'increasePageview'));
        add_action('wp_ajax_nopriv_increase_pageview', array($this, 'increasePageview'));
        if ($this->defaultBoardId) {
            add_action('publish_mytory_board_post', array($this, 'defaultMytoryBoard'));
        }
    }

    function registerMytoryBoardPost()
    {
        $labels = array(
            'name' => '게시글',
            'singular_name' => '게시글',
            'add_new' => '게시글 추가',
            'add_new_item' => '게시글 추가',
            'edit_item' => '게시글 수정',
            'new_item' => '게시글 추가',
            'all_items' => '게시글',
            'view_item' => '게시글 상세 보기',
            'search_items' => '게시글 검색',
            'not_found' => '등록된 게시글이 없습니다',
            'not_found_in_trash' => '휴지통에 게시글이 없습니다',
            'parent_item_colon' => '부모 게시글:',
            'menu_name' => '게시글',
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'rewrite' => array(
                'slug' => 'b',
                'with_front' => false,
            ),
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'custom-field', 'comments', 'revisions'),
        );

        register_post_type('mytory_board_post', $args);
    }

    function registerMytoryBoard()
    {
        $labels = array(
            'name' => '게시판',
            'singular_name' => '게시판',
            'search_items' => '게시판 검색',
            'popular_items' => '많이 쓴 게시판',
            'all_items' => '게시판 목록',
            'edit_item' => '게시판 수정',
            'view_item' => '게시판 보기',
            'update_item' => '저장',
            'add_new_item' => '게시판 추가',
            'new_item_name' => '새 게시판 이름',
            'separate_items_with_commas' => '여러 개 입력하려면 쉽표(,)로 구분하세요',
            'add_or_remove_items' => '게시판 추가 혹은 삭제',
            'choose_from_most_used' => '많이 쓴 게시판 중 선택',
            'not_found' => '게시판이 없습니다',
            'menu_name' => '게시판',
        );

        $args = array(
            'labels' => $labels,
            'hierarchical' => true,
            'show_admin_column' => true,
            'rewrite' => array(
                'slug' => 'mb',
                'with_front' => false,
            ),
        );

        register_taxonomy('mytory_board', 'mytory_board_post', $args);
    }

    function addRole()
    {
        add_role(
            'board_writer',
            '게시판 글쓴이',
            array(
                'read' => true,
                'upload_files' => true,
            )
        );
    }

    function removeRole()
    {
        remove_role('board_writer');
    }

    function flushRewriteRules()
    {
        $this->registerMytoryBoard();
        $this->registerMytoryBoardPost();
        flush_rewrite_rules();
    }

    function defaultMytoryBoard($post_id)
    {
        if (!has_term('', 'mytory_board', $post_id)) {
            wp_set_object_terms($post_id, $this->defaultBoardId, 'mytory_board');
        }
    }

    function scripts()
    {
        wp_enqueue_style('mytory-board-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script('mytory-board-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), false, true);
        if (is_singular()) {
            // 어딘가에 data-post-id="1234" 라고 넣으면 그걸 참조해서 페이지뷰를 올린다.
            wp_enqueue_script('mytory-pageview', plugin_dir_url(__FILE__) . 'pageview.js', array('jquery'), false, true);
        }
    }

    function savePost($post_id, $post, $is_update)
    {
        remove_action('save_post_mytory_board_post', array($this, 'savePost'));
        $post->post_name = $post->ID;
        wp_update_post((array)$post);
        add_action('save_post_mytory_board_post', array($this, 'savePost'), 10, 3);

        foreach ($_POST['meta'] as $k => $v) {
            update_post_meta($post_id, "mytory_board_{$k}", $v);
        }
    }

    function globalJsVariable()
    {
        global $wp_query;
        ?>
        <script type="text/javascript">
            var MytoryBoard = {
                ajaxUrl: <?= json_encode(admin_url( "admin-ajax.php" )); ?>,
                ajaxNonce: <?= json_encode(wp_create_nonce("mytory-board-ajax-nonce")); ?>,
                wpDebug: <?= defined(WP_DEBUG) ? WP_DEBUG : 'false' ?>
            };
        </script><?php
    }

    function increasePageview()
    {
        wp_verify_nonce($_POST['nonce'], 'mytory-board-ajax-nonce');
        $post_id = $_POST['post_id'];
        if ($result = update_post_meta($post_id, 'pageview', get_post_meta($post_id, 'pageview', true) + 1)) {
            echo json_encode(array('result' => 1, 'parameters' => $_POST));
        } else {
            echo json_encode(array('result' => $result, 'parameters' => $_POST));
        }
        wp_die();
    }

    public function getEditLink()
    {
        $edit_link = get_permalink(get_page_by_path('write'));
        $parsed = parse_url($edit_link);
        print_r($parsed);
        if (empty($parsed['query'])) {
            $parsed['query'] = '';
        }
        parse_str($parsed['query'], $query_string);
        print_r($query_string);
        $query_string['writing_id'] = get_the_ID();
        $edit_link = "{$parsed['scheme']}://{$parsed['host']}{$parsed['path']}?" . http_build_query($query_string);
        return $edit_link;
    }

    public function getDeleteLink($redirect_to)
    {
        return plugin_dir_url(__FILE__) . 'delete.php?writing_id=' . get_the_ID() . '&redirect_to=' . urlencode($redirect_to);
    }
}

$MytoryBoard = new MytoryBoard();
include_once 'MytoryBoardAdmin.php';
include_once 'functions.php';

