<?php
/*
Plugin Name: Script Compressor
Plugin URI: http://rp.exadge.com/2008/04/30/script-compressor/
Description: This plugin compresses javascript files and css files.
Version: 1.4.1
Author: Regen
Author URI: http://rp.exadge.com
*/

/**
 * @author Regen
 * @copyright Copyright (C) 2008 Regen
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link http://rp.exadge.com/2008/04/30/script-compressor/ Script Compressor
 * @access public
 */

/**
 * Script Compressor main class.
 * 
 */
class ScriptCompressor {
	/**
	 * Gettext domain.
	 * 
	 * @var string
	 */
	var $domain;
	
	/**
	 * Pluguin name.
	 * 
	 * @var string
	 */
	var $plugin_name;
	
	/**
	 * Path of this plugin.
	 * 
	 * @var string
	 */
	var $plugin_path;
	
	/**
	 * Pluguin options.
	 * 
	 * @var array
	 */
	var $options;
	
	/**
	 * Initialize ScriptCompressor.
	 * 
	 */
	function ScriptCompressor() {
		$this->domain = 'script-compressor';
		$this->plugin_name = 'script-compressor';
		if (defined('WP_PLUGIN_URL')) {
			$this->plugin_path = WP_PLUGIN_URL . '/' . $this->plugin_name;
			load_plugin_textdomain($this->domain, str_replace(ABSPATH, '', WP_PLUGIN_DIR) . '/' . $this->plugin_name);
		} else {
			$this->plugin_path = get_option('siteurl') . '/' . PLUGINDIR . '/'.$this->plugin_name;
			load_plugin_textdomain($this->domain, PLUGINDIR . '/' . $this->plugin_name);
		}
		
		add_action('admin_menu', array(&$this, 'regist_menu'));
		
		register_activation_hook(__FILE__, array(&$this, 'active'));
		register_deactivation_hook(__FILE__, array(&$this, 'deactive'));
		
		$this->get_option();
		
		$this->set_hooks();
	}
	
	/**
	 * Set WP hooks.
	 * 
	 */
	function set_hooks() {
		if ($this->options['sc_comp']['auto_js_comp'] || ($this->options['sc_comp']['css_comp'] && $this->options['css_method'] == 'composed'))
			add_action('get_header', array(&$this, 'regist_header_comp'));
		else
			remove_action('get_header', array(&$this, 'regist_header_comp'));
		
		if ($this->options['sc_comp']['css_comp'])
			add_filter('mod_rewrite_rules', array(&$this, 'rewrite_sc'));
		else
			remove_filter('mod_rewrite_rules', array(&$this, 'rewrite_sc'));
	}
	
	/**
	 * WP activation hook.
	 * 
	 */
	function active() {
		global $wp_rewrite;
		
		$wp_rewrite->flush_rules();
	}
	
	/**
	 * WP deactivation hook.
	 * 
	 */
	function deactive() {
		global $wp_rewrite;
		
		remove_action('get_header', array(&$this, 'regist_header_comp'));
		remove_filter('mod_rewrite_rules', array(&$this, 'rewrite_sc'));
		
		$wp_rewrite->flush_rules();
	}
	
	/**
	 * Get pluguin options.
	 * 
	 */
	function get_option() {
		$this->options = (array)get_option('scriptcomp_option');
		
		/* {{{ Set default value */
		if (!isset($this->options['sc_comp']['auto_js_comp'])) {
			$this->options['sc_comp']['auto_js_comp'] = true;
		}
		if (!isset($this->options['sc_comp']['css_comp'])) {
			$this->options['sc_comp']['css_comp'] = true;
		}
		if (!isset($this->options['css_method'])) {
			$this->options['css_method'] = 'respective';
		}
		if (!isset($this->options['gzip'])) {
			$this->options['gzip'] = false;
		}
		/* }}} */
	}
	
	/**
	 * Save pluguin options.
	 * 
	 */
	function update_option() {
		update_option('scriptcomp_option', $this->options);
	}
	
	/**
	 * Delete pluguin options.
	 * 
	 */
	function delete_option() {
		$this->options = array();
		delete_option('scriptcomp_option');
	}
	
	/**
	 * Start javascript compression.
	 * 
	 */
	function comp_start() {
		ob_start(array(&$this, 'compress'));
	}
	
	/**
	 * End javascript compression.
	 * 
	 */
	function comp_end() {
		ob_end_flush();
	}
	
	/**
	 * Compress content.
	 *
	 * @param string $content Compression target.
	 * @return string Compressed content.
	 */
	function compress($content) {
		$regex_js = '%<script\s.*src=(?:"|\')(?:(?!http)|(?:https?://' . preg_quote($_SERVER['HTTP_HOST']) . '))/?(.+?\.js(?:\?.*)?)(?:"|\').*>\s*</script>(?:\r?\n)*%m';
		$regex_css = '%<link\s.*href=(?:"|\')(?:(?!http)|(?:https?://' . preg_quote($_SERVER['HTTP_HOST']) . '))/?(.+?\.css(?:\?.*)?)(?:"|\').*/?>(?:\r?\n)*%m';
		
		$output = '';
		
		if ($this->options['sc_comp']['auto_js_comp']) {
			if (preg_match_all($regex_js, $content, $matches)) {
				$jsfiles = $this->buildURL($matches[1]);
				
				$content = preg_replace($regex_js, '', $content);
				
				$output .= '<script type="text/javascript" src="' . $jsfiles . '"></script>' . "\n";
			}
		}
		if ($this->options['sc_comp']['css_comp'] && $this->options['css_method'] == 'composed') {
			if (preg_match_all($regex_css, $content, $matches)) {
				$cssfiles = $this->buildURL($matches[1]);
				
				$content = preg_replace($regex_css, '', $content);
				
				$output .= '<link rel="stylesheet" href="' . $cssfiles . '" type="text/css" media="all" />' . "\n";
			}
		}
		
		return $content . $output;
	}
	
	/**
	 * Build URL for compression.
	 *
	 * @param array $urls matches.
	 * @return string URL.
	 * @see ScriptCompressor::compress()
	 */
	function buildURL($urls) {
		$url = $this->plugin_path . '/jscsscomp.php?q=';
		foreach ($urls as $path) {
			$url .= $path . ',';
		}
		$url = substr($url, 0, -1);
		return $url;
	}
	
	/**
	 * Get script paths from URI.
	 *
	 * @return array Local script paths.
	 */
	function getScripts() {
		if (strpos($_SERVER['SCRIPT_URI'], $this->plugin_path) === false)
			$files = array($_SERVER['REQUEST_URI']);
		else
			$files =  explode(',', str_replace(str_replace(get_option('home'), '', $this->plugin_path . '/jscsscomp.php?q='), '', $_SERVER['REQUEST_URI']));
		
		array_walk($files,
			create_function('&$file',
				'$file = str_replace(\'../\', \'\', $file);$file = $_SERVER[\'DOCUMENT_ROOT\'] . ($file[0] == \'/\' ? \'\' : \'/\') . $file;'
			)
		);
		
		return $files;
	}
	
	/**
	 * Regist WP_head hooks.
	 *
	 */
	function regist_header_comp() {
		global $wp_filter;
		
		$max_priority = max(array_keys($wp_filter['wp_head'])) + 1;
		
		add_action('wp_head', array(&$this, 'comp_start'), 0);
		add_action('wp_head', array(&$this, 'comp_end'), $max_priority);
	}
	
	/**
	 * WP hook for rewrite.
	 *
	 * @param string $rewrite Rewrite data.
	 * @return string Rewrite data with rules of this pluguin.
	 */
	function rewrite_sc($rewrite) {
		$plugin_path_rewrite = str_replace(get_option('home'), '', get_option('siteurl')) . '/wp-content/plugins/' . $this->plugin_name;
		$url = $plugin_path_rewrite . '/jscsscomp.php';
		$rule = '';
		
		$rule .= 'RewriteEngine on' . "\n";
		if (!empty($this->options['rewritecond'])) $rule .= $this->options['rewritecond'] . "\n";
		$rule .= 'RewriteRule ^(.*)\.css ' . $url . '?q=$1.css [NC,T=text/css,L]' . "\n";
		
		return $rule . $rewrite;
	}
	
	/**
	 * Regist this plugin to WP menu.
	 *
	 */
	function regist_menu() {
		 add_options_page(__('Script Compressor Options', $this->domain), __('Script Compressor', $this->domain), 8, 'sc_option_page', array(&$this, 'sc_options_page'));
	}

