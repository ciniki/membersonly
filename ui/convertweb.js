//
// This app will handle the listing, additions and deletions of events.  These are associated tenant.
//
function ciniki_membersonly_convertweb() {
    //
    // events panel
    //
    this.menu = new M.panel('Convert Members Only',
        'ciniki_membersonly_convertweb', 'menu',
        'mc', 'medium', 'sectioned', 'ciniki.membersonly.convertweb.menu');
    this.menu.data = {'pagename':'Members Only'};
    this.menu.sections = {
        '_buttons':{'label':'', 'buttons':{
            'go':{'label':'Convert Now', 'fn':'M.ciniki_membersonly_convertweb.menu.convert();'},
            }},
        };
    this.menu.open = function(cb) {
        this.refresh();
        this.show(cb);
    }
    this.menu.convert = function() {
        M.api.getJSONCb('ciniki.membersonly.convertweb', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            M.alert('Done!'); 
        });
    }
    this.menu.addClose('Back');

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
        var appContainer = M.createContainer(appPrefix, 'ciniki_membersonly_convertweb', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        this.menu.open(cb);
    }
};
