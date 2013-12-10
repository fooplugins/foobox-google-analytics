<?php
/**
 * FooBox Extension For Google Analytics Class
 *
 * @package   foobox-google-analytics
 * @author    Brad Vincent <brad@fooplugins.com>
 * @license   GPL-2.0+
 * @link      https://github.com/fooplugins/foobox-google-analytics
 * @copyright 2013 FooPlugins LLC
 */

class FooBox_Extension_For_Google_Analytics {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	protected $version = '1.0.2';

	/**
	 * Unique identifier for your plugin.
	 *
	 * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
	 * match the Text Domain file header in the main plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'foobox-google-analytics';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if (null == self::$instance) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since     1.0.1
	 *
	 * @return    string    The plugin slug
	 */
	public function get_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Initialize the plugin
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		if (is_admin()) {

			add_action('foobox_pre_tab', array($this, 'add_ga_settings_tab'));

		} else {

			add_action('init', array($this, 'init_scripts'));

		}
	}

	private function get_foobox() {
		if (array_key_exists('foobox', $GLOBALS)) {
			return $GLOBALS['foobox'];
		}
		return false;
	}

	public function init_scripts() {
		$foobox = $this->get_foobox();
		if ($foobox === false) return;

		$where = 'wp_head';

		if ($foobox->is_option_checked('scripts_in_footer')) {
			$where = 'wp_print_footer_scripts';
		}

		add_action($where, array(&$this, 'output_javascript'), 11);
	}

	public function add_ga_settings_tab($tab_id) {
		if ($tab_id === 'demo') {
			$foobox = $GLOBALS['foobox'];
			if ($foobox === false) return;

			$foobox->admin_settings_add_tab('ga', __('Google Analytics', 'foobox-google-analytics'));

			$foobox->admin_settings_add(array(
				'id'      => 'ga_track_pageviews',
				'title'   => __('Enable Pageview Tracking', 'foobox-google-analytics'),
				'desc'    => __('If enabled, a pageview will be recorded when an image is opened in FooBox.', 'foobox-google-analytics'),
				'default' => 'on',
				'type'    => 'checkbox',
				'tab'     => 'ga'
			));

			$foobox->admin_settings_add(array(
				'id'      => 'ga_deeplink_pageviews',
				'title'   => __('Track Deeplink Pageviews', 'foobox-google-analytics'),
				'desc'    => __('If both pageview tracking and FooBox deeplinking is enabled, then the deeplink URL will be recorded in Google Analytics.', 'foobox-google-analytics'),
				'default' => 'on',
				'type'    => 'checkbox',
				'tab'     => 'ga'
			));

			$foobox->admin_settings_add(array(
				'id'      => 'ga_track_events',
				'title'   => __('Enable Event Tracking', 'foobox-google-analytics'),
				'desc'    => __('If enabled, a custom event will be recorded when an image is opened in FooBox.', 'foobox-google-analytics'),
				'default' => 'on',
				'type'    => 'checkbox',
				'tab'     => 'ga'
			));

			$foobox->admin_settings_add(array(
				'id'      => 'ga_event_category',
				'title'   => 'Event Category',
				'desc'    => __('Used in event tracking, this is the name for the group of objects you want to track. In this scenario, the group of objects are your images shown within FooBox.', 'foobox-google-analytics'),
				'default' => __( 'Images', 'foobox-google-analytics' ),
				'type'    => 'text',
				'tab'     => 'ga'
			));

			$foobox->admin_settings_add(array(
				'id'      => 'ga_event_action',
				'title'   => 'Event Action',
				'desc'    => __('Used in event tracking, this is the name for the type of user interaction. In this scenario, viewing the image within FooBox.', 'foobox-google-analytics'),
				'default' => __( 'View', 'foobox-google-analytics' ),
				'type'    => 'text',
				'tab'     => 'ga'
			));

			$foobox->admin_settings_add(array(
				'id'      => 'ga_track_social',
				'title'   => __('Enable Social Tracking', 'foobox-google-analytics'),
				'desc'    => __('If enabled, all social shares from FooBox will be tracked as an event in Google Analytics.', 'foobox-google-analytics'),
				'default' => 'on',
				'type'    => 'checkbox',
				'tab'     => 'ga'
			));

			$foobox->admin_settings_add(array(
				'id'      => 'ga_social_category',
				'title'   => 'Social Category',
				'desc'    => __('Used in social tracking, this is the category used when tracking social share events from FooBox.', 'foobox-google-analytics'),
				'default' => __( 'Social Share', 'foobox-google-analytics' ),
				'type'    => 'text',
				'tab'     => 'ga'
			));

			if ($foobox->is_option_checked('enable_debug')) {
				$foobox->admin_settings_add(array(
					'id'    => 'ga_output',
					'title' => __( 'Javascript Output (Debug)', 'foobox-google-analytics' ),
					'type'  => 'html',
					'desc'  => '<pre>' . htmlentities($this->generate_javascript()) . '</pre>',
					'tab'   => 'ga'
				));
			}
		}
	}

	public function output_javascript() {
		echo $this->generate_javascript();
	}

	public function generate_javascript() {
		$foobox = $GLOBALS['foobox'];
		if ($foobox === false) return;

		$track_pageviews = $foobox->is_option_checked('ga_track_pageviews', true);
		$track_events    = $foobox->is_option_checked('ga_track_events', true);
		$track_deeplinks = !$foobox->is_option_checked('disble_deeplinking') && $foobox->is_option_checked('ga_deeplink_pageviews', true);
		$event_category  = $foobox->get_option('ga_event_category', 'Images');
		$event_action    = $foobox->get_option('ga_event_action', 'View');
		$track_social    = $foobox->is_option_checked('ga_track_social', true);
		$social_category = $foobox->get_option('ga_social_category', 'Social Share');

		if ($track_pageviews === false && $track_events === false && $track_social === false) {
			//got nothing to do here
			return;
		}

		$ga_js  = '';
		$gaq_js = '';

		if ($track_pageviews === true) {
			if ($track_deeplinks) {
				$ga_js .= "ga('send', 'pageview', location.pathname + location.search  + location.hash);
					";
				$gaq_js .= "_gaq.push(['_trackPageview', location.pathname + location.search  + location.hash]);
					";
			} else {
				$ga_js .= "ga('send', 'pageview', trackUrl);
					";
				$gaq_js .= "_gaq.push(['_trackPageview', trackUrl]);
					";
			}
		}

		if ($track_events === true) {
			$ga_js .= "ga('send', 'event', '$event_category', '$event_action', e.thumb.target);";
			$gaq_js .= "_gaq.push(['_trackEvent', '$event_category', '$event_action', e.thumb.target]);";
		}

		if ($track_social === true) {
			$ga_social = "$('.foobox-social a').click(function(e) {
				var social_action = $(this).attr('title'),
					social_url = $(this).attr('href');
				if (typeof ga != 'undefined') {
					ga('send', 'event', '{$social_category}', social_action, social_url);
				} else if (typeof _gaq != 'undefined') {
					_gaq.push(['_trackEvent', '{$social_category}', social_action, social_url]);
				}
			});";
		} else {
			$ga_social = '';
		}

		$base_url = untrailingslashit(home_url());

		$js = "
	/* FooBox Google Analytics code */
	(function( FOOBOX, $, undefined ) {
		FOOBOX.setup_ga = function() {
			$('.foobox-instance').bind('foobox_image_onload', function(e) {
				var trackUrl = e.thumb.target.replace('{$base_url}', '');
				if (typeof ga != 'undefined') {
					{$ga_js}
				} else if (typeof _gaq != 'undefined') {
					{$gaq_js}
				}
			});
			{$ga_social}
		};
	}( window.FOOBOX = window.FOOBOX || {}, jQuery ));

	jQuery(function($) {
		FOOBOX.setup_ga();
	});
";

		return '<script type="text/javascript">' . $js . '</script>';
	}
}