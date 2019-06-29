<?php
include '../../../wp-blog-header.php';
http_response_code(200);

$post_id = (int)$_POST['ID'];
if ($post_id) {
    $post = get_post($post_id);

    // 로그인한 사용자가 작성한 글의 수정 권한 검사
    if ($post->post_author != 0 and $post->post_author != get_current_user_id()) {
        die('자기 글만 수정할 수 있습니다.');
    }

    // 로그인하지 않은 사용자가 작성한 글의 수정 권한 검사
    if ($post->post_author == 0) {
        $anonymous_password = get_post_meta($post_id, 'anonymous_password', true);
        if (sha1($_POST['anonymous_password']) != $anonymous_password) {
            die('비밀번호가 틀립니다.');
        }
    }

}

$post_title = trim($_POST['post_title']);
if (empty($post_title)) {
    die('제목을 입력하세요.');
}

if ( ! is_user_logged_in() and empty($_POST['custom_author'])) {
    die('이름을 입력하세요.');
}

if ( ! is_user_logged_in() and empty($_POST['anonymous_password'])) {
    die('비밀번호를 입력하세요.');
}

$post_data = array(
    'ID'           => $post_id,
    'post_title'   => $post_title,
    'post_content' => $_POST['post_content'],
    'author'       => (is_user_logged_in()) ? get_current_user_id() : 0,
    'post_type'    => 'mytory_board_post',
    'post_status'  => $_POST['post_status'],
    'ping_status'  => false,
);

if (strstr($post_data['post_content'], 'tpr0808')
    or strstr($post_data['post_title'], 'tpr0808')
) {
    ?>
    <script>
        alert('저장했습니다. 관리자 검토후 게시합니다.');
        location.href = '/kt/b';
    </script>
    <?php
    exit;
}

if ( ! empty($_POST['name'])) {
    // name 필드는 봇용 함정(honeypot)이다. js를 켜고 들어오면 아무 값도 들어가지 않는다. 따라서 값이 있으면 js를 끄고 들어온 봇이다.
    $post_data['post_status'] = 'private';
}


if ( ! $post_id) {
    $post_id = wp_insert_post($post_data);
} else {
    $post_id = wp_update_post($post_data);
}

if ( ! empty($_POST['name'])) {
    // name 필드는 봇용 함정(honeypot)이다. js를 켜고 들어오면 아무 값도 들어가지 않는다. 따라서 값이 있으면 js를 끄고 들어온 봇이다.
    die('저장했습니다. 글은 승인 후 공개합니다.');
}

if ($MytoryBoard->canSetNameByPost and ! empty($_POST['custom_author'])) {
    update_post_meta($post_id, 'custom_author', $_POST['custom_author']);
}
if ($MytoryBoard->canSetNameByPost and empty($_POST['custom_author'])) {
    update_post_meta($post_id, 'custom_author', '');
}

if ( ! is_user_logged_in()) {
    update_post_meta($post_id, 'anonymous_password', sha1($_POST['anonymous_password']));
}

wp_redirect(get_permalink($post_id));