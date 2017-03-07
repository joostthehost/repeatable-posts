(function($) {

	/*
	 * POST REPEAT
	 */
	$( document ).on( 'click', '.edit-hm-post-repeat', function( e ) {

		e.preventDefault();

		$( this ).hide();
		$( '.misc-pub-hm-post-repeat strong' ).hide();
		$( '#hm-post-repeat' ).show();
		$( '#hm-post-repeat' ).find( 'select' ).focus();

	} );

	$( document ).on( 'click', '#hm-post-repeat a', function( e ) {

		e.preventDefault();

		$( '.misc-pub-hm-post-repeat strong' ).text( $( '#hm-post-repeat' ).find( 'option:selected' ).text() ).show();
		$( '.edit-hm-post-repeat' ).show();
		$( '#hm-post-repeat' ).hide();

	} );


	/*
	 * POST UNPUBLISH
	 */
    $( document ).on( 'click', '.edit-hm-post-unpublish', function( e ) {

        e.preventDefault();

        $( this ).hide();
        //$( '.misc-pub-hm-post-unpublish strong' ).hide();
        $( '#hm-post-unpublish' ).show();
        $( '#hm-post-unpublish' ).find( 'select' ).focus();

    } );

    $( document ).on( 'click', '#hm-post-unpublish a', function( e ) {

        e.preventDefault();

        $( '.misc-pub-hm-post-unpublish strong' ).text( $( '#hm-post-unpublish' ).find( 'option:selected' ).text() ).show();
        $( '.edit-hm-post-unpublish' ).show();
        $( '#hm-post-unpublish' ).hide();

    } );

}(jQuery));