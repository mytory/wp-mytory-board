<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>삭제 비밀번호</title>
    <style>
        body {
            font-family: 'Apple SD Gothic Neo', 'malgun gothic', 'nanumgothic', sans-serif;
        }
        .form {
            width: 100%;
            text-align: center;
            line-height: 1.7;
        }
    </style>
</head>
<body>
<form class="form" action="delete.php" method="post">
    <h1>게시글 삭제</h1>
    <input type="hidden" name="writing_id" value="<?= esc_attr($_REQUEST['writing_id']) ?>">
    <input type="hidden" name="redirect_to" value="<?= esc_attr($_REQUEST['redirect_to']) ?>">
    <label for="password">비밀번호를 입력하세요.</label>
    <br>
    <input type="password" name="password" id="password" title="비밀번호">
    <br>
    <input type="submit" value="확인">
</form>
</body>
</html>