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

    echo "<h3><a href='".makeEventURL("onNavBarCall", "upload_pdb_setup.php")."'>Input PDB files</a></h3>\n<ul>\n";
    echo "<li>Upload PDB files from local disk.</li>\n";
    echo "<li>Retrieve PDB files from the PDB or NDB.</li>\n";
    echo "</ul>\n";
    
    echo "<h3><a href='".makeEventURL("onNavBarCall", "upload_other_setup.php")."'>Input other files</a></h3>\n<ul>\n";
    echo "<li>Upload kinemages for viewing in KiNG.</li>\n";
    echo "<li>Upload electron density maps.</li>\n";
    echo "<li><i>Retrieve 2Fo-Fc and Fo-Fc (difference) maps from the EDS.</i></li>\n";
    echo "<li>Upload custom heterogen dictionaries (for adding hydrogens).</li>\n";
    echo "</ul>\n";
    
    echo "<h3><a href='".makeEventURL("onNavBarCall", "reduce_setup.php")."'>Add hydrogens</a></h3>\n<ul>\n";
    echo "<li>Add missing hydrogens.</li>\n";
    echo "<li>Optimize H-bond networks.</li>\n";
    echo "<li>Check for Asn, Gln, His flips.</li>\n";
    echo "</ul>\n";

    echo "<h3><a href='".makeEventURL("onNavBarCall", "aacgeom_setup.php")."'>All-atom contact and geometric analyses</a></h3>\n<ul>\n";
    echo "<li>All-atom steric contacts (clashlist, clash score, contact dots)</li>\n";
    echo "<li>Protein geometry evaluation (Ramachandran plot, rotamers, C&beta; deviations)</li>\n";
    echo "<li><i>Nucleic acid geometry (base-phosphate perpendiculars)</i></li>\n";
    echo "<li>Multi-criterion chart and kinemage displays</li>\n";
    echo "</ul>\n";

    echo "<h3><a href='".makeEventURL("onNavBarCall", "sswing_setup1.php")."'>Refit sidechains</a></h3>\n<ul>\n";
    echo "<li>Automatically refit sidechains based on electron density and all-atom contacts.</li>\n";
    echo "</ul>\n";

    echo "<h3><a href='".makeEventURL("onNavBarCall", "makekin_setup.php")."'>Make simple kinemages</a></h3>\n<ul>\n";
    echo "<li>Make kinemages using basic Prekin scripts.</li>\n";
    echo "<li>Kinemages can be combined and edited in KiNG with File | Append.</li>\n";
    echo "<li>KiNG can save modified kinemages to the server with File | Save as.</li>\n";
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
    
    echo "<h3><a href='".makeEventURL("onNavBarGoto", "feedback_setup.php")."'>Feedback &amp; bugs</a></h3>\n<ul>\n";
    echo "<li>Report problems with MolProbity or suggestions for improvement.</li>\n";
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
    
    echo "<hr><pre>\$_SESSION = ";
    print_r($_SESSION);
    echo "</pre>";

    echo mpPageFooter();
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
