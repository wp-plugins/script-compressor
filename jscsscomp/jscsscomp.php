<?php
/**
 * jscsscomp - JavaScript and CSS files compressor 
 * Copyright (C) 2007 Maxim Martynyuk
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301,
 * USA.
 * 
 * @author Maxim Martynyuk <flashkot@mail.ru>
 * @version $Id: jscsscomp.php 21 2007-04-28 10:31:36Z flashkot $
 */

require_once('../../../../wp-config.php');
global $scriptcomp;

//error_reporting(E_ALL);
//ini_set('display_errors', true);

define('CACHE_DIR' , realpath('cache/'));

if ($scriptcomp->options['charaset'])
	define('FILES_ENCODING' , $scriptcomp->options['charaset']);
else
	define('FILES_ENCODING' , 'utf-8');

// Disable zlib compression, if present, for duration of this script.  
// So we don't double gzip 
ini_set("zlib.output_compression", "Off");

// Set the cache control header
// http 1.1 browsers MUST revalidate -- always
header("Cache-Control: must-revalidate");
header('Vary: Accept-Encoding');

// this is filetypes counters. used to send correct content-type header
$js_files = 0;
$css_files = 0;

// convert request param 'q' to files list 
$files =  explode(',', $_GET['q']);

array_walk($files, 'path_trim');

if($js_files + $css_files == 0){
	// TODO: output correct code then file extension is unknown
	header("HTTP/1.0 404 Not Found");
	print_r($_GET['q']);
	exit;
}

$lmt = 0;
$longFilename = ''; // This is generated for the Hash

foreach($files as $id => $file){
	if(!empty($file)){
		$longFilename .= $file;
		$fileLmt = @filemtime($file);
		if($fileLmt > $lmt){
			$lmt = $fileLmt;
		}
	}else{
		unset($files[$id]);
	}
}

$lmt_str = gmdate('D, d M Y H:i:s', $lmt) . ' GMT';

//print_r($files);

/////////////////////////////////////////////////////////////////////////////
// Begin *BROWSER* Cache Control

// Here we check to see if the browser is doing a cache check
// First we'll do an etag check which is to see if we've already stored
// the hash of the filename . '-' . $newestFile.  If we find it
// nothing has changed so let the browser know and then die.  If we
// don't find it (or it's a mismatch) something has changed so force
// the browser to ignore the cache.

$fileHash = md5($longFilename);   // This generates a key from the collective file names
$hash = $fileHash . '-'.$lmt;     // This appends the newest file date to the key.
//$headers = getallheaders();       // Get all the headers the browser sent us.

if (preg_match("/$hash/i", $_SERVER['HTTP_IF_NONE_MATCH'])) {// Look for a hash match
	// Our hash+filetime was matched with the browser etag value so nothing
	// has changed.  Just send the last modified date and a 304 (nothing changed) 
	// header and exit.
	header('Last-Modified: '.$lmt_str, true, 304);
	exit;
}

// We're still alive so save the hash+latest modified time in the e-tag.
header("ETag: \"{$hash}\"");

// For an additional layer of protection we'll see if the browser
// sent us a last-modified date and compare that with $newestFile
// If there's no change we'll send a cache control header and die.

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
   if ($newestFile <= strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
      // No change so send a 304 header and terminate
       header('Last-Modified: '.$lmt_str, true, 304);
       exit;
    }
}

// Set the last modified date as the date of the NEWEST file in the list.
header('Last-Modified: '.$lmt_str);

// End *BROWSER* Cache Control
/////////////////////////////////////////////////////////////////////////////


// we process only files with 'js' or 'css' extensions
if($js_files > 0){
	$file_type = 'js';
	$Content_type = 'text/javascript; charset=' . FILES_ENCODING;
}
if($css_files > 0){
	$file_type = 'css';
	$Content_type = 'text/css; charset=' . FILES_ENCODING;
}
if($css_files > 0 and $js_files > 0){
	$Content_type = 'text/plain; charset=' . FILES_ENCODING;
}

header('Content-type: ' . $Content_type);

$compress_file = false;

if(!isset($_SERVER['HTTP_ACCEPT_ENCODING']) or strrpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === false){
	$compress_file = false;
}else{
	$compress_file = true;
	$enc = in_array('x-gzip', explode(',', strtolower(str_replace(' ', '', $_SERVER['HTTP_ACCEPT_ENCODING'])))) ? "x-gzip" : "gzip";
}

if(!$compress_file){
	if($file_type == 'css'){
		$content = compose_file($files);
		
		// remove comments
		$content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
		// remove tabs, spaces, newlines, etc.
		$content = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $content);
		
		return $content;
		exit;
	}else{
		$cache_file = CACHE_DIR . '/' . $hash;

		if(is_file($cache_file) and is_readable($cache_file)){
			echo file_get_contents($cache_file);
			exit;
		}
		
		// include('class.JavaScriptPacker.php');
		// $jsPacker = new JavaScriptPacker(compose_file($files));
	    // $cacheData = $jsPacker->pack();

	    define('JSMIN_AS_LIB', true); // prevents auto-run on include
	    include('jsmin.php');
        $jsMin = new JSMin(compose_file($files), false);
        $cacheData = $jsMin->minify();
	    
 		$cacheData = $jsPacker->pack();
		
		$fp = @fopen($cache_file, "wb");
		if ($fp) {
			fwrite($fp, $cacheData);
			fclose($fp);
		}
		echo $cacheData;
		exit;
	}	
}else{
	$cache_file = CACHE_DIR . '/' . $hash . '.gz';

	if(is_file($cache_file) and is_readable($cache_file)){
		header("Content-Encoding: " . $enc);
		echo file_get_contents($cache_file);
		exit;
	}
	
	$content = compose_file($files);
	
	if($file_type == 'js'){
		//include('class.JavaScriptPacker.php');
		//$jsPacker = new JavaScriptPacker($content, 0, false, false);
 		//$content = $jsPacker->pack();
	    define('JSMIN_AS_LIB', true); // prevents auto-run on include
	    include('jsmin.php');
        $jsMin = new JSMin($content, false);
        $content = $jsMin->minify();
	}else{
		// remove comments
		$content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
		// remove tabs, spaces, newlines, etc.
		$content = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $content);
	}
	
	$cacheData = gzencode($content, 9, FORCE_GZIP);
	
	$fp = @fopen($cache_file, "wb");
	if ($fp) {
		fwrite($fp, $cacheData);
		fclose($fp);
	}
	
	header("Content-Encoding: " . $enc);
	echo $cacheData;
	exit;	
}


function path_trim(&$val){
	global $js_files, $css_files;
	
	// TODO: check what this function allow acces only to files we can show.
	
	// cut off anything wat looks like /../ folder
	$val = str_replace('../', '', trim($val, '\\/'));
	
	// check what file is with JS or CSS extension
	if(!preg_match('/\.(js|css)(?:\?.*)?$/iD',$val, $matches)){
		$val = '';
		return false;
	}
	
	$val = preg_replace('/\.(js|css)(?:\?.*)$/', '.$1', $val);
	
	if(strtolower($matches[1]) == 'js'){
		++$js_files;
	}elseif(strtolower($matches[1]) == 'css'){
		++$css_files;
	}

	//add DOCUMENT_ROOT and return full path to a file
	$val = rtrim($_SERVER['DOCUMENT_ROOT'], '\\/') . '/' . $val;
	
	if(!is_readable($val) and !is_file($val)){
		$val = '';
		return false;
	}
}

function compose_file($files){
	$content = '';
	foreach($files as $file){
		$content .= file_get_contents($file) . "\n\n";
	}
	return $content;
}
?>