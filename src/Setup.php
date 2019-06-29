<?php

namespace Mytory\Board;

class Setup {
	public $mytoryBoard;

	public function __construct( MytoryBoard $mytoryBoard ) {
		$this->mytoryBoard = $mytoryBoard;
	}

	public function flushRewriteRules() {
		$this->mytoryBoard->registerMytoryBoard();
		$this->mytoryBoard->registerMytoryBoardPost();
		flush_rewrite_rules();
	}

	public function addRole() {
		add_role(
			'board_writer',
			'게시판 글쓴이',
			array(
				'read'         => true,
				'upload_files' => true,
			)
		);
	}

	public function removeRole() {
		remove_role( 'board_writer' );
	}

	public function createDefaultBoard() {
		$result = wp_create_term( "기본", 'mytory_board' );

		if ( is_wp_error( $result ) ) {
			die( "기본 게시판 생성 중 에러: {$result->get_error_message()}" );
		}
	}
}