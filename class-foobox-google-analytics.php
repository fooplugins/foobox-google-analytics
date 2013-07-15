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
	protected $version = '1.0.0';

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
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Initialize the plugin
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		if ( is_admin() ) {

			add_action( 'foobox_pre_tab', array( $this, 'add_ga_settings_tab' ) );

		} else {

			add_action('init', array( $this, 'init_scripts' ) );

		}
	}

	public function init_scripts() {
		$foobox = $GLOBALS['foobox'];

		$where = 'wp_head';

		if ($foobox->is_option_checked('scripts_in_footer')) {
			$where = 'wp_print_footer_scripts';
		}

		add_action($where, array(&$this, 'output_javascript'), 11);
	}

	public function add_ga_settings_tab($tab_id) {
		if ( $tab_id === 'demo' ) {
			$foobox = $GLOBALS['foobox'];

			$foobox->admin_settings_add_tab('ga', __('Google Analytics', 'foobox'));

			$foobox->admin_settings_add(array(
				'id'      => 'ga_track_pageviews',
				'title'   => __('Enable Pageview Tracking', 'foobox'),
				'desc'    => __('If enabled, a pageview will be recorded when an image is opened in FooBox.', 'foobox'),
				'default' => 'on',
				'type'    => 'checkbox',
				'tab'     => 'ga'
			));

			$foobox->admin_settings_add(array(
				'id'      => 'ga_track_events',
				'title'   => __('Enable Event Tracking', 'foobox'),
				'desc'    => __('If enabled, a custom event will be recorded when an image is opened in FooBox.', 'foobox'),
				'default' => 'on',
				'type'    => 'checkbox',
				'tab'     => 'ga'
			));

			$foobox->admin_settings_add(array(
				'id'    => 'ga_event_category',
				'title' => 'Event Category',
				'desc'  => __('Used in event tracking, this is the name for the group of objects you want to track. In this scenario, the group of objects are your images shown within FooBox.', 'foobox'),
				'default' => 'Images',
				'type'  => 'text',
				'tab'   => 'ga'
			));

			$foobox->admin_settings_add(array(
				'id'    => 'ga_event_action',
				'title' => 'Event Action',
				'desc'  => __('Used in event tracking, this is the name for the type of user interaction. In this scenario, viewing the image within FooBox.', 'foobox'),
				'default' => 'View',
				'type'  => 'text',
				'tab'   => 'ga'
			));

			if ($foobox->is_option_checked('enable_debug')) {
				$foobox->admin_settings_add(array(
					'id'      => 'ga_output',
					'title'   => __('Javscript Output (Debug)', 'foobox'),
					'type'    => 'html',
					'desc'	  => '<pre>' . htmlentities( $this->generate_javascript() ) . '</pre>',
					'tab'     => 'ga'
				));
			}
		}
	}

	public function output_javascript() {
		echo $this->generate_javascript();
	}

	public function generate_javascript() {

		$foobox = $GLOBALS['foobox'];

		$track_pageviews = $foobox->is_option_checked('ga_track_pageviews', true);
		$track_events = $foobox->is_option_checked('ga_track_events', true);
		$event_category = $foobox->get_option('ga_event_category', 'Images');
		$event_action = $foobox->get_option('ga_event_action', 'View');

		if ($track_pageviews === false && $track_events === false) {
			//got nothing to do here
			return;
		}

		$ga_js = '';
		$gaq_js = '';

		if ($track_pageviews === true) {
			$ga_js .= "ga('send', 'pageview', e.thumb.target);
					";
			$gaq_js .= "_gaq.push(['_trackPageview', e.thumb.target]);
					";
		}

		if ($track_events === true) {
			$ga_js .= "ga('send', 'event', '$event_category', '$event_action', e.thumb.target);";
			$gaq_js .= "_gaq.push(['_trackEvent', '$event_category', '$event_action', e.thumb.target]);";
		}

		$js = "
	/* FooBox Google Analytics code */
	(function( FOOBOX, $, undefined ) {
		FOOBOX.setup_ga = function() {
			$('.foobox-instance').bind('foobox_image_onload', function(e) {
				if (typeof ga != 'undefined') {
					{$ga_js}
				} else if (typeof _gaq != 'undefined') {
					{$gaq_js}
				}
			});
		};
	}( window.FOOBOX = window.FOOBOX || {}, jQuery ));

	jQuery(function($) {
		FOOBOX.setup_ga();
	});
";
		return '<script type="text/javascript">' . $js . '</script>';
	}
}