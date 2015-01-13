<?php

function get_post_collection( $post_id = null ) {

	static $post_collections;

	$post = get_post( $post_id );

	if ( is_array( $post_collections ) and isset( $post_collections[$post->ID] ) )
		return $post_collections[$post->ID];

	if ( !post_type_supports( $post->post_type, 'post-collections' ) )
		return false;

	# We can't use get_the_terms() here because it doesn't support
	# the arguments we need for our term ordering.

	$collection = wp_get_object_terms( $post->ID, 'post-collection', array(
		'orderby' => 'term_order'
	) );

	# @TODO something like get_posts( 'include=foo,bar' ) to prime the post cache in one hit

	foreach ( $collection as $term ) {
		$term_post = get_post( $term->name );
		# Check if the post exists and is published
		if ( is_a( $term_post, 'WP_Post' ) && $term_post->post_status == 'publish' ) {
			$term->post = $term_post;
		}
	}

	$post_collections[$post->ID] = $collection;

	return $collection;

}

?>