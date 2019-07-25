# 워드프레스 게시판 플러그인

`composer.json` 예시
-------------------

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
    "mytory/wp-mytory-board": "^0.5"
  }
}
~~~

이렇게 한 뒤 `composer install` 실행

todo
----

- board 설정에 따라서 role을 더해 줘야 한다. role을 검사해서 없으면 더해주는 메뉴를 추가한다. 

