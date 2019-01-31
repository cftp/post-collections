<?php

use Timber\Timber;

/**
 * Class Collection_Widget
 */
class Collection_Widget extends WP_Widget {

	/**
	 * The available layout options.
	 */
	const LAYOUT_OPTIONS = [
		'hero'             => [
			'label' => 'Hero',
			'class' => 'listing--hero',
		],
		'single'           => [
			'label' => 'Single',
			'class' => 'listing--single',
		],
		'single-w-sidebar' => [
			'label' => 'Single inc sidebar',
			'class' => 's-container--inc-sidebar listing--single-inc-sidebar',
		],
		'grid'             => [
			'label' => 'Grid',
			'class' => 'listing--grid',
		],
		'grid-w-sidebar'   => [
			'label' => 'Grid inc sidebar',
			'class' => 's-container--inc-sidebar listing--grid-inc-sidebar',
		],
		'filmstrip-3'      => [
			'label' => 'Filmstrip 3',
			'class' => 'filmstrip-3 listing--filmstrip',
		],
		'filmstrip-4'      => [
			'label' => 'Filmstrip 4',
			'class' => 'filmstrip-4 listing--filmstrip',
		],
		'filmstrip-5'      => [
			'label' => 'Filmstrip 5',
			'class' => 'filmstrip-5 listing--filmstrip',
		],
		'sidebar'          => [
			'label' => 'Sidebar',
			'class' => 'listing--sidebar',
		],
	];

	/**
	 * The available container options.
	 */
	const CONTAINER_OPTIONS = [
		'default'   => [
			'label' => 'Default',
			'class' => 's-container',
		],
		'full'      => [
			'label' => 'Full',
			'class' => 's-container s-container--full sticky-anchor',
		],
		'fullbleed' => [
			'label' => 'Fullbleed',
			'class' => 's-container s-container--fullbleed sticky-anchor',
		],
		'sidebar'   => [
			'label' => 'Sidebar',
			'class' => 's-sidebar',
		],
	];

	/**
	 * The available style options.
	 */
	const STYLE_OPTIONS = [
		'style-a' => [
			'label' => 'Style A',
			'class' => 'a',
		],
		'style-b' => [
			'label' => 'Style B',
			'class' => 'b',
		],
		'style-c' => [
			'label' => 'Style C',
			'class' => 'c',
		],
	];

	/**
	 * The available image options.
	 */
	const IMAGE_OPTIONS = [
		'text-only' => [
			'label' => 'Text Only',
			'class' => '',
		],
		'landscape' => [
			'label' => 'Landscape',
			'class' => 'image-aspect-landscape',
		],
		'square'    => [
			'label' => 'Square',
			'class' => 'image-aspect-square',
		],
		'portrait'  => [
			'label' => 'Portrait',
			'class' => 'image-aspect-portrait',
		],
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		WP_Widget::__construct( 'collection_widget', __( 'Collection Widget' ), [
			'description' => __( 'Collection Widget' ),
		] );
	}

	/**
	 * The widget template.
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		if ( empty( $instance['collection_items'] ) ) {
			return;
		}

		$extra_classes = [];
		$title         = ( isset( $instance['title'] ) ? $instance['title'] : '' );
		$title         = apply_filters( 'widget_title', $title );
		$hide_title    = ( isset( $instance['hide_title'] ) ? $instance['hide_title'] : false );
		$layout        = ( isset( $instance['layout'] ) ? $instance['layout'] : 'single' );
		$container     = ( isset( $instance['container'] ) ? $instance['container'] : 'default' );
		$image         = ( isset( $instance['image'] ) ? $instance['image'] : '' );
		$style         = ( isset( $instance['style'] ) ? $instance['style'] : 'style-a' );

		if ( $layout && ! empty( self::LAYOUT_OPTIONS[ $layout ]['class'] ) ) {
			$extra_classes[] = self::LAYOUT_OPTIONS[ $layout ]['class'];

			if ( $style && ! empty( self::STYLE_OPTIONS[ $style ]['class'] ) ) {
				$extra_classes[] = self::LAYOUT_OPTIONS[ $layout ]['class'] . '-' . self::STYLE_OPTIONS[ $style ]['class'];
			}
		}

		if ( $container ) {
			$extra_classes = array_merge( explode( ' ', $container ), $extra_classes );
		}

		if ( $image && ! empty( self::IMAGE_OPTIONS[ $image ]['class'] ) ) {
			$extra_classes[] = self::IMAGE_OPTIONS[ $image ]['class'];
		}

		echo $args['before_widget'] = str_replace( 'class="', 'class="' . implode( ' ', $extra_classes ) . ' ', $args['before_widget'] );

		if ( ! empty( $title ) && ! $hide_title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		$items                  = explode( ',', $instance['collection_items'] );
		$context                = Timber::get_context();
		$context['posts']       = Timber::get_posts( $items );
		$context['image_style'] = $image;

		echo Timber::fetch( 'post-collections/post-collections.twig', $context );

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$id     = mt_rand( 1, 10000 );
		$title  = ( isset( $instance['title'] ) ? $instance['title'] : '' );
		$layout = ( isset( $instance['layout'] ) ? $instance['layout'] : 'single' );
		$image  = ( isset( $instance['image'] ) ? $instance['image'] : '' );
		$style  = ( isset( $instance['style'] ) ? $instance['style'] : 'style-a' );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">Title</label>
			<input class="widefat"
				   id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				   type="text"
				   value="<?php echo esc_attr( $title ); ?>"/>
		</p>

		<p>
			<label>Add an item to this collection:</label>
			<input type="hidden"
				   class="js-post-collection-items-wrapper js-post-collection-items-<?php echo esc_attr( $id ); ?>"
				   name="<?php echo esc_attr( $this->get_field_name( 'collection_items' ) ); ?>">
		</p>

		<?php if ( ! empty( self::LAYOUT_OPTIONS ) ) : ?>
			<p>
				<label for="layout">Layout</label>
				<select class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'layout' ) ); ?>">
					<?php foreach ( self::LAYOUT_OPTIONS as $key => $layout_option ) : ?>
						<option
							<?php selected( $layout, $key ); ?>
								value="<?php echo esc_attr( $key ); ?>">
							<?php echo esc_html( $layout_option['label'] ); ?>
						</option>
					<?php endforeach; ?>s
				</select>
			</p>
		<?php endif; ?>

		<?php if ( ! empty( self::STYLE_OPTIONS ) ) : ?>
			<p>
				<label for="layout">Style</label>
				<select class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'style' ) ); ?>">
					<?php foreach ( self::STYLE_OPTIONS as $key => $layout_option ) : ?>
						<option
							<?php selected( $style, $key ); ?>
								value="<?php echo esc_attr( $key ); ?>">
							<?php echo esc_html( $layout_option['label'] ); ?>
						</option>
					<?php endforeach; ?>s
				</select>
			</p>
		<?php endif; ?>

		<?php if ( ! empty( self::IMAGE_OPTIONS ) ) : ?>
			<p>
				<label for="layout">Image</label>
				<select class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'image' ) ); ?>">
					<?php foreach ( self::IMAGE_OPTIONS as $key => $layout_option ) : ?>
						<option
							<?php selected( $image, $key ); ?>
								value="<?php echo esc_attr( $key ); ?>">
							<?php echo esc_html( $layout_option['label'] ); ?>
						</option>
					<?php endforeach; ?>s
				</select>
			</p>
		<?php endif; ?>

		<style>
			.js-post-collection-items-wrapper,
			.js-post-collection-items-wrapper .select2-input {
				display: block;
				width: 100%;
			}
		</style>

		<script>
		  jQuery(function ($) {
			var selections = [
				<?php
				if ( ! empty( $instance['collection_items'] ) ) {
					$items = explode( ',', $instance['collection_items'] );
					foreach ( $items as $item ) {
						$post = get_post( $item );
						if ( ! $post instanceof WP_Post ) {
							continue;
						}

						echo '{id:' . esc_js( $post->ID ) . ",text:'" . esc_js( $post->post_title ) . "'},";
					}
				}
				?>
			];

			$('.js-post-collection-items-<?php echo esc_attr( $id ); ?>').select2({
			  multiple: true,
			  placeholder: "Search for an item",
			  minimumInputLength: 1,
			  ajax: {
				url: "/wp-json/wp/v2/posts",
				dataType: 'json',
				quietMillis: 250,
				data: function (term, page) {
				  return {
					search: term,
				  };
				},
				results: function (data, page) {
				  let myResults = [];
				  $.each(data, function (index, item) {
					myResults.push({
					  'id': item.id,
					  'text': item.title.rendered
					});
				  });
				  return {
					results: myResults
				  };
				},
				cache: true
			  },
			  allowClear: true,
			  initSelection: function (element, callback) {
				callback(selections);
			  }
			}).select2('val', []);
		  });
		</script>

		<?php
	}
}

add_action( 'widgets_init', function () {
	if ( ! defined( 'KEYSTONE_PREMIUM' ) || ! KEYSTONE_PREMIUM ) {
		return;
	}

	register_widget( 'Collection_Widget' );
} );
