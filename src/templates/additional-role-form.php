<?php
/**
 * @var WP_User $wp_user
 */
?>
<h2>지회 설정</h2>
<table class="form-table">
    <tr>
        <th>
            지회
        </th>
        <td>
			<?php
			global $wp_roles;

			$max_length_branch_name = max( array_map( function ( $role ) {
				return mb_strlen( $role['name'] );
			}, $wp_roles->roles ) );

			$branch_roles = [];

			foreach ( $wp_roles->roles as $role_key => $role ) {
				if ( ! strstr( $role_key, $this->mytory_board->taxonomyKey ) ) {
					continue;
				}
				$branch_roles[] = [
					'name' => $role['name'],
					'key'  => $role_key,
				];
			}

			usort( $branch_roles, function ( $a, $b ) {
				return $a['name'] > $b['name'];
			} );

			foreach ( $branch_roles as $branch_role ) {
				?>
                <label style="white-space: nowrap; margin-bottom: .5em; display: inline-block; width: <?= $max_length_branch_name * 0.9 ?>em">
                    <input type="checkbox" name="branch_role_key[]" value="<?= $branch_role['key'] ?>"
						<?= in_array( $branch_role['key'], $wp_user->roles ) ? 'checked' : '' ?>>
					<?php if ( in_array( $branch_role['key'], $wp_user->roles ) ) { ?>
                        <strong style="color: #0073aa;"><?= $branch_role['name'] ?></strong>
					<?php } else { ?>
						<?= $branch_role['name'] ?>
					<?php } ?>
                </label>
			<?php } ?>
        </td>
    </tr>
</table>