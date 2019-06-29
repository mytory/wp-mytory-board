<?php

namespace Mytory\Board;

/**
 * @package           MytoryBoard
 * Plugin Name:       Mytory Board
 * Description:       한국형 게시판 Mytory Board
 * Author:            mytory
 * Author URI:        http://mytory.net
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */
class MytoryBoard {
	/**
	 * 게시판을 설정하지 않고 글을 썼을 때 기본으로 설정할 게시판이 있는지
	 * @var null
	 */
	public $defaultBoardId = null;

	/**
	 * 로그인 안 한 사람이 글을 쓸 수 있는지
	 * @var bool
	 */
	public $canAnonymousWriting = true;

	/**
	 * 글별로 이름을 따로 설정할 수 있는지
	 * @var bool
	 */
	public $canSetNameByPost = true;

	function __construct( $config = [] ) {
		$this->setConfig( $config );

		add_action( 'init', array( $this, 'registerMytoryBoardPost' ) );
		add_action( 'init', array( $this, 'registerMytoryBoard' ) );
		add_action( 'admin_menu', [ $this, 'addSubMenu' ] );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
		add_action( 'save_post_mytory_board_post', array( $this, 'savePost' ), 10, 3 );
		add_action( 'wp_head', array( $this, 'globalJsVariable' ) );
		add_action( 'wp_ajax_increase_pageview', array( $this, 'increasePageview' ) );
		add_action( 'wp_ajax_nopriv_increase_pageview', array( $this, 'increasePageview' ) );
		add_action( 'publish_mytory_board_post', array( $this, 'defaultMytoryBoard' ) );
	}

	private function setConfig( $config ) {
		$this->defaultBoardId      = $config['defaultBoardId'] ?? $this->defaultBoardId;
		$this->canAnonymousWriting = $config['canAnonymousWriting'] ?? $this->canAnonymousWriting;
		$this->canSetNameByPost    = $config['canSetNameByPost'] ?? $this->canSetNameByPost;
	}


	function addSubMenu() {
		$wp_term_query = new \WP_Term_Query( [
			'taxonomy'   => 'mytory_board',
			'hide_empty' => false,
		] );

		if ( $wp_term_query->terms ) {
			foreach ( $wp_term_query->terms as $term ) {
				add_submenu_page(
					'edit.php?post_type=mytory_board_post',
					"{$term->name} 게시판",
					"{$term->name} 게시판",
					'edit_others_posts',
					'mytory_board_' . $term->term_id,
					function () use ( $term ) {
						$url = site_url( '/wp-admin/edit.php?post_type=mytory_board_post&mytory_board=' . $term->slug );
						?>
                        <meta http-equiv="refresh" content="0;url=<?= $url ?>"/>
						<?php
					}
				);
			}
		}
	}


	function registerMytoryBoard() {
		$labels = array(
			'name'                       => '게시판',
			'singular_name'              => '게시판',
			'search_items'               => '게시판 검색',
			'popular_items'              => '많이 쓴 게시판',
			'all_items'                  => '게시판 목록',
			'edit_item'                  => '게시판 수정',
			'view_item'                  => '게시판 보기',
			'update_item'                => '저장',
			'add_new_item'               => '게시판 추가',
			'new_item_name'              => '새 게시판 이름',
			'separate_items_with_commas' => '여러 개 입력하려면 쉽표(,)로 구분하세요',
			'add_or_remove_items'        => '게시판 추가 혹은 삭제',
			'choose_from_most_used'      => '많이 쓴 게시판 중 선택',
			'not_found'                  => '게시판이 없습니다',
			'menu_name'                  => '게시판',
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug'       => 'mb',
				'with_front' => false,
			),
		);

		register_taxonomy( 'mytory_board', 'mytory_board_post', $args );
	}

	function registerMytoryBoardPost() {
		$labels = array(
			'name'               => '게시글',
			'singular_name'      => '게시글',
			'add_new'            => '게시글 추가',
			'add_new_item'       => '게시글 추가',
			'edit_item'          => '게시글 수정',
			'new_item'           => '게시글 추가',
			'all_items'          => '게시글',
			'view_item'          => '게시글 상세 보기',
			'search_items'       => '게시글 검색',
			'not_found'          => '등록된 게시글이 없습니다',
			'not_found_in_trash' => '휴지통에 게시글이 없습니다',
			'parent_item_colon'  => '부모 게시글:',
			'menu_name'          => '게시글',
		);

		$args = array(
			'labels'      => $labels,
			'public'      => true,
			'has_archive' => true,
			'rewrite'     => array(
				'slug'       => 'b',
				'with_front' => false,
			),
			'supports'    => array( 'title', 'editor', 'author', 'thumbnail', 'custom-field', 'comments', 'revisions' ),
		);

		register_post_type( 'mytory_board_post', $args );
	}

	/**
	 * 게시판 없이 글을 저장하면, 기본 게시판을 설정한다. 단, 기본 게시판 번호가 설정돼 있어야 한다.
	 *
	 * @param $post_id
	 */
	function defaultMytoryBoard( $post_id ) {
		if ( ! empty( $this->defaultBoardId ) and ! has_term( '', 'mytory_board', $post_id ) ) {
			wp_set_object_terms( $post_id, $this->defaultBoardId, 'mytory_board' );
		}
	}

	function scripts() {
		wp_enqueue_style( 'mytory-board-style', plugin_dir_url( __FILE__ ) . 'style.css' );
		wp_enqueue_script( 'mytory-board-script', plugin_dir_url( __FILE__ ) . 'script.js', array( 'jquery' ), false, true );
		if ( is_singular() ) {
			// 어딘가에 data-post-id="1234" 라고 넣으면 그걸 참조해서 페이지뷰를 올린다.
			wp_enqueue_script( 'mytory-pageview', plugin_dir_url( __FILE__ ) . 'pageview.js', array( 'jquery' ), false,
				true );
		}
	}

	function savePost( $post_id, $post, $is_update ) {
		remove_action( 'save_post_mytory_board_post', array( $this, 'savePost' ) );
		$post->post_name = $post->ID;
		wp_update_post( (array) $post );
		add_action( 'save_post_mytory_board_post', array( $this, 'savePost' ), 10, 3 );

		if ( ! empty( $_POST['meta'] ) ) {
			foreach ( $_POST['meta'] as $k => $v ) {

				if ( mb_strcut( $k, 0, 13, 'utf-8' ) === 'mytory_board_' ) {
					update_post_meta( $post_id, $k, $v );
				} else {
					update_post_meta( $post_id, "mytory_board_{$k}", $v );
				}

			}
		}

	}

	function globalJsVariable() {
		global $wp_query;
		?>
        <script type="text/javascript">
            var MytoryBoard = {
                ajaxUrl: <?= json_encode( admin_url( "admin-ajax.php" ) ); ?>,
                ajaxNonce: <?= json_encode( wp_create_nonce( "mytory-board-ajax-nonce" ) ); ?>,
                wpDebug: <?= defined( WP_DEBUG ) ? WP_DEBUG : 'false' ?>
            };
        </script><?php
	}

	function increasePageview() {
		wp_verify_nonce( $_POST['nonce'], 'mytory-board-ajax-nonce' );
		$post_id = $_POST['post_id'];
		if ( $result = update_post_meta( $post_id, 'pageview', get_post_meta( $post_id, 'pageview', true ) + 1 ) ) {
			echo json_encode( array( 'result' => 1, 'parameters' => $_POST ) );
		} else {
			echo json_encode( array( 'result' => $result, 'parameters' => $_POST ) );
		}
		wp_die();
	}

	public function getEditLink() {
		$edit_link = get_permalink( get_page_by_path( 'write' ) );
		$parsed    = parse_url( $edit_link );
		print_r( $parsed );
		if ( empty( $parsed['query'] ) ) {
			$parsed['query'] = '';
		}
		parse_str( $parsed['query'], $query_string );
		print_r( $query_string );
		$query_string['writing_id'] = get_the_ID();
		$edit_link                  = "{$parsed['scheme']}://{$parsed['host']}{$parsed['path']}?" . http_build_query( $query_string );

		return $edit_link;
	}

	public function getDeleteLink( $redirect_to ) {
		return plugin_dir_url( __FILE__ ) . 'delete.php?writing_id=' . get_the_ID() . '&redirect_to=' . urlencode( $redirect_to );
	}
}


