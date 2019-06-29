<?php
include '../../../wp-blog-header.php';
http_response_code(200);

$post_id = (int)$_REQUEST['writing_id'];
$post    = get_post($post_id);

if ($post->post_author > 0) {
    if ($post->post_author == get_current_user_id()) {
        wp_delete_post($post_id);
        if (filter_input(INPUT_GET, 'redirect_to')) {
            $location = filter_input(INPUT_GET, 'redirect_to');
        } else {
            $location = home_url();
        }
        header("location: " . $location);
        exit;
    }
    die('Invalid!');
}

if ($post->post_author == 0 and empty($_REQUEST['password'])) {
    // 익명글이고 아직 비밀번호 입력한 게 아니라면 비밀번호 입력폼
    include 'delete-password-form.php';
    exit;
}

if ($post->post_author == 0 and ! empty($_REQUEST['password'])) {
    $password_hash         = get_post_meta($post_id, 'anonymous_password', true);
    $password_confirm_hash = sha1($_REQUEST['password']);
    if ($password_hash == $password_confirm_hash) {
        wp_delete_post($post_id);
        if ($_REQUEST['redirect_to']) {
            $location = $_REQUEST['redirect_to'];
        } else {
            $location = home_url();
        }
        header("location: " . $location);
        exit;
    } else {
        ?>
        <script>
            alert('비밀번호가 틀립니다.');
            history.go(-2);
        </script>
        <?php
        exit;
    }
}