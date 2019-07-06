<?php
/**
 * @var WP_Term[] $terms
 * @var WP_User[] $applied_users
 * @var array[] $user_applied_list
 */
?>
<style>
    [v-cloak] {
        visibility: hidden;
    }
</style>

<div class="wrap" id="app">
    <h1>게시판 가입 신청자</h1>

    <p></p>

    <table v-if="appliedUsers.length" v-cloak class="wp-list-table widefat fixed striped">
        <thead>
        <tr>
            <th>이름</th>
            <th>현재</th>
            <th>신청</th>
            <th>이메일</th>
            <th>가입일</th>
        </tr>
        </thead>

        <tr v-for="user in appliedUsers">
            <td v-cloak>{{ user.data.display_name }}</td>
            <td v-cloak>
                <ul>
                    <li v-for="role in currentRoles(user)">
                        {{ role.name }}
                    </li>
                </ul>
            </td>
            <td v-cloak>
                <ul>
                   <li v-for="role in appliedRoles(user)">
                       <span>{{ role.name }}</span>
                       <button class="button" @click="approve(user, role)">승인</button>
                   </li>
                </ul>
            </td>
            <td v-cloak>{{ user.data.user_email }}</td>
            <td v-cloak>{{ user.data.user_registered.substr(0, 10) }}</td>
        </tr>
    </table>

    <p v-if="appliedUsers.length === 0" v-cloak>현재 가입 신청자가 없습니다.</p>

</div>

<?php if ( WP_DEBUG ) { ?>
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
<?php } else { ?>
    <script src="https://cdn.jsdelivr.net/npm/vue"></script>
<?php } ?>
<script src="https://cdn.jsdelivr.net/npm/lodash@4.17.11/lodash.min.js"></script>
<script
        src="https://code.jquery.com/jquery-3.4.1.min.js"
        integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@8"></script>
<!-- Optional: include a polyfill for ES6 Promises for IE11 and Android browser -->
<script src="https://cdn.jsdelivr.net/npm/promise-polyfill"></script>

<script>
    (function () {
        var appliedUsers = <?= json_encode( $applied_users ); ?>;
        var userAppliedList = <?= json_encode( $user_applied_list ); ?>;
        var roles = <?= json_encode( $roles ); ?>;
        var mytoryBoard = <?= json_encode( $this->mytory_board ) ?>

        new Vue({
            el: '#app',
            data: {
                'appliedUsers': appliedUsers,
                'userAppliedList': userAppliedList,
                'roles': roles,
                'mytoryBoard': mytoryBoard
            },
            methods: {
                currentRoles: function (user) {
                    var that = this;
                    var currentRoles = [];
                    _.each(_.values(user.roles), function (roleKey) {
                        currentRoles.push({
                            boardId: _.last(roleKey.split('-')),
                            key: roleKey,
                            name: that.roles[roleKey].name
                        });
                    });
                    return currentRoles;
                },
                appliedRoles: function (user) {
                    var that = this;
                    var appliedRoles = [];
                    _.forEach(this.userAppliedList[user.ID], function (boardId) {
                        var roleKey = that.mytoryBoard.taxonomyKey + '-writer-' + boardId;
                        appliedRoles.push({
                            boardId: boardId,
                            key: roleKey,
                            name: that.roles[roleKey].name
                        });
                    });
                    return appliedRoles;
                },
                approve: function (user, role) {
                    var that = this;
                    jQuery.post(window.ajaxurl, {
                        action: 'approve_member_' + this.mytoryBoard.taxonomyKey,
                        user_id: user.ID,
                        role_key: role.key,
                        board_id: role.boardId
                    }, function (data) {
                        if (data.result === 'success') {

                            // apply 제거.
                            that.userAppliedList[user.ID] = _.filter(that.userAppliedList[user.ID], function (boardId) {
                                return boardId !== role.boardId
                            });

                            // role 추가.
                            var index = _.findIndex(that.appliedUsers, function (appliedUser) {
                                return appliedUser.ID === user.ID;
                            });

                            that.appliedUsers[index].roles.push(role.key);

                            Swal.fire({
                                title: '승인했습니다.',
                                text: user.data.display_name + ' 님을 ' + role.name + '으로 승인했습니다.',
                                type: 'success'
                            });
                        } else {
                            Swal.fire({
                                title: '에러가 발생했습니다.',
                                text: data.message,
                                type: 'error'
                            });
                        }

                    }, 'json');
                }
            }
        });
    }())
</script>