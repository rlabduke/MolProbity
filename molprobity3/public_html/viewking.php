<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Launches a page with an embedded KiNG instance to view a kinemage.

INPUTS (via Get or Post):
    url             URL of the kinemage to load

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    mpSessReadOnly();
    mpLog("king:User opened a kinemage file in KiNG");

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

# MAIN - the beginning of execution for this page
############################################################################
// Start the page: produces <HTML>, <HEAD>, <BODY> tags
$url = $_REQUEST['url'];
$file = basename($url);
echo mpPageHeader("KiNG - $file");

############################################################################
?>
<center>
<applet code="king/Kinglet.class" archive="king.jar" width=750 height=600>
<param name="mode" value="flat">
<?php
    echo "    <param name='kinSource' value='$url'>\n";
    // For supporting electron density maps:
    /*if(isset($edmap_list))
    {
        foreach($edmap_list as $edmap)
        {
            if(isset($ed_param))    $ed_param .= " ".$edmap;
            else                    $ed_param = $edmap;
        }
        if(isset($ed_param))
        {
            echo "<param name=\"edmapBase\" value=\"$web_dir\">\n";
            echo "<param name=\"edmapList\" value=\"$ed_param\">\n";
        }
    }*/
?>
</applet>
</center>
<?php echo mpPageFooter(); ?>
