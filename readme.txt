=== FriendFeed Activity Widget ===
Contributors: evansims, nathanchase
Donate link: http://evansims.com/projects/friendfeed_activity_widget
Tags: friendfeed, widget
Requires at least: 2.5
Tested up to: 2.5.1
Stable tag: trunk

The FriendFeed Activity Widget is a WordPress widget pulls your FriendFeed stream and shares it with visitors to your blog.

== Description ==

The FriendFeed Activity Widget is a simple WordPress widget that pulls your FriendFeed stream, pretties it up a bit and shares it with visitors to your blog. It's essentially a lifestream plugin that requires just a few steps to set up.

Please read the installation instructions carefully and ensure that you extract the widget to the correct folder path.

== Installation ==

1. Extract the zip archive and upload the contents to your /wp-contents/plugins/ folder. The files should end up in a directory named "friendfeed-activity".
2. Log into your WordPress Dashboard and activate the plugin.
3. Go to your Design -> Widgets page and add the widget to your sidebar. Go ahead and configure it with your API key, nickname and any additional settings you'd like.

That's it!

== Frequently Asked Questions ==

= I'm seeing %BEG_OF_TITLE% and %END_OF_TITLE% in my Widgets UI =

Ensure that your web server is running PHP5 and has CURL support enabled.

= Will the widget run under PHP4? =

It is highly doubtful, though it has not been tested. Please let me know if you try it with success.

== Screenshots ==

1. An example of the Widget in action.

== Changes ==

Version 1.1.2

* Fixes a bug with Twitter hash tag detection.

Version 1.1.1

* Hotfix which should resolve the path issue introduced in the previous release.

Version 1.1

* Added grouping support and formatting for many additional services.
* Moved styling to an external stylesheet, widget.css.
* Added date formatting options to widget settings.
* Added proper html encoding for feed links (XHTML validates.)
* Changed output from UL to DIV structure; may require CSS changes. Sorry!

Version 1.0.1

* Updated to the latest FriendFeed API wrapper.
* Fixed some XHTML formatting problems.
* Fixed a potential incompatibility problem with other FriendFeed plugins.

Version 1.0a4

* Fixed an issue with certain grouped events being dropped.
* Added grouping support and formatting for Flickr events.

Version 1.0a3

* Output changes to resolve potential compatibility problems with other FF plugins.
* Embedded default styling into widget (suggested by bwana.tv)
* Rewrote caching system.

Version 1.0a2

* Fixes compatibility problem with Glenn Slaven's FriendFeed comments plugin.

Version 1.0a1

* First plugin release; alpha-state.
