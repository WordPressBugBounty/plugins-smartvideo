<?php

namespace Swarmify\Smartvideo;

/**
 * Registers upload acceleration
 *
 * @link       https://swarmify.com/?smartvideo_wordpress_plugin
 * @since      2.1.0
 *
 * @package    Swarmify
 * @subpackage Swarmify/includes
 */

/**
 * Register upload acceleration for the plugin.
 *
 * Hooks into media upload subsytem to improve uploading of large
 * media files.
 *
 * @package    Swarmify
 */
class UploadAccelerator {

	/**
	 * Upper bound on the number of chunks a single upload can declare.
	 * Plupload normally chunks at half of `post_max_size`, so even a 100GB
	 * upload would be a few thousand chunks; 10k is a generous ceiling.
	 *
	 * @since 2.3.0
	 * @var int
	 */
	const MAX_CHUNKS = 10000;

	/**
	 * UploadAccelerator instance.
	 *
	 * @since 2.1.0
	 * @static
	 * @var UploadAccelerator
	 */
	private static $instance = false;

	/**
	 * Get the instance.
	 *
	 * Returns the current instance, creates one if it
	 * doesn't exist. Ensures only one instance of
	 * UploadAccelerator is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 *
	 * @return UploadAccelerator
	 */
	public static function get_instance() {

		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * Initializes and adds functions to filter and action hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Only enable if the option is turned on for it so that users with problems
		// can disable.
		if ( get_option( 'swarmify_toggle_uploadacceleration', 'on' ) === 'on' ) {
			add_filter( 'plupload_init', array( $this, 'filter_plupload_settings' ) );
			add_filter( 'upload_post_params', array( $this, 'filter_plupload_params' ) );
			add_filter( 'plupload_default_settings', array( $this, 'filter_plupload_settings' ) );
			add_filter( 'plupload_default_params', array( $this, 'filter_plupload_params' ) );
			add_action( 'wp_ajax_swarmify_upload_accelerator', array( $this, 'ajax_chunk_receiver' ) ); // WP auto-prefixes it in admin-ajax.php
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_upload_id_script' ) );

			// Hourly GC for accumulator files left by aborted/disconnected uploads.
			add_action( 'swarmify_cleanup_chunks', array( $this, 'cleanup_stale_chunks' ) );
			if ( ! wp_next_scheduled( 'swarmify_cleanup_chunks' ) ) {
				if ( false === wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'swarmify_cleanup_chunks' ) ) {
					$this->log_debug( 'SmartVideo Upload: Failed to schedule swarmify_cleanup_chunks. Stale .part files will accumulate.' );
				}
			}

			// This is used by other forms and confuses them. But it gets ignored
			// for media uploads as we set a custom limit in 'filter_plupload_settings'
			// add_filter( 'upload_size_limit', array( $this, 'filter_upload_size_limit' ) );
		}
	}

	/**
	 * Filter plupload params.
	 *
	 * @since 1.2.0
	 */
	public function filter_plupload_params( $plupload_params ) {

		$plupload_params['action'] = 'swarmify_upload_accelerator';
		return $plupload_params;
	}

	/**
	 * Enqueue inline JS that injects a per-file upload ID into plupload's
	 * multipart params. Plupload generates a unique `file.id` for each file
	 * added to the queue, but does not POST it by default. This hook adds it
	 * as `swarmify_upload_id` so the server can disambiguate concurrent uploads
	 * of the same filename by the same user.
	 *
	 * @since 2.3.0
	 */
	public function enqueue_upload_id_script() {
		// Wrap the plupload.Uploader constructor so every instance — whether
		// created by wp.Uploader (modal media library) or directly by WP core's
		// plupload-handlers.js (wp-admin/media-new.php) — gets a BeforeUpload
		// binding that sends file.id as swarmify_upload_id.
		//
		// wp_add_inline_script silently no-ops on un-enqueued handles, so
		// attaching to both 'wp-plupload' and 'plupload-handlers' is safe.
		$js = <<<'JS'
(function(){
if(typeof plupload==="undefined"||!plupload.Uploader||plupload.Uploader.__svPatched)return;
plupload.Uploader.__svPatched=true;
var Orig=plupload.Uploader;
plupload.Uploader=function(opts){
	var inst=new Orig(opts);
	inst.bind("BeforeUpload",function(up,file){
		up.settings.multipart_params=up.settings.multipart_params||{};
		up.settings.multipart_params.swarmify_upload_id=file.id;
	});
	return inst;
};
plupload.Uploader.prototype=Orig.prototype;
}());
JS;

		wp_add_inline_script( 'wp-plupload', $js );
		wp_add_inline_script( 'plupload-handlers', $js );
	}

	/**
	 * Filter plupload settings.
	 *
	 * @since 1.0.0
	 */
	public function filter_plupload_settings( $plupload_settings ) {
		$chunk_size = $this->get_chunk_size( '' );
		$retries    = 7;

		$plupload_settings['url']                      = admin_url( 'admin-ajax.php' );
		$plupload_settings['filters']['max_file_size'] = $this->filter_upload_size_limit( '' ) . 'b';
		$plupload_settings['chunk_size']               = $chunk_size . 'b';
		$plupload_settings['max_retries']              = $retries;
		return $plupload_settings;
	}

	/**
	 * Return the maximum upload size.
	 *
	 * Free space of temp directory.
	 *
	 * @since 1.0.0
	 *
	 * @return float $bytes Free disk space in bytes.
	 */
	public function filter_upload_size_limit( $unused ) {

		// Check whether the `disk_free_space` function is disabled
		$disabled          = ini_get( 'disable_functions' );
		$freeSpaceDisabled = $disabled && strpos( $disabled, 'disk_free_space' ) !== false;

		if ( $freeSpaceDisabled ) {
			$bytes = null;
		} else {
			$bytes = disk_free_space( sys_get_temp_dir() );
		}

		if ( false === $bytes || is_null( $bytes ) ) {
			$bytes = 5 * 1024 * 1024 * 1024;
		}
		return $bytes;
	}

	/**
	 * Return the chunk size to use
	 *
	 * Half of the `post_max_size`.
	 *
	 * @since 1.0.0
	 *
	 * @return int $bytes Chunk size for uploads
	 */
	public function get_chunk_size( $unused ) {

		$post_max = ini_get( 'post_max_size' );
		if ( false === $post_max || '' === $post_max ) {
			return 4 * 1024 * 1024; // 4MB default
		}

		$val = trim( $post_max );
		if ( '' === $val ) {
			return 4 * 1024 * 1024; // 4 MB default
		}
		$last = strtolower( $val[ strlen( $val ) - 1 ] );
		$val  = intval( $val );
		switch ( $last ) {
			// The 'G' modifier is available since PHP 5.1.0
			case 'g':
				$val *= 1024; // Fall-through
			case 'm':
				$val *= 1024; // Fall-through
			case 'k':
				$val *= 1024;
		}

		// Use half of the `post_max_size` as a safe chunk size value, minimum 4 MB.
		return max( intval( $val / 2 ), 4 * 1024 * 1024 );
	}

	/**
	 * Return the directory used to accumulate chunks during chunked uploads.
	 *
	 * Lives under WP_CONTENT_DIR (not in sys_get_temp_dir, which is typically
	 * world-writable on shared hosting and lets a co-tenant pre-plant a symlink
	 * at the predictable accumulator path). Created with restrictive perms,
	 * fronted by .htaccess + index.php to prevent direct access on Apache and
	 * to suppress directory listing on misconfigured nginx.
	 *
	 * @since 2.3.0
	 *
	 * @return string Absolute path to the chunks directory.
	 */
	private function get_chunks_dir() {
		foreach ( $this->get_chunks_dir_candidates() as $candidate ) {
			$dir = $this->prepare_chunks_dir( $candidate );
			if ( '' !== $dir ) {
				return $dir;
			}
		}
		return '';
	}

	/**
	 * Locations to try for the chunk accumulator, in order of preference.
	 *
	 * Not every host keeps wp-content writable — Pantheon, WP VIP and read-only
	 * container deploys ship the code directory immutable and leave only the
	 * uploads dir writable. Uploads is writable on any working install by
	 * definition, so it is a safe second choice. Neither is the OS temp dir,
	 * which is what this deliberately does not fall back to: a world-writable
	 * shared temp dir is what let a co-tenant pre-plant a symlink at the
	 * predictable accumulator path.
	 *
	 * @since 2.3.3
	 *
	 * @return string[] Absolute directory paths to try.
	 */
	private function get_chunks_dir_candidates() {
		$candidates = [ WP_CONTENT_DIR . '/.swarmify-chunks' ];

		$uploads = wp_get_upload_dir();
		if ( empty( $uploads['error'] ) && ! empty( $uploads['basedir'] ) ) {
			$candidates[] = $uploads['basedir'] . '/.swarmify-chunks';
		}

		return $candidates;
	}

	/**
	 * Create and harden one candidate chunks directory.
	 *
	 * @since 2.3.3
	 *
	 * @param string $dir Absolute path to prepare.
	 * @return string The path, or '' if it cannot be used.
	 */
	private function prepare_chunks_dir( $dir ) {
		// is_dir() follows symlinks, so a co-tenant who pre-plants a link here
		// would otherwise have the whole accumulator redirected into a directory
		// they control — the same attack the move off the OS temp dir closed.
		if ( is_link( $dir ) ) {
			$this->log_debug( 'SmartVideo Upload: Refusing symlinked chunks directory: ' . $dir );
			return '';
		}
		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			$this->log_debug( 'SmartVideo Upload: Failed to create chunks directory: ' . $dir );
			return '';
		}
		// Re-apply perms + guard files on every call: a directory pre-created by
		// an older plugin version (or anything else) never enters the mkdir
		// branch, so without this its 0755-or-laxer perms and missing guards
		// would silently persist.
		if ( ! @chmod( $dir, 0700 ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors -- Intentional: suppresses warnings on hostile shared-hosting paths; return value is checked.
			$this->log_debug( 'SmartVideo Upload: Failed to set 0700 on chunks directory: ' . $dir );
		}
		// The accumulator guards downstream assume no co-tenant can write here, so a
		// directory chmod could not lock down must be refused rather than trusted.
		clearstatcache( true, $dir );
		$perms = @fileperms( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- Intentional: suppresses warnings on hostile shared-hosting paths; return value is checked.
		if ( false === $perms || ( $perms & 0022 ) ) {
			$this->log_debug( 'SmartVideo Upload: Refusing group/world-writable chunks directory: ' . $dir );
			return '';
		}
		if ( ! file_exists( $dir . '/.htaccess' ) ) {
			if ( false === @file_put_contents( $dir . '/.htaccess', "Require all denied\n" ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors -- Intentional: suppresses warnings on hostile shared-hosting paths; return value is checked.
				$this->log_debug( 'SmartVideo Upload: Failed to write .htaccess guard to chunks directory: ' . $dir );
			}
		}
		if ( ! file_exists( $dir . '/index.php' ) ) {
			if ( false === @file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors -- Intentional: suppresses warnings on hostile shared-hosting paths; return value is checked.
				$this->log_debug( 'SmartVideo Upload: Failed to write index.php guard to chunks directory: ' . $dir );
			}
		}
		// @chmod above silently no-ops on a dir owned by another OS user (e.g. one
		// created by a prior root wp-cli upload), leaving it unwritable to the web
		// user. Detect that here so the caller returns a clean "service unavailable"
		// instead of failing on the first chunk write and reporting it as a
		// confusing "missing accumulator" on the next chunk.
		if ( ! is_writable( $dir ) ) {
			$this->log_debug( 'SmartVideo Upload: Chunks directory not writable by web user (ownership mismatch?): ' . $dir );
			return '';
		}
		return $dir;
	}

	/**
	 * Sweep accumulator files older than 24 hours.
	 *
	 * Bound to the `swarmify_cleanup_chunks` hourly cron event. A successful
	 * upload renames the .part file out of the chunks dir; anything left over
	 * is from a client that disconnected or never sent the final chunk.
	 *
	 * @since 2.3.0
	 */
	public function cleanup_stale_chunks() {
		// Sweeps every candidate, not just the one currently in use: a site whose
		// wp-content became writable again would otherwise strand whatever the
		// uploads-dir fallback left behind.
		foreach ( $this->get_chunks_dir_candidates() as $dir ) {
			$this->cleanup_stale_chunks_in( $dir );
		}
	}

	/**
	 * Sweep one accumulator directory.
	 *
	 * @since 2.3.3
	 *
	 * @param string $dir Absolute directory path.
	 * @return void
	 */
	private function cleanup_stale_chunks_in( $dir ) {
		// is_dir() follows symlinks, and this is the only path here that deletes.
		if ( ! is_dir( $dir ) || is_link( $dir ) ) {
			return;
		}
		$threshold = time() - DAY_IN_SECONDS;
		$matches   = glob( $dir . '/*.part' );
		if ( ! is_array( $matches ) ) {
			return;
		}
		foreach ( $matches as $file ) {
			if ( ! is_file( $file ) || is_link( $file ) ) {
				continue;
			}
			// Sibling .lock mtime reflects the last successfully appended chunk, so a paced upload isn't swept mid-upload.
			$lock = $file . '.lock';
			if ( file_exists( $lock ) ) {
				$lock_mtime = @filemtime( $lock ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- Intentional: suppresses warnings on hostile shared-hosting paths; return value is checked.
				if ( false !== $lock_mtime && $lock_mtime >= $threshold ) {
					continue;
				}
			}
			$mtime = @filemtime( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- Intentional: suppresses warnings on hostile shared-hosting paths; return value is checked.
			if ( false !== $mtime && $mtime < $threshold ) {
				wp_delete_file( $file );
				if ( file_exists( $lock ) ) {
					wp_delete_file( $lock );
				}
				if ( file_exists( $file . '.size' ) ) {
					wp_delete_file( $file . '.size' );
				}
			}
		}

		// The sidecar is written before the accumulator is opened, so a run that
		// bails in between leaves one with no .part sibling for the loop above to
		// sweep it as.
		$sidecars = glob( $dir . '/*.part.size' );
		if ( ! is_array( $sidecars ) ) {
			return;
		}
		foreach ( $sidecars as $sidecar ) {
			if ( ! is_file( $sidecar ) || is_link( $sidecar ) ) {
				continue;
			}
			if ( file_exists( substr( $sidecar, 0, -5 ) ) ) {
				continue;
			}
			$mtime = @filemtime( $sidecar ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- Intentional: suppresses warnings on hostile shared-hosting paths; return value is checked.
			if ( false !== $mtime && $mtime < $threshold ) {
				wp_delete_file( $sidecar );
			}
		}
	}

	/**
	 * Return a file's mime type.
	 *
	 * @since 1.2.0
	 *
	 * @param string $filename File name.
	 * @return var string $mimetype Mime type.
	 */
	public function get_mime_content_type( $filename ) {

		if ( function_exists( 'mime_content_type' ) ) {
			return mime_content_type( $filename );
		}

		if ( function_exists( 'finfo_open' ) ) {
			$finfo    = finfo_open( FILEINFO_MIME );
			$mimetype = finfo_file( $finfo, $filename );
			if ( is_resource( $finfo ) ) {
				// PHP 7.3/7.4: finfo_open returns a resource that must be freed.
				// On 8.0+ it returns an auto-GC'd finfo object, so this is skipped
				// (finfo_close is deprecated since 8.5).
				finfo_close( $finfo ); // phpcs:ignore Generic.PHP.DeprecatedFunctions.Deprecated -- only reached on PHP 7.3/7.4 where $finfo is a resource; guarded by is_resource() so it never runs (or warns) on 8.0+.
			}
			return $mimetype;
		}

		return 'application/octet-stream';
	}

	/**
	 * Write a debug message to the PHP error log, but only when WP_DEBUG is on.
	 *
	 * @since 2.3.0
	 *
	 * @param string $message Message to log.
	 */
	private function log_debug( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional diagnostic logging, gated behind WP_DEBUG.
			error_log( $message );
		}
	}

	/**
	 * Determine the chunk stride to seek by.
	 *
	 * The plupload settings filter advertises get_chunk_size() to the client, but
	 * it runs at the default priority, so any plugin filtering
	 * plupload_default_settings later wins and the browser slices at a size the
	 * server never sees.
	 * Seeking by the server's own figure then scatters chunks at the wrong
	 * offsets and silently produces a corrupt file.
	 *
	 * Every chunk but the last is exactly one stride long, so the received length
	 * is authoritative. The first one observed is recorded alongside the
	 * accumulator and reused for the short final chunk. A later chunk that
	 * disagrees means the stride changed mid-upload, which cannot be reconciled —
	 * fail loudly rather than write a corrupt file.
	 *
	 * @since 2.3.3
	 *
	 * @param string $accumPath Accumulator path; the record lives beside it.
	 * @param string $tempName  Uploaded chunk's temp path.
	 * @param int    $chunk     Zero-based chunk index.
	 * @param int    $chunks    Total chunks; 0 or 1 means unchunked.
	 * @return int Stride in bytes.
	 */
	private function resolve_chunk_size( $accumPath, $tempName, $chunk, $chunks ) {
		if ( $chunks <= 1 ) {
			return $this->get_chunk_size( '' );
		}

		$sizePath = $accumPath . '.size';
		$stored   = false;
		if ( file_exists( $sizePath ) && ! is_link( $sizePath ) ) {
			$raw = @file_get_contents( $sizePath ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- Intentional: suppresses warnings on hostile shared-hosting paths; return value is checked.
			if ( false !== $raw && ctype_digit( trim( $raw ) ) ) {
				$stored = (int) trim( $raw );
			}
		}

		$observed = @filesize( $tempName ); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- Intentional: suppresses warnings on hostile shared-hosting paths; return value is checked.
		$is_final = ( $chunk === $chunks - 1 );

		if ( ! $is_final && false !== $observed && $observed > 0 ) {
			if ( false === $stored ) {
				// A symlink makes the read above fall through to here, turning this write into an arbitrary-file write.
				if ( is_link( $sizePath ) ) {
					wp_send_json_error( [ 'message' => esc_html__( 'Refusing to write to non-regular upload metadata file.', 'swarmify' ) ] );
				}
				// A short write (disk full mid-write) leaves a truncated number that still reads as a valid stride.
				$encoded = (string) $observed;
				$written = @file_put_contents( $sizePath, $encoded ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions -- Intentional: sidecar in our own private dir; return value is checked.
				if ( strlen( $encoded ) !== $written ) {
					wp_send_json_error( [ 'message' => esc_html__( 'Failed to record upload chunk size. Please retry the upload.', 'swarmify' ) ] );
				}
				return $observed;
			}
			if ( $stored !== $observed ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Upload chunk size changed mid-transfer. Please retry the upload.', 'swarmify' ) ] );
			}
			return $stored;
		}

		if ( false !== $stored ) {
			return $stored;
		}

		// Falling back to the advertised size here is what silently corrupted files before 2.3.3.
		wp_send_json_error( [ 'message' => esc_html__( 'Upload chunk size could not be determined. Please retry the upload.', 'swarmify' ) ] );
	}

	/**
	 * AJAX chunk receiver.
	 * Ajax callback for plupload to handle chunked uploads.
	 * Based on code by Davit Barbakadze
	 * https://gist.github.com/jayarjo/5846636
	 *
	 * @since 1.2.0
	 */
	public function ajax_chunk_receiver() {

		// Authenticate first — before inspecting $_FILES — so an unauthenticated
		// probe with an empty form can't differentiate "endpoint exists" from
		// "endpoint not registered".
		if ( ! is_user_logged_in() || ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'Sorry, you do not have permission to upload files.', 'swarmify' ) );
		}
		check_admin_referer( 'media-form' );

		/** Check that we have an upload and there are no errors. */
		if ( empty( $_FILES ) || ( ! empty( $_FILES['async-upload'] ) && isset( $_FILES['async-upload']['error'] ) && UPLOAD_ERR_OK !== $_FILES['async-upload']['error'] )) {
			/** Failed to move uploaded file. */
			$this->log_debug( 'SmartVideo Upload: Failed to move uploaded file.' );
			wp_send_json_error( [ 'message' => esc_html__( 'Failed to move uploaded file.', 'swarmify' ) ] );

		} else {
			// tmp_name is server-generated by PHP — sanitize_text_field() would
			// corrupt paths like /tmp/phpXXXXXX by stripping characters. Validate
			// via is_uploaded_file() instead of trusting $_FILES blindly.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- $_FILES tmp_name is validated by is_uploaded_file()/move_uploaded_file below; sanitizing the path would break the upload.
			$tempName = isset( $_FILES['async-upload']['tmp_name'] ) ? $_FILES['async-upload']['tmp_name'] : '';
			if ( '' === $tempName || ! is_uploaded_file( $tempName ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Missing or invalid upload tmp name.', 'swarmify' ) ] );
			}

			/** Check and get file chunks. */
			$chunk  = isset( $_POST['chunk'] ) ? intval( $_POST['chunk'] ) : 0;
			$chunks = isset( $_POST['chunks'] ) ? intval( $_POST['chunks'] ) : 0;

			// $chunks=0 is plupload's single-shot upload (no chunking) and requires
			// $chunk=0; $chunks>0 means chunked, valid range 0..$chunks-1.
			if ( $chunk < 0 || $chunks < 0 || $chunks > self::MAX_CHUNKS
				|| ( 0 === $chunks && 0 !== $chunk )
				|| ( $chunks > 0 && $chunk >= $chunks ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Invalid chunk index.', 'swarmify' ) ] );
			}

			/** Get file name. */
			if ( isset( $_POST['name'] ) ) {
				$fileName = sanitize_file_name( $_POST['name'] );
			} elseif ( isset( $_FILES['async-upload']['name'] ) ) {
				$fileName = sanitize_file_name( $_FILES['async-upload']['name'] );
			} else {
				$fileName = '';
			}
			if ( '' === $fileName ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Missing file name.', 'swarmify' ) ] );
			}

			// Per-upload unique ID prevents race conditions when the same user
			// uploads the same filename from two browser tabs simultaneously.
			// Plupload's file.id is injected into multipart_params by our inline
			// JS (see enqueue_upload_id_script). Validate format to reject garbage.
			$uploadId = isset( $_POST['swarmify_upload_id'] ) ? sanitize_text_field( wp_unslash( $_POST['swarmify_upload_id'] ) ) : '';
			if ( ! is_string( $uploadId ) || '' === $uploadId || ! preg_match( '/^[a-zA-Z0-9_-]{1,64}$/', $uploadId ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Missing or invalid upload ID.', 'swarmify' ) ] );
			}

			// Accumulate chunks under a private dir, not sys_get_temp_dir() (world-
			// writable on shared hosting → a co-tenant can guess (user_id, sanitized
			// filename), pre-plant a symlink at <md5>.part, and have chunk 0's 'wb'
			// truncate /var/www/html/wp-config.php or any path the PHP user can write).
			$chunksDir = $this->get_chunks_dir();
			if ( '' === $chunksDir ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Upload service unavailable. Please contact an administrator.', 'swarmify' ) ] );
			}
			$accumPath = $chunksDir . '/' . md5( get_current_user_id() . '_' . $uploadId . '_' . $fileName ) . '.part';
			$lockPath  = $accumPath . '.lock';

			clearstatcache( true, $accumPath );
			if ( file_exists( $accumPath ) ) {
				if ( is_link( $accumPath ) || ! is_file( $accumPath ) ) {
					// Refuse to touch a non-regular accumulator file. The chunks
					// dir is private and 0700 (see get_chunks_dir), so a co-tenant
					// cannot plant a symlink here; this guard is defense in depth.
					wp_send_json_error( [ 'message' => esc_html__( 'Refusing to write to non-regular accumulator file.', 'swarmify' ) ] );
				}
			} elseif ( 0 !== $chunk ) {
				// Chunk N>0 with no accumulator means out-of-order delivery or replay.
				wp_send_json_error( [ 'message' => esc_html__( 'Missing accumulator for non-zero chunk.', 'swarmify' ) ] );
			}

			// Write each chunk at its absolute offset instead of appending, so a
			// late or duplicate chunk 0 (plupload retries up to max_retries, and
			// HTTP/2 can reorder requests in flight) rewrites its own bytes
			// idempotently — an accumulator that already holds later chunks is no
			// longer truncated. 'cb' = O_CREAT without O_TRUNC. The offset uses
			// the size the client actually used, which is not necessarily the one
			// we advertised — see resolve_chunk_size().
			$chunk_size = $this->resolve_chunk_size( $accumPath, $tempName, $chunk, $chunks );
			$offset     = $chunk * $chunk_size;

			// Derive the size cap before seeking so a large chunk index can't
			// fseek the accumulator into a multi-GB sparse extent ahead of any
			// size check. Match the client-side cap (disk_free_space with 5GB
			// fallback) — NOT wp_max_upload_size(): that returns
			// min(upload_max_filesize, post_max_size), which would defeat the
			// accelerator (chunked uploads exist precisely to bypass
			// post_max_size). The accelerator's contract has always been "as large
			// as the disk can hold", which we cap at 5GB if disk_free_space is
			// disabled. Admins wanting tighter bounds can filter swarmify_upload_max_size.
			$maxSize = (int) apply_filters( 'swarmify_upload_max_size', $this->filter_upload_size_limit( '' ) );
			if ( $maxSize <= 0 ) {
				$maxSize = 5 * 1024 * 1024 * 1024;
			}
			if ( $offset > $maxSize ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Upload exceeds maximum allowed size.', 'swarmify' ) ] );
			}

			$out = fopen( $accumPath, 'cb' );
			if ( ! $out ) {
				$this->log_debug( 'SmartVideo Upload: Failed to open accumulator: ' . $accumPath );
				wp_send_json_error( [ 'message' => esc_html__( 'Failed to write uploaded chunk.', 'swarmify' ) ] );
			}
			if ( 0 !== fseek( $out, $offset ) ) {
				fclose( $out );
				$this->log_debug( 'SmartVideo Upload: Failed to seek accumulator to chunk offset: ' . $accumPath );
				wp_send_json_error( [ 'message' => esc_html__( 'Failed to write uploaded chunk.', 'swarmify' ) ] );
			}

			$in = fopen( $tempName, 'rb' );
			if ( ! $in ) {
				$this->log_debug( 'SmartVideo Upload: Failed to open input stream: ' . $tempName );
				fclose( $out );
				wp_send_json_error( [ 'message' => esc_html__( 'Failed to read uploaded chunk.', 'swarmify' ) ] );
			}

			$abort_msg = '';
			// 1 MB read buffer — large video uploads move far fewer fread/fwrite
			// syscalls than the old 4 KB buffer for the same bytes.
			$read_buffer_size = 1024 * 1024;
			while ( ! feof( $in ) ) {
				$buff = fread( $in, $read_buffer_size );
				if ( false === $buff ) {
					$abort_msg = esc_html__( 'Read error on uploaded chunk.', 'swarmify' );
					break;
				}
				if ( '' === $buff ) {
					continue;
				}
				$written = fwrite( $out, $buff );
				if ( false === $written || $written < strlen( $buff ) ) {
					$abort_msg = esc_html__( 'Write error on accumulator.', 'swarmify' );
					break;
				}
				if ( ftell( $out ) > $maxSize ) {
					$abort_msg = esc_html__( 'Upload exceeds maximum allowed size.', 'swarmify' );
					break;
				}
			}
			fclose( $in );

			// Flush user-space buffers to the OS before closing, so a full-disk
			// condition is caught here rather than silently lost in fclose().
			if ( '' === $abort_msg && false === fflush( $out ) ) {
				$abort_msg = esc_html__( 'Flush error on accumulator (disk may be full).', 'swarmify' );
			}

			// On the final chunk, drop any bytes past the assembled end. 'cb' never
			// truncates on open, so an orphaned accumulator at this path (a shorter
			// prior upload the cleanup cron hasn't swept) would otherwise leave
			// stale trailing bytes in the finished file. Relies on plupload's
			// in-order delivery: the last index arrives last, so its end is the
			// true size. Fully reorder-tolerant assembly would need a per-chunk
			// received manifest — out of scope here.
			if ( '' === $abort_msg && ( ! $chunks || $chunk === $chunks - 1 ) ) {
				$end_pos = ftell( $out );
				if ( false === $end_pos || ! ftruncate( $out, $end_pos ) ) {
					$abort_msg = esc_html__( 'Failed to finalize accumulator size.', 'swarmify' );
				}
			}
			fclose( $out );

			if ( '' !== $abort_msg ) {
				// Accumulator survives so the client's chunk-level retry doesn't restart the whole upload.
				wp_send_json_error( [ 'message' => $abort_msg ] );
			}

			touch( $lockPath );

			if ( file_exists( $tempName ) ) {
				wp_delete_file( $tempName );
			}

			/** Check if file has finished uploading all parts. */
			if ( ! $chunks || $chunk === $chunks - 1 ) {

				/** Recreate upload in $_FILES global and pass off to WordPress. */
				if ( ! rename( $accumPath, $tempName ) ) {
					$this->log_debug( 'SmartVideo Upload: rename failed: ' . $accumPath . ' -> ' . $tempName );
					wp_delete_file( $accumPath );
					wp_delete_file( $lockPath );
					wp_delete_file( $accumPath . '.size' );
					wp_send_json_error( [ 'message' => esc_html__( 'Failed to finalize upload.', 'swarmify' ) ] );
				}
				wp_delete_file( $lockPath );
				wp_delete_file( $accumPath . '.size' );
				$_FILES['async-upload']['name'] = $fileName;
				$_FILES['async-upload']['size'] = filesize( $tempName );
				$_FILES['async-upload']['type'] = $this->get_mime_content_type( $tempName );
				// blog_charset is admin-modifiable; strip CR/LF to prevent HTTP header
				// injection (CWE-113) if the option is ever poisoned with `\r\n...`.
				$blog_charset = (string) get_option( 'blog_charset' );
				$blog_charset = preg_replace( '/[\r\n]/', '', $blog_charset );
				header( 'Content-Type: text/html; charset=' . $blog_charset );

				// Via ajax like modal media uploader
				if ( ! isset( $_POST['short'] ) || ! isset( $_POST['type'] ) ) {

					send_nosniff_header();
					nocache_headers();
					wp_ajax_upload_attachment();
					wp_send_json_error( [ 'message' => esc_html__( 'Unexpected fallthrough after upload handler.', 'swarmify' ) ], 500 );

				} else { // add new media page

					// post_id is optional on this branch: missing → unattached upload
					// (matches normal WP async upload). When present, reject garbage
					// (arrays, "abc") so a caller can't silently corrupt the value;
					// is_numeric() still allows numeric strings like "42". get_post()
					// + current_user_can() below catches any value that doesn't map
					// to an editable post and falls back to 0.
					$post_id = 0;
					if ( isset( $_POST['post_id'] ) ) {
						if ( ! is_numeric( $_POST['post_id'] ) ) {
							wp_send_json_error( [ 'message' => esc_html__( 'Invalid post_id.', 'swarmify' ) ] );
							return;
						}
						$post_id = (int) $_POST['post_id'];
					}
					if ( ! get_post( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
						$post_id = 0;
					}

					$id = media_handle_upload( 'async-upload', $post_id );
					if ( is_wp_error( $id ) ) {
						echo '<div class="error-div error">
						<a class="dismiss" href="#" onclick="jQuery(this).parents(\'div.media-item\').slideUp(200, function(){jQuery(this).remove();});">' . esc_html__( 'Dismiss', 'swarmify' ) . '</a>
						<strong>' 
						/* translators: %s: file name */
						. sprintf( esc_html__( '&#8220;%s&#8221; has failed to upload.', 'swarmify' ), esc_html( $fileName ) ) . '</strong><br />' .
						esc_html( $id->get_error_message() ) . '</div>';
						exit;
					}

					if ( isset( $_POST['short'] ) && in_array( $_POST['short'], [ '1', 'true' ], true ) ) {
						// Short form response - attachment ID only.
						echo esc_js( $id );
					} elseif ( isset( $_POST['type'] ) ) {
						// Long form response - big chunk o html.

						// used to look up an "async_upload_$type" filter
						$type = sanitize_key($_POST['type']);

						/**
						 * Filter the returned ID of an uploaded attachment.
						 *
						 * The dynamic portion of the hook name, `$type`, refers to the attachment type,
						 * such as 'image', 'audio', 'video', 'file', etc.
						 *
						 * @since 1.2.0
						 *
						 * @param int $id Uploaded attachment ID.
						 */

						// stupid, stupid linter
						$allowed_html = array(
							'div'      => array(
								'class' => array(),
								'id'    => array(),
								'style' => array(),
							),
							'span'     => array(
								'class'       => array(),
								'id'          => array(),
								'aria-hidden' => array(),
							),
							'input'    => array(
								'type'     => array(),
								'id'       => array(),
								'name'     => array(),
								'value'    => array(),
								'class'    => array(),
								'required' => array(),
								'onclick'  => array(),
							),
							'a'        => array(
								'class'   => array(),
								'href'    => array(),
								'target'  => array(),
								'id'      => array(),
								'onclick' => array(),
							),
							'table'    => array(
								'class' => array(),
							),
							'thead'    => array(
								'class' => array(),
								'id'    => array(),
							),
							'tbody'    => array(),
							'th'       => array(
								'scope' => array(),
								'class' => array(),
							),
							'tr'       => array(
								'class' => array(),
							),
							'td'       => array(
								'class'   => array(),
								'id'      => array(),
								'colspan' => array(),
								'style'   => array(),
							),
							'p'        => array(
								'class' => array(),
							),
							'strong'   => array(),
							'small'    => array(),
							'textarea' => array(
								'id'       => array(),
								'name'     => array(),
								'required' => array(),
							),
							'img'      => array(
								'class' => array(),
								'src'   => array(),
								'alt'   => array(),
							),
							'br'       => array(
								'class' => array(),
							),
							'label'    => array(
								'for'   => array(),
								'class' => array(),
							),
							'button'   => array(
								'type'                => array(),
								'class'               => array(),
								'data-clipboard-text' => array(),
							),
						);

						echo wp_kses( apply_filters( "async_upload_{$type}", $id ), $allowed_html );
					} else {
						$this->log_debug( 'SmartVideo Upload: unexpected else branch ($_POST short/type unset after isset guard)' );
					}
				}
			} else {
				// Intermediate chunk received successfully.
				wp_send_json_success();
			}

			wp_die();
		}
	}
}
