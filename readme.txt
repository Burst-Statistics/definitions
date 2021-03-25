=== Definitions - Internal Linkbuilding ===
Contributors: RogierLankhorst, aahulsebos, leonwimmenhoeve, 
Tags: linkbuilding, definitions, tooltips, hyperlinks, pillar pages, glossary,
Requires at least: 4.0
Tested up to: 4.1
Stable tag: trunk

descr./

== Installation ==
Download the plugin from the WordPress plugin directory
Upload the plugin in your plugins directory,
Go to “plugins” in your WordPress admin, then click activate

== Frequently Asked Questions ==
Can I remove the shipped .js and .css files?
Of course, just use the deregister option in your functions.php
->wp_deregister_script($handle ), where $handle = rldh-tooltipjs, or rldh-js. 
->wp_deregister_style( $handle ) where $handle = rldh-tooltipcss
