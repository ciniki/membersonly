<?php
//
// Description
// -----------
// This function is used recursively to convert pages and their children to ciniki.web pages.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_membersonly_convertpagetoweb(&$ciniki, $tnid, $parent_id, $page, $parents) {

    //
    // Create the page in ciniki.web
    //
    error_log("Converting page: {$page['id']} - {$page['title']}");

    $page['article_title'] = '';
    $page['parent_id'] = $parent_id;
    $page['page_type'] = 10;
    $page['page_redirect_url'] = '';
    $page['page_module'] = '';
    $page['menu_flags'] = 0x01;
    $page['flags'] |= 0x14;

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.web.page', $page, 0x04);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.membersonly.41', 'msg'=>'Unable to add the page', 'err'=>$rc['err']));
    }
    $page_id = $rc['id'];
    
    //
    // Create the images 
    //
    if( isset($page['images']) ) {
        foreach($page['images'] as $image) {
            // FIXME: Initial development this was not required for client
            error_log("  Image: {$image['id']}");
            $image['page_id'] = $page_id;
        }
    }

    //
    // Create the files
    //
    if( isset($page['files']) ) {
        foreach($page['files'] as $file) {
            error_log("  File: {$file['id']}");
            $file['page_id'] = $page_id;
            $file['sequence'] = 1;
            $file['binary_content'] = '';

            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.web.page_file', $file, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.membersonly.42', 'msg'=>'Unable to add the file', 'err'=>$rc['err']));
            }

            //
            // Copy file from old to new
            //
            $file['new_filename'] = $file['new_dir'] . '/' . $rc['uuid'][0] . '/' . $rc['uuid'];
            if( file_exists($file['new_filename']) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.membersonly.43', 'msg'=>'File already exists', 'err'=>$rc['err']));
            }
            if( !copy($file['old_filename'], $file['new_filename']) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.membersonly.44', 'msg'=>'Unable to copy the file', 'err'=>$rc['err']));
            }
        }
    }

    //
    // Check and convert any child pages
    //
    if( isset($parents[$page['id']]['pages']) ) {
        foreach($parents[$page['id']]['pages'] as $child_page) {
            $rc = ciniki_membersonly_convertpagetoweb($ciniki, $tnid, $page_id, $child_page, $parents);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.membersonly.39', 'msg'=>'Unable to convert child page', 'err'=>$rc['err']));
            }
        }
    }

    return array('stat'=>'ok');
}
?>
