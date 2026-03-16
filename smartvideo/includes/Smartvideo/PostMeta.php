<?php

namespace Swarmify\Smartvideo;

/**
 * Registers the per-page SmartVideo disable toggle.
 *
 * Provides a meta box in the classic editor and exposes the meta field
 * to the REST API so the Gutenberg sidebar panel can read/write it.
 */
class PostMeta {

	/**
	 * Meta key used to disable SmartVideo on individual posts/pages.
	 */
	const META_KEY = '_smartvideo_disabled';

	/**
	 * Register the post meta for REST API visibility.
	 */
	public function register_meta() {
		$post_types = get_post_types( array( 'public' => true ) );

		foreach ( $post_types as $post_type ) {
			register_post_meta(
				$post_type,
				self::META_KEY,
				array(
					'show_in_rest'  => true,
					'single'        => true,
					'type'          => 'boolean',
					'default'       => false,
					'auth_callback' => function ( $allowed, $meta_key, $object_id ) {
						return current_user_can( 'edit_post', $object_id );
					},
				)
			);
		}
	}

	/**
	 * Add the classic editor meta box.
	 */
	public function add_meta_box() {
		$post_types = get_post_types( array( 'public' => true ) );

		add_meta_box(
			'smartvideo_disable',
			__( 'SmartVideo', 'swarmify' ),
			array( $this, 'render_meta_box' ),
			$post_types,
			'side',
			'default'
		);
	}

	/**
	 * Render the classic editor meta box.
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public function render_meta_box( $post ) {
		$disabled = (bool) get_post_meta( $post->ID, self::META_KEY, true );
		wp_nonce_field( 'smartvideo_disable_nonce', 'smartvideo_disable_nonce' );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::META_KEY ); ?>" value="1" <?php checked( $disabled ); ?> />
			<?php esc_html_e( 'Disable SmartVideo on this page', 'swarmify' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Prevents the SmartVideo player script from loading on this page.', 'swarmify' ); ?></p>
		<?php
	}

	/**
	 * Save the meta box value (classic editor).
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['smartvideo_disable_nonce'] ) ||
			 ! wp_verify_nonce( $_POST['smartvideo_disable_nonce'], 'smartvideo_disable_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$disabled = ! empty( $_POST[ self::META_KEY ] );
		update_post_meta( $post_id, self::META_KEY, $disabled );
	}

	/**
	 * Check if SmartVideo is disabled for a given post.
	 *
	 * @param int|null $post_id Post ID (defaults to current post).
	 * @return bool
	 */
	public static function is_disabled( $post_id = null ) {
		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}
		if ( ! $post_id ) {
			return false;
		}
		return (bool) get_post_meta( $post_id, self::META_KEY, true );
	}
}
