Þ    "      ,  /   <      ø  Î   ù     È     á     ò  '        *     A  >   J           ª  n   »     *  $   7     \     õ  J   û     F     W     f  
   u               ¬  I   µ  ô   ÿ  r   ô  5   g  Ê        h	  2   w	  3   ª	     Þ	     ^
     c
  Î     !   Ó     õ       &        9     L  :   _  3        Î  {   Ú     V  )   c       	   &  T   0  !     !   §     É     â     ò  !        &  X   -  Ü     Z   c  ?   ¾    þ       0     Q   D  ¬        C                                  "                   	                       
                                                   !                               &lt;?php sc_comp_start() ?&gt;<br />&lt;script type="text/javascript" src="foo.js"&gt;&lt;/script&gt;<br />&lt;script type="text/javascript" src="bar.js"&gt;&lt;/script&gt;<br />&lt;?php sc_comp_end() ?&gt; Additional template tags Auto-compression CSS compression CSS compression condition (mod_rewrite) CSS compression method Composed Example: <code>RewriteCond %{REQUEST_URI} !.*wp-admin.*</code> Give the write permission to %s. Gzip compression If you check "Javascript compression for headers", the contents of wp_head() will be compressed automatically. Instructions Javascript compression for wp_head() Javascripts and CSS between <code>&lt;?php sc_comp_start() ?&gt;</code> and <code>&lt;?php sc_comp_end() ?&gt;</code> will be compressed by this plugin. Notes Only files located in the same server as your WordPress can be compressed. Options removed. Options saved. Remove options Respective Script Compressor Script Compressor Options Settings The extensions of Javascript and CSS should be .js and .css respectively. This method compresses <strong>composed</strong> CSS files in wp_head(). The frequency of the HTTP request is less than "Respective" but there is a possibility that paths of images in CSS files break and that The media type becomes ineffective. This method compresses <strong>respective</strong> CSS files (former method). This uses .htaccess and mod_rewrite. This plugin makes caches in the compression progress. This text is inserted in the upper part of RewriteRule added by this plugin in your .htaccess. Please see <a href="http://httpd.apache.org/docs/2.0/mod/mod_rewrite.html#rewritecond">RewriteCond doc</a>. Update Options Use gzip compression for the cache and the output. You can remove the above options from the database. Your .htaccess is not writable so you may need to re-save your <a href="options-permalink.php">permalink settings</a> manually. e.g. Project-Id-Version: Script Compressor
Report-Msgid-Bugs-To: 
POT-Creation-Date: 2008-07-31 23:12+0900
PO-Revision-Date: 
Last-Translator: Regen <g(DOT)regen100(AT)gmail(DOT)com>
Language-Team: 
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit
X-Poedit-Language: Japanese
X-Poedit-KeywordsList: __;_e
X-Poedit-Basepath: .
X-Poedit-SearchPath-0: C:\svn\script-compressor\trunk
 &lt;?php sc_comp_start() ?&gt;<br />&lt;script type="text/javascript" src="foo.js"&gt;&lt;/script&gt;<br />&lt;script type="text/javascript" src="bar.js"&gt;&lt;/script&gt;<br />&lt;?php sc_comp_end() ?&gt; è¿½å ã®ãã³ãã¬ã¼ãã¿ã° èªåå§ç¸® CSSå§ç¸®ä½¿ç¨ CSSå§ç¸®ãããæ¡ä»¶ (mod_rewrite) CSSå§ç¸®ã®æ¹æ³ ã¾ã¨ãã¦å§ç¸® ä¾: <code>RewriteCond %{REQUEST_URI} !.*wp-admin.*</code> %s ã«æ¸ãè¾¼ã¿æ¨©éãä¸ãã¦ãã ããã Gzip å§ç¸® "ãããã¼ã«Javascriptå§ç¸®èªåé©ç¨"ãä½¿ç¨ããå ´åã¯wp_head()ã®åå®¹ãèªåçã«å§ç¸®ããã¾ãã å©ç¨æ¹æ³ wp_head() ã«Javascriptå§ç¸®èªåé©ç¨ <code>&lt;?php sc_comp_start() ?&gt;</code>ã¨<code>&lt;?php sc_comp_end() ?&gt;</code>ã§æ¬ã£ãé¨åã® Javascript ã¨ CSS ãå§ç¸®ããã¾ãã æ³¨æç¹ å§ç¸®ã§ãããã¡ã¤ã«ã¯ããµã¼ãã¼åã«ãããã¡ã¤ã«ã®ã¿ã§ãã è¨­å®ãåé¤ããã¾ããã è¨­å®ãä¿å­ããã¾ããã ãªãã·ã§ã³ãåé¤ åå¥ã«å§ç¸® Script Compressor Script Compressor ãªãã·ã§ã³ è¨­å® Javascriptã¨CSSã®æ¡å¼µå­ã¯ãããã .jsã.css ã§ããå¿è¦ãããã¾ãã wp_head() ã® CSS ã<strong>ã¾ã¨ãã¦å§ç¸®</strong>ãã¾ããHTTP ãªã¯ã¨ã¹ãã®åæ°ã¯æ¸ãã¾ãããç»åã®ãã¹ãå£ããããã¡ãã£ã¢ã¿ã¤ããå¹ããªããªãå¯è½æ§ãããã¾ãã .htaccess ã¨ mod_rewrite ãä½¿ã£ã¦ CSS ã<strong>åå¥ã«å§ç¸®</strong>ãã¾ãã å§ç¸®å¦çã®ããã«ãã­ã£ãã·ã¥ãè¡ããã¾ãã ãã®ãã­ã¹ãã¯ .htaccess ã®ä¸­ã®ããã®ãã©ã°ã¤ã³ã«ãã£ã¦è¿½å ããã RewriteRule ã®ä¸ã«æ¿å¥ããã¾ãã<a href="http://httpd.apache.org/docs/2.0/mod/mod_rewrite.html#rewritecond">RewriteCond ã®èª¬æ</a>ãè¦ã¦ãã ããã è¨­å®ãä¿å­ ã­ã£ãã·ã¥ã¨åºåã« Gzip å§ç¸®ãä½¿ã ãã¼ã¿ãã¼ã¹ããä¸ã®ãªãã·ã§ã³ã®ãã¼ã¿ãåé¤ã§ãã¾ãã .htaccess ãæ¸ãè¾¼ã¿å¯è½ã§ã¯ãªãã®ã§<a href="options-permalink.php">ãã¼ããªã³ã¯è¨­å®</a>ãæåã§è¨­å®ããå¿è¦ãããããããã¾ããã ä¾: 