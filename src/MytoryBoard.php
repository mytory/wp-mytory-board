<?php

namespace Mytory\Board;

use WP_Term_Query;

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

	/**
	 * 게시판별로 권한 관리를 할지 결정.
	 * 클라이언트단을 제어해 주지는 않는다. 관리자단 제어용 권한이다.
	 * 클라이언트단에서는 각자 알아서 코딩해야 한다.
	 * @var bool
	 */
	public $roleByBoard = false;

	/**
	 * @var array roleByBaord가 true인 경우 전체 공개 게시판의 슬러그를 적는다.
	 */
	public $publicBoardSlugs = [ 'public' ];


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
	 * @type boolean $roleByBoard : 게시판별로 권한을 지정할 수 있게 함. Default false
	 * @type array $publicBoardSlugs : roleByBaord가 true인 경우 전체 공개 게시판의 슬러그를 적는다. Default ['public']
	 * }
	 */
	function __construct( $config = [] ) {
		$this->setConfig( $config );

		add_action( 'init', [ $this, 'registerPostType' ] );
		add_action( 'admin_init', [ $this, 'boardCapabilities' ] );
		add_action( 'init', [ $this, 'registerBoardTaxonomy' ] );
		add_action( 'admin_menu', [ $this, 'addSubMenu' ] );

		add_action( "save_post_{$this->postTypeKey}", [ $this, 'updateMeta' ], 10, 3 );
		add_action( "save_post_{$this->postTypeKey}", [ $this, 'slugToId' ], 10, 3 );

		add_action( 'wp_head', [ $this, 'globalJsVariable' ] );
		add_action( "wp_ajax_{$this->taxonomyKey}_increase_pageview", [ $this, 'increasePageView' ] );
		add_action( "wp_ajax_nopriv_{$this->taxonomyKey}_increase_pageview", [ $this, 'increasePageView' ] );
		add_action( 'pre_get_posts', [ $this, 'onlyMyMedia' ] );

		if ( $this->roleByBoard ) {

			// 게시판을 만들 때마다 해당 게시판의 권한을 정의하는 role을 만듦.
			add_action( "create_{$this->taxonomyKey}", [ $this, 'addRole' ], 10, 2 );

			// 게시판을 수정하면 role의 이름을 수정함.
			add_action( "edit_{$this->taxonomyKey}", [ $this, 'updateRole' ], 10, 2 );

			// 게시판을 삭제하면 role도 삭제함.
			add_action( "delete_{$this->taxonomyKey}", [ $this, 'removeRole' ], 10, 4 );

			// 글쓸 때 사용자의 role에 따라 글의 게시판을 정함.
			// 워드프레스는 게시판별 권한 모델을 갖고 있지 않기 때문에, 일반 사용자는 그냥 게시판을 선택할 수 없게 하는 수밖에 없음.
			add_action( "save_post_{$this->postTypeKey}", [ $this, 'addBoardTermToPost' ], 10, 3 );

			add_action( 'pre_get_posts', [ $this, 'onlyMyBoardPost' ] );
		} else {
			// 게시판별로 롤을 관리하는 게 아닌 경우에.

			// taxonomyLabel 글쓴이 Role을 추가함.
			$this->addDefaultRole();

			// 게시글의 기본 게시판을 정함.
			add_action( "publish_{$this->postTypeKey}", [ $this, 'defaultMytoryBoard' ] );
		}

		new MytoryBoardAdmin( $this );
	}

	private function setConfig( $config ) {
		$this->defaultBoardId      = $config['defaultBoardId'] ?? $this->defaultBoardId;
		$this->canAnonymousWriting = $config['canAnonymousWriting'] ?? $this->canAnonymousWriting;
		$this->canSetNameByPost    = $config['canSetNameByPost'] ?? $this->canSetNameByPost;
		$this->roleByBoard         = $config['roleByBoard'] ?? $this->roleByBoard;
		$this->publicBoardSlugs    = $config['publicBoardSlugs'] ?? $this->publicBoardSlugs;

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
		$labels = [
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
		];

		$args = [
			'labels'            => $labels,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'rewrite'           => [
				'slug'       => $this->taxonomyRewriteSlug,
				'with_front' => false,
			],
		];

		register_taxonomy( $this->taxonomyKey, $this->postTypeKey, $args );
	}

	function registerPostType() {
		$labels = [
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
		];

		$args = [
			'labels'       => $labels,
			'public'       => true,
			'has_archive'  => true,
			'rewrite'      => [
				'slug'       => $this->postTypeRewriteSlug,
				'with_front' => false,
			],
			'show_ui'      => true,
			'supports'     => [ 'title', 'editor', 'author', 'thumbnail', 'custom-field', 'comments', 'revisions' ],
			'capabilities' => [
				'edit_post'              => "edit_{$this->postTypeKey}",
				'read_post'              => "read_{$this->postTypeKey}",
				'delete_post'            => "delete_{$this->postTypeKey}",
				'delete_published_posts' => "delete_published_{$this->postTypeKey}" . "s", // s가 눈에 안 띌까봐 일부러 이렇게 씀.
				'edit_posts'             => "edit_{$this->postTypeKey}" . "s", // s가 눈에 안 띌까봐 일부러 이렇게 씀.
				'edit_published_posts'   => "edit_published_{$this->postTypeKey}" . "s", // s가 눈에 안 띌까봐 일부러 이렇게 씀.
				'edit_others_posts'      => "edit_other_{$this->postTypeKey}",
				'publish_posts'          => "publish_{$this->postTypeKey}",
				'read_private_posts'     => "read_private_{$this->postTypeKey}",
			],
			'map_meta_cap' => true,
		];

		register_post_type( $this->postTypeKey, $args );
	}

	public function boardCapabilities() {
		$capabilities = [
			"edit_{$this->postTypeKey}",
			"read_{$this->postTypeKey}",
			"delete_{$this->postTypeKey}",
			"delete_published_{$this->postTypeKey}" . "s", // s가 눈에 안 띌까봐 일부러 이렇게 씀.
			"edit_{$this->postTypeKey}" . "s", // s가 눈에 안 띌까봐 일부러 이렇게 씀.
			"edit_published_{$this->postTypeKey}" . "s", // s가 눈에 안 띌까봐 일부러 이렇게 씀.
			"edit_other_{$this->postTypeKey}",
			"publish_{$this->postTypeKey}",
			"read_private_{$this->postTypeKey}",
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
	 * todo 게시판을 지정하고 저장하면 어떻게 할지 정해야 한다. 예컨대 tax_input 값이 들어온다면.
	 *
	 * @param $post_id
	 */
	function defaultMytoryBoard( $post_id ) {
		if ( ! empty( $this->defaultBoardId ) and ! has_term( '', $this->taxonomyKey, $post_id ) ) {
			wp_set_object_terms( $post_id, $this->defaultBoardId, $this->taxonomyKey );
		}
	}

	function slugToId( $post_id, \WP_Post $post, $is_update ) {
		remove_action( "save_post_{$this->postTypeKey}", [ $this, 'slugToId' ] );
		$post->post_name = $post->ID;
		wp_update_post( $post );
		add_action( "save_post_{$this->postTypeKey}", [ $this, 'slugToId' ], 10, 3 );
	}

	/**
	 * <code><input name="meta[_mytory_board_custom_key]"></code>로 값을 넘기면 postmeta에 저장한다.
	 * <code>mytory_board</code>는 당연히 <code>$taxonomyKey</code>로 대체해야 한다.
	 *
	 * @param $post_id
	 * @param \WP_Post $post
	 * @param $is_update
	 */
	function updateMeta( $post_id, \WP_Post $post, $is_update ) {
		if ( ! empty( $_POST['meta'] ) ) {
			foreach ( $_POST['meta'] as $k => $v ) {
				if ( strpos( $k, "_{$this->taxonomyKey}_" ) === 0 ) {
					update_post_meta( $post_id, $k, $v );
				}
			}
		}
	}

	/**
	 * 워드프레스의 기본 권한 모델로는 저장할 때 글을 특정 board에만 넣게 할 수가 없다.
	 * 그래서 hook을 걸게 했다.
	 *
	 * @param $post_id
	 * @param \WP_Post $post
	 * @param $is_update
	 */
	function addBoardTermToPost( $post_id, \WP_Post $post, $is_update ) {
		if ( ! empty( $_POST['tax_input'][ $this->taxonomyKey ] ) ) {
			// 게시판 값이 입력돼 들어오면 그냥 넘긴다.
			return;
		}

		if ( current_user_can( "manage_categories" ) ) {
			// 게시판 값을 넣을 수 있는 사용자라면 그냥 넘긴다.
			return;
		}

		$wp_user = wp_get_current_user();

		foreach ( $wp_user->roles as $role ) {
			if ( strpos( $role, "{$this->taxonomyKey}-writer-" ) === 0 ) {
				$term_id = (int) str_replace( "{$this->taxonomyKey}-writer-", '', $role );
				wp_add_object_terms( $post_id, $term_id, $this->taxonomyKey );

				// 맨 앞의 게시판에 넣고 끊는다.
				break;
			}
		}
	}

	function globalJsVariable() {
		global $wp_query;
		?>
        <script type="text/javascript">
            window['<?= $this->taxonomyKey ?>'] = {
                ajaxUrl: <?= json_encode( admin_url( "admin-ajax.php" ) ); ?>,
                ajaxNonce: <?= json_encode( wp_create_nonce( "{$this->taxonomyKey}-ajax-nonce" ) ); ?>,
                wpDebug: <?= defined( WP_DEBUG ) ? WP_DEBUG : 'false' ?>
            };
        </script><?php
	}

	function increasePageView() {
		wp_verify_nonce( $_POST['nonce'], "{$this->taxonomyKey}-ajax-nonce" );
		$post_id   = $_POST['post_id'];
		$new_value = get_post_meta( $post_id, "_{$this->taxonomyKey}_pageview", true ) + 1;
		if ( $result = update_post_meta( $post_id, "_{$this->taxonomyKey}_pageview", $new_value ) ) {
			echo json_encode( [ 'result' => 1, 'parameters' => $_POST ] );
		} else {
			echo json_encode( [ 'result' => $result, 'parameters' => $_POST ] );
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
		$query_string['writing_id'] = get_the_ID();
		$edit_link                  = "{$parsed['scheme']}://{$parsed['host']}{$parsed['path']}?" . http_build_query( $query_string );

		return $edit_link;
	}

	public function getDeleteLink( $redirect_to ) {
		return Helper::url( 'delete.php?writing_id=' . get_the_ID() . '&redirect_to=' . urlencode( $redirect_to ) );
	}

	/**
	 * 게시판을 추가할 때 role도 만든다.
	 *
	 * @param $term_id
	 * @param $tt_id
	 */
	public function addRole( $term_id, $tt_id ) {
		$term = get_term( $term_id );

		if ( in_array( $term->slug, $this->publicBoardSlugs ) ) {
			return;
		}

		add_role( "{$this->taxonomyKey}-writer-{$term_id}", "{$term->name} 회원", [
			'read'                                        => true,
			'upload_files'                                => true,
			"edit_{$this->postTypeKey}"                   => true,
			"edit_{$this->postTypeKey}" . "s"             => true, // s가 눈에 안 띌까봐 일부러 이렇게 씀.
			"edit_published_{$this->postTypeKey}" . "s"   => true, // s가 눈에 안 띌까봐 일부러 이렇게 씀.
			"publish_{$this->postTypeKey}"                => true,
			"read_{$this->postTypeKey}"                   => true,
			"delete_{$this->postTypeKey}"                 => true,
			"delete_published_{$this->postTypeKey}" . "s" => true, // s가 눈에 안 띌까봐 일부러 이렇게 씀.
		] );
		add_role( "{$this->taxonomyKey}-editor-{$term_id}", "{$term->name} 편집자", get_role( 'editor' )->capabilities );
	}

	/**
	 * 게시판 이름이 변경되면 role 이름도 변경된다.
	 *
	 * @param $term_id
	 * @param $tt_id
	 */
	public function updateRole( $term_id, $tt_id ) {

		$term = get_term( $term_id );
		if ( in_array( $term->slug, $this->publicBoardSlugs ) ) {
			return;
		}

		remove_role( "{$this->taxonomyKey}-writer-{$term_id}" );
		remove_role( "{$this->taxonomyKey}-editor-{$term_id}" );
		$this->addRole( $term_id, $tt_id );
	}

	/**
	 * @param int $term_id
	 * @param int $tt_id
	 * @param \WP_Term|\WP_Error $deleted_term
	 * @param array $object_ids
	 */
	public function removeRole( int $term_id, int $tt_id, $deleted_term, array $object_ids ) {
		$term = get_term( $term_id );
		if ( in_array( $term->slug, $this->publicBoardSlugs ) ) {
			return;
		}

		remove_role( "{$this->taxonomyKey}-writer-{$term_id}" );
		remove_role( "{$this->taxonomyKey}-editor-{$term_id}" );
	}

	private function addDefaultRole() {
		if ( ! get_role( "{$this->taxonomyKey}_writer" ) ) {
			add_role(
				"{$this->taxonomyKey}_writer",
				"{$this->taxonomyLabel} 글쓴이",
				[
					'read'                                      => true,
					'upload_files'                              => true,
					"edit_{$this->postTypeKey}"                 => true,
					"edit_{$this->postTypeKey}" . "s"           => true, // s가 눈에 안 띌까봐 일부러 이렇게 씀.
					"publish_{$this->postTypeKey}"              => true,
					"edit_published_{$this->postTypeKey}" . "s" => true, // s가 눈에 안 띌까봐 일부러 이렇게 씀.
					"read_{$this->postTypeKey}"                 => true,
					"delete_{$this->postTypeKey}"               => true,
				]
			);
		}
	}

	public function onlyMyMedia( $wp_query_obj ) {

		global $current_user, $pagenow;

		$is_attachment_request = ( $wp_query_obj->get( 'post_type' ) == 'attachment' );

		if ( ! $is_attachment_request ) {
			return;
		}

		if ( ! is_a( $current_user, 'WP_User' ) ) {
			return;
		}

		if ( ! in_array( $pagenow, [ 'upload.php', 'admin-ajax.php' ] ) ) {
			return;
		}

		if ( ! current_user_can( 'delete_pages' ) ) {
			$wp_query_obj->set( 'author', $current_user->ID );
		}

		return;
	}

	/**
	 * 게시판별로 권할 관리를 하게 됐다면, 자기가 권한을 가진 게시판의 글만 봐야 한다.
	 *
	 * @param $wp_query_obj
	 */
	public function onlyMyBoardPost( $wp_query_obj ) {
		global $current_user, $pagenow;

		$is_borad_post_request = ( $wp_query_obj->get( 'post_type' ) === $this->postTypeKey );

		if ( ! $is_borad_post_request ) {
			return;
		}

		if ( ! is_a( $current_user, 'WP_User' ) ) {
			return;
		}

		if ( ! current_user_can( 'delete_pages' ) ) {
			// 관리자 권한이 없다면

			$boardSlugsCanRead = array_merge( array_map( function ( $term ) {
				return $term->slug;
			}, $this->getMyBoards() ), $this->publicBoardSlugs );

			$wp_query_obj->set( 'tax_query', [
				[
					'taxonomy' => $this->taxonomyKey,
					'field'    => 'slug',
					'terms'    => $boardSlugsCanRead,
				]
			] );
		}
	}

	/**
	 * 게시판별로 권한을 부여하는 옵션을 활성화한 상태라면 자신이 접근 가능한 게시판의 term 배열을 리턴한다.
	 * 그렇지 않다면 전체 게시판의 term 배열을 리턴한다.
	 *
	 * @param bool $include_public_boards : 전체 공개 게시판 포함 여부
	 *
	 * @return \WP_Term[]
	 */
	public function getMyBoards( $include_public_boards = false ) {
		$wp_user = wp_get_current_user();

		if ( array_intersect( $wp_user->roles, [ 'administrator', 'editor' ] ) or ! $this->roleByBoard ) {
			// 편집자 이상 혹은, 게시판별 권한 관리를 하지 않는다면 전체 공개 게시판을 제외한 나머지 전체 게시판을 리턴한다.
			$args = [
				'taxonomy'   => $this->taxonomyKey,
				'hide_empty' => false,
				'number'     => 0,
			];

			if ( ! $include_public_boards ) {
				$args['exclude'] = array_map( function ( $slug ) {
					$term = get_term_by( 'slug', $slug, $this->taxonomyKey );
					if ( $term ) {
						return $term->term_id;
					}

					return null;
				}, $this->publicBoardSlugs );
			}

			return ( new WP_Term_Query( $args ) )->terms;
		}

		if ( $this->roleByBoard ) {

			$boards = [];
			foreach ( $wp_user->roles as $role ) {
				if ( strpos( $role, "{$this->taxonomyKey}-" ) === 0 ) {
					$term_id  = preg_replace( "/{$this->taxonomyKey}-(writer|editor)-/", '', $role );
					$boards[] = get_term( $term_id );
				}
			}

			if ( $include_public_boards ) {
				foreach ( $this->publicBoardSlugs as $public_board_slug ) {
					$boards[] = get_term_by( 'slug', $public_board_slug );
				}
			}

			return $boards;
		}
	}
}


