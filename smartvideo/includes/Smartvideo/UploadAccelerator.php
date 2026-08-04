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
 * @package    Swarmify
 */
class UploadAccelerator {

	/**
	 * Upper bound on the number of chunks a single upload can declare.
	 * Even a 100GB upload runs to only a few thousand chunks, so anything
	 * past 10k is a bogus request.
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
	 * Get the singleton instance, creating it on first call.
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
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( get_option( 'swarmify_toggle_uploadacceleration', 'on' ) === 'on' ) {
			add_filter( 'plupload_init', array( $this, 'filter_plupload_settings' ) );
			add_filter( 'upload_post_params', array( $this, 'filter_plupload_params' ) );
			add_filter( 'plupload_default_settings', array( $this, 'filter_plupload_settings' ) );
			add_filter( 'plupload_default_params', array( $this, 'filter_plupload_params' ) );
			add_action( 'wp_ajax_swarmify_upload_accelerator', array( $this, 'ajax_chunk_receiver' ) ); // admin-ajax.php prefixes the posted action name with wp_ajax_
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_upload_id_script' ) );

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
	 * Enqueue inline JS that posts plupload's per-file id as `swarmify_upload_id`,
	 * so the server can tell apart concurrent uploads of the same filename by the
	 * same user. Plupload does not send that id on its own.
	 *
	 * @since 2.3.0
	 */
	public function enqueue_upload_id_script() {
		// Patch the constructor rather than one uploader, so every uploader gets
		// the binding whichever admin screen created it. Attaching to both script
		// handles is safe — wp_add_inline_script no-ops on un-enqueued handles.
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
		$chunk_size = $this->get_chunk_size();
		$retries    = 7;

		$plupload_settings['url']                      = admin_url( 'admin-ajax.php' );
		$plupload_settings['filters']['max_file_size'] = $this->filter_upload_size_limit() . 'b';
		$plupload_settings['chunk_size']               = $chunk_size . 'b';
		$plupload_settings['max_retries']              = $retries;
		return $plupload_settings;
	}

	/**
	 * Return the maximum upload size.
	 *
	 * @since 1.0.0
	 *
	 * @return float $bytes Free disk space in bytes.
	 */
	public function filter_upload_size_limit() {

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
	public function get_chunk_size() {

		$post_max = ini_get( 'post_max_size' );
		if ( false === $post_max || '' === $post_max ) {
			return 4 * 1024 * 1024;
		}

		$val = trim( $post_max );
		if ( '' === $val ) {
			return 4 * 1024 * 1024;
		}
		$last = strtolower( $val[ strlen( $val ) - 1 ] );
		$val  = intval( $val );
		switch ( $last ) {
			case 'g':
				$val *= 1024; // Fall-through
			case 'm':
				$val *= 1024; // Fall-through
			case 'k':
				$val *= 1024;
		}

		return max( intval( $val / 2 ), 4 * 1024 * 1024 );
	}

	/**
	 * Return the directory where upload chunks accumulate.
	 *
	 * Kept inside the site rather than the shared OS temp dir, where anyone else
	 * on the host could pre-plant a symlink at the predictable accumulator path,
	 * and locked down against being read over the web.
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
	 * Some hosts ship wp-content read-only, so the uploads dir — writable on any
	 * working install — is the fallback. The OS temp dir is deliberately not a
	 * candidate: it is shared with everyone else on the host.
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
		// is_dir() follows symlinks, so check for one first: a planted link would
		// redirect the whole accumulator into a directory someone else controls.
		if ( is_link( $dir ) ) {
			$this->log_debug( 'SmartVideo Upload: Refusing symlinked chunks directory: ' . $dir );
			return '';
		}
		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			$this->log_debug( 'SmartVideo Upload: Failed to create chunks directory: ' . $dir );
			return '';
		}
		// Re-apply the perms and guard files on every call — a directory that
		// already exists skips the mkdir branch above and would otherwise keep
		// whatever permissions it was created with.
		if ( ! @chmod( $dir, 0700 ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors -- Intentional: suppresses warnings on hostile shared-hosting paths; return value is checked.
			$this->log_debug( 'SmartVideo Upload: Failed to set 0700 on chunks directory: ' . $dir );
		}
		// Everything downstream assumes nobody else can write here, so refuse a
		// directory the chmod above could not lock down.
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
		// The chmod above silently does nothing on a directory owned by another
		// OS user, so check writability here.
		if ( ! is_writable( $dir ) ) {
			$this->log_debug( 'SmartVideo Upload: Chunks directory not writable by web user (ownership mismatch?): ' . $dir );
			return '';
		}
		return $dir;
	}

	/**
	 * Sweep accumulator files older than 24 hours.
	 *
	 * A finished upload renames its .part file out of the chunks dir, so anything
	 * left behind is from a client that never completed.
	 *
	 * @since 2.3.0
	 */
	public function cleanup_stale_chunks() {
		// Sweep every candidate, not just the one in use — a site that regained a
		// writable wp-content would otherwise strand what the fallback left behind.
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

		// An upload that bails after writing the .size sidecar but before the
		// .part file leaves the sidecar stranded; the loop above only sweeps
		// .part files.
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
				// On PHP 7.x finfo_open returns a resource that has to be freed by
				// hand; on 8.0+ it returns an object that cleans itself up, and
				// finfo_close is deprecated there.
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
	 * Another plugin can override the chunk size we advertise, so the browser may
	 * slice at a size this class never chose; seeking by our own figure would put
	 * chunks at the wrong offsets and quietly corrupt the file.
	 *
	 * Every chunk but the last is exactly one stride long, so the first received
	 * length is authoritative — it is recorded beside the accumulator and reused
	 * for the short final chunk. A later chunk that disagrees cannot be
	 * reconciled, so fail loudly instead.
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
			return $this->get_chunk_size();
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
				// A partial write would leave a truncated number that still reads as a valid stride.
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

		// Never fall back to the advertised size — guessing the stride corrupts the file silently.
		wp_send_json_error( [ 'message' => esc_html__( 'Upload chunk size could not be determined. Please retry the upload.', 'swarmify' ) ] );
	}

	/**
	 * Ajax callback for plupload that reassembles a chunked upload.
	 *
	 * Based on code by Davit Barbakadze
	 * https://gist.github.com/jayarjo/5846636
	 *
	 * @since 1.2.0
	 */
	public function ajax_chunk_receiver() {

		// Authenticate before touching $_FILES, so a logged-out probe can't tell
		// whether this endpoint exists.
		if ( ! is_user_logged_in() || ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'Sorry, you do not have permission to upload files.', 'swarmify' ) );
		}
		check_admin_referer( 'media-form' );

		if ( empty( $_FILES ) || ( ! empty( $_FILES['async-upload'] ) && isset( $_FILES['async-upload']['error'] ) && UPLOAD_ERR_OK !== $_FILES['async-upload']['error'] )) {
			$this->log_debug( 'SmartVideo Upload: Failed to move uploaded file.' );
			wp_send_json_error( [ 'message' => esc_html__( 'Failed to move uploaded file.', 'swarmify' ) ] );

		} else {
			// tmp_name is server-generated, so sanitizing it would corrupt paths
			// like /tmp/phpXXXXXX; is_uploaded_file() below is the real check.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- $_FILES tmp_name is validated by is_uploaded_file()/move_uploaded_file below; sanitizing the path would break the upload.
			$tempName = isset( $_FILES['async-upload']['tmp_name'] ) ? $_FILES['async-upload']['tmp_name'] : '';
			if ( '' === $tempName || ! is_uploaded_file( $tempName ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Missing or invalid upload tmp name.', 'swarmify' ) ] );
			}

			$chunk  = isset( $_POST['chunk'] ) ? intval( $_POST['chunk'] ) : 0;
			$chunks = isset( $_POST['chunks'] ) ? intval( $_POST['chunks'] ) : 0;

			// plupload sends chunks=0 for a single-shot upload; otherwise the index
			// has to fall inside 0..chunks-1.
			if ( $chunk < 0 || $chunks < 0 || $chunks > self::MAX_CHUNKS
				|| ( 0 === $chunks && 0 !== $chunk )
				|| ( $chunks > 0 && $chunk >= $chunks ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Invalid chunk index.', 'swarmify' ) ] );
			}

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

			// The per-upload id keeps two tabs uploading the same filename from
			// sharing one accumulator; our inline JS supplies it (see
			// enqueue_upload_id_script).
			$uploadId = isset( $_POST['swarmify_upload_id'] ) ? sanitize_text_field( wp_unslash( $_POST['swarmify_upload_id'] ) ) : '';
			if ( ! is_string( $uploadId ) || '' === $uploadId || ! preg_match( '/^[a-zA-Z0-9_-]{1,64}$/', $uploadId ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Missing or invalid upload ID.', 'swarmify' ) ] );
			}

			$chunksDir = $this->get_chunks_dir();
			if ( '' === $chunksDir ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Upload service unavailable. Please contact an administrator.', 'swarmify' ) ] );
			}
			$accumPath = $chunksDir . '/' . md5( get_current_user_id() . '_' . $uploadId . '_' . $fileName ) . '.part';
			$lockPath  = $accumPath . '.lock';

			clearstatcache( true, $accumPath );
			if ( file_exists( $accumPath ) ) {
				if ( is_link( $accumPath ) || ! is_file( $accumPath ) ) {
					// Defense in depth: the chunks dir is already private, but
					// never write through a symlink.
					wp_send_json_error( [ 'message' => esc_html__( 'Refusing to write to non-regular accumulator file.', 'swarmify' ) ] );
				}
			} elseif ( 0 !== $chunk ) {
				// Chunk N>0 with no accumulator means out-of-order delivery or replay.
				wp_send_json_error( [ 'message' => esc_html__( 'Missing accumulator for non-zero chunk.', 'swarmify' ) ] );
			}

			// Write each chunk at its own offset rather than appending, so a
			// retried or reordered chunk rewrites its own bytes instead of
			// clobbering ones already received.
			// The stride is the size the client actually sliced at, which need not
			// be the one advertised — see resolve_chunk_size().
			$chunk_size = $this->resolve_chunk_size( $accumPath, $tempName, $chunk, $chunks );
			$offset     = $chunk * $chunk_size;

			// Work out the cap before seeking, so a huge chunk index can't open a
			// multi-GB sparse hole first. The cap is free disk space, matching the
			// client — not WordPress's own upload limit, which chunked uploads
			// exist precisely to get past.
			$maxSize = (int) apply_filters( 'swarmify_upload_max_size', $this->filter_upload_size_limit() );
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
			// A 1 MB buffer keeps the syscall count sane on multi-gigabyte videos.
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

			// Flush before closing so a full disk is reported here — fclose()
			// swallows the error.
			if ( '' === $abort_msg && false === fflush( $out ) ) {
				$abort_msg = esc_html__( 'Flush error on accumulator (disk may be full).', 'swarmify' );
			}

			// On the final chunk, cut off anything past the assembled end: 'cb'
			// never truncates, so a longer leftover accumulator at this path would
			// otherwise trail stale bytes into the finished file. This takes the
			// last chunk as arriving last, which is what plupload does.
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

			if ( ! $chunks || $chunk === $chunks - 1 ) {

				// Hand the assembled file to WordPress as if it had arrived in one piece.
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
				// blog_charset is admin-editable, so strip CR/LF before it goes into
				// a header (CWE-113).
				$blog_charset = (string) get_option( 'blog_charset' );
				$blog_charset = preg_replace( '/[\r\n]/', '', $blog_charset );
				header( 'Content-Type: text/html; charset=' . $blog_charset );

				// Request came from the modal media uploader.
				if ( ! isset( $_POST['short'] ) || ! isset( $_POST['type'] ) ) {

					send_nosniff_header();
					nocache_headers();
					wp_ajax_upload_attachment();
					wp_send_json_error( [ 'message' => esc_html__( 'Unexpected fallthrough after upload handler.', 'swarmify' ) ], 500 );

				} else { // add new media page

					// post_id is optional here — leaving it out means an unattached
					// upload, as with any WordPress async upload.
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
						echo esc_js( $id );
					} elseif ( isset( $_POST['type'] ) ) {
						// Names the async_upload_{$type} filter applied below.
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
				wp_send_json_success();
			}

			wp_die();
		}
	}
}
