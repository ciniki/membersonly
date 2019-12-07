<?php
//
// Description
// ===========
// This method will move all the membersonly for a tenant to ciniki-storage.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_membersonly_movetoStorage(&$ciniki) {

    //
    // Sysadmins are allowed full access
    //
    if( ($ciniki['session']['user']['perms'] & 0x01) != 0x01 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.membersonly.13', 'msg'=>'Permission Denied'));
    }
ini_set('memory_limit', '1024M');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    $strsql = "SELECT ciniki_membersonly_page_files.id, "
        . "ciniki_tenants.id AS tnid, "
        . "ciniki_tenants.uuid AS tenant_uuid, "
        . "ciniki_membersonly_page_files.uuid, "
        . "ciniki_membersonly_page_files.binary_content "
        . "FROM ciniki_membersonly_page_files, ciniki_tenants "
        . "WHERE ciniki_membersonly_page_files.tnid = ciniki_tenants.id "
        . "ORDER BY ciniki_membersonly_page_files.tnid "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.membersonly', 'page_file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $membersonly = $rc['rows'];
    foreach($membersonly as $file) {
        if( $file['binary_content'] != '' ) {
            $args = array();
            $storage_dirname = $ciniki['config']['ciniki.core']['storage_dir'] . '/'
                . $file['tenant_uuid'][0] . '/' . $file['tenant_uuid']
                . "/ciniki.membersonly/"
                . $file['uuid'][0];
            $storage_filename = $storage_dirname . '/' . $file['uuid'];
            if( !is_dir($storage_dirname) ) {
                if( !mkdir($storage_dirname, 0700, true) ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.membersonly.33', 'msg'=>'Unable to add file'));
                }
            }
            if( file_exists($storage_filename) ) {
                error_log('FILE[' . $file['id'] . ']: file already exists');
            } elseif( file_put_contents($storage_filename, $file['binary_content']) === FALSE ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.membersonly.15', 'msg'=>'Unable to add file'));
            }
            $rc = ciniki_core_objectUpdate($ciniki, $file['tnid'], 'ciniki.membersonly.file',
                $file['id'], $args, 0x07);
        } else {
            error_log('FILE[' . $file['id'] . ']: binary_content is empty');
            $storage_dirname = $ciniki['config']['ciniki.core']['storage_dir'] . '/'
                . $file['tenant_uuid'][0] . '/' . $file['tenant_uuid']
                . "/ciniki.membersonly/"
                . $file['uuid'][0];
            $storage_filename = $storage_dirname . '/' . $file['uuid'];
            $binary_content = file_get_contents($storage_filename);
//            $args['checksum'] = crc32($binary_content);
            $rc = ciniki_core_objectUpdate($ciniki, $file['tnid'], 'ciniki.membersonly.file',
                $file['id'], $args, 0x07);
        }
    }

    return array('stat'=>'ok');
}
?>
