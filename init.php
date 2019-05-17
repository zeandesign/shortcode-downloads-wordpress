<?php

function zd_downloads_cpt() {
  $labels = array(
    'name'               => __( 'Downloads', 'zd' ),
    'singular_name'      => __( 'Download', 'zd' ),
    'menu_name'          => __( 'Downloads', 'zd' ),
    'name_admin_bar'     => __( 'Download', 'zd' ),
    'add_new'            => __( 'Add New', 'zd' ),
    'add_new_item'       => __( 'Add New', 'zd' ),
    'new_item'           => __( 'New', 'zd' ),
    'edit_item'          => __( 'Edit', 'zd' ),
    'view_item'          => __( 'View', 'zd' ),
    'all_items'          => __( 'All', 'zd' ),
    'search_items'       => __( 'Search', 'zd' ),
    'parent_item_colon'  => __( 'Parent', 'zd' ),
    'not_found'          => __( 'No found', 'zd' ),
    'not_found_in_trash' => __( 'No found in Trash.', 'zd' )
  );
 
  $args = array(
    'labels'             => $labels,
    'public'             => true,
    'publicly_queryable' => false,
    'show_ui'            => true,
    'show_in_menu'       => true,
    'query_var'          => true,
    'rewrite'            => array( 'slug' => __( 'downloads', 'zd' ) ),
    'menu_icon'          => 'dashicons-download',
    'capability_type'    => 'post',
    'has_archive'        => false,
    'hierarchical'       => false,
    'menu_position'      => null,
    'show_in_rest'       => true,
    'rest_base'          => 'downloads',
    'rest_controller_class' => 'WP_REST_Posts_Controller',
    'supports'           => array( 'title', 'author' )
  );
 
  register_post_type( 'downloads', $args );
}

add_action( 'init', 'zd_downloads_cpt' );

function zd_downloads_metabox() {
	$prefix = 'zd_';
	
	$downloads_page = new_cmb2_box( array(
		'id'           => $prefix . 'metabox_downloads',
		'title'        => __( 'Options', 'zd' ),
		'object_types' => array( 'downloads' ),
	) );
	
	$downloads_page->add_field( array(
		'name' => __( 'Archive', 'zd' ),
		'id'   => $prefix . 'download',
		'type' => 'file',
	) );
}

add_action( 'cmb2_admin_init', 'zd_downloads_metabox' );

function zd_downloads_callback( $atts, $content = null ) {
	global $post;
	
	$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
	
	$atts = shortcode_atts( array(
		    'class'          => false,
		    'order'          => 'DESC',
		    'orderby'        => 'date',
		    'posts_per_page' => 12,
     ), $atts );
	
	$class   = array();
	$class[] = ( $atts['class'] ) ? ' ' . $atts['class'] : null;

	// Args
	$args['post_type']      = 'downloads';
	$args['post_status']    = 'publish';
	$args['order']          = $atts['order'];
        $args['orderby']        = $atts['orderby'];
	$args['posts_per_page'] = $atts['posts_per_page'];
	$args['paged']          = $paged;

	ob_start();	
	$output = '';
	
	$post_query = new WP_Query( $args );
	if ( $post_query->have_posts() ) :
	
	// Paged
	$items_per_page = $args['posts_per_page'];
	$current_page_permalink = get_page_link( $post->ID );
	$total_found_posts = $post_query->found_posts;
	$total_pages = ceil( $post_query->found_posts / $items_per_page );
	
	$currentPage = $paged;
        $numPages = $total_pages;
        $howMany = 4;
	
	if ( $total_pages > 1 ) :
		$output .= '<nav>';
		$output .= '<ul class="pagination justify-content-center">';

		//$output .= '<li class="page-item">Page '. $currentPage .' of '. $numPages .'</li>';
		
        if ( $currentPage > $howMany ) : // $currentPage > 1
            $output .= '<li class="page-item"><a href="'. $current_page_permalink .'">'. __( 'First', 'zd' ) .'</a></li>';
            $output .= '<li class="page-item">'. get_previous_posts_link( '&laquo;' ) .'</li>';
        endif;

        if ( $currentPage > $howMany + 1 ) :
            $output .= '<li class="page-item"><span>...</span></li>';
        endif;
    
		for ( $pageIndex = $currentPage - $howMany; $pageIndex <= $currentPage + $howMany; $pageIndex++ ) : 
			 $url = $pageIndex == 1 ? $current_page_permalink : $current_page_permalink. 'page/'. $pageIndex .'/';

			 if ( $pageIndex >= 1 && $pageIndex <= $numPages ) :
			    $output .= ( $currentPage == $pageIndex ) ? '<li class="page-item active"><span>'. $pageIndex .'</span></li>' : '<li class="page-item"><a class="page-link" href="'. $url .'">'. $pageIndex .'</a></li>';
			 endif;
		endfor;

		if ( $currentPage < $numPages - $howMany ) :
		    $output .= '<li class="page-item"><span>...</span></li>';
		endif;

		if ( $currentPage < $numPages - $howMany ) : // $currentPage < $numPages
		    $output .= '<li class="page-item">'. get_next_posts_link( '&raquo;', $post_query->max_num_pages ) .'</li>';
		    $output .= '<li class="page-item"><a href="'. $current_page_permalink. 'page/'. $total_pages .'/' .'">'. __( 'Last', 'zd' ) .'</a></li>';
		endif; 
	
		$output .= '</ul>';
		$output .= '</nav>';
	endif;
	
	$output .= '<div class="table-responsive'. implode( ' ', array_filter( $class ) ) .'">'; 
	$output .= '<table class="table table-downloads table-striped table-bordered">';
	$output .= '<thead class="thead-light">
				<tr>
				  <th>Preview</th>
				  <th scope="col">Title</th>
				  <th scope="col">Ext.</th>
				  <th scope="col">Download</th>
				</tr>
			    </thead>';
	$output .= '<tbody>';
	
	if ( $post_query->found_posts > 1 ) :
		$i = 1;
		while ( $post_query->have_posts() ) : $post_query->the_post();
			$url_download = get_post_meta( get_the_ID(), 'zd_download', true );
			$id_download = get_post_meta( get_the_ID(), 'zd_download_id', true );
			$attachment_element = wp_get_attachment_image( $id_download, 'thumbnail' );
			$filetype = wp_check_filetype( $url_download );
		
			if ( strstr( $filetype['type'], 'video/' ) ) :
				$mime_type = 'Video';
			elseif ( strstr( $filetype['type'], 'audio/' ) ) :
				$mime_type = 'Audio';
			elseif ( strstr( $filetype['type'], 'image/' ) ) :
				$mime_type = '<a href="'. $url_download .'" class="ilightbox">'. $attachment_element .'</a>';
			endif;
			
			$output .= '<tr>';
			$output .= '<td class="align-middle preview">'. $mime_type .'</td>';
			$output .= '<td class="align-middle"><strong>'. get_the_title() .'</strong></td>';
			$output .= '<td class="align-middle">'. ZD_Helper::file_info_extension( $url_download ) .'</td>';
			$output .= '<td class="align-middle"><a href="'. $url_download .'" download>Download</a></td>';
			$output .= '</tr>';
		    $i++;
		endwhile;
	else :
	   $output .= '<tr><td class="align-middle" colspan="4">'. __( 'No posts found.', 'zd' ) .'</td></tr>';
	endif;
	
	$output .= '</tbody>';
	$output .= '</table>';
	$output .= '</div>';
	endif;
	
	return $output;
	return ob_get_clean();
}

add_shortcode( 'downloads', 'zd_downloads_callback' );
