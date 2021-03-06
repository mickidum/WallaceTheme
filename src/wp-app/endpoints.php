<?php

add_action('rest_api_init', function(){

	register_rest_route( 'wallace/v1','site-data', array(
							'methods'         => 'GET',
							'callback'        => 'wal_request_site_data',

	));

	register_rest_route( 'wallace/v1','posts', array(
							'methods'         => 'GET',
							'callback'        => 'wal_request_posts',

	));

	register_rest_route( 'wallace/v1','posts' . '/(?P<id>[\d]+)', array(
							'methods'         => 'GET',
							'callback'        => 'wal_request_post',

	));

	register_rest_route( 'wallace/v1','pages', array(
							'methods'         => 'GET',
							'callback'        => 'wal_request_pages',

	));

	register_rest_route( 'wallace/v1','pages' . '/(?P<id>[\d]+)', array(
							'methods'         => 'GET',
							'callback'        => 'wal_request_page',

	));

	register_rest_route( 'wallace/v1','site-menus', array(
							'methods'         => 'GET',
							'callback'        => 'wal_request_site_menus',

	));

	register_rest_route( 'wallace/v1','category' . '/(?P<id>[\d]+)', array(
							'methods'         => 'GET',
							'callback'        => 'wal_request_posts_by_category',

	));

});

function wal_request_site_data($request){
	$site_data = [
		'title' => get_bloginfo(),
		'iconUrl' => get_site_icon_url(),
		'pathToIndex' => wal_get_index()

	];
	return $site_data;
}

// GETTING MENUS
function wal_request_site_menus($request){
	$site_menus = [];

	foreach (get_nav_menu_locations() as $slug => $a_id) {
    
    if ($a_id) {
    	$menu = wp_get_nav_menu_object( $a_id );
			$menu->items = wp_get_nav_menu_items($menu->term_id);
			$site_menus[$slug] = $menu;
		}
	
  }

	return $site_menus;
}


function wal_get_index(){
	$index = '';
	$home_url = home_url('', 'relative');
	if($home_url === ''){
		//$index = '/';
	}
	else{
		$index = substr($home_url, 1);
	}
	return $index;
}

function wal_request_post($request){

		$id = (int) $request['id'];

		$post_request = new WP_REST_Request('GET', '/wp/v2/posts/' . $id);
		$post_request->set_query_params($request->get_query_params() );
				
		$response = rest_get_server()->dispatch($post_request);
		$raw_post= $response->data;
		$new_response = array();
		$post = wal_modify_post($raw_post, true);

		$posts[0] = $post;

		$new_response['posts'] = $posts;
		return $new_response;
}

// GET STATIC PAGE BY ID
function wal_request_page($request){

		$id = (int) $request['id'];

		$post_request = new WP_REST_Request('GET', '/wp/v2/pages/' . $id);
			
		$response = rest_get_server()->dispatch($post_request);
		$raw_post= $response->data;
		$new_response = array();
		$post = wal_modify_post($raw_post, true);

		$posts[0] = $post;

		$new_response['posts'] = $posts;
		return $new_response;
		
		return $new_response;
}

function wal_request_pages($request){
	
	$post_request = new WP_REST_Request('GET', '/wp/v2/pages');
	
	$response = rest_get_server()->dispatch($post_request);
	$data = $response->data;
	$new_response = array();
	$new_response['pages'] = $data;
	
	return $new_response;
}

function wal_modify_post($raw_post, $get_content){
		$post = array();

		$post['type'] = 0;
		$post['id'] = $raw_post['id'];
		$post['title'] = $raw_post['title']['rendered'];
		$post['excerpt'] = $raw_post['excerpt']['rendered'];
		$post['featured'] = $post['id'] == Wallace::get_featured_post_id() ? true : false;
		$post['categories'] = get_the_category($post['id']);  
		// $post['categoryString'] = "Uncategorized";
		foreach ($post['categories'] as $key => $category) {
		  if($key == 0 && $post['categories'] !== []){
		    $post['categoryString'] = $category->name;
		  }
		  else{
		    $post['categoryString'] = $post['categoryString'] . ', ' . $category->name;
		  }
		}   
		if($get_content){
			$post['content'] = $raw_post['content']['rendered'];
	        $post['contentLoaded'] = true;
		}
		else{
			$post['content'] = '';
	        $post['contentLoaded'] = false;
		}

		$post['loadedAfterBootstrap'] = false;
		
      	$post['date'] = get_the_date();
        $post['path'] = substr(parse_url($raw_post['link'], PHP_URL_PATH), 1, -1);
        $post['itemVisible'] = 'visible';
        $post['navigatingTo'] = false;
		
		if(has_post_thumbnail($post['id'])){
	        $post['imageURLLowRes'] = wp_get_attachment_image_src(
	                (int) get_post_thumbnail_id($post['id']), 'medium')[0];
	        $post['imageURLHiRes'] = wp_get_attachment_image_src(
	                (int) get_post_thumbnail_id($post['id']), 'large')[0];
	      }
	      else{
	        $post['imageURLLowRes'] = 'NONE';
	        $post['imageURLHiRes'] = 'NONE';

	      }
	      return $post;
}

function wal_request_posts($request){
	

	$currentApiPage = $request->get_param('page');
	$show_featured = $request->get_param('featured');

	$post_request = new WP_REST_Request('GET', '/wp/v2/posts');
	$post_request->set_query_params($request->get_query_params() );
	$post_request->set_param("per_page", 4);
	
	
	$response = rest_get_server()->dispatch($post_request);
	$data = $response->data;
	$new_response = array();
	$posts = array();
	if($show_featured == 'true'){

		$featured_post = Wallace::get_featured_post();
		if($featured_post !== null){
			array_push($posts, $featured_post);
		}

	}
	foreach ($data as $raw_post){
		if($raw_post['id'] !== Wallace::get_featured_post_id()){
			$post = wal_modify_post($raw_post, false);
			array_push($posts, $post);
		}
	}

	$new_response['posts'] = $posts;
	if($currentApiPage !== null){
		$new_response['api_page'] = $currentApiPage;
	}
	else{
		$new_response['api_page'] = 1;
	}
	$new_response['total_api_pages'] = $response->get_headers()['X-WP-TotalPages'];
	return $new_response;
}



// POSTS BY CATEGORY
function wal_request_posts_by_category($request){

	$id = (int) $request->get_param('id');

	$currentApiPage = $request->get_param('page');

	$post_request = new WP_REST_Request('GET', '/wp/v2/posts');
	$post_request->set_query_params($request->get_query_params() );
	$post_request->set_param("per_page", 8);
	$post_request->set_param("categories", $id);
	
	$response = rest_get_server()->dispatch($post_request);
	$data = $response->data;
	$new_response = array();
	$posts = array();
	

	if($show_featured == 'true'){

		$featured_post = Wallace::get_featured_post();
		if($featured_post !== null){
			array_push($posts, $featured_post);
		}

	}
	foreach ($data as $raw_post){
		if($raw_post['id'] !== Wallace::get_featured_post_id()){
			$post = wal_modify_post($raw_post, false);
			array_push($posts, $post);
		}
	}

	$new_response['posts'] = $posts;
	if($currentApiPage !== null){
		$new_response['api_page'] = $currentApiPage;
	}
	else{
		$new_response['api_page'] = 1;
	}
	$new_response['total_api_pages'] = $response->get_headers()['X-WP-TotalPages'];
	return $new_response;

}

	

?>