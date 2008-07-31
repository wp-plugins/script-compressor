<?php

/**
 * Javascript and CSS compressor class.
 *
 */
class Compressor {
	/**
	 * Target files
	 *
	 * @var array
	 */
	var $files;
	
	/**
	 * Target type (js or css)
	 *
	 * @var string
	 */
	var $type;
	
	/**
	 * Content charset.
	 *
	 * @var string
	 */
	var $charset;
	
	/**
	 * Whether compress by gzip.
	 *
	 * @var bool
	 */
	var $gzip;
	
	/**
	 * Whether replace paths
	 *
	 * @var bool
	 */
	var $replacePath;
	
	/**
	 * Cache directory.
	 *
	 * @var string
	 */
	var $cacheDir;
	
	/**
	 * Headers to send.
	 *
	 * @var array
	 */
	var $headers;
	
	/**
	 * Time that target was modified.
	 *
	 * @var integer
	 */
	var $lastModified;
	
	/**
	 * Initialize Compressor class.
	 *
	 * @param array|string $files Target file(s).
	 * @param string $charset Charset in content-type.
	 * @param bool $gzip Whether compress by gzip
	 * @param bool $replacePath Whether replace paths.
	 * @param string $cache Cache directory.
	 * @return Compressor
	 */
	function Compressor($files, $charset = 'utf-8', $gzip = false, $replacePath = false, $cache = 'cache') {
		$this->files = is_array($files) ? $files : array($files);
		$this->charset = $charset;
		$this->replacePath = $replacePath;
		$this->cacheDir = $cache;
		
		$this->setType();
		
		/* {{{ Check HTTP_IF_MODIFIED_SINCE */
		$this->lastModified = $this->getLastModified();
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
			if (!headers_sent() && $this->lastModified <= strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
				$this->headers['HTTP'] = '304 Not Modified';
			}
		}
		/* }}} */
		
		/* {{{ Check gzip */
		$this->gzip = false;
		if ($gzip && function_exists('gzencode') && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strrpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false){
			$this->gzip = true;
			$enc = in_array('x-gzip', explode(',', strtolower(str_replace(' ', '', $_SERVER['HTTP_ACCEPT_ENCODING'])))) ? 'x-gzip' : 'gzip';
			$this->headers['Content-Encoding'] = $enc;
		}
		if ($this->gzip) {
			ini_set("zlib.output_compression", "Off");
			$this->headers['Vary'] = 'Accept-Encoding';
		}
		/* }}} */
		
		$this->headers['Cache-Control'] = 'must-revalidate';
		
		if (count($this->files) == 0) {
			$this->headers['HTTP'] = '404 Not Found';
		}
	}
	
	/**
	 * Get target type.
	 *
	 */
	function setType() {
		$js_files = $css_files = 0;
		foreach ($this->files as $id => $file) {
			if (!preg_match('/(.+\.)(js|css)(?:\?.*)?$/iD', $file, $matches)) {
				unset($this->files[$id]);
				continue;
			}
			
			$this->files[$id] = $matches[1] . $matches[2];
			
			if(strtolower($matches[2]) == 'js'){
				++$js_files;
			} else if (strtolower($matches[2]) == 'css'){
				++$css_files;
			}
		}
		
		if ($js_files > 0 && $css_files > 0) {
			$this->type = 'plain';
			$this->headers['Content-Type'] = 'text/plain; charset=' . $this->charset;
		} else if ($js_files > 0) {
			$this->type = 'js';
			$this->headers['Content-Type'] = 'text/javascript; charset=' . $this->charset;
		} else if ($css_files > 0) {
			$this->type = 'css';
			$this->headers['Content-Type'] = 'text/css; charset=' . $this->charset;
		} else if ($js_files + $css_files == 0) {
			$this->type = 'none';
		}
	}
	
	/**
	 * Get time that target was modified.
	 *
	 * @return integer File time.
	 */
	function getLastModified() {
		$lastModified = 0;
		foreach($this->files as $id => $file){
			if (file_exists($file)) {
				$fileLmt = @filemtime($file);
				if($fileLmt > $lastModified){
					$lastModified = $fileLmt;
				}
			} else {
				unset($this->files[$id]);
			}
		}
		
		$this->headers['Last-Modified'] = gmdate('D, d M Y H:i:s', $lastModified) . ' GMT';
		
		return $lastModified;
	}
	
	/**
	 * Get target hash.
	 *
	 * @return string Taget hash (md5).
	 */
	function getHash() {
		return md5(implode(',', $this->files));
	}
	
	/**
	 * Get composed target.
	 *
	 * @return string Composed content.
	 */
	function getComposed(){
		if ($this->type == 'none') return '';
		
		$content = '';
		foreach($this->files as $file){
			$file_content = file_get_contents($file) . "\n\n";
			if ($this->type == 'css' && $this->replacePath) {
				if (preg_match_all('%url\((?:"|\')?(.+?)(?:"|\')?\)%i', $file_content, $matches, PREG_SET_ORDER)) {
					$from = $to = array();
					
					foreach ($matches as $val) {
						if (!preg_match('%(http://|data:)%', $val[1], $protocol)) {
							$from[] = $val[0];
							$to[] =
								'url("' .
								(isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] .
								str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname($file)) . '/' .
								preg_replace('%^\.?/%', '', $val[1]) .
								'")';
						}
					}
					$file_content = str_replace($from, $to, $file_content);
				}
			}
			$content .= $file_content;
		}
		
		return $content;
	}
	
	/**
	 * Send required headers.
	 *
	 */
	function sendHeader() {
		foreach ($this->headers as $header => $var) {
			if ($header == 'HTTP') {
				header('HTTP/1.1 ' . $var);
			} else {
				header($header . ': ' . $var);
			}
		}
	}
	
	/**
	 * Compress target.
	 *
	 * @return string Compressed content.
	 */
	function getContent() {
		if ($this->type == 'none' || $this->headers['HTTP'] == '304 Not Modified') return '';
		
		$cache_file = $this->cacheDir . '/' . $this->getHash() . '-' . $this->lastModified . ($this->gzip ? '.gz' : '');
		
		if (is_file($cache_file) && is_readable($cache_file)) {
			$content = file_get_contents($cache_file);
		} else {
			$content = $this->getComposed();
			
			switch ($this->type) {
				case 'js':
					require_once 'jsmin.php';
					$content = JSMin::minify($content);
					break;
				case 'css':
					$content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
					$content = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $content);
					break;
			}
			
			if ($this->gzip) $content = gzencode($content, 9, FORCE_GZIP);
			
			$fp = @fopen($cache_file, "wb");
			if ($fp) {
				fwrite($fp, $content);
				fclose($fp);
			}
		}
		
		return $content;
	}
}
?>