<?php // (C) Copyright Bobbing Wide 2016, 2017, 2023

/**
 * oik_widget_cache class
 *
 * This class started life as WidgetOutputCache ( Copyright 2013-2015 Kaspars Dambis ( kasparsd ) )
 * but has been changed to work with included_ids instead of excluded_ids.
 * 
 * @TODO
 * Additionally the logic is intended to be lazy loaded on the first widget.
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

	public $timer_start;
	public $cache_key;
	public $dependencies;
	public $cached_widget;
	public $dependencies_cache;
	public $bw_jq;
	public $bw_jq_changes;

	// Store IDs of widgets to include from cache
	private $included_ids = array();


	/**
	 * Constructor for oik_widget_cache
	 * 
	 */
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
		
		
		add_action( "oik_loaded", array( $this, "oik_widget_cache_oik_loaded" ) );

	}


	public static function instance() {

		static $instance;

		if ( ! $instance )
			$instance = new self();

		return $instance;

	}


	/** 
	 * Implements 'plugins_loaded' 
	 */
	function init_l10n() {

		//load_plugin_textdomain( 'oik-widget-cache', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	}
	
	/** 
	 * Implements 'init' hook
	 * 
	 * Determines which widgets are to be cached.
	 */
	function init() {
		$this->included_ids = (array) get_option( 'cache-widgets-included', array() );
	}
	
	/**
	 * Implement 'widget_display_callback' filter
	 * 
	 * @param array $instance
	 * @param WP_Widget $widget_object
	 * @param array $args
	 * @return false|$instance - false when we're doing it, $instance otherwise
	 */
	function widget_callback( $instance, $widget_object, $args ) {
		
		// Don't return the widget
		if ( false === $instance || ! is_subclass_of( $widget_object, 'WP_Widget' ) ) {
			return $instance;
		}

		if ( !in_array( $widget_object->id, $this->included_ids ) ) {
			return $instance;
		}

		$this->timer_start = microtime(true);
		$this->set_cache_key( $widget_object->id );

		$this->get_cached_widget();

		if ( empty( $this->cached_widget ) ) {
			$this->save_dependencies();
			$this->save_bw_jq();
			ob_start();
			$widget_object->widget( $args, $instance );
			$this->cached_widget = ob_get_contents();
			ob_end_clean();
			$this->determine_dependencies();
			$this->determine_bw_jq_changes();
			$this->cache_widget( $args );
			$this->display_widget( "into cache" );
		} else {
			$this->replay_dependencies();
			$this->display_widget( "from cache" );
			$this->replay_bw_jq_changes();
		}
		// We already echoed the widget, so return false
		return false;
	}
	
	/**
	 * Displays the widget.
	 * 
	 * @param string $widget_contents
	 * @param string $method
	 
			printf(
				'%s <!-- Stored in widget cache in %s seconds (%s) -->',
				$cached_widget,
				round( microtime(true) - $timer_start, 4 ),
				$cache_key
			);
			
			
			printf(
				'%s <!--  in %s seconds (%s) -->',
				$cached_widget,
				round( microtime(true) - $timer_start, 4 ),
				$cache_key
			);

	 */
	function display_widget( $method ) {
		$elapsed = microtime( true ) - $this->timer_start;
		$elapsed = number_format( $elapsed, 6 );
		printf( "<!-- Method: %s, ID: %s, Elapsed %s -->", $method, $this->cache_key, $elapsed );
		echo $this->cached_widget;
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

			// Widget is being removed, remove it from inclusion too
			$include_pos_key = array_search( $widget_id, $this->included_ids );
			unset( $this->included_ids[ $include_pos_key ] );

		}

		$this->included_ids = array_unique( $this->included_ids );

		update_option( 'cache-widgets-included', $this->included_ids );

	}
	
	/**
	 * Sets the cache key.
	 *
	 * Sets a unique cache key for the widget ID and cache widgets version
	 */
	function set_cache_key( $id ) {
		$id_version = $id;
		$id_version .= get_option( 'cache-widgets-version', 1 );
		$id_version = md5( $id_version );
		$this->cache_key = 'cwdgt-' . $id_version;
	}
	
	/**
	 * Saves the current state of dependencies.
	 */
	function save_dependencies() {
		$this->dependencies =  null;
		if ( $this->dependencies_cache ) {
			$this->dependencies_cache->save_dependencies();
		}
	}
	
	/**
	 * Determines dependencies
	 */
	function determine_dependencies() {
		if ( $this->dependencies_cache ) {
			$this->dependencies_cache->query_dependencies_changes();
			$this->dependencies = $this->dependencies_cache->serialize_dependencies();
		}
		bw_trace2( $this->dependencies, "dependencies!" );
	}
	
	/**
	 * Replays the dependencies saved when the widget was cached.
	 *
	 */
	function replay_dependencies() {
		if ( $this->dependencies_cache ) {
			$this->dependencies_cache->reload_dependencies( $this->dependencies );
			$this->dependencies_cache->replay_dependencies();
		}
	}
	
	/**
	 * Caches the widget and dependencies
	 * 
	 */
	function cache_widget( $args ) {
		$duration = 43200;
		$duration = apply_filters( 'widget_output_cache_ttl', $duration, $args );
		
		$cached_widget = array( "widget" => $this->cached_widget
													, "dependencies" => $this->dependencies
													, "bw_jq_changes" => $this->bw_jq_changes
													);
    set_transient( $this->cache_key, $cached_widget, $duration );
	}
	
	/**
	 * Retrieves the cached widget. 
	 */
	function get_cached_widget() {
		$cached_widget = get_transient( $this->cache_key );
		bw_trace2( $cached_widget );
		
		if ( is_array( $cached_widget ) ) {
		
			$this->cached_widget = $cached_widget['widget'];
			$this->dependencies = $cached_widget['dependencies'];
			$this->bw_jq_changes = $cached_widget['bw_jq_changes'];
		} else {
			$this->cached_widget = $cached_widget; 
		}
	}
	
	/**
	 * Saves existing bw_jq global
	 * 
	 * What if the global is not set?
	 */
	function save_bw_jq() {
		global $bw_jq;
		$this->bw_jq = $bw_jq;
	}
	
	/**
	 * Tests for new inline jQuery code.
	 * 
	 */
	function determine_bw_jq_changes() {
		global $bw_jq;
		if ( $bw_jq != $this->bw_jq ) {
			$saved_len = ( null === $this->bw_jq ) ? 0 : strlen( $this->bw_jq );
			$this->bw_jq_changes =	substr( $bw_jq, $saved_len );
		} else {
			$this->bw_jq_changes = null;
		}
	}
	
	/**
	 * Replays the changes to $bw_jq
	 *
	 * If there are some changes then we need to requeue these.
	 * We have to test that bw_jq exists; oik may have been deactivated since the data was cached.
	 */
	function replay_bw_jq_changes() {
		if ( $this->bw_jq_changes && function_exists( "bw_jq") ) {
			bw_jq( $this->bw_jq_changes );
		}
	}
	
	
	/**
	 * Implement "oik_loaded" 
	 * 
	 * When oik has been loaded we can try loading the class-dependencies-cache library
	 * if this is available then we'll be able to cache the scripts and styles produced in widgets
	 */
	function oik_widget_cache_oik_loaded() {
		oik_require_lib( "class-dependencies-cache" );
		if ( class_exists( "dependencies_cache" ) ) {
			$this->dependencies_cache = dependencies_cache::instance();
		}	else {
			$this->dependencies_cache = null;
		}	
	}
	

}
