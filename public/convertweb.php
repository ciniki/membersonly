<?php
//
// Description
// ===========
// This method will load the pages in membersonly and convert them to a 
// membersonly page in the ciniki.web module.
//
// The pages must not exist in the ciniki.web module already, new pages will be created.
//
// *****NOTE*****: This has not be tested with ciniki_membersonly_page_images, or multilevel child pages.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_membersonly_convertweb(&$ciniki) {

    //
    // Sysadmins are allowed full access
    //
    if( ($ciniki['session']['user']['perms'] & 0x01) != 0x01 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.membersonly.40', 'msg'=>'Permission Denied'));
    }

    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //
    // Load the tenant UUID for storage
    //
    $strsql = "SELECT uuid FROM ciniki_tenants "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['tenant']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.170', 'msg'=>'Unable to get tenant details'));
    }
    $storage_dirname = $ciniki['config']['ciniki.core']['storage_dir'] . '/'
        . $rc['tenant']['uuid'][0] . '/' . $rc['tenant']['uuid'];

    //
    // Load all the pages from members only
    //
    $strsql = "SELECT pages.id, "
        . "pages.parent_id, "
        . "pages.title, "
        . "pages.permalink, "
        . "pages.sequence, "
        . "pages.flags, "
        . "pages.primary_image_id, "
        . "pages.primary_image_caption, "
        . "pages.primary_image_url, "
        . "pages.synopsis, "
        . "pages.content, "
        . "pages.child_title "
        . "FROM ciniki_membersonly_pages AS pages "
        . "WHERE pages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY pages.parent_id, pages.sequence "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.membersonly', array(
        array('container'=>'parents', 'fname'=>'parent_id', 'fields'=>array('id'=>'parent_id')),
        array('container'=>'pages', 'fname'=>'id', 
            'fields'=>array('id', 'parent_id', 'title', 'permalink', 'sequence', 'flags', 
                'primary_image_id', 'primary_image_caption', 'primary_image_url',
                'synopsis', 'content', 'child_title'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.membersonly.35', 'msg'=>'Unable to load pages', 'err'=>$rc['err']));
    }
    $parents = isset($rc['parents']) ? $rc['parents'] : array();

    //
    // Load all the images
    //
    $strsql = "SELECT images.id, "
        . "images.page_id, "
        . "images.name, "
        . "images.permalink, "
        . "images.sequence, "
        . "images.webflags, "
        . "images.image_id, "
        . "images.description "
        . "FROM ciniki_membersonly_page_images AS images "
        . "WHERE images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY images.page_id, id "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.membersonly', array(
        array('container'=>'pages', 'fname'=>'page_id', 'fields'=>array('id'=>'page_id')),
        array('container'=>'images', 'fname'=>'id', 
            'fields'=>array('id', 'page_id', 'name', 'permalink', 'sequence', 'webflags', 'image_id', 
                'description'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.membersonly.36', 'msg'=>'Unable to load images', 'err'=>$rc['err']));
    }
    $images = isset($rc['pages']) ? $rc['pages'] : array();

    //
    // Load all the files
    //
    $strsql = "SELECT files.id, "
        . "files.uuid, "
        . "files.page_id, "
        . "files.extension, "
        . "files.name, "
        . "files.permalink, "
        . "files.webflags, "
        . "files.description, "
        . "files.org_filename "
        . "FROM ciniki_membersonly_page_files AS files "
        . "WHERE files.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY files.page_id, id "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.membersonly', array(
        array('container'=>'pages', 'fname'=>'page_id', 'fields'=>array('id'=>'page_id')),
        array('container'=>'files', 'fname'=>'id', 
            'fields'=>array('id', 'uuid', 'page_id', 'extension', 'name', 'permalink', 'webflags', 
                'description', 'org_filename'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.membersonly.37', 'msg'=>'Unable to load files', 'err'=>$rc['err']));
    }
    $files = isset($rc['pages']) ? $rc['pages'] : array();

    //
    // Setup the file old and new storage dir
    //
    foreach($files as $pid => $page) {
        if( isset($page['files']) ) {
            foreach($page['files'] as $fid => $file) {
                $files[$pid]['files'][$fid]['old_filename'] = $storage_dirname
                    . '/ciniki.membersonly/' . $file['uuid'][0] . '/' . $file['uuid'];
                $files[$pid]['files'][$fid]['new_dir'] = $storage_dirname
                    . '/ciniki.web/pagefiles/';
            }
        }
    }

    //
    // Merge details
    //
    foreach($parents as $parent_id => $parent) {
        if( isset($parent['pages']) ) {
            foreach($parent['pages'] as $pid => $page) {
                if( isset($images[$pid]['images']) ) {
                    $parents[$parent_id]['pages'][$pid]['images'] = $images[$pid]['images'];
                }
                if( isset($files[$pid]['files']) ) {
                    $parents[$parent_id]['pages'][$pid]['files'] = $files[$pid]['files'];
                }
            }
        }
    }

    //
    // Recursively convert each parent and their children, starting with the parent 0 (zero)
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'membersonly', 'private', 'convertpagetoweb');
    if( isset($parents[0]['pages']) ) {
        foreach($parents[0]['pages'] as $page) {
            $rc = ciniki_membersonly_convertpagetoweb($ciniki, $args['tnid'], 0, $page, $parents);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.membersonly.38', 'msg'=>'Unable to convert page', 'err'=>$rc['err']));
            }
        }
    }

    return array('stat'=>'ok');
}
?>
