# 워드프레스 게시판 플러그인

`composer.json` 예시
-------------------

repositories 항목 참조.

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


설치 후 해 줘야 할 것
-----------------

### 설치 스크립트

    php vendor/mytory/wp-mytory-board/install.php


### 제거

    php vendor/mytory/wp-mytory-board/uninstall.php


