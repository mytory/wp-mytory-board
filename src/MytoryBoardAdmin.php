<?php

namespace Mytory\Board;

/**
 * Created by PhpStorm.
 * User: mytory
 * Date: 10/30/16
 * Time: 1:41 PM
 */
class MytoryBoardAdmin {

	private $mytory_board;

	function __construct(MytoryBoard $mytory_board) {

		$this->mytory_board = $mytory_board;

		add_action( 'admin_menu', array( $this, 'addMenuPage' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'adminScripts' ) );
		add_action( "wp_ajax_{$this->mytory_board->postTypeKey}_search_post", array( $this, 'searchPost' ) );
	}

	function addMenuPage() {
		add_submenu_page(
			"edit.php?post_type={$this->mytory_board->postTypeKey}",
			'고정글',
			'고정글',
			'edit_others_posts',
			"sticky-posts",
			array( $this, 'stickyPosts' )
		);
	}

	function adminScripts() {
		$screen = get_current_screen();

		if ( $screen->id == "{$this->mytory_board->postTypeKey}_page_sticky-posts" ) {
			wp_enqueue_script( "{$this->mytory_board->taxonomyKey}-sticky-posts", Helper::url('sticky-posts.js'),
				array( 'jquery-ui-autocomplete', 'underscore' ), false, true );
		}
	}

	function stickyPosts() {
		$result_message = "";
		if ( ! empty( $_POST ) ) {
			wp_verify_nonce( $_POST['_wpnonce'], "{$this->mytory_board->taxonomyKey}-sticky-posts" );
			$diff = array_diff( get_option( 'sticky_posts' ), explode( ',', $_POST['sticky_posts'] ) );
			if ( update_option( 'sticky_posts', explode( ',', $_POST['sticky_posts'] ) ) ) {
				$result_message = '저장했습니다.';
			} elseif ( empty( $diff ) ) {
				$result_message = '추가/제거한 글이 없어서 저장하지 않았습니다.';
			} else {
				$result_message = '저장중 오류가 있었습니다.';
			}
		}
		include __DIR__ . '/sticky-posts.php';
	}

	function searchPost() {
		global $wp_query;
		$args = array(
			'post_type'      => 'any',
			's'              => $_GET['term'],
			'posts_per_page' => 50,
		);
		if ( ! empty( $_GET['selected'] ) ) {
			$args['post__not_in'] = explode( ',', $_GET['selected'] );
		}
		$wp_query = new \WP_Query( $args );

		$posts_for_autocomplete = array();
		while ( have_posts() ): the_post();
			$posts_for_autocomplete[] = array(
				'id'    => get_the_ID(),
				'value' => get_the_title() . ' (' . get_the_date() . ')',
			);
		endwhile;
		if ( empty( $posts_for_autocomplete ) ) {
			$posts_for_autocomplete = array(
				array(
					'id'    => '',
					'value' => '검색 결과가 없습니다.'
				)
			);
		}
		echo json_encode( $posts_for_autocomplete );
		wp_die();
	}
}