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
    echo "<h3><a href='".makeEventURL("onUploadFiles")."'>Upload / fetch files</a></h3>\n<ul>\n";
    echo "<li>Upload PDB files from local disk.</li>\n";
    echo "<li>Retrieve PDB files from the PDB or NDB.</li>\n";
    echo "<li>Upload electron density maps.</li>\n";
    echo "<li><i>Retrieve 2Fo-Fc and Fo-Fc (difference) maps from the EDS.</i></li>\n";
    echo "<li>Upload custom heterogen dictionaries (for adding hydrogens).</li>\n";
    echo "<li><i>Upload kinemages for viewing in KiNG.</i></li>\n";
    echo "</ul>\n";
    
    echo "<hr>\n";

    echo "<h3><a href='".makeEventURL("onNavBarGoto", "notebook_main.php")."'>Lab notebook</a></h3>\n<ul>\n";
    echo "<li>See notebook entries made automatically by MolProbity.</li>\n";
    echo "<li>Annotate automatic entries with notes and comments.</li>\n";
    echo "<li>Create your own new entries in the notebook.</li>\n";
    echo "<li><i>Print the notebook or save it as an HTML page.</i></li>\n";
    echo "</ul>\n";
    
    echo "<hr><pre>";
    print_r($_SESSION);
    echo "</pre>";

    echo mpPageFooter();
}
#}}}########################################################################

#{{{ onSetDetail
############################################################################
function onSetDetail($arg, $req)
{
    $c = getContext();
    $c['suppressDetail'] = $arg;
    setContext($c);
}
#}}}########################################################################

#{{{ onUploadFiles
############################################################################
/**
* Documentation for this function.
*/
function onUploadFiles($arg, $req)
{
    pageCall("upload_setup.php");
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
