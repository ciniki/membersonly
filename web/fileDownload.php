<?php
//
// Description
// ===========
// This function will return the file details and content so it can be sent to the client.
//
// Returns
// -------
//
function ciniki_membersonly_web_fileDownload($ciniki, $business_id, $page_id, $file_permalink) {

    //
    // Get the file details
    //
    $strsql = "SELECT ciniki_membersonly_page_files.id, "
        . "ciniki_membersonly_page_files.name, "
        . "ciniki_membersonly_page_files.permalink, "
        . "ciniki_membersonly_page_files.extension, "
        . "ciniki_membersonly_page_files.binary_content "
        . "FROM ciniki_membersonly_page_files "
        . "WHERE ciniki_membersonly_page_files.page_id = '" . ciniki_core_dbQuote($ciniki, $page_id) . "' "
        . "AND ciniki_membersonly_page_files.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND CONCAT_WS('.', ciniki_membersonly_page_files.permalink, ciniki_membersonly_page_files.extension) = '" . ciniki_core_dbQuote($ciniki, $file_permalink) . "' "
        . "AND (ciniki_membersonly_page_files.webflags&0x01) = 0 "      // Make sure file is to be visible
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.info', 'file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['file']) ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.membersonly.27', 'msg'=>'Unable to find requested file'));
    }
    $rc['file']['filename'] = $rc['file']['name'] . '.' . $rc['file']['extension'];

    return array('stat'=>'ok', 'file'=>$rc['file']);
}
?>
