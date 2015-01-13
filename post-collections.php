<?php
/*
Plugin Name:  Post Collections
Description:  Post Collections
Version:      1.1.1
Author:       John Blackbourn for CFTP

Copyright © 2012 John Blackbourn & Code for the People Ltd

*/

defined( 'ABSPATH' ) or die();

class Post_Collections {

	function __construct() {

		# Actions
		add_action( 'init',                           array( $this, 'init' ), 99 );
		add_action( 'add_meta_boxes',                 array( $this, 'meta_boxes' ) );
		add_action( 'wp_ajax_post_collection_search', array( $this, 'ajax_search' ) );
		add_action( 'load-post-new.php',              array( $this, 'enqueue_assets' ) );
		add_action( 'load-post.php',                  array( $this, 'enqueue_assets' ) );

		require $this->plugin_path( 'template.php' );

	}

	function init() {

		load_plugin_textdomain( 'post-collections', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		$post_types = $this->get_supported_post_types();

		if ( empty( $post_types ) )
			return;

		foreach ( $post_types as $post_type => $args )
			add_post_type_support( $post_type, 'post-collections', $args );

		register_taxonomy( 'post-collection', array_keys( $post_types ), array(
			'public' => false,
			'sort'   => 'term_order'
		) );

	}

	function ajax_search() {

		$pto = get_post_type_object( $_POST['post_type'] );

		if ( !$pto or !current_user_can( $pto->cap->edit_posts ) )
			die( '-2');

		$type    = $this->get_supported_post_type( $_POST['post_type'] );
		$search  = $_POST['search'];

		if ( empty( $type ) )
			die( '-3' );

		query_posts( array(
			'posts_per_page' => 20,
			'post_status'    => 'publish',
			'post_type'      => $type['post_types'],
			's'              => $search,
		) );

		if ( !have_posts() )
			die( '-4' );

		$posts = array();

		while ( have_posts() ) {
			the_post();

			$posts[] = array(
				'ID'         => get_the_ID(),
				'post_title' => get_the_title(),
				'post_type'  => get_post_type_object( get_post_type() )->labels->singular_name,
			);

		}

		wp_send_json( compact( 'posts' )  );

	}

	/**
	 * Return the supported post types
	 * 
	 * @uses post_collection_types_in (custom filter)
	 * 
	 * @return array An array of post types, with indexes equal to values
	 */
	function get_supported_post_types() {

		if ( isset( $this->post_types ) )
			return $this->post_types;

		$this->post_types = apply_filters( 'post_collection_types', array() );

		foreach ( $this->post_types as $type => &$pt ) {
			if ( !is_array( $pt ) )
				$pt = array();
			if ( !isset( $pt['post_types'] ) ) {
				$pt['post_types'] = get_post_types( array( 'public' => true ) );
				unset( $pt['post_types']['attachment'] );
			}
			if ( ! is_array( $pt[ 'post_types' ] ) )
				$pt[ 'post_types' ] = (array) $pt[ 'post_types' ];
			// N.B. to filterers the indexes and values in the post_types array are the same, e.g.
			// array(
			//     [post] => post,
			//     [page] => page
			// );
			$pt[ 'post_types' ] = apply_filters( 'post_collection_types_in', $pt[ 'post_types' ], $type );
		}

		return $this->post_types;

	}

	function get_supported_post_type( $post_type ) {

		$types = $this->get_supported_post_types();

		if ( !isset( $types[$post_type] ) )
			return false;

		unset( $types[$post_type]['post_types'][$post_type] );

		return $types[$post_type];

	}

	function meta_boxes( $post_type ) {

		if ( !post_type_supports( $post_type, 'post-collections' ) )
			return;

		$title = apply_filters( 'post_collection_title', __( 'Collection', 'post-collections' ), $post_type );

		add_meta_box(
			'post-collections',
			$title,
			array( $this, 'meta_box' ),
			$post_type,
			'normal',
			'high'
		);

	}

	function meta_box( $post, $args ) {

		$type       = $this->get_supported_post_type( $post->post_type );
		$collection = get_post_collection( $post->ID );

		?>

		<div id="post-collection-controls" class="hide-if-no-js">
			<p class="description"><?php _e( 'Add an item to this collection:', 'post-collections' ); ?></p>
			<p><label><?php _e( 'Search:', 'post-collections' ); ?> <input id="post-collection-search" autocomplete="off" type="text" /></label><img src="<?php echo admin_url( 'images/wpspin_light.gif' ); ?>" alt="" class="ajax-loading" /></p>
			<div id="post-collection-results">
				<ul></ul>
			</div>
			<input type="button" class="button" id="post-collection-add" value="<?php esc_attr_e( 'Add to Collection', 'post-collections' ); ?>" />
		</div>

		<ul id="post-collection-list" class="menu">
			<li class="menu-item" id="post-collection-master">
				<dl class="menu-item-bar">
					<dt class="menu-item-handle">
						<span class="item-title"></span>
						<span class="item-controls">
							<span class="item-type"></span>
						</span>
						<a href="#" class="item-remove" title="<?php esc_attr_e( 'Remove', 'post-collections' ); ?>">
							<span class="screen-reader-text"><?php _e( 'Remove', 'post-collections' ); ?></span>
						</a>
					</dt>
				</dl>
				<input class="item-input" type="hidden" name="tax_input[post-collection][]" value="" />
			</li>
			<?php

			foreach ( $collection as $term ) {

				if ( empty( $term->post ) )
					continue;

				$pto = get_post_type_object( $term->post->post_type );

				?>
				<li class="menu-item">
					<dl class="menu-item-bar">
						<dt class="menu-item-handle">
							<span class="item-title"><?php echo get_the_title( $term->post ); ?></span>
							<span class="item-controls">
								<span class="item-type"><?php echo esc_html( $pto->labels->singular_name ); ?></span>
							</span>
							<a href="#" class="item-remove" title="<?php esc_attr_e( 'Remove', 'post-collections' ); ?>">
								<span class="screen-reader-text"><?php _e( 'Remove', 'post-collections' ); ?></span>
							</a>
						</dt>
					</dl>
					<input class="item-input" type="hidden" name="tax_input[post-collection][]" value="<?php echo intval( $term->name ); ?>" />
				</li>
				<?php

			}

			?>
		</ul>
		<div class="clear"></div>
		<?php

	}

	function enqueue_assets() {

		if ( !post_type_supports( get_current_screen()->post_type, 'post-collections' ) )
			return;

		wp_enqueue_script(
			'post-collections',
			$this->plugin_url( 'post-collections.js' ),
			array( 'jquery', 'jquery-ui-sortable' ),
			$this->plugin_ver( 'post-collections.js' )
		);

		wp_localize_script(
			'post-collections',
			'post_collections',
			array(
				'post_type'  => get_current_screen()->post_type,
				'no_results' => __( 'No results', 'post_collections')
			)
		);

		wp_enqueue_style(
			'post-collections',
			$this->plugin_url( 'post-collections.css' ),
			null,
			$this->plugin_ver( 'post-collections.css' )
		);

	}

	function plugin_url( $file = '' ) {
		return $this->plugin( 'url', $file );
	}

	function plugin_path( $file = '' ) {
		return $this->plugin( 'path', $file );
	}

	function plugin_ver( $file ) {
		return filemtime( $this->plugin_path( $file ) );
	}

	function plugin_base() {
		return $this->plugin( 'base' );
	}

	function plugin( $item, $file = '' ) {
		if ( !isset( $this->plugin ) ) {
			$this->plugin = array(
				'url'  => plugin_dir_url( __FILE__ ),
				'path' => plugin_dir_path( __FILE__ ),
				'base' => plugin_basename( __FILE__ )
			);
		}
		return $this->plugin[$item] . ltrim( $file, '/' );
	}

}

global $post_collections;

$post_collections = new Post_Collections;

?>