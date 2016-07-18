<?php
//
// Description
// ===========
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_membersonly_pageAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'parent_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Parent'), 
        'title'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Title'), 
        'permalink'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Permalink'), 
        'category'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Category'),
        'sequence'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Sequence'),
        'primary_image_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Image'),
        'primary_image_caption'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Image Caption'),
        'primary_image_url'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Image URL'),
        'child_title'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Children Title'),
        'synopsis'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Synopsis'),
        'content'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Content'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'membersonly', 'private', 'checkAccess');
    $rc = ciniki_membersonly_checkAccess($ciniki, $args['business_id'], 'ciniki.membersonly.pageAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    } 

    //
    // Get a UUID for use in permalink
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    $rc = ciniki_core_dbUUID($ciniki, 'ciniki.membersonly');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2169', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
    }
    $args['uuid'] = $rc['uuid'];

    //
    // Determine the permalink
    //
    if( !isset($args['permalink']) || $args['permalink'] == '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['title']);
    }

    //
    // Check the permalink doesn't already exist
    //
    $strsql = "SELECT id, title, permalink FROM ciniki_membersonly_pages "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND parent_id = '" . ciniki_core_dbQuote($ciniki, $args['parent_id']) . "' "
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.membersonly', 'page');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2170', 'msg'=>'You already have page with this name, you must choose another.'));
    }

    //
    // Check the sequence
    //
    if( !isset($args['sequence']) || $args['sequence'] == '' || $args['sequence'] == '0' ) {
        $strsql = "SELECT MAX(sequence) AS max_sequence "
            . "FROM ciniki_membersonly_page_images "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND page_id = '" . ciniki_core_dbQuote($ciniki, $args['page_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.membersonly', 'seq');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( isset($rc['seq']) && isset($rc['seq']['max_sequence']) ) {
            $args['sequence'] = $rc['seq']['max_sequence'] + 1;
        } else {
            $args['sequence'] = 1;
        }
    }

    //
    // Add the image to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    return ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.membersonly.page', $args, 0x07);
}
?>
