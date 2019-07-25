# 워드프레스 게시판 플러그인

프로젝트 `composer.json` 예시
---------------------------

자신의 프로젝트에 MytoryBoard를 추가하려고 하는 경우 composer에 MytoryBoard의 repository를 추가하고, 이후에 require한다.

composer.json을 자신의 테마 루트에 만들고 아래를 참고해서 만든다.

아래 예시에서 repositories 항목을 눈여겨 봐야 한다.

~~~ json
{
  "name": "my/project",
  "type": "project",
  "authors": [
    {
      "name": "an, hyeong-woo",
      "email": "mytory@gmail.com"
    }
  ],
  "repositories": [
    {
      "type": "git",
      "url": "https://github.com/mytory/wp-mytory-board.git"
    }
  ],
  "require": {
    "mytory/wp-mytory-board": "^0.9"
  }
}
~~~

이렇게 한 뒤 `composer install` 실행

Usage
-----

아래처럼 하면 일반 게시판이 하나(`$mytoryBoard`), 회원용 게시판이 하나(`$memberBoard`) 생긴다.

~~~ php
// functions.php
require 'vendor/autoload.php';  // 경로는 환경에 맞게 수정

$mytoryBoard = new \Mytory\Board\MytoryBoard();
$memberBoard = new \Mytory\Board\MytoryBoard( [
	'taxonomyKey'         => 'member_board',
	'taxonomyLabel'       => '회원용 게시판',
	'taxonomyRewriteSlug' => 'member',
	'postTypeKey'         => 'member_board_post',
	'postTypeLabel'       => '회원 게시글',
	'postTypeRewriteSlug' => 'mp',
	'roleByBoard'         => true
] );
~~~


