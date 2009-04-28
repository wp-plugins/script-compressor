=== Script Compressor ===
Contributors: Regen
Tags: compress, javascript, css
Requires at least: 2.5
Tested up to: 2.7.1
Stable tag: 1.6

このプラグインはJavascript、CSSを圧縮するプラグインです。

== 説明 ==

このプラグインは、テーマや他のプラグインによって読み込まれるJavaScriptとCSSの圧縮を自動的に行うプラグインです。
スクリプトが圧縮されると、スクリプト内の余分なスペースや改行、及びコメントなどが削除され、最低限まで切り詰められます。
圧縮処理は [jscsscomp](http://code.google.com/p/jscsscomp/) をもとにしています。

= 特徴 =

*   他のプラグインによって読み込まれるスクリプトは、何もしなくても自動的に圧縮されます。
*   テーマファイルに記述されたスクリプトは、スクリプトの読み込みの記述を指定されたタグで囲うことで圧縮することができます。
*   他のプラグインが読み込むスクリプトの自動的な圧縮をするかどうか、管理画面から変更できます。
*   CSS圧縮をする条件を記述できます。

== インストール ==

1. /wp-content/plugins/ に解凍したプラグインをアップロードしてください
2. /wp-content/plugins/script-compressor/cache へ書き込み権限を与えてください
   **(バージョン1.4から変わりました)**
3. プラグインメニューから有効化してください
4. 設定 -> Script Compressor から設定してください

== FAQ ==

= CSSが適応されなくなった =

キャッシュが働いていますので、CSSを直接開いてからスーパーリロード(Ctrl+F5)してください。

== スクリーンショット ==

1. 管理画面の一部
