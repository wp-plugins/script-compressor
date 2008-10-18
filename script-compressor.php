<?php
/*
Plugin Name: Script Compressor
Plugin URI: http://rp.exadge.com/2008/04/30/script-compressor/
Description: This plugin compresses javascript files and css files.
Version: 1.5
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
		if (!isset($this->options['sc_comp'])) {
			$this->options['sc_comp'] = array();
			$this->options['sc_comp']['auto_js_comp'] = true;
			$this->options['sc_comp']['css_comp'] = true;
		} else if (!isset($this->options['sc_comp']['auto_js_comp'])) {
			$this->options['sc_comp']['auto_js_comp'] = false;
		} else if (!isset($this->options['sc_comp']['css_comp'])) {
			$this->options['sc_comp']['css_comp'] = false;
		}

		$this->options += array(
			'jspos' => array(),
			'css_method' => 'respective',
			'gzip' => false,
			'cache' => 'cache'
		);
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
		$regex_js = '%<script\s.*src=(?:"|\')(?:(?!http)|(?:https?://' . preg_quote($_SERVER['HTTP_HOST'], '%') . '))/?(.+?\.js(?:\?.*)?)(?:"|\').*>\s*</script>(?:\r?\n)*%m';
		$regex_css = '%<link\s.*href=(?:"|\')(?:(?!http)|(?:https?://' . preg_quote($_SERVER['HTTP_HOST'], '%') . '))/?(.+?\.css(?:\?.*)?)(?:"|\').*/?>(?:\r?\n)*%m';

		$output_bef = '';
		$output_aft = '';

		if ($this->options['sc_comp']['auto_js_comp']) {
			if (preg_match_all($regex_js, $content, $matches)) {
				list($befjs, $aftjs) = $this->buildJsURL($matches[1]);

				$content = preg_replace($regex_js, '', $content);

				if (strlen($befjs) > 0) {
					$output_bef .= '<script type="text/javascript" src="' . $befjs . '"></script>' . "\n";
				}
				if (strlen($aftjs) > 0) {
					$output_aft .= '<script type="text/javascript" src="' . $aftjs . '"></script>' . "\n";
				}
			}
		}
		if ($this->options['sc_comp']['css_comp'] && $this->options['css_method'] == 'composed') {
			if (preg_match_all($regex_css, $content, $matches)) {
				$cssfiles = $this->buildCSSURL($matches[1]);

				$content = preg_replace($regex_css, '', $content);

				if (strlen($cssfiles) > 0) {
					$output_aft .= '<link rel="stylesheet" href="' . $cssfiles . '" type="text/css" media="all" />' . "\n";
				}
			}
		}

		return $output_bef . $content . $output_aft;
	}

	/**
	 * Build URL for js compression.
	 *
	 * @param array $urls matches.
	 * @return array URL.
	 * @see ScriptCompressor::compress()
	 */
	function buildJsURL($urls) {
		$regex = '/';
		foreach ($this->options['jspos'] as $js) {
			$regex .= '(' . preg_quote($js) . ')|';
		}
		$regex = substr($regex, 0, -1) . '/i';

		$url = $this->plugin_path . '/jscsscomp.php?q=';
		$before = $after = $url;
		foreach ($urls as $path) {
			if (preg_match($regex, $path)) {
				$before .= $path . ',';
			} else {
				$after .= $path . ',';
			}
		}
		$before = ($before == $url) ? '' : substr($before, 0, -1);
		$after = ($after == $url) ? '' : substr($after, 0, -1);

		return array($before, $after);
	}

	/**
	 * Build URL for css compression.
	 *
	 * @param array $urls matches.
	 * @return string URL.
	 * @see ScriptCompressor::compress()
	 */
	function buildCSSURL($urls) {
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
		$files = explode(',', preg_replace('%.+/jscsscomp\.php\?q=%', '', $_SERVER['REQUEST_URI']));

		foreach ($files as $id => $file) {
			$file = str_replace('../', '', $file);
			$file = rtrim($_SERVER['DOCUMENT_ROOT'], '\\/') . '/' . $file;
			$files[$id] = $file;
		}

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
		$plugin_path_rewrite = preg_replace('%https?://' . preg_quote($_SERVER['HTTP_HOST']) . '%', '', get_option('siteurl')) . '/wp-content/plugins/' . $this->plugin_name;
		$url = $plugin_path_rewrite . '/jscsscomp.php';
		$rule = '';

		$rule .= 'RewriteEngine on' . "\n";
		if (!empty($this->options['rewritecond'])) $rule .= $this->options['rewritecond'] . "\n";
		$rule .= 'RewriteRule ^(.*)\.css$ ' . $url . '?q=$1.css [NC,T=text/css,L]' . "\n";

		return $rule . $rewrite;
	}

	/**
	 * Regist this plugin to WP menu.
	 *
	 */
	function regist_menu() {
		add_options_page(__('Script Compressor Options', $this->domain), __('Script Compressor', $this->domain), 8, 'sc_option_page', array(&$this, 'sc_options_page'));
		add_filter('plugin_action_links', array(&$this, 'add_action_links'), 10, 2);
	}

	/**
	 * Add settings link to pluguin menu.
	 *
	 * @param array $links action links.
	 * @return array Links added settings link.
	 */
	function add_action_links($links, $file){
		if ($file == $this->plugin_name . '/' . basename(__FILE__)) {
			$settings_link = '<a href="options-general.php?page=sc_option_page">' . __('Settings', $this->domain) . '</a>';
			$links = array_merge(array($settings_link), $links);
		}
		return $links;
	}

	/**
	 * Pluguin option page.
	 *
	 */
	function sc_options_page() {
		global $wp_rewrite;

		$cache_dir = dirname(__FILE__) . '/' . $this->options['cache'];
		if (!is_writable($cache_dir)) {
			echo '<div class="error"><p>' . sprintf(__('Give the write permission to %s.', $this->domain), $cache_dir) . '</p></div>';
		}

		if (isset($_POST['action'])) {
			switch ($_POST['action']) {
				case 'update':
					$this->options['sc_comp'] = array();
					if (isset($_POST['sc_comp'])) {
						foreach ($_POST['sc_comp'] as $set) {
							$this->options['sc_comp'][$set] = true;
						}
					}
					$this->options['jspos'] = explode("\n", str_replace(array("\r\n", "\n\n"), array("\n", ''), $_POST['jspos']));
					$this->options['css_method'] = $_POST['css_method'];
					$this->options['rewritecond'] = str_replace("\r\n", "\n", $_POST['rewritecond']);
					$this->options['gzip'] = isset($_POST['gzip']);

					$this->update_option();

					$this->set_hooks();

					$wp_rewrite->flush_rules();
					if (is_writable(get_home_path() . '.htaccess')) {
						echo '<div class="updated"><p><strong>' . __('Options saved.', $this->domain) . '</strong></p></div>';
					} else {
						echo '<div class="updated"><p><strong>' . __('Options saved.', $this->domain) . ' ' . __('Your .htaccess is not writable so you may need to re-save your <a href="options-permalink.php">permalink settings</a> manually.', $this->domain) . '</strong></p></div>';
					}
					break;
				case 'remove':
					$this->delete_option();
					$this->set_hooks();

					$wp_rewrite->flush_rules();
					if (is_writable(get_home_path() . '.htaccess')) {
						echo '<div class="updated"><p><strong>' . __('Options removed.', $this->domain) . '</strong></p></div>';
					} else {
						echo '<div class="updated"><p><strong>' . __('Options removed.', $this->domain) . ' ' . __('Your .htaccess is not writable so you may need to re-save your <a href="options-permalink.php">permalink settings</a> manually.', $this->domain) . '</strong></p></div>';
					}
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
		$value['jspos'] = implode("\n", $this->options['jspos']);
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
<th scope="row"><?php _e('Position of Javascripts', $this->domain) ?></th>
<td>
	<textarea class="code" rows="3" cols="40" wrap="off" name="jspos"><?php echo $value['jspos'] ?></textarea>
	<p><?php _e('This plugin will output compressed Javascripts after the header. However some scripts need to be loaded before other scripts. So you can input a part of script URL that need to be loaded most first (one a line).', $this->domain) ?></p>
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
<br />
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
<br />
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