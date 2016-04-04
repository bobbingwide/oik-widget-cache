<?php // (C) Copyright Bobbing Wide 2016

/**
 * oik_widget_cache class
 *
 * This class started life as WidgetOutputCache
 * but has been changed to work with included_ids instead of excluded_ids
 * Additionally the logic is intended to be lazy loaded on the first widget
 * 
 * There is also the potential to split the WordPress admin code from the front-end
 * which would require implementation as multiple classes
 * with oik_widget_cache_admin extending oik_widget_cache
 *
 * and the ability to indicate how long something should be cached
 * or what causes the cache to be cleared.
 *
 */
class oik_widget_cache {

	// Store IDs of widgets to include from cache
	private $included_ids = array();


	protected function __construct() {

		// Enable localization
		add_action( 'plugins_loaded', array( $this, 'init_l10n' ) );

		// Overwrite widget callback to cache the output
		add_filter( 'widget_display_callback', array( $this, 'widget_callback' ), 10, 3 );

		// Cache invalidation for widgets
		add_filter( 'widget_update_callback', array( $this, 'cache_bump' ) );

		// Allow widgets to be included from the cache
		add_action( 'in_widget_form', array( $this, 'widget_controls' ), 10, 3 );

		// Load widget cache include settings
		add_action( 'init', array( $this, 'init' ), 10 );

		// Save widget cache settings
		add_action( 'sidebar_admin_setup', array( $this, 'save_widget_controls' ) );

	}


	public static function instance() {

		static $instance;

		if ( ! $instance )
			$instance = new self();

		return $instance;

	}


	function init_l10n() {

		//load_plugin_textdomain( 'oik-widget-cache', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	}


	function init() {

		$this->included_ids = (array) get_option( 'cache-widgets-included', array() );

	}


	function widget_callback( $instance, $widget_object, $args ) {
		
		// Don't return the widget
		if ( false === $instance || ! is_subclass_of( $widget_object, 'WP_Widget' ) )
			return $instance;

		if ( !in_array( $widget_object->id, $this->included_ids ) )
			return $instance;

		$timer_start = microtime(true);

		$cache_key = sprintf( 'cwdgt-%s',
				md5( $widget_object->id . get_option( 'cache-widgets-version', 1 ) )
			);

		$cached_widget = get_transient( $cache_key );

		if ( empty( $cached_widget ) ) {

			ob_start();
				$widget_object->widget( $args, $instance );
				$cached_widget = ob_get_contents();
			ob_end_clean();

			set_transient(
				$cache_key,
				$cached_widget,
				apply_filters( 'widget_output_cache_ttl', 60 * 12, $args )
			);

			printf(
				'%s <!-- Stored in widget cache in %s seconds (%s) -->',
				$cached_widget,
				round( microtime(true) - $timer_start, 4 ),
				$cache_key
			);

		} else {

			printf(
				'%s <!-- From widget cache in %s seconds (%s) -->',
				$cached_widget,
				round( microtime(true) - $timer_start, 4 ),
				$cache_key
			);

		}

		// We already echoed the widget, so return false
		return false;
		
	}


	function cache_bump( $instance ) {

		update_option( 'cache-widgets-version', time() );

		return $instance;

	}


	function widget_controls( $object, $return, $instance ) {

		$is_included = in_array( $object->id, $this->included_ids );

		printf(
			'<p>
				<label>
					<input type="checkbox" name="widget-cache-include" value="%s" %s />
					%s
				</label>
			</p>',
			esc_attr( $object->id ),
			checked( $is_included, true, false ),
			esc_html__( 'Include this widget in cache', 'oik-widget-cache' )
		);

	}


	function save_widget_controls() {

		// current_user_can( 'edit_theme_options' ) is already being checked in widgets.php
		if ( empty( $_POST ) || ! isset( $_POST['widget-id'] ) )
			return;

		$widget_id = $_POST['widget-id'];
		$is_included = isset( $_POST['widget-cache-include'] );

		if ( ! isset($_POST['delete_widget']) && $is_included ) {

			// Wiget is being saved and it is being included too
			$this->included_ids[] = $widget_id;

		} elseif ( in_array( $widget_id, $this->included_ids ) ) {

			// Widget is being removed, remove it from exclusions too
			$include_pos_key = array_search( $widget_id, $this->included_ids );
			unset( $this->included_ids[ $include_pos_key ] );

		}

		$this->included_ids = array_unique( $this->included_ids );

		update_option( 'cache-widgets-included', $this->included_ids );

	}


}
