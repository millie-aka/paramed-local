$page = array(
 	'post_type'    => 'page',
 	'post_content' => '[gpasc_drafts form_id="1" form_path="/draft-links"]',
 	'post_title'   => 'Manual Test',
 	'post_name'    => 'manual-test',
 	'post_status'  => 'publish',
 	'post_parent'  => 0,
 	'post_author'  => 1
);

return wp_insert_post( $page );

