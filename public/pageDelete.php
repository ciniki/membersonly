<?php
//
// Description
// -----------
// This method will delete a page from the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the page is attached to.
// page_id:         The ID of the page to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_membersonly_pageDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'page_id'=>array('required'=>'yes', 'default'=>'', 'blank'=>'yes', 'name'=>'Page'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'membersonly', 'private', 'checkAccess');
    $ac = ciniki_membersonly_checkAccess($ciniki, $args['business_id'], 'ciniki.membersonly.pageDelete');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Get the uuid of the page to be deleted
    //
    $strsql = "SELECT uuid FROM ciniki_membersonly_pages "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['page_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.membersonly', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.membersonly.6', 'msg'=>'The page does not exist'));
    }
    $item = $rc['item'];

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.membersonly');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Check if there are subpages
    //
    $strsql = "SELECT COUNT(id) AS num_pages "
        . "FROM ciniki_membersonly_pages "
        . "WHERE parent_id = '" . ciniki_core_dbQuote($ciniki, $args['page_id']) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.membersonly', 'pages');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['pages']['num_pages']) && $rc['pages']['num_pages'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.membersonly.7', 'msg'=>'There are still children, you must remove them first.'));
    }

    //
    // Remove the images
    //
    $strsql = "SELECT id, uuid, image_id FROM ciniki_membersonly_page_images "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND page_id = '" . ciniki_core_dbQuote($ciniki, $args['page_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.membersonly', 'image');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.membersonly');
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $images = $rc['rows'];
        
        foreach($images as $iid => $image) {
            $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.membersonly.page_image', 
                $image['id'], $image['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.membersonly');
                return $rc; 
            }
        }
    }

    //
    // Remove the files for the page
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_membersonly_page_files "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND page_id = '" . ciniki_core_dbQuote($ciniki, $args['page_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.membersonly', 'file');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.membersonly');
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $files = $rc['rows'];
        foreach($files as $fid => $file) {
            $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.membersonly.page_file', 
                $file['id'], $file['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.membersonly');
                return $rc; 
            }
        }
    }

    //
    // Remove the page
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.membersonly.page', 
        $args['page_id'], $item['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.membersonly');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.membersonly');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'membersonly');

    return array('stat'=>'ok');
}
?>
