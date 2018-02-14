<!DOCTYPE html> <html lang="en"> <head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/themes/default/style.min.css" />
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
			width: calc( 100% - 550px );
		}
		.log_code {
			font-size: 0;
		}
		.jstree-default .jstree-leaf .jstree-themeicon {
		    background-position: -102px -68px;
		}
		.hljs {
			height: calc( 100vh - 300px );
		}
		.accesslog ol {
			padding: 10px;
		    font-size: 14px;
		    line-height: 2em;
		    -webkit-margin-before: 0;
		    -webkit-margin-after: 0;
		    white-space: nowrap;
		}
	</style> </head> <body>
	<?php
		define( 'LOG_PATH', '/var/log/' );
		define('DISPLAY_REVERSE',true);
		define( 'DIRECTORY_SEPARATOR', '/' );
		function get_log_files( $dir, &$results = array() ) {
			$files = scandir( $dir );
			foreach ( $files as $key => $file ) {
				$path = realpath( $dir.DIRECTORY_SEPARATOR.$file );
				if ( ! is_dir( $path ) ) {
					$files_list[] = $path;
				} elseif ( $file != "." && $file != ".." ) {
					$dirs_list[] = $path;
				}
			}
			foreach ( $files_list as $path ) {
				preg_match( "/^.*\/(\S+)$/", $path, $matches );
				$name = $matches[ 1 ];
				$results[ $dir ][ $name ] = array( 'name' => $name, 'path' => $path );
			}
			foreach ( $dirs_list as $path ) {
				get_log_files( $path, $results );
			}
			return $results;
		}
		$files = get_log_files( LOG_PATH );
		ksort( $files );
		foreach ( $files as $dir_name => $file_array ) {
			ksort( $file_array );
			foreach ( $file_array as $key => $val ) {
				$default = $key;
				$log_files[ $key ] = $val;
			}
		}
		$log = (!isset($_GET['p'])) ? $default : urldecode($_GET['p']);
		$lines = (!isset($_GET['lines'])) ? '50': $_GET['lines'];
		//$file = $log_files[$log]['path'];
		$file = $log;
		$title = substr($log, (strrpos($log, '/')+1));
		function tail($filename, $lines = 50, $buffer = 4096) {
			$output = '';
			$chunk = '';
			if ( 'gz' == substr( $filename, -2 ) ) {
				$gz = gzopen( $filename, "rb" );
				gzseek( $gz, -1, SEEK_END );
				if ( gzread( $gz, 10000 ) != "\n" ) $lines -= 1;
				while( gztell( $gz ) > 0 && $lines >= 0 ) {
					// Figure out how far back we should jump
					$seek = min( gztell( $gz ), $buffer );
					// Do the jump (backwards, relative to where we are)
					gzseek( $gz, -$seek, SEEK_CUR );
					// Read a chunk and prepend it to our output
					$output = ( $chunk = gzread( $gz, $seek ) ) . $output;
					// Jump back to where we started reading
					gzseek( $gz, -mb_strlen( $chunk, '8bit' ), SEEK_CUR );
					// Decrease our line counter
					$lines -= substr_count( $chunk, "\n" );
				}
			} else {
				$f = fopen( $filename, "rb" );
				fseek( $f, -1, SEEK_END );
				if ( fread( $f, 1 ) != "\n" ) $lines -= 1;
				while( ftell( $f ) > 0 && $lines >= 0 ) {
					// Figure out how far back we should jump
					$seek = min( ftell( $f ), $buffer );
					// Do the jump (backwards, relative to where we are)
					fseek( $f, -$seek, SEEK_CUR );
					// Read a chunk and prepend it to our output
					$output = ( $chunk = fread( $f, $seek ) ) . $output;
					// Jump back to where we started reading
					fseek( $f, -mb_strlen( $chunk, '8bit' ), SEEK_CUR );
					// Decrease our line counter
					$lines -= substr_count( $chunk, "\n" );
				}
			}
			while( $lines++ < 0 ) {
				// Find first newline and remove all text before that
				$output = substr( $output, strpos( $output, "\n" ) + 1 );
			}
			if ( 'gz' == substr( $filename, -2 ) ) {
				gzclose( $gz );
			} else {
				fclose( $f );
			}
			// Close file and return
			return $output;
		}
	?>
	<div class="log_container">
		<div class="log_menu">
			<div id="tree_container" class="left-menu">
				<ul>
				<?php
					$current_file = isset($_GET['k']) ? $_GET['k'] : '';
					// Generate a menu
					foreach ( $files as $dir => $files_array ) {

						if ( isset( $files_array[$current_file] ) ) {
							echo '<li class="jstree-open">' . $dir;
						}else{
							echo '<li>' . $dir;
						}
						echo '<ul>';
						foreach( $files_array as $k => $f ) {
							if ( ! is_file( $f['path'] ) ){
								// File does not exist, remove it from the array, so it does not appear in the menu.
								unset( $files_array[ $k ] );
								continue;
							}
							$active = ( $f['path'] == $log ) ? 'class="jstree-clicked"' : '';
							echo '<li class="file"><a ' . $active . ' href="?p=' . urlencode( $f['path'] ) . '&lines=' . $lines . '&k='.$k.'">' . $f['name'] . 
'</a></li>';
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
				<h1><?php echo $title;?></h1>
				<h2>The last <?php echo $lines ?> lines of <?php echo $file ?>.</h2>
				<p>How many lines to display?</p>
				<form action="" method="get">
					<input type="hidden" name="p" value="<?php echo $log ?>">
					<select name="lines" onchange="this.form.submit()">
						<option value="10" <?php echo ($lines=='10') ? 'selected':'' ?>>10</option>
						<option value="50" <?php echo ($lines=='50') ? 'selected':'' ?>>50</option>
						<option value="100" <?php echo ($lines=='100') ? 'selected':'' ?>>100</option>
						<option value="500" <?php echo ($lines=='500') ? 'selected':'' ?>>500</option>
						<option value="1000" <?php echo ($lines=='1000') ? 'selected':'' ?>>1000</option>
					</select>
				</form>
			</div>
			<div class="log_code">
				<pre>
					<code class="accesslog">
						<ol reversed>
							<?php
								$output = tail( $file, $lines );
								$output = explode( "\n", $output );
								if ( DISPLAY_REVERSE ) {
									// Latest first
									$output = array_reverse( $output );
								}
								if ( '' == $output[0] ) {
									unset( $output[0] );
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
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.0/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.3/jstree.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.8.0/highlight.min.js"></script>
	<script src="http://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.4.0/languages/accesslog.min.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			$('#tree_container').jstree().bind('select_node.jstree', function(e, data) {
				var href = data.node.a_attr.href;
				document.location.href = href;
			});
		});
		hljs.initHighlightingOnLoad();
	</script> </body> </html>
