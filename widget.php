<?php
/*
Plugin Name: FriendFeed Activity Widget
Plugin URI: http://evansims.com/projects/friendfeed_activity_widget
Description: A widget for displaying your FriendFeed activity on your blog.
Author: Evan Sims
Version: 1.0.1
Author URI: http://evansims.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

	@define('NL', "\n");

	function friendfeed_activity($options = null) {

		if(!$options) { echo '<p>Please configure your FriendFeed widget.</p>'; return; }

		if(!function_exists('widget_friendfeed')) { widget_friendfeed_init(false); }
		widget_friendfeed($options);

	}

	function widget_friendfeed_init($widgetized = true) {

		if(!$widgetized) if (!function_exists('register_sidebar_widget') || !function_exists('register_widget_control')) return;

		function widget_friendfeed($args, $options = null) {

			extract($args);

			$username = '';
			$apikey = '';
			$title = 'FriendFeed';

			if(!$options) $options = get_option('widget_friendfeed');
			if(isset($options['username'])) $username = $options['username'];
			if(isset($options['apikey'])) $apikey = $options['apikey'];
			if(isset($options['title'])) $title = $options['title'];

			echo $before_widget;
			if($title) {
				echo $before_title;
				echo $title;
				echo $after_title;
			}

			if(!$username || !$apikey) { echo '<p>Please configure your FriendFeed widget.</p>' . $after_widget; return; }

			$path = __FILE__;
			$path = substr($path, 0, strrpos($path, '/') + 1);

			$timer = null; $stream = null;
			if(file_exists("{$path}cache.dat")) { require_once("{$path}cache.dat"); }
			if(!$stream || !$timer || (time() - $timer) >= 900) {
				$old_stream = $stream;
				$stream = widget_friendfeed_update($username, $apikey, $path);
				if(!$stream) $stream = $old_stream;
				unset($old_stream);
			}

			if($stream) {
				$stream = unserialize($stream);
				widget_friendfeed_render($stream, $timer, $options);
			} else { echo('<p>Your freed could not be retrieved at this time. Try again later.</p>'); }

			echo $after_widget;

		}

		function widget_friendfeed_render($ret, $published, $options) {

			if(!$ret) return('<p>Your freed could not be retrieved at this time. Try again later.</p>');
			if(!$published) $published = time();

			$group_events = true;
			$twitter_hide_replies = true;
			$limit_events = 10;

			if(isset($options['group_events'])) $group_events = (bool)$options['group_events'];
			if(isset($options['twitter_hide_replies'])) $twitter_hide_replies = (bool)$options['twitter_hide_replies'];
			if(isset($options['limit_events'])) $limit_events = (int)$options['limit_events'];

			if($limit_events > 30) $limit_events = 30;
			if($limit_events < 1) $limit_events = 1;

			$mutators = array(

				'default' => array('title' => false, 'linkall' => true, 'group_format' => null),
				'twitter::' => array('title' => false, 'linkall' => false, 'group_format' => null),
				'lastfm::' => array('title' => 'Favorited %link+title', 'linkall' => false, 'group_format' => null),
				'magnolia::group' => array('title' => 'Bookmarked %count links:', 'linkall' => false, 'group_format' => null),
				'magnolia::' => array('title' => 'Bookmarked a link: %link+title', 'linkall' => false, 'group_format' => null),
				'flickr::' => array('title' => 'Shared an image: %link+title', 'linkall' => false, 'group_format' => 'thumbnails'),
				'flickr::group' => array('title' => 'Shared %count images:', 'linkall' => false, 'group_format' => 'thumbnails'),
				'google-reader::' => array('title' => 'Shared: %link+title', 'linkall' => false, 'group_format' => null),
				'google-reader::group' => array('title' => 'Shared %count links:', 'linkall' => false, 'group_format' => null),
				'youtube:favorite:' => array('title' => 'Favorited a video: %link+title', 'linkall' => false, 'group_format' => 'thumbnails'),
				'youtube:favorite:group' => array('title' => 'Favorited %count videos:', 'linkall' => false, 'group_format' => 'thumbnails'),
				'smugmug::' => array('title' => 'Shared an image: %link+title', 'linkall' => false, 'group_format' => 'thumbnails'),
				'smugmug::group' => array('title' => 'Shared %count images:', 'linkall' => false, 'group_format' => 'thumbnails'),
				'reddit:like:' => array('title' => 'Liked a story: %link+title', 'linkall' => false, 'group_format' => null),
				'reddit:like:group' => array('title' => 'Liked %count stories:', 'linkall' => false, 'group_format' => null)

			);

			$events = array();

			//print_r($ret);

			foreach($ret->entries as $event) {
				if($twitter_hide_replies == true && $event->service->name == 'Twitter' && substr($event->title, 0, 1) == '@') continue;
				$type = ''; if(isset($event->service->entryType)) $type = $event->service->entryType;

				$thumb = array();
				if($event->media && count($event->media)) {
					for($t = 0; $t < count($event->media); $t++) {

						$thumb['url'] = $event->link;
						$thumb['width'] = 0;
						$thumb['height'] = 0;

						$title = $event->title;
						if(count($event->media) > 1 && isset($event->media[$t]->title)) $title = $event->media[$t]->title;

						$link = $event->link;
						if(count($event->media) > 1 && isset($event->media[$t]->content[0]->url)) $link = $event->media[$t]->content[0]->url;

						if(isset($event->media[$t]->thumbnails[0]->url)) $thumb['url'] = $event->media[$t]->thumbnails[0]->url;
						if(isset($event->media[$t]->thumbnails[0]->width)) $thumb['width'] = $event->media[$t]->thumbnails[0]->width;
						if(isset($event->media[$t]->thumbnails[0]->height)) $thumb['height'] = $event->media[$t]->thumbnails[0]->height;

						if(count($event->media) > 1) $events[] = array('link' => $link, 'title' => $title, 'published' => $event->published, 'service-icon' => $event->service->iconUrl, 'service' => ereg_replace('[^A-Za-z0-9\-]','',str_replace(' ', '-', strtolower($event->service->name))), 'type' => $type, 'thumbnail' => $thumb, 'group' => false);
					}
					if(count($event->media) > 1) continue;
				}

				$events[] = array('link' => $event->link, 'title' => $event->title, 'published' => $event->published, 'service-icon' => $event->service->iconUrl, 'service' => ereg_replace('[^A-Za-z0-9\-]','',str_replace(' ', '-', strtolower($event->service->name))), 'type' => $type, 'thumbnail' => $thumb, 'group' => false);

			} unset($ret);

			if($group_events) {

				$prev_event = null;
				$prev_type = null;

				for($e = 0; $e < count($events); $e++) {
					if($events[$e]['service'] != 'twitter' && $events[$e]['service'] == $prev_event && $events[$e]['type'] == $prev_type) {
						$events[$e-1]['group'] = true;
						$events[$e]['group'] = true;
					}
					$prev_event = $events[$e]['service'];
					$prev_type = $events[$e]['type'];
				}

				unset($prev_event);
				unset($prev_type);

			}

			echo '<!-- Updated on ' . date('r', $published) . ' -->' . NL;
			echo '<ul id="friendfeed-activity" style="margin: 1em 0; padding: 0;">' . NL;

			$event_counter = 0;
			for($e = 0; $e < count($events); $e++) {

				$event_counter++;
				if($event_counter > $limit_events) break;

				$event = $events[$e];
				$children = array();
				if($events[$e]['group']) {
					for($n = ($e + 1); $n < count($events); $n++) {
						if($events[$n]['group'] && $events[$n]['service'] == $events[$e]['service'] && $events[$n]['type'] == $events[$e]['type']) {
							$e = $n;
							$children[] = $events[$n];
						} else {
							break;
						}
					}
				}

				$mutateid = "{$event['service']}:{$event['type']}:";
				if($children) $mutateid .= 'group';
				$mutator = $mutators['default'];
				if(isset($mutators[$mutateid])) $mutator = $mutators[$mutateid];

				//echo "<!-- Mutator: {$mutateid} -->\n";

				echo "<li class=\"ff-{$event['service']}\" style=\"list-style-image: url('{$event['service-icon']}'); margin: 0 0 .75em 0; padding: 0;\">" . NL;
				echo '<p class="title" style="margin: 0; padding: 0;">' . NL;

				if($mutator && $mutator['title']) {
					$title = str_replace('%title', $event['title'], $mutator['title']);
					if($children) { $title = str_replace('%count', count($children) + 1, $title); }
					$title = str_replace('%link+title', "<a href=\"{$event['link']}\" class=\"external\">{$event['title']}</a>", $title);
					echo $title;
				} elseif (!$children) {
					if($event['link'] && (!$mutator || $mutator['linkall'])) echo "<a href=\"{$event['link']}\" class=\"external\">";
					if(!$mutator['linkall']) { $event['title'] = preg_replace("/(ftp:\/\/|http:\/\/|https:\/\/|www|[a-zA-Z0-9-]+\.|[a-zA-Z0-9\.-]+@)(([a-zA-Z0-9-][a-zA-Z0-9-]+\.)+[a-zA-Z0-9-\.\/\_\?\%\#\&\=\;\~\!\(\)]+)/","<a href=\"http://\\1\\2\" class=\"external\">\\1\\2</a>", $event['title']); $event['title'] = str_replace('http://http://', 'http://', $event['title']); }
					if($event['service'] == 'twitter' && strpos($event['title'], '@')) {
						$out = '';
						for($i = 0; $i < strlen($event['title']); $i++) {
							if($event['title'][$i] != '@') { $out .= $event['title'][$i]; continue; }
							if($event['title'][$i] == '@') {
								$twit_identity = null;
								for($s = ($i+1); $s < strlen($event['title']); $s++) {
									if(((ord($event['title'][$s]) > 47 && ord($event['title'][$s]) < 58) ||
									   (ord($event['title'][$s]) > 64 && ord($event['title'][$s]) < 91) ||
									   (ord($event['title'][$s]) > 96 && ord($event['title'][$s]) < 123) || ord($event['title'][$s]) == 95) && $s + 1 < strlen($event['title'])) continue;

									$twit_identity = trim(substr($event['title'], ($i + 1), ($s - $i)));
									$out .= '@<a href="http://twitter.com/' . $twit_identity . '">' . $twit_identity . '</a> ';
									$i = $s;
									break;
								}
								if(!$twit_identity) $out .= '@';
							}
						}
						$event['title'] = $out;
					}
					if($event['service'] == 'twitter' && strpos($event['title'], '#')) {
						$out = '';
						for($i = 0; $i < strlen($event['title']); $i++) {
							if($event['title'][$i] != '#') { $out .= $event['title'][$i]; continue; }
							if($event['title'][$i] == '#') {
								$twit_hash = null;
								for($s = ($i+1); $s < strlen($event['title']); $s++) {
									if(((ord($event['title'][$s]) > 47 && ord($event['title'][$s]) < 58) ||
									   (ord($event['title'][$s]) > 64 && ord($event['title'][$s]) < 91) ||
									   (ord($event['title'][$s]) > 96 && ord($event['title'][$s]) < 123)) && $s + 1 < strlen($event['title'])) continue;

									$twit_hash = trim(substr($event['title'], ($i + 1), ($s - $i)));
									$out .= '#<a href="http://hashtags.org/tag/' . $twit_hash . '/">' . $twit_hash . '</a> ';
									$i = $s;
									break;
								}
								if(!$twit_hash) $out .= '#';
							}
						}
						$event['title'] = $out;
					}
					echo $event['title'];
					if($event['link'] && (!$mutator || $mutator['linkall'])) echo '</a>';
				}
				echo '</p>' . NL;

				if($children || $event['thumbnail']) {

					array_unshift($children, $event);
					if(!isset($mutator['group_format']) || !$mutator['group_format']) $mutator['group_format'] = 'list';

					if($mutator['group_format'] == 'thumbnails') {
						echo '<div class="thumbnails" style="margin: .25em 0 0 0; padding: 0;">' . NL;
					} elseif($mutator['group_format'] == 'list') {
						echo '<ul style="list-style: none; margin: .25em 0 0 0; padding: 0;">' . NL;
					}

					if(!$children) $children[] = $event;

					foreach($children as $child) {

						if($mutator['group_format'] == 'thumbnails') {
							echo '<a href="' . $child['link'] . '" rel="me" class="external" title="' . $child['title'] . '">';
							echo '<img alt="' . $child['title']. '" src="' . $child['thumbnail']['url'] . '" />'; // width="' . $child['thumbnail']['width'] . '" height="' . $child['thumbnail']['height'] . '" />';
							echo '</a>' . NL;
						} elseif($mutator['group_format'] == 'list') {
							echo '<li style="margin: 0 0 .25em 0; padding: 0;"><p style="margin: 0; padding: 0;"><a href="' . $child['link'] . '" rel="me" class="external" title="' . $child['title'] . '">';
							echo $child['title'];
							echo '</a></p></li>' . NL;
						}

					}

					if($mutator['group_format'] == 'thumbnails') {
						echo '</div>' . NL;
					} elseif($mutator['group_format'] == 'list') {
						echo '</ul>' . NL;
					}

				}

				echo '<p class="published" style="margin: 0; padding: 0;"><small>';
				if(date('dmY') == date('dmY', $event['published'])) echo 'Today';
				else echo date('l', $event['published']);
				echo ' at ' . date('G:i', $event['published']) . '</small></p>' . NL;

				echo "</li>" . NL;

			}

			echo '<li class="ff-account" style="list-style: none;"><p style="margin: 0; padding: 0"><a href="http://friendfeed.com/' . $options['username'] . '" rel="me" class="external">More &#8230;</a></p></li>' . NL;

			echo '</ul>' . NL . NL;

			//$stream = ob_get_clean();

			return true;

		}

		function widget_friendfeed_update($username, $api, $path) {

			if(file_exists("{$path}cache.dat") && !is_writable("{$path}cache.dat") && !chmod("{$path}cache.dat", 0755)) { echo('<p><strong>Error &#8211;</strong> Cache is not writable. Please ensure the cache.dat file is writable.'); exit; }

			if(!class_exists('FriendFeed')) require_once("{$path}friendfeed.php");
			$feed = new FriendFeed($username, $api);
			$ret = $feed->fetch_user_feed($username);
			if(!$ret) return;

			$ret = serialize($ret);

			$output = '<?php $timer = \'' . time() . '\'; $stream = \'' . str_replace('\'', '\\\'', $ret) . '\'; ?>';
			$handle = @fopen("{$path}cache.dat", 'w');
			if(!$handle) { echo('<p><strong>Error &#8211;</strong> Unable to write to cache file. Please ensure the cache.dat has proper write-access permissions.'); exit; }
			if($handle) @fwrite($handle, $output);
			if($handle) @fclose($handle);

			return $ret;

		}

		function widget_friendfeed_control() {

			$options = get_option('widget_friendfeed');
			$username = '';
			$apikey = '';
			$title = '';
			$group_events = true;
			$twitter_hide_replies = true;
			$limit_events = 10;

			if($options) {
				if(isset($options['username'])) $username = $options['username'];
				if(isset($options['apikey'])) $apikey = $options['apikey'];
				if(isset($options['title'])) $title = $options['title'];
				if(isset($options['group_events'])) $group_events = $options['group_events'];
				if(isset($options['twitter_hide_replies'])) $twitter_hide_replies = $options['twitter_hide_replies'];
				if(isset($options['limit_events'])) $limit_events = $options['limit_events'];

				if($options['group_events'] != 'true') $group_events = false;
				if($options['twitter_hide_replies'] != 'true') $twitter_hide_replies = false;
			}

			if(isset($_POST['widget_friendfeed_nickname'])) {
				$options['username'] = stripslashes($_POST['widget_friendfeed_nickname']);
				$options['apikey'] = stripslashes($_POST['widget_friendfeed_apikey']);
				$options['title'] = stripslashes($_POST['widget_friendfeed_title']);
				$options['limit_events'] = stripslashes($_POST['widget_friendfeed_limit_events']);
				if($_POST['widget_friendfeed_group_events']) { $options['group_events'] = 'true'; } else { $options['group_events'] = ''; }
				if($_POST['widget_friendfeed_twitter_hide_replies']) { $options['twitter_hide_replies'] = 'true'; } else { $options['twitter_hide_replies'] = ''; }
				update_option('widget_friendfeed', $options);
			}

?>
		  <p><label for="widget_friendfeed_title">Widget Title:</label> <input type="text" name="widget_friendfeed_title" id="widget_friendfed_title" value="<?php echo $title?>" /></p>

		  <table style="width: 100%; border-collapse: collapse; border-spacing: 0; padding: 0; margin: 1em 0; font-family: Arial, sans-serif; border: 4px solid #6797d3; color: #222222">

			<tr>
			  <td style="background-color: #ecf2fa; padding: 3px; padding-left: 5px; padding-top: 5px; border: 0; border-bottom: 1px solid #6797d3"><a href="http://friendfeed.com/" target="_blank"><img src="http://friendfeed.com/static/images/logo-api.png" width="160" height="34" alt="FriendFeed" style="padding:0; border:0; margin:0"/></a></td>
			  <td style="background-color: #ecf2fa; padding: 3px; padding-right: 20px; border: 0; border-bottom: 1px solid #6797d3; text-align: right; vertical-align: middle; font-size: 16pt; font-weight: bold; color: gray">remote login</td>
			</tr>
			<tr>
			  <td style="background-color: white; padding: 15px; border: 0" colspan="2">
				<table style="border-collapse: collapse; border-spacing: 0; border: 0; padding: 0; margin: 0">
				  <tr>

					<td style="border: 0; padding: 5px; font-size: 10pt"><label for="widget_friendfeed_nickname">FriendFeed nickname:</label></td>
				<td style="border: 0; padding: 5px; font-size: 10pt"><input type="text" name="widget_friendfeed_nickname" id="widget_friendfeed_nickname" style="width: 10em" value="<?php echo $username?>" /></td>
			  </tr>
			  <tr>
				<td style="border: 0; padding: 5px; font-size: 10pt"><label for="widget_friendfeed_apikey">Remote key [ <a href="http://friendfeed.com/remotekey" style="color: #1030cc" target="_blank">find your key</a> ]:</label></td>
				<td style="border: 0; padding: 5px; font-size: 10pt"><input type="password" name="widget_friendfeed_apikey" id="widget_friendfeed_apikey" style="width: 10em" value="<?php echo $apikey?>" /></td>

			  </tr>
			</table>
			  </td>
			</tr>
		  </table>

		  <p>Display up to <select name="widget_friendfeed_limit_events" style="vertical-align: middle;"><?php

			for($i = 1; $i < 31; $i++) {
				if($i == $limit_events) {
					echo '<option selected="selected">' . $i . '</option>';
				} else {
					echo '<option>' . $i . '</option>';
				}
			}

		  ?></select> events.</p>

		  <p><input type="checkbox" name="widget_friendfeed_group_events" id="widget_friendfeed_group_events" value="true" <?php if($group_events) {  ?>checked="checked"<?php } ?> /> <label for="widget_friendfeed_group_events">Group events where possible</label><br />
		     <input type="checkbox" name="widget_friendfeed_twitter_hide_replies" id="widget_friendfeed_twitter_hide_replies" value="true" <?php if($twitter_hide_replies) { ?>checked="checked"<?php } ?> /> <label for="widget_friendfeed_twitter_hide_replies">Hide Twitter messages beginning with @replies</label></p>

<?php

		}

		if(!$widgetized) {

			if(function_exists('wp_register_sidebar_widget') && function_exists('wp_register_widget_control')) {
				wp_register_sidebar_widget('ffactivity', 'FriendFeed Activity', 'widget_friendfeed', array('classname' => 'widget_ffactivity', 'description' => 'Share your FriendFeed.com activity on your blog'));
				wp_register_widget_control('ffactivity', 'FriendFeed Activity', 'widget_friendfeed_control', array('width' => 400, 'height' => 300));
			} else {
				register_sidebar_widget('FriendFeed Activity', 'widget_friendfeed');
				register_widget_control('FriendFeed Activity', 'widget_friendfeed_control', 400, 300);
			}

		}

	}

	add_action('plugins_loaded', 'widget_friendfeed_init');

?>