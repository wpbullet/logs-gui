<?php

namespace WPBullet;

if ( isset( $_SERVER[ 'WPLIB_BOX' ] ) ) {
	ini_set( 'display_errors', 1 );
	ini_set( 'display_startup_errors', 1 );
	error_reporting( E_ALL );
}

/**
 * Class Log_Viewer
 * @package WPBullet
 *
 *  use WPBullet\Log_Viewer;
 *  $viewer = new Log_Viewer();
 *
 *  $viewer = new \WPBullet\Log_Viewer();
 *
 */
class Log_Viewer {

	private $_log_files = array();

	var $log_file;
	var $line_count;
	var $buffer_size = 4098;
	var $page_title;
	var $logs_path;
	var $view_descending = true;
	var $default = null;

	/**
	 * @param string $filepath
	 *
	 * @return string
	 */
	function sanitize_filepath( $filepath ) {
		return $filepath;
	}

	/**
	 * @param string $content
	 *
	 * @return string
	 */
	function esc_html( $content ) {
		return $content;
	}


	/**
	 * @param string $content
	 *
	 * @return string
	 */
	function esc_attr( $content ) {
		return $content;
	}


	/**
	 * Log_Viewer constructor.
	 *
	 * @param string $logs_path
	 */
	function __construct( $logs_path = '/var/log/' ) {
		$this->logs_path = $logs_path;
	}

	/**
	 *
	 */
	function initialize() {

	    $x = (array) 1;


		$this->_log_files = $this->get_log_files( $this->logs_path );

		ksort( $this->_log_files );

		foreach ( $this->_log_files as $dir_name => $file_array ) {
			ksort( $file_array );
			foreach ( $file_array as $key => $val ) {
				$this->default     = $this->sanitize_filepath( $key );
				$log_files[ $key ] = $val;
			}
		}

		$this->log_file = ! empty( $_GET[ 'p' ] )
			? $this->sanitize_filepath( urldecode( $_GET['p'] ) )
			: $this->default;

		$this->line_count = ! empty( $_GET[ 'lines' ] )
			? intval( $_GET['lines'] )
			:  50;

		$this->page_title = substr( $this->log_file, ( strrpos( $this->log_file, '/' ) + 1 ) );

	}

	/**
	 *
	 */
	function the_logs_html() {
		$this->the_header_html();
		$this->the_body_html();
		$this->the_footer_html();
	}

	/**
	 *
	 */
	function the_header_html() {
		?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta http-equiv="X-UA-Compatible" content="ie=edge">
            <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/themes/default/style.min.css"/>
            <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.10.0/styles/default.min.css">
            <title>Logs Viewer</title>
            <style media="screen">
                body {
                    font-family: sans-serif;
                }

                .log_container {
                    display: flex;
                }

                .log_menu {
                    width: 350px;
                }

                .left-menu {
                    bottom: 0;
                    height: 100%;
                    overflow: scroll;
                    position: fixed;
                    top: 0;
                    width: 350px;
                }

                .log_content {
                    margin: 0 auto;
                    height: 100vh;
                    width: calc(100% - 550px);
                }

                .log_code {
                    font-size: 14px;
                    line-height: 2em;
                }

                .jstree-default .jstree-leaf .jstree-themeicon {
                    background-position: -102px -68px;
                }

                .hljs {
                    height: calc(100vh - 300px);
                }

                .accesslog ol {
                    padding: 0 10px;
                }
            </style>
        </head>
        <body>
		<?php
	}

	/**
	 *
	 */
	function the_body_html() {
		?>
        <div class="log_container">
            <div class="log_menu">
                <div id="tree_container" class="left-menu">
                    <ul>
						<?php
						$current_file = isset( $_GET['k'] )
							? $this->sanitize_filepath( $_GET['k'] )
							: '';

						// Generate a menu
						foreach ( $this->_log_files as $dir => $files_array ) {
							if ( ! is_array( $files_array ) ) {
								continue;
							}
							if ( ! is_string( $dir ) ) {
								continue;
							}
							$dir = $this->sanitize_filepath( $dir );
							if ( isset( $files_array[ $current_file ] ) ) {
								echo '<li class="jstree-open">' . $this->esc_html( $dir );
							} else {
								echo '<li>' . $this->esc_html( $dir );
							}

							echo '<ul>';
							foreach ( $files_array as $key => $file_info ) {
								if ( ! is_array( $file_info ) ) {
									continue;
								}
								if ( empty( $file_info[ 'path' ] ) ) {
									continue;
								}
								if ( ! is_file( $file_info['path'] ) ) {
									// File does not exist, remove it from the array, so it does not appear in the menu.
									unset( $files_array[ $key ] );
									continue;
								}

								$active = $file_info[ 'path' ] === $this->log_file
									? 'class="jstree-clicked"' :
									'';

								echo sprintf(
									'<li class="file"><a %s href="?p=%s&lines=%d&k=%s">%s</a></li>',
                                    $this->esc_attr( $active ),
									$this->esc_attr( urlencode( $file_info['path'] ) ),
									intval( $this->line_count ),
									$this->esc_attr( $key ),
									$this->esc_html( $file_info['name'] )
                                );
							}
							echo '</ul>';
							echo '</li>';
						}
						?>
                    </ul>
                </div>
            </div>
            <div class="log_content">
                <div class="log_header">
                    <h1><?php echo $this->page_title; ?></h1>
                    <h2>The last <?php echo intval( $this->line_count ); ?> lines of <?php echo $this->esc_html( $this->log_file ); ?>.</h2>
                    <p>How many lines to display?</p>
                    <form action="" method="get">
                        <input type="hidden" name="p" value="<?php echo $this->log_file ?>">
                        <select name="lines" onchange="this.form.submit()">
                            <option value="10" <?php echo ( $this->line_count == '10' ) ? 'selected' : '' ?>>10</option>
                            <option value="50" <?php echo ( $this->line_count == '50' ) ? 'selected' : '' ?>>50</option>
                            <option value="100" <?php echo ( $this->line_count == '100' ) ? 'selected' : '' ?>>100</option>
                            <option value="500" <?php echo ( $this->line_count == '500' ) ? 'selected' : '' ?>>500</option>
                            <option value="1000" <?php echo ( $this->line_count == '1000' ) ? 'selected' : '' ?>>1000</option>
                        </select>
                    </form>
                </div>
                <div class="log_code">
				<pre>
					<code class="accesslog">
						<ol reversed>
							<?php
							$output = $this->tail( $this->log_file, $this->line_count );
							$output = explode( "\n", $output );
							if ( $this->view_descending ) {
								// Latest first
								$output = array_reverse( $output );
							}
							$output = implode( '<br>', $output );
							echo $output;
							?>
						</ol>
					</code>
				</pre>
                </div>
            </div>
        </div>
		<?php
	}

	/**
	 *
	 */
	function the_footer_html() {
		?>
        <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.0/jquery.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/jstree/3.3.3/jstree.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.8.0/highlight.min.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.4.0/languages/accesslog.min.js"></script>
        <script type="text/javascript">
            $(document).ready(function () {
                $('#tree_container').jstree().bind('select_node.jstree', function (e, data) {
                    document.location.href = data.node.a_attr.href;
                });
            });
            hljs.initHighlightingOnLoad();
        </script>
        </body>
        </html>
		<?php
	}

	/**
	 * @return array
	 */
	function log_files() {
	    return $this->_log_files;
	}

	/**
	 * @param string $log_dir
	 * @param array $results
	 *
	 * @return array
     *
     * For WPLib Box:
     *
     *      docker logs php-fpm-7.1.9
     *
	 */
	function get_log_files( string $log_dir, array &$results = array() ) {

		do {

			$err_msg = false;

			if ( ! is_string( $log_dir ) ) {
				$err_msg = sprintf(
					'ERROR: 1st parameter passed to %1$s is not a string; received type %2$s instead.',
					__METHOD__,
					gettype( $log_dir )
				);

			} else if ( ! is_dir( $log_dir ) ) {
				$err_msg = sprintf(
					'ERROR: 1st parameter passed to %1$s is not a valid directory; received %2$s instead.',
					__METHOD__,
					$log_dir
				);

			} else if ( ! is_array( $results ) ) {
				$err_msg = sprintf(
					'ERROR: 2nd parameter passed to %1$s is not an array; received type %2$s instead.',
					__METHOD__,
					gettype( $results )
				);
			}

			if ( $err_msg ) {
				trigger_error( $err_msg );
				break;
			}

			$this->_log_files = scandir( $log_dir );
			$dirs_list        = $files_list = array();
			foreach ( $this->_log_files as $key => $file ) {
				$path = realpath( $log_dir . DIRECTORY_SEPARATOR . $file );
				if ( ! is_dir( $path ) ) {
					$files_list[] = $path;
				} elseif ( $file != "." && $file != ".." ) {
					$dirs_list[] = $path;
				}
			}
			foreach ( $files_list as $path ) {
				if ( ! preg_match( "/^.*\/(\S+)$/", $path, $matches ) ) {
					continue;
				}
				$name                         = $matches[1];
				$results[ $log_dir ][ $name ] = array(
					'name' => $name,
					'path' => $path
				);
			}
			foreach ( $dirs_list as $path ) {
				$this->get_log_files( $path, $results );
			}

		} while ( false );

		assert(
            is_array( $results ),
            sprintf(
                'ERROR: %1$s return value is not an array; received type %2$s instead.',
                __METHOD__,
                gettype( $results )
            )
        );

		return $results;

	}

	/**
	 * @param string $log_file
	 * @param string $line_count
	 * @return bool|string
	 */
	function tail( $log_file, $line_count ) {
		$output = '';
		$chunk  = '';
		$gz = $f = null;
		if ( '.gz' == substr( $log_file, -3 ) ) {
			$gz = gzopen( $log_file, "rb" );
			gzseek( $gz, - 1, SEEK_END );
			if ( gzread( $gz, 10000 ) != "\n" ) {
				$line_count -= 1;
			}
			while ( gztell( $gz ) > 0 && $line_count >= 0 ) {
				// Figure out how far back we should jump
				$seek = min( gztell( $gz ), $this->buffer_size );
				// Do the jump (backwards, relative to where we are)
				gzseek( $gz, - $seek, SEEK_CUR );
				// Read a chunk and prepend it to our output
				$output = ( $chunk = gzread( $gz, $seek ) ) . $output;
				// Jump back to where we started reading
				gzseek( $gz, - mb_strlen( $chunk, '8bit' ), SEEK_CUR );
				// Decrease our line counter
				$line_count -= substr_count( $chunk, "\n" );
			}
		} else {
			$f = fopen( $log_file, "rb" );
			fseek( $f, - 1, SEEK_END );
			if ( fread( $f, 1 ) != "\n" ) {
				$line_count -= 1;
			}
			while ( ftell( $f ) > 0 && $line_count >= 0 ) {
				// Figure out how far back we should jump
				$seek = min( ftell( $f ), $this->buffer_size );
				// Do the jump (backwards, relative to where we are)
				fseek( $f, - $seek, SEEK_CUR );
				// Read a chunk and prepend it to our output
				$output = ( $chunk = fread( $f, $seek ) ) . $output;
				// Jump back to where we started reading
				fseek( $f, - mb_strlen( $chunk, '8bit' ), SEEK_CUR );
				// Decrease our line counter
				$line_count -= substr_count( $chunk, "\n" );
			}
		}
		while ( $line_count ++ < 0 ) {
			// Find first newline and remove all text before that
			$output = substr( $output, strpos( $output, "\n" ) + 1 );
		}
		if ( 'gz' == substr( $log_file, - 2 ) ) {
			gzclose( $gz );
		} else {
			fclose( $f );
		}

		// Close file and return
		return $output;
	}

}

$viewer = new Log_Viewer();

$viewer->initialize();
$viewer->the_logs_html();
