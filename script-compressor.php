<?
/*
Plugin Name: Script Compressor
Plugin URI: http://rp.exadge.com/2008/04/30/script-compressor/
Description: This plugin compresses javascript files and css files.
Version: 1.2
Author: Regen
Author URI: http://rp.exadge.com
*/

/*
    Copyright (C) 2008 Regen
    This program is licensed under the GNU GPL.
*/

class ScriptCompressor {
	var $domain, $plugin_name, $plugin_path, $options;
	
	function ScriptCompressor() {
		$this->domain = 'script-compressor';
		$this->plugin_name = 'script-compressor';
		$this->plugin_path = get_option('siteurl').'/wp-content/plugins/'.$this->plugin_name;
		
		load_plugin_textdomain($this->domain, 'wp-content/plugins/'.$this->plugin_name);
		
		add_action('admin_menu', array(&$this, 'regist_menu'));
		
		register_activation_hook(__FILE__, array(&$this, 'active'));
		register_deactivation_hook(__FILE__, array(&$this, 'deactive'));
		
		$this->get_sc_option();
		
		$this->set_hooks();
	}
	
	function set_hooks() {
		if (isset($this->options['sc_comp']['auto_js_comp']))
			add_action('get_header', array(&$this, 'regist_header_comp'));
		else
			remove_action('get_header', array(&$this, 'regist_header_comp'));
		
		if (isset($this->options['sc_comp']['css_comp']))
			add_filter('mod_rewrite_rules', array(&$this, 'regist_rewrite'));
		else
			remove_filter('mod_rewrite_rules', array(&$this, 'regist_rewrite'));
	}
	
	function active() {
		global $wp_rewrite;
		
		$wp_rewrite->flush_rules();
	}
	
	function deactive() {
		global $wp_rewrite;
		
		remove_action('get_header', array(&$this, 'regist_header_comp'));
		remove_filter('mod_rewrite_rules', array(&$this, 'regist_rewrite'));
		
		$wp_rewrite->flush_rules();
	}
	
	function get_sc_option() {
		$this->options = (array)get_option('scriptcomp_option');
	}
	
	function update_sc_option() {
		update_option('scriptcomp_option', $this->options);
	}
	
	function delete_sc_option() {
		$this->options = array();
		delete_option('scriptcomp_option');
	}
	
	function comp_start() {
		ob_start(array(&$this, 'do_compress'));
	}
	
	function comp_end() {
		ob_end_flush();
	}
	
	function do_compress($header_data) {
		$regex_js = '/<script\s.*src=(?:"|\')(.+?\.js(?:\?.*)?)(?:"|\').*>.*<\/script>(\r?\n)*/m';
		
		$output = '';
		
		if (preg_match_all($regex_js, $header_data, $matches)) {
			$jsfiles = $this->buildURL($matches[1]);
			
			$header_data = preg_replace($regex_js, '', $header_data);
			
			$output .= "\n" . '<script type="text/javascript" src="' . $jsfiles . '"></script>' . "\n";
		}
		
		return $header_data.$output;
	}
	
	function buildURL($urls) {
		$url = $this->plugin_path . '/jscsscomp/jscsscomp.php?q=';
		foreach ($urls as $path) {
			$path = str_replace(get_bloginfo('home') . '/', '', $path);
			$url .= $path . ',';
		}
		$url = substr($url, 0, -1);
		return $url;
	}
	
	function regist_header_comp() {
		global $wp_filter;
		
		$max_priority = max(array_keys($wp_filter['wp_head'])) + 1;
		
		add_action('wp_head', array(&$this, 'comp_start'), 0);
		add_action('wp_head', array(&$this, 'comp_end'), $max_priority);
	}
	
	function regist_rewrite($rewrite) {
		$plugin_path_rewrite = str_replace(get_option('home'), '', get_option('siteurl')) . '/wp-content/plugins/' . $this->plugin_name;
		$url = $plugin_path_rewrite . '/jscsscomp/jscsscomp.php';
		
		$rule = 'RewriteEngine on' . "\n";
		if (!empty($this->options['rewritecond'])) $rule .= $this->options['rewritecond'] . "\n";
		$rule .= 'RewriteRule ^(.*)\.css ' . $url . '?q=$1.css [NC,L]' . "\n";
		
		return $rule . $rewrite;
	}
	
	function regist_menu() {
		 add_options_page(__('Script Compressor Options', $this->domain), __('Script Compressor', $this->domain), 8, 'sc_option_page', array(&$this, 'sc_options_page'));
	}

	function sc_options_page() {
		global $wp_rewrite;
		
		if (isset($_POST['action'])) {
			switch ($_POST['action']) {
				case 'update':
					$this->options['sc_comp'] = array();
					foreach ($_POST['sc_comp'] as $set)
						$this->options['sc_comp'][$set] = true;
					$this->options['charaset'] = $_POST['charaset'];
					$this->options['rewritecond'] = $_POST['rewritecond'];
					
					$this->update_sc_option();
					
					$this->set_hooks();
					$wp_rewrite->flush_rules();
					
					echo '<div class="updated"><p><strong>' . __('Options saved', $this->domain) . '</strong></p></div>';
					break;
				case 'remove':
					$this->delete_sc_option();
					$this->set_hooks();
					$wp_rewrite->flush_rules();
					
					echo '<div class="updated"><p><strong>' . __('Options removed', $this->domain) . '</strong></p></div>';
					break;
			}
		}
		
		$value = array();
		if (isset($this->options['sc_comp'])) {
			foreach ($this->options['sc_comp'] as $col => $whether)
				$value[$col] = $whether ? 'checked="checked" ' : '';
		}
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
		<label><input type="checkbox" name="sc_comp[]" value="auto_js_comp" <?php echo $value['auto_js_comp'] ?>/> <?php _e('Javascript compression for headers', $this->domain) ?></label>
	</p>
	<p>
		<label><input type="checkbox" name="sc_comp[]" value="css_comp" <?php echo $value['css_comp'] ?>/> <?php _e('CSS compression', $this->domain) ?></label>
	</p>
</td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('Script charaset', $this->domain) ?></th>
<td>
	<input type="text" name="charaset" value="<?php echo $this->options['charaset'] ?>" size="20" class="code" /><br />
	<?php _e('Default: utf-8', $this->domain) ?>
</td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('CSS compression condition', $this->domain) ?></th>
<td>
	<textarea class="code" rows="3" cols="40" name="rewritecond"><?php echo $this->options['rewritecond'] ?></textarea>
	<p><?php _e('This text is inserted in the upper part of RewriteRule added by this plugin in your .htaccess. Please see <a href="http://httpd.apache.org/docs/2.0/mod/mod_rewrite.html#rewritecond">RewriteCond doc</a>.', $this->domain) ?></p>
	<p><?php _e('Example: <code>RewriteCond %{REQUEST_URI} !.*wp-admin.*</code>', $this->domain) ?></p>
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
<p><?php _e('Javascripts between <code>&lt;?php function_exists(\'sc_comp_start\') or sc_comp_start() ?&gt;</code> and <code>&lt;?php function_exists(\'sc_comp_end\') or sc_comp_end() ?&gt;</code> will be compressed by this plugin.', $this->domain) ?></p>
<p><?php _e('e.g.', $this->domain) ?><br /><code style="display: block; padding: 6px; background-color: #eeeeee; border: #dfdfdf solid 1px;"><?php _e('&lt;?php function_exists(\'sc_comp_start\') or sc_comp_start() ?&gt;<br />&lt;script type="text/javascript" src="foo.js"&gt;&lt;/script&gt;<br />&lt;script type="text/javascript" src="bar.js"&gt;&lt;/script&gt;<br />&lt;?php function_exists(\'sc_comp_end\') or sc_comp_end() ?&gt;', $this->domain) ?></code></p>
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

function sc_comp_start() {
	global $scriptcomp;
	
	$scriptcomp->comp_start();
}

function sc_comp_end() {
	global $scriptcomp;
	
	$scriptcomp->comp_end();
}

$scriptcomp = &new ScriptCompressor();

?>