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


	public $taxonomyKey = 'mytory_board';
	public $taxonomyLabel = '게시판';
	public $taxonomyRewriteSlug = 'mb';
	public $postTypeKey = 'mytory_board_post';
	public $postTypeLabel = '게시글';
	public $postTypeRewriteSlug = 'b';


	/**
	 * MytoryBoard constructor.
	 *
	 * @param array $config {
	 *
	 * @type int $defaultBoardId : 기본 게시판 term id
	 * @type boolean $canAnonymousWriting : 익명 쓰기 가능 여부
	 * @type boolean $canSetNameByPost : 게시글별 이름 설정 가능 여부
	 * @type string $taxonomyKey : 게시판 taxonomy key
	 * @type string $taxonomyLabel : 게시판 taxonomy label
	 * @type string $taxonomyRewriteSlug : 게시판 url rewrite slug. Default 'mb'
	 * @type string $postTypeKey : 게시글 post type key
	 * @type string $postTypeLabel : 게시글 post type label
	 * @type string $postTypeRewriteSlug : 게시글 url rewrite slug. Default 'b'
	 * }
	 */
	function __construct( $config = [] ) {
		$this->setConfig( $config );

		add_action( 'init', [ $this, 'registerPostType' ] );
		add_action( 'admin_init', [ $this, 'boardCapabilities' ] );
		add_action( 'init', [ $this, 'registerBoardTaxonomy' ] );
		add_action( 'admin_menu', [ $this, 'addSubMenu' ] );
		add_action( "save_post_{$this->postTypeKey}", [ $this, 'savePost' ], 10, 3 );
		add_action( 'wp_head', [ $this, 'globalJsVariable' ] );
		add_action( "wp_ajax_{$this->taxonomyKey}_increase_pageview", [ $this, 'increasePageview' ] );
		add_action( "wp_ajax_nopriv_{$this->taxonomyKey}_increase_pageview", [ $this, 'increasePageview' ] );
		add_action( "publish_{$this->postTypeKey}", [ $this, 'defaultMytoryBoard' ] );

		new MytoryBoardAdmin( $this );
	}

	private function setConfig( $config ) {
		$this->defaultBoardId      = $config['defaultBoardId'] ?? $this->defaultBoardId;
		$this->canAnonymousWriting = $config['canAnonymousWriting'] ?? $this->canAnonymousWriting;
		$this->canSetNameByPost    = $config['canSetNameByPost'] ?? $this->canSetNameByPost;
		$this->taxonomyKey         = $config['taxonomyKey'] ?? $this->taxonomyKey;
		$this->taxonomyLabel       = $config['taxonomyLabel'] ?? $this->taxonomyLabel;
		$this->taxonomyRewriteSlug = $config['taxonomyRewriteSlug'] ?? $this->taxonomyRewriteSlug;
		$this->postTypeKey         = $config['postTypeKey'] ?? $this->postTypeKey;
		$this->postTypeLabel       = $config['postTypeLabel'] ?? $this->postTypeLabel;
		$this->postTypeRewriteSlug = $config['postTypeRewriteSlug'] ?? $this->postTypeRewriteSlug;
	}


	function addSubMenu() {
		$wp_term_query = new \WP_Term_Query( [
			'taxonomy'   => $this->taxonomyKey,
			'hide_empty' => false,
		] );

		if ( $wp_term_query->terms ) {
			foreach ( $wp_term_query->terms as $term ) {
				add_submenu_page(
					"edit.php?post_type={$this->postTypeKey}",
					"{$term->name} {$this->taxonomyLabel}",
					"{$term->name} {$this->taxonomyLabel}",
					'edit_others_posts',
					"{$this->taxonomyKey}_{$term->term_id}",
					function () use ( $term ) {
						$url = site_url( "/wp-admin/edit.php?post_type={$this->postTypeKey}&{$this->taxonomyKey}={$term->slug}" );
						?>
                        <meta http-equiv="refresh" content="0;url=<?= $url ?>"/>
						<?php
					}
				);
			}
		}
	}


	function registerBoardTaxonomy() {
		$labels = array(
			'name'                       => "{$this->taxonomyLabel}",
			'singular_name'              => "{$this->taxonomyLabel}",
			'search_items'               => "{$this->taxonomyLabel} 검색",
			'popular_items'              => "많이 쓴 {$this->taxonomyLabel}",
			'all_items'                  => "{$this->taxonomyLabel} 목록",
			'edit_item'                  => "{$this->taxonomyLabel} 수정",
			'view_item'                  => "{$this->taxonomyLabel} 보기",
			'update_item'                => "저장",
			'add_new_item'               => "{$this->taxonomyLabel} 추가",
			'new_item_name'              => "새 {$this->taxonomyLabel} 이름",
			'separate_items_with_commas' => "여러 개 입력하려면 쉽표(,)로 구분하세요",
			'add_or_remove_items'        => "{$this->taxonomyLabel} 추가 혹은 삭제",
			'choose_from_most_used'      => "많이 쓴 {$this->taxonomyLabel} 중 선택",
			'not_found'                  => "{$this->taxonomyLabel}이 없습니다",
			'menu_name'                  => "{$this->taxonomyLabel}",
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug'       => $this->taxonomyRewriteSlug,
				'with_front' => false,
			),
		);

		register_taxonomy( $this->taxonomyKey, $this->postTypeKey, $args );
	}

	function registerPostType() {
		$labels = array(
			'name'               => "{$this->postTypeLabel}",
			'singular_name'      => "{$this->postTypeLabel}",
			'add_new'            => "{$this->postTypeLabel} 추가",
			'add_new_item'       => "{$this->postTypeLabel} 추가",
			'edit_item'          => "{$this->postTypeLabel} 수정",
			'new_item'           => "{$this->postTypeLabel} 추가",
			'all_items'          => "{$this->postTypeLabel}",
			'view_item'          => "{$this->postTypeLabel} 상세 보기",
			'search_items'       => "{$this->postTypeLabel} 검색",
			'not_found'          => "등록된 {$this->postTypeLabel}이 없습니다",
			'not_found_in_trash' => "휴지통에 {$this->postTypeLabel}이 없습니다",
			'parent_item_colon'  => "부모 {$this->postTypeLabel}:",
			'menu_name'          => "{$this->postTypeLabel}",
		);

		$args = [
			'labels'       => $labels,
			'public'       => true,
			'has_archive'  => true,
			'rewrite'      => [
				'slug'       => $this->postTypeRewriteSlug,
				'with_front' => false,
			],
			'supports'     => [ 'title', 'editor', 'author', 'thumbnail', 'custom-field', 'comments', 'revisions' ],
			'capabilities' => [
				'edit_post'          => "edit_{$this->postTypeKey}",
				'edit_posts'         => "edit_{$this->postTypeKey}" . "s", // s가 눈에 안 띌까봐 일부러 이렇게 씀.
				'edit_others_posts'  => "edit_other_{$this->postTypeKey}",
				'publish_posts'      => "publish_{$this->postTypeKey}",
				'read_post'          => "read_{$this->postTypeKey}",
				'read_private_posts' => "read_private_{$this->postTypeKey}",
				'delete_post'        => "delete_{$this->postTypeKey}",
			],
			'map_meta_cap' => true,
		];

		register_post_type( $this->postTypeKey, $args );
	}

	public function boardCapabilities() {
		$capabilities = [
			"edit_{$this->postTypeKey}",
			"edit_{$this->postTypeKey}" . "s", // s가 눈에 안 띌까봐 일부러 이렇게 씀.
			"edit_other_{$this->postTypeKey}",
			"publish_{$this->postTypeKey}",
			"read_{$this->postTypeKey}",
			"read_private_{$this->postTypeKey}",
			"delete_{$this->postTypeKey}",
		];

		$roles = [ 'administrator', 'editor' ];

		foreach ( $roles as $role ) {
			$wp_role = get_role( $role );
			foreach ( $capabilities as $capability ) {
				$wp_role->add_cap( $capability );
			}
		}
	}

	/**
	 * 게시판 없이 글을 저장하면, 기본 게시판에 넣는다. 단, 그러려면 기본 게시판 번호가 설정돼 있어야 한다.
	 *
	 * @param $post_id
	 */
	function defaultMytoryBoard( $post_id ) {
		if ( ! empty( $this->defaultBoardId ) and ! has_term( '', $this->taxonomyKey, $post_id ) ) {
			wp_set_object_terms( $post_id, $this->defaultBoardId, $this->taxonomyKey );
		}
	}

	function savePost( $post_id, $post, $is_update ) {
		remove_action( "save_post_{$this->postTypeKey}", array( $this, 'savePost' ) );
		$post->post_name = $post->ID;
		wp_update_post( (array) $post );
		add_action( "save_post_{$this->postTypeKey}", array( $this, 'savePost' ), 10, 3 );

		if ( ! empty( $_POST['meta'] ) ) {
			foreach ( $_POST['meta'] as $k => $v ) {

				if ( mb_strcut( $k, 0, 13, 'utf-8' ) === "{$this->taxonomyKey}_" ) {
					update_post_meta( $post_id, $k, $v );
				} else {
					update_post_meta( $post_id, "{$this->taxonomyKey}_{$k}", $v );
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
                ajaxNonce: <?= json_encode( wp_create_nonce( "{$this->taxonomyKey}-ajax-nonce" ) ); ?>,
                wpDebug: <?= defined( WP_DEBUG ) ? WP_DEBUG : 'false' ?>
            };
        </script><?php
	}

	function increasePageview() {
		wp_verify_nonce( $_POST['nonce'], "{$this->taxonomyKey}-ajax-nonce" );
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
		return Helper::url( 'delete.php?writing_id=' . get_the_ID() . '&redirect_to=' . urlencode( $redirect_to ) );
	}
}


