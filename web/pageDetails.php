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
function ciniki_membersonly_web_pageDetails($ciniki, $settings, $business_id, $args) {
	//
	// Get the main information
	//
	$strsql = "SELECT ciniki_membersonly_pages.id, "
		. "ciniki_membersonly_pages.parent_id, "
		. "ciniki_membersonly_pages.title, "
		. "ciniki_membersonly_pages.permalink, "
		. "ciniki_membersonly_pages.sequence, "
		. "ciniki_membersonly_pages.primary_image_id, "
		. "ciniki_membersonly_pages.primary_image_caption, "
		. "ciniki_membersonly_pages.primary_image_url, "
		. "ciniki_membersonly_pages.child_title, "
		. "ciniki_membersonly_pages.synopsis, "
		. "ciniki_membersonly_pages.content, "
		. "ciniki_membersonly_page_images.image_id, "
		. "ciniki_membersonly_page_images.name AS image_name, "
		. "ciniki_membersonly_page_images.permalink AS image_permalink, "
		. "ciniki_membersonly_page_images.description AS image_description, "
		. "UNIX_TIMESTAMP(ciniki_membersonly_page_images.last_updated) AS image_last_updated "
		. "FROM ciniki_membersonly_pages "
		. "LEFT JOIN ciniki_membersonly_page_images ON ("
			. "ciniki_membersonly_pages.id = ciniki_membersonly_page_images.page_id "
			. "AND ciniki_membersonly_pages.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND (ciniki_membersonly_page_images.webflags&0x01) = 0 "
			. ") "
		. "WHERE ciniki_membersonly_pages.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	//
	// Permalink or Content Type must be specified
	//
	if( isset($args['permalink']) && $args['permalink'] != '' ) {
		$strsql .= "AND ciniki_membersonly_pages.permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' ";
	} elseif( isset($args['page_id']) && $args['page_id'] != '' ) {
		$strsql .= "AND ciniki_membersonly_pages.id = '" . ciniki_core_dbQuote($ciniki, $args['page_id']) . "' ";
	} else {
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2193', 'msg'=>'I\'m sorry, we were unable to find the page you requested.'));
	}

	if( isset($args['parent_id']) && $args['parent_id'] != '' ) {
		$strsql .= "AND ciniki_membersonly_pages.parent_id = '" . ciniki_core_dbQuote($ciniki, $args['parent_id']) . "' ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.info', array(
		array('container'=>'page', 'fname'=>'id',
			'fields'=>array('id', 'parent_id', 
				'title', 'permalink', 'sequence', 
				'image_id'=>'primary_image_id', 'image_caption'=>'primary_image_caption', 
				'image_url'=>'primary_image_url', 'child_title', 'synopsis', 'content')),
		array('container'=>'images', 'fname'=>'image_id', 
			'fields'=>array('image_id', 'title'=>'image_name', 'permalink'=>'image_permalink',
				'description'=>'image_description', 'last_updated'=>'image_last_updated')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['page']) || count($rc['page']) < 1 ) {
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2194', 'msg'=>"I'm sorry, but we can't find the page you requested."));
	}
	$page = array_pop($rc['page']);

	//
	// Check if any files are attached to the page
	//
	$strsql = "SELECT id, name, extension, permalink, description "
		. "FROM ciniki_membersonly_page_files "
		. "WHERE ciniki_membersonly_page_files.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_membersonly_page_files.page_id = '" . ciniki_core_dbQuote($ciniki, $page['id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.info', array(
		array('container'=>'files', 'fname'=>'id', 
			'fields'=>array('id', 'name', 'extension', 'permalink', 'description')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['files']) ) {
		$page['files'] = $rc['files'];
	}

	//
	// Check if there are any children
	//
	$strsql = "SELECT id, title, "
		. "primary_image_id, "
		. "permalink, category, synopsis, content, "
		. "IF(content<>'','yes','no') AS is_details "
		. "FROM ciniki_membersonly_pages "
		. "WHERE parent_id = '" . ciniki_core_dbQuote($ciniki, $page['id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "ORDER BY category, sequence, title "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
//		array('container'=>'children', 'fname'=>'id',
//			'fields'=>array('id', 'name'=>'title')),
		array('container'=>'children', 'fname'=>'category', 
			'fields'=>array('name'=>'category')),
		array('container'=>'list', 'fname'=>'id', 
			'fields'=>array('id', 'title', 'permalink', 'image_id'=>'primary_image_id',
				'synopsis', 'content', 'is_details')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['children']) ) {	
		// If only one category or no category, then display as a list.
		if( count($rc['children']) == 1 ) {
			$page['children'] = array();
			$list = array_pop($rc['children']);
			$list = $list['list'];
			foreach($list as $cid => $child) {
				$page['children'][$child['permalink']] = array(
					'id'=>$child['id'], 
					'name'=>$child['title'], 
					'list'=>array($cid=>$child),
					);
			}
		} else {
			$page['child_categories'] = $rc['children'];
		}

	}

	return array('stat'=>'ok', 'page'=>$page);
}
?>
