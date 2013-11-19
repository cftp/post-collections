
jQuery(function($){

	var searchTimer = null;

	$('#post-collection-list').sortable({
		handle      : '.menu-item-handle',
		placeholder : 'sortable-placeholder'
	});

	$('#post-collection-list').on('click', '.item-remove', function(e){
		$(this).closest('li').slideUp(function(){
			$(this).remove();
		});
		e.preventDefault();
	});

	$('#post-collection-search').keypress(function(e) {

		var t = $(this);

		if ( 13 == e.which ) {
			post_collection_search( t );
			return false;
		}

		if ( searchTimer )
			clearTimeout( searchTimer );

		searchTimer = setTimeout( function(){
			post_collection_search( t );
		}, 500 );

	});

	$('#post-collection-add').click(function(e){
		checked = $('#post-collection-results').find(':checked');
		if ( !checked.length )
			return;
		checked.each(function(i,v){
			item = $(this);
			item.prop('checked',false);
			// clone master menu item
			m = $('#post-collection-master').clone();
			// fill the shit in
			m.find('.item-title').text( item.attr('data-title') );
			m.find('.item-type').text( item.attr('data-type') );
			m.find('.item-input').val( item.attr('data-id') );
			m.removeAttr('id');
			// append it
			m.appendTo( $('#post-collection-list' ) );
		});
	} );

	function post_collection_search( input ) {

		search = $.trim( input.val() );

		if ( search.length < 2 )
			return;

		params = {
			'action'    : 'post_collection_search',
			'post_type' : post_collections.post_type,
			'search'    : search
		};

		$('#post-collection-controls').find('.ajax-loading').css('visibility','visible');

		$.post( ajaxurl, params, function( r ) {

			if ( r.posts ) {
				html = '';
				$.each( r.posts, function(i,v){
					html += '<li><label><input type="checkbox" value="' + v.ID + '" data-title="' + v.post_title + '" data-type="' + v.post_type + '" data-id="' + v.ID + '" />&nbsp;' + v.post_type + ': ' + v.post_title + '</label></li>';
				} );
				$('#post-collection-add').show();
			} else {
				$('#post-collection-add').hide();
				html = '<li>' + post_collections.no_results + '</li>';
			}

			$('#post-collection-results').find('ul').html(html);
			$('#post-collection-results').show();
			$('#post-collection-controls').find('.ajax-loading').css('visibility','hidden');

		} );

	}

});
