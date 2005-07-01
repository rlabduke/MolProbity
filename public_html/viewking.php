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
// I can't remember why I was thinking this page could interfere with
// background jobs, but I think (at the moment) that this should be safe.
//
// You could get in trouble if a background job was running (hence the check here)
// or if you somehow hit the size-change link while another page was loading (unlikely).
if(isset($_REQUEST['size']) && !$_SESSION['bgjob']['isRunning'])
{
    mpSessReadOnly(false);
    // (Note that changing size breaks the "Close" button, at least in Safari)
    $_SESSION['kingSize'] = $_REQUEST['size'];
}
if($_SESSION['kingSize']        == "tiny")  $size = "width=600 height=400"; //  640 x 480
elseif($_SESSION['kingSize']    == "small") $size = "width=700 height=500"; //  800 x 600
elseif($_SESSION['kingSize']    == "large") $size = "width=950 height=650"; // 1024 x 768
else                                        $size = "width=750 height=600"; // Good for most people

$url = $_REQUEST['url'];
$file = basename($url);
// Start the page: produces <HTML>, <HEAD>, <BODY> tags
echo mpPageHeader("KiNG - $file");

############################################################################
?>
<center>
<applet code="king/Kinglet.class" archive="king.jar" <?php echo $size; ?>>
<param name="mode" value="flat">
<?php
    echo "    <param name='kinSource' value='$url'>\n";
    
    // For kinemage load/save:
    $kinfiles = implode(' ', listDir($_SESSION['dataDir'].'/'.MP_DIR_KINS));
    echo "    <param name='kinfileList' value='$kinfiles'>\n";
    echo "    <param name='kinfileBase' value='$_SESSION[dataURL]/".MP_DIR_KINS."'>\n";
    echo "    <param name='kinfileSaveHandler' value='save_kinemage.php?$_SESSION[sessTag]'>\n";
    
    // For supporting electron density maps:
    if(is_array($_SESSION['edmaps']))
    {
        foreach($_SESSION['edmaps'] as $edmap)
        {
            if(isset($ed_param))    $ed_param .= " ".MP_DIR_EDMAPS."/".$edmap;
            else                    $ed_param = MP_DIR_EDMAPS."/".$edmap;
        }
        if(isset($ed_param))
        {
            echo "    <param name='edmapBase' value='$_SESSION[dataURL]'>\n";
            echo "    <param name='edmapList' value='$ed_param'>\n";
        }
    }
?>
</applet>

<br><form>
<table border='0' width='100%'><tr>
<td align='left'>
When finished, you should 
<input type="button" value="close this window"
language="JavaScript" onclick="self.close();">.
</td>
<td><a href='help/java.html'>Don't see anything?</a></td>
<td align='right'>
<?php
if($_SESSION['kingSize'] == "tiny") echo "tiny";
else echo "<a href='viewking.php?$_SESSION[sessTag]&url=$url&size=tiny'>tiny</a>";
echo " | ";
if($_SESSION['kingSize'] == "small") echo "small";
else echo "<a href='viewking.php?$_SESSION[sessTag]&url=$url&size=small'>small</a>";
echo " | ";
if($_SESSION['kingSize'] == "default") echo "default";
else echo "<a href='viewking.php?$_SESSION[sessTag]&url=$url&size=default'>default</a>";
echo " | ";
if($_SESSION['kingSize'] == "large") echo "large";
else echo "<a href='viewking.php?$_SESSION[sessTag]&url=$url&size=large'>large</a>";
?>
</td>
</tr></table>
</form>


</center>
<?php echo mpPageFooter(); ?>
