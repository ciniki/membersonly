<?php
//
// Description
// ===========
// This method will return the file in it's binary form.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the requested file belongs to.
// file_id:         The ID of the file to be downloaded.
//
// Returns
// -------
// Binary file.
//
function ciniki_membersonly_pageFileDownload($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'file_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'File'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'membersonly', 'private', 'checkAccess');
    $rc = ciniki_membersonly_checkAccess($ciniki, $args['tnid'], 'ciniki.membersonly.pageFileDownload'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the uuid for the file
    //
    $strsql = "SELECT files.id, "
        . "files.uuid, "
        . "tenants.uuid AS tenant_uuid, "
        . "files.name, "
        . "files.extension, "
        . "files.binary_content "
        . "FROM ciniki_membersonly_page_files AS files, ciniki_tenants AS tenants "
        . "WHERE files.id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
        . "AND files.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND files.tnid = tenants.id "
        . "AND tenants.id = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.membersonly', 'file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['file']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.membersonly.32', 'msg'=>'Unable to find file'));
    }
    $file = $rc['file'];
    $filename = $rc['file']['name'] . '.' . $rc['file']['extension'];

    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
    header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT"); 
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    if( $rc['file']['extension'] == 'pdf' ) {
        header('Content-Type: application/pdf');
    } elseif( $rc['file']['extension'] == 'mp3' ) {
        header('Content-Type: audio/mpeg');
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.membersonly.14', 'msg'=>'Unsupported file type'));
    }

    $storage_filename = $ciniki['config']['ciniki.core']['storage_dir'] . '/'
        . $file['tenant_uuid'][0] . '/' . $file['tenant_uuid']
        . '/ciniki.membersonly/'
        . $file['uuid'][0] . '/' . $file['uuid'];
    if( !file_exists($storage_filename) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.membersonly.31', 'msg'=>'File does not exist.'));
    }
//    $file['binary_content'] = 
    $binary_content = file_get_contents($storage_filename);

    // Specify Filename
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Content-Length: ' . strlen($binary_content));
    header('Cache-Control: max-age=0');

    print $binary_content;
    exit();
    
    return array('stat'=>'binary');
}
?>
