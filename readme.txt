=== Definitions Hyperlinks ===
Contributors: Stroknal
Tags: definitions, tooltips, hyperlinks
Requires at least: 4.0
Tested up to: 4.1
Stable tag: trunk

Plugin to autoreplace in the content of a page or post every instance of a word that is defined in the definitions. By creating a separate page for every definition you create a lot of high value content, so it is seo friendly.  

== Installation ==
Download the plugin from the wordpres plugin directory
Upload the plugin in your plugins directory,
Go to “plugins” in your wordpress admin, then click activate

== Frequently Asked Questions ==
Can I remove the shipped .js and .css files?
Of course, just use the deregister option in your functions.php
->wp_deregister_script($handle ), where $handle = rldh-tooltipjs, or rldh-js. 
->wp_deregister_style( $handle ) where $handle = rldh-tooltipcss