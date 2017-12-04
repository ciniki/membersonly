//
// This app will handle the listing, additions and deletions of events.  These are associated tenant.
//
function ciniki_membersonly_pages() {
    //
    // Panels
    //
    this.init = function() {
        //
        // events panel
        //
        this.menu = new M.panel('Members Only',
            'ciniki_membersonly_pages', 'menu',
            'mc', 'medium', 'sectioned', 'ciniki.membersonly.main.menu');
        this.menu.sections = {
            'pages':{'label':'', 'type':'simplegrid', 'num_cols':1,
                'addTxt':'Add a page',
                'addFn':'M.ciniki_membersonly_pages.pageEdit(\'M.ciniki_membersonly_pages.showMenu();\',0,0);',
                },
            };
        this.menu.sectionData = function(s) { return this.data[s]; }
        this.menu.cellValue = function(s, i, j, d) { 
            return d.page.title;
        };
        this.menu.rowFn = function(s, i, d) { 
            return 'M.ciniki_membersonly_pages.pageEdit(\'M.ciniki_membersonly_pages.showMenu();\',\'' + d.page.id + '\',0);';
        };
        this.menu.addClose('Back');
    }


    this.createEditPanel = function(cb, pid, parent_id, rsp) {
        var pn = 'edit_' + pid;
        //
        // Check if panel already exists, and reset for use
        //
        if( this.pn == null ) {
            //
            // The panel to display the edit form
            //
            this[pn] = new M.panel('Page',
                'ciniki_membersonly_pages', pn,
                'mc', 'medium mediumaside', 'sectioned', 'ciniki.membersonly.pages.edit');
            this[pn].data = {}; 
            this[pn].stackedData = [];
            this[pn].page_id = pid;
            this[pn].sections = {
                '_image':{'label':'', 'aside':'yes', 'type':'imageform', 'fields':{
                    'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 
                        'controls':'all', 'history':'no', 
                        'addDropImage':function(iid) {
                            M.ciniki_membersonly_pages[pn].setFieldValue('primary_image_id', iid, null, null);
                            return true;
                            },
                        'addDropImageRefresh':'',
                        'deleteImage':'M.ciniki_membersonly_pages.'+pn+'.deletePrimaryImage',
                        },
                }},
                '_image_caption':{'label':'', 'aside':'yes', 'fields':{
                    'primary_image_caption':{'label':'Caption', 'type':'text'},
    //              'primary_image_url':{'label':'URL', 'type':'text'},
                }},
                'details':{'label':'', 'aside':'yes', 'fields':{
                    'parent_id':{'label':'Parent Page', 'type':'select', 'options':{}},
                    'title':{'label':'Title', 'type':'text'},
                    'sequence':{'label':'Page Order', 'type':'text', 'size':'small'},
                }},
                '_synopsis':{'label':'Synopsis', 'fields':{
                    'synopsis':{'label':'', 'type':'textarea', 'size':'small', 'hidelabel':'yes'},
                }},
                '_content':{'label':'Content', 'fields':{
                    'content':{'label':'', 'type':'textarea', 'size':'large', 'hidelabel':'yes'},
                }},
                'files':{'label':'Files', 'aside':'yes',
                    'type':'simplegrid', 'num_cols':1,
                    'headerValues':null,
                    'cellClasses':[''],
                    'addTxt':'Add File',
                    'addFn':'M.ciniki_membersonly_pages.'+pn+'.editComponent(\'ciniki.membersonly.pagefiles\',\'M.ciniki_membersonly_pages.'+pn+'.updateFiles();\',{\'file_id\':\'0\'});',
                    },
                '_files':{'label':'', 'aside':'yes', 'fields':{
                    '_flags_10':{'label':'Reverse Order', 'type':'flagtoggle', 'bit':0x1000, 'field':'flags', 'default':'off'},
                }},
                'images':{'label':'Gallery', 'aside':'yes', 'type':'simplethumbs'},
                '_images':{'label':'', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
                    'addTxt':'Add Image',
                    'addFn':'M.ciniki_membersonly_pages.'+pn+'.editComponent(\'ciniki.membersonly.pageimages\',\'M.ciniki_membersonly_pages.'+pn+'.addDropImageRefresh();\',{\'add\':\'yes\'});',
                    },
                '_child_title':{'label':'Child Pages Heading', 'fields':{
                    'child_title':{'label':'', 'hidelabel':'yes', 'type':'text'},
                }},
                'pages':{'label':'', 'type':'simplegrid', 'num_cols':1, 
                    'addTxt':'Add Child Page',
                    'addFn':'M.ciniki_membersonly_pages.'+pn+'.childEdit(0);',
                    },
                '_buttons':{'label':'', 'buttons':{
                    'save':{'label':'Save', 'fn':'M.ciniki_membersonly_pages.'+pn+'.savePage();'},
                    'delete':{'label':'Delete', 'fn':'M.ciniki_membersonly_pages.'+pn+'.deletePage();'},
                }},
            };
            this[pn].fieldHistoryArgs = function(s, i) {
                return {'method':'ciniki.membersonly.pageHistory', 'args':{'tnid':M.curTenantID,
                    'page_id':this.page_id, 'field':i}};
            };
            this[pn].sectionData = function(s) { 
                return this.data[s];
            };
            this[pn].fieldValue = function(s, i, j, d) {
                return this.data[i];
            };
            this[pn].cellValue = function(s, i, j, d) {
                if( s == 'pages' ) {
                    return d.page.title;
                } else if( s == 'files' ) {
                    return d.file.name;
                }
            };
            this[pn].rowFn = function(s, i, d) {
                if( s == 'pages' ) {
    //              return 'M.ciniki_membersonly_pages.pageEdit(\'M.ciniki_membersonly_pages.updateChildren();\',\'' + d.page.id + '\',0);';
                    return 'M.ciniki_membersonly_pages.'+pn+'.childEdit(\'' + d.page.id + '\');';
                } else if( s == 'files' ) {
                    return 'M.startApp(\'ciniki.membersonly.pagefiles\',null,\'M.ciniki_membersonly_pages.'+pn+'.updateFiles();\',\'mc\',{\'file_id\':\'' + d.file.id + '\'});';
                }
            };
            this[pn].thumbFn = function(s, i, d) {
                return 'M.startApp(\'ciniki.membersonly.pageimages\',null,\'M.ciniki_membersonly_pages.'+pn+'.addDropImageRefresh();\',\'mc\',{\'page_id\':M.ciniki_membersonly_pages.'+pn+'.page_id,\'page_image_id\':\'' + d.image.id + '\'});';
            };
            this[pn].deletePrimaryImage = function(fid) {
                this.setFieldValue(fid, 0, null, null);
                return true;
            };
            this[pn].addDropImage = function(iid) {
                if( this.page_id == 0 ) {
                    var c = this.serializeForm('yes');
                    var rsp = M.api.postJSON('ciniki.membersonly.pageAdd', 
                        {'tnid':M.curTenantID}, c);
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    this.page_id = rsp.id;
                }
                var rsp = M.api.getJSON('ciniki.membersonly.pageImageAdd', 
                    {'tnid':M.curTenantID, 'image_id':iid, 'page_id':this.page_id});
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                return true;
            };
            this[pn].addDropImageRefresh = function() {
                if( M.ciniki_membersonly_pages[pn].page_id > 0 ) {
                    M.api.getJSONCb('ciniki.membersonly.pageGet', {'tnid':M.curTenantID, 
                        'page_id':M.ciniki_membersonly_pages[pn].page_id, 'images':'yes'}, function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            }
                            var p = M.ciniki_membersonly_pages[pn];
                            p.data.images = rsp.page.images;
                            p.refreshSection('images');
                            p.show();
                        });
                }
                return true;
            };
            this[pn].editComponent = function(a,cb,args) {
                if( this.page_id == 0 ) {
                    var p = this;
                    var c = this.serializeFormData('yes');
                    M.api.postJSONFormData('ciniki.membersonly.pageAdd', 
                        {'tnid':M.curTenantID}, c, function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            }
                            p.page_id = rsp.id;
                            args['page_id'] = rsp.id;
                            M.startApp(a,null,cb,'mc',args);
                        });
                } else {
                    args['page_id'] = this.page_id;
                    M.startApp(a,null,cb,'mc',args);
                }
            };

            this[pn].updateFiles = function() {
                if( this.page_id > 0 ) {
                    M.api.getJSONCb('ciniki.membersonly.pageGet', {'tnid':M.curTenantID, 
                        'page_id':this.page_id, 'files':'yes'}, function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            }
                            var p = M.ciniki_membersonly_pages[pn];
                            p.data.files = rsp.page.files;
                            p.refreshSection('files');
                            p.show();
                        });
                }
                return true;
            };

            this[pn].updateChildren = function() {
                if( this.page_id > 0 ) {
                    M.api.getJSONCb('ciniki.membersonly.pageGet', {'tnid':M.curTenantID, 
                        'page_id':this.page_id, 'children':'yes'}, function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            }
                            var p = M.ciniki_membersonly_pages[pn];
                            p.data.pages = rsp.page.pages;
                            p.refreshSection('pages');
                            p.show();
                        });
                }
                return true;
            };

            this[pn].childEdit = function(cid) {
                if( this.page_id == 0 ) {
                    var p = this;
                    var c = this.serializeFormData('yes');
                    M.api.postJSONFormData('ciniki.membersonly.pageAdd', 
                        {'tnid':M.curTenantID}, c, function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            }
                            p.page_id = rsp.id;
                            M.ciniki_membersonly_pages.pageEdit('M.ciniki_membersonly_pages.'+pn+'.updateChildren();',cid,p.page_id);
                        });
                } else {
                    M.ciniki_membersonly_pages.pageEdit('M.ciniki_membersonly_pages.'+pn+'.updateChildren();',cid,this.page_id);
                }
            };
            this[pn].addButton('save', 'Save', 'M.ciniki_membersonly_pages.'+pn+'.savePage();');
            this[pn].addClose('Cancel');
            this[pn].savePage = function() {
                var p = this;
                if( this.page_id > 0 ) {
                    var c = this.serializeFormData('no');
                    if( c != null ) {
                        M.api.postJSONFormData('ciniki.membersonly.pageUpdate', 
                            {'tnid':M.curTenantID, 'page_id':this.page_id}, c, function(rsp) {
                                if( rsp.stat != 'ok' ) {
                                    M.api.err(rsp);
                                    return false;
                                }
                                p.close();
                            });
                    } else {
                        this.close();
                    }
                } else {
                    var c = this.serializeFormData('yes');
                    M.api.postJSONFormData('ciniki.membersonly.pageAdd', 
                        {'tnid':M.curTenantID}, c, function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            }
                            p.close();
                        });
                }
            };
            this[pn].deletePage = function() {
                var p = this;
                if( confirm('Are you sure you want to delete this page? All files and images will also be removed from this page.') ) {
                    M.api.getJSONCb('ciniki.membersonly.pageDelete', {'tnid':M.curTenantID, 
                        'page_id':p.page_id}, function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            }
                            p.close();
                        });
                }
            };
        }

//      this[pn].sections.details.fields.parent_id.options = {'0':'None'};
        if( rsp.parentlist != null && rsp.parentlist.length > 0 ) {
            for(i in rsp.parentlist) {
                if( rsp.parentlist[i].page.id != this[pn].page_id ) {
                    this[pn].sections.details.fields.parent_id.options[rsp.parentlist[i].page.id] = rsp.parentlist[i].page.title;
                }
            }
        }
        this[pn].data = rsp.page;
        this[pn].sections.details.fields.parent_id.active = 'yes';
        if( this[pn].page_id == 0 && parent_id != null ) {
            this[pn].data.parent_id = parent_id;
            if( parent_id == 0 ) {
                this[pn].data.title = 'Members Only';
            }
        }
        if( this[pn].data.parent_id == 0 ) {
            this[pn].sections.details.fields.parent_id.active = 'no';
        }
        this[pn].refresh();
        this[pn].show(cb);
    }

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_membersonly_pages', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        this.showMenu(cb);
    }

    this.showMenu = function(cb) {
        M.api.getJSONCb('ciniki.membersonly.pageList', {'tnid':M.curTenantID, 
            'parent_id':'0'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                if( rsp.pages.length == 0 ) {
                    M.ciniki_membersonly_pages.pageEdit(cb, 0, 0);
                } else if( rsp.pages.length == 1 ) {
                    M.ciniki_membersonly_pages.pageEdit(cb, rsp.pages[0].page.id, 0);
                } else {
                    var p = M.ciniki_membersonly_pages.menu;
                    p.data = rsp;
                    p.refresh();
                    p.show(cb);
                }
            });
    };

    this.pageEdit = function(cb, pid, parent_id) {
        M.api.getJSONCb('ciniki.membersonly.pageGet', {'tnid':M.curTenantID,
            'page_id':pid, 'images':'yes', 'files':'yes', 
                'children':'yes', 'parentlist':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_membersonly_pages.createEditPanel(cb, pid, parent_id, rsp);    
            });
    };


};
