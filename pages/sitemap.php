<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This is an overall "site map" or super-index page for MolProbity3 experts.
*****************************************************************************/
// This variable must be defined for index.php to work! Must match class below.
$delegate = new SitemapDelegate();
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class SitemapDelegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    echo mpPageHeader("Site map", "sitemap");
    echo "<i>Features in italics have not yet been implemented.</i>\n";

    echo "<h3><a href='".makeEventURL("onUploadPDBs")."'>Input PDB files</a></h3>\n<ul>\n";
    echo "<li>Upload PDB files from local disk.</li>\n";
    echo "<li>Retrieve PDB files from the PDB or NDB.</li>\n";
    echo "</ul>\n";
    
    echo "<h3><a href='".makeEventURL("onUploadOther")."'>Input other files</a></h3>\n<ul>\n";
    echo "<li>Upload kinemages for viewing in KiNG.</li>\n";
    echo "<li>Upload electron density maps.</li>\n";
    echo "<li><i>Retrieve 2Fo-Fc and Fo-Fc (difference) maps from the EDS.</i></li>\n";
    echo "<li>Upload custom heterogen dictionaries (for adding hydrogens).</li>\n";
    echo "</ul>\n";
    
    echo "<h3><a href='".makeEventURL("onAddH")."'>Add hydrogens</a></h3>\n<ul>\n";
    echo "<li>Add missing hydrogens.</li>\n";
    echo "<li>Optimize H-bond networks.</li>\n";
    echo "<li>Check for Asn, Gln, His flips.</li>\n";
    echo "</ul>\n";

    echo "<h3><a href='".makeEventURL("onRefitSC")."'>Refit sidechains</a></h3>\n<ul>\n";
    echo "</ul>\n";

    echo "<hr>\n";

    echo "<h3><a href='".makeEventURL("onNavBarGoto", "file_browser.php")."'>View & download files</a></h3>\n<ul>\n";
    echo "<li>View the original files you submitted or retrieved from the network.</li>\n";
    echo "<li>View kinemages, charts, and other files created by MolProbity.</li>\n";
    echo "<li><i>Download all files packaged as a ZIP, or individual files one at a time.</i></li>\n";
    echo "</ul>\n";
    
    echo "<h3><a href='".makeEventURL("onNavBarGoto", "notebook_main.php")."'>Lab notebook</a></h3>\n<ul>\n";
    echo "<li>See notebook entries made automatically by MolProbity.</li>\n";
    echo "<li>Annotate automatic entries with notes and comments.</li>\n";
    echo "<li>Create your own new entries in the notebook.</li>\n";
    echo "<li><i>Print the notebook or save it as an HTML page.</i></li>\n";
    echo "</ul>\n";
    
    echo "<h3><a href='".makeEventURL("onNavBarGoto", "save_session.php")."'>Save session</a></h3>\n<ul>\n";
    echo "<li>Save your results from this session and come back later to keep working.</li>\n";
    echo "<li>Creates a page that can be bookmarked in your web browser.</li>\n";
    echo "</ul>\n";
    
    echo "<h3><a href='".makeEventURL("onNavBarGoto", "logout.php")."'>Log out</a></h3>\n<ul>\n";
    echo "<li>Destroy all the files created during this session.</li>\n";
    echo "<li>Start over with a new session, if you want.</li>\n";
    echo "<li>Free up disk space for other users. (Thanks!)</li>\n";
    echo "</ul>\n";
    
    echo "<hr><pre>";
    print_r($_SESSION);
    echo "</pre>";

    echo mpPageFooter();
}
#}}}########################################################################

#{{{ onUploadPDBs, onUploadOther, onAddH, onRefitSC
############################################################################
function onUploadPDBs($arg, $req)
{
    pageCall("upload_pdb_setup.php");
}

function onUploadOther($arg, $req)
{
    pageCall("upload_other_setup.php");
}

function onAddH($arg, $req)
{
    pageCall("reduce_setup.php");
}

function onRefitSC($arg, $req)
{
    pageCall("sswing_setup1.php");
}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

}//end of class definition
?>
