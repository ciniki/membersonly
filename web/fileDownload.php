<?php
//
// Description
// ===========
// This function will return the file details and content so it can be sent to the client.
//
// Returns
// -------
//
function ciniki_membersonly_web_fileDownload($ciniki, $tnid, $page_id, $file_permalink) {

    //
    // Get the file details
    //
    $strsql = "SELECT ciniki_membersonly_page_files.id, "
        . "ciniki_membersonly_page_files.uuid, "
        . "ciniki_tenants.uuid AS tenant_uuid, "
        . "ciniki_membersonly_page_files.name, "
        . "ciniki_membersonly_page_files.permalink, "
        . "ciniki_membersonly_page_files.extension, "
        . "ciniki_membersonly_page_files.binary_content "
        . "FROM ciniki_membersonly_page_files, ciniki_tenants "
        . "WHERE ciniki_membersonly_page_files.page_id = '" . ciniki_core_dbQuote($ciniki, $page_id) . "' "
        . "AND ciniki_membersonly_page_files.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND CONCAT_WS('.', ciniki_membersonly_page_files.permalink, ciniki_membersonly_page_files.extension) = '" . ciniki_core_dbQuote($ciniki, $file_permalink) . "' "
        . "AND (ciniki_membersonly_page_files.webflags&0x01) = 0 "      // Make sure file is to be visible
        . "AND ciniki_membersonly_page_files.tnid = ciniki_tenants.id "
        . "AND ciniki_tenants.id = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.info', 'file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['file']) ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.membersonly.27', 'msg'=>'Unable to find requested file'));
    }
    $file = $rc['file'];
    $rc['file']['filename'] = $rc['file']['name'] . '.' . $rc['file']['extension'];

    //
    // Load file from storage
    //
    $storage_filename = $ciniki['config']['ciniki.core']['storage_dir'] . '/'
        . $file['tenant_uuid'][0] . '/' . $file['tenant_uuid']
        . '/ciniki.membersonly/'
        . $file['uuid'][0] . '/' . $file['uuid'];
    if( !file_exists($storage_filename) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.membersonly.10', 'msg'=>'File does not exist.'));
    }
    $file['binary_content'] = file_get_contents($storage_filename);

    return array('stat'=>'ok', 'file'=>$file);
}
?>
