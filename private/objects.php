<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_membersonly_objects($ciniki) {
	
	$objects = array();
	$objects['page'] = array(
		'name'=>'Page',
		'sync'=>'yes',
		'table'=>'ciniki_membersonly_pages',
		'fields'=>array(
			'parent_id'=>array(),
			'title'=>array(),
			'permalink'=>array(),
			'category'=>array(),
			'sequence'=>array(),
			'primary_image_id'=>array('ref'=>'ciniki.images.image'),
			'primary_image_caption'=>array(),
			'primary_image_url'=>array(),
			'child_title'=>array(),
			'synopsis'=>array(),
			'content'=>array(),
			),
		'history_table'=>'ciniki_membersonly_history',
		);
	$objects['page_image'] = array(
		'name'=>'Content Image',
		'sync'=>'yes',
		'table'=>'ciniki_membersonly_page_images',
		'fields'=>array(
			'page_id'=>array('ref'=>'ciniki.membersonly.page'),
			'name'=>array(),
			'permalink'=>array(),
			'webflags'=>array(),
			'image_id'=>array('ref'=>'ciniki.images.image'),
			'description'=>array(),
			),
		'history_table'=>'ciniki_membersonly_history',
		);
	$objects['page_file'] = array(
		'name'=>'Content File',
		'sync'=>'yes',
		'table'=>'ciniki_membersonly_page_files',
		'fields'=>array(
			'page_id'=>array('ref'=>'ciniki.membersonly.page'),
			'extension'=>array(),
			'name'=>array(),
			'permalink'=>array(),
			'webflags'=>array(),
			'description'=>array(),
			'org_filename'=>array(),
			'binary_content'=>array('history'=>'no'),
			),
		'history_table'=>'ciniki_membersonly_history',
		);

	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