	/**
	 * Pluguin option page.
	 *
	 */
	function sc_options_page() {
		global $wp_rewrite;
		
		if (isset($_POST['action'])) {
			switch ($_POST['action']) {
				case 'update':
					$this->options['sc_comp'] = array();
					foreach ($_POST['sc_comp'] as $set) {
						$this->options['sc_comp'][$set] = true;
					}
					$this->options['css_method'] = $_POST['css_method'];
					$this->options['rewritecond'] = str_replace("\r\n", "\n", $_POST['rewritecond']);
					$this->options['gzip'] = isset($_POST['gzip']);
					
					$this->update_option();
					
					$this->set_hooks();
					$wp_rewrite->flush_rules();
					
					echo '<div class="updated"><p><strong>' . __('Options saved', $this->domain) . '</strong></p></div>';
					break;
				case 'remove':
					$this->delete_option();
					$this->set_hooks();
					$wp_rewrite->flush_rules();
					
					echo '<div class="updated"><p><strong>' . __('Options removed', $this->domain) . '</strong></p></div>';
					break;
			}
		}
		
		$value = array();
		$checked = 'checked="checked" ';
		if (isset($this->options['sc_comp'])) {
			foreach ($this->options['sc_comp'] as $col => $whether) {
				$value[$col] = $whether ? $checked : '';
			}
		}
		switch ($this->options['css_method']) {
			case 'respective':
				$value['css_method']['respective'] = $checked;
				$value['css_method']['composed'] = '';
				break;
			case 'composed':
				$value['css_method']['respective'] = '';
				$value['css_method']['composed'] = $checked;
				break;
		}
		$value['gzip'] = $this->options['gzip'] ? $checked : '';
		?>

<div class="wrap">
<h2><?php _e('Script Compressor Options', $this->domain) ?></h2>
<form action="?page=sc_option_page" method="post" id="sc_option">
<table class="form-table">
<tbody>
<tr valign="top">
<th scope="row"><?php _e('Auto-compression', $this->domain) ?></th>
<td>
	<p>
		<label><input type="checkbox" name="sc_comp[]" value="auto_js_comp" <?php echo $value['auto_js_comp'] ?>/> <?php _e('Javascript compression for wp_head()', $this->domain) ?></label>
	</p>
	<p>
		<label><input type="checkbox" name="sc_comp[]" value="css_comp" <?php echo $value['css_comp'] ?>/> <?php _e('CSS compression', $this->domain) ?></label>
	</p>
</td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('CSS compression method', $this->domain) ?></th>
<td>
	<p>
		<label><input type="radio" name="css_method" value="respective" <?php echo $value['css_method']['respective'] ?>/> <?php _e('Respective', $this->domain) ?></label><br />
		<?php _e('This method compresses <strong>respective</strong> CSS files (former method). This uses .htaccess and mod_rewrite.', $this->domain) ?>
	</p>
	<p>
		<label><input type="radio" name="css_method" value="composed" <?php echo $value['css_method']['composed'] ?>/> <?php _e('Composed', $this->domain) ?></label><br />
		<?php _e('This method compresses <strong>composed</strong> CSS files in wp_head(). The frequency of the HTTP request is less than "Respective" but there is a possibility that paths of images in CSS files break and that The media type becomes ineffective.', $this->domain) ?>
	</p>
</td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('CSS compression condition (mod_rewrite)', $this->domain) ?></th>
<td>
	<textarea class="code" rows="3" cols="40" wrap="off" name="rewritecond"><?php echo $this->options['rewritecond'] ?></textarea>
	<p><?php _e('This text is inserted in the upper part of RewriteRule added by this plugin in your .htaccess. Please see <a href="http://httpd.apache.org/docs/2.0/mod/mod_rewrite.html#rewritecond">RewriteCond doc</a>.', $this->domain) ?></p>
	<p><?php _e('Example: <code>RewriteCond %{REQUEST_URI} !.*wp-admin.*</code>', $this->domain) ?></p>
</td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('Gzip compression', $this->domain) ?></th>
<td>
	<p>
		<label><input type="checkbox" name="gzip" value="gzip" <?php echo $value['gzip'] ?>/> <?php _e('Use gzip compression for the cache and the output.', $this->domain) ?></label>
	</p>
</td>
</tr>
</tbody></table>
<p class="submit">
<input type="hidden" name="action" value="update" />
<input type="submit" value="<?php _e('Update Options', $this->domain) ?>" name="submit"/>
</p>
</form>
<h2><?php _e('Instructions', $this->domain) ?></h2>
<h3><?php _e('Additional template tags', $this->domain) ?></h3>
<p><?php _e('Javascripts and CSS between <code>&lt;?php sc_comp_start() ?&gt;</code> and <code>&lt;?php sc_comp_end() ?&gt;</code> will be compressed by this plugin.', $this->domain) ?></p>
<p><?php _e('e.g.', $this->domain) ?><br /><code style="display: block; padding: 6px; background-color: #eeeeee; border: #dfdfdf solid 1px;"><?php _e('&lt;?php sc_comp_start() ?&gt;<br />&lt;script type="text/javascript" src="foo.js"&gt;&lt;/script&gt;<br />&lt;script type="text/javascript" src="bar.js"&gt;&lt;/script&gt;<br />&lt;?php sc_comp_end() ?&gt;', $this->domain) ?></code></p>
<p><?php _e('If you check "Javascript compression for headers", the contents of wp_head() will be compressed automatically.', $this->domain) ?></p>
<h2><?php _e('Notes', $this->domain) ?></h2>
<ul>
<li><?php _e('This plugin makes caches in the compression progress.', $this->domain) ?></li>
<li><?php _e('Only files located in the same server as your WordPress can be compressed.', $this->domain) ?></li>
<li><?php _e('The extensions of Javascript and CSS should be .js and .css respectively.', $this->domain) ?></li>
</ul>
<h2><?php _e('Remove options', $this->domain) ?></h2>
<p><?php _e('You can remove the above options from the database.', $this->domain) ?></p>
<form action="?page=sc_option_page" method="post" id="sc_remove_option">
<p>
<input type="hidden" name="action" value="remove" />
<input id="sc_remove_bt" type="submit" class="button" value="<?php _e('Remove options', $this->domain) ?>" name="submit" />
</p>
</form>
</div>

		<?php
	}
}

$scriptcomp = &new ScriptCompressor();

/**
 * Start javascript compression.
 */
function sc_comp_start() {
	global $scriptcomp;
	
	$scriptcomp->comp_start();
}

/**
 * End javascript compression.
 */
function sc_comp_end() {
	global $scriptcomp;
	
	$scriptcomp->comp_end();
}