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
function ciniki_membersonly_web_pages($ciniki, $settings, $tnid, $args) {
    //
    // Find the list of pages with no parent
    //
    $strsql = "SELECT id, title, "
        . "primary_image_id, "
        . "permalink, category, synopsis, content, "
        . "'yes' AS is_details "
        . "FROM ciniki_membersonly_pages "
        . "WHERE parent_id = 0 "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY sequence, title "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'categories', 'fname'=>'category', 
            'fields'=>array('name'=>'category')),
        array('container'=>'list', 'fname'=>'id', 
            'fields'=>array('id', 'title', 'permalink', 'image_id'=>'primary_image_id',
                'synopsis', 'content', 'is_details')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['categories']) ) {    
        return array('stat'=>'ok', 'categories'=>$rc['categories']);
    }
    
    return array('stat'=>'ok', 'pages'=>array());
}
?>
