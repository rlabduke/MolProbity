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
// Note that changing size breaks the "Close" button, at least in Safari, b/c the page reloads.
if(isset($_REQUEST['size']))
{
    $kingSize = $_REQUEST['size'];
    setcookie('viewking_kingSize', $kingSize);
    
    // You could get in trouble if a background job was running (hence the check here)
    // or if you somehow hit the size-change link while another page was loading (unlikely),
    // because this update would be overwriten when the background job exited (hopefully),
    // or this update would overwrite the results of the background job just as it ended (disaster).
    if(!$_SESSION['bgjob']['isRunning'])
    {
        mpSessReadOnly(false);
        $_SESSION['kingSize'] = $kingSize;
    }
}
// By favoring cookies over session data, we can change size even while a background job is running.
elseif(isset($_COOKIE['viewking_kingSize']))    $kingSize = $_COOKIE['viewking_kingSize'];
elseif(isset($_SESSION['kingSize']))            $kingSize = $_SESSION['kingSize'];
else                                            $kingSize = 'default';

if($kingSize        == "tiny")  $size = "width='600' height='400'"; //  640 x 480
elseif($kingSize    == "small") $size = "width='700' height='500'"; // 800 x 600
elseif($kingSize    == "large") $size = "width='1300' height='950'"; // 1400 x 1050
elseif($kingSize    == "huge")  $size = "width='1500' height='1100'"; // 1600 x 1200
else                            $size = "width='950' height='650'"; // Good for most people (1024 x 768)
// Unfortunately, percentage sizes don't work reliably, as that would be a nicer way to go for "default".

$url = $_REQUEST['url'];
$file = basename($url);
// Start the page: produces <HTML>, <HEAD>, <BODY> tags
echo mpPageHeader("KiNG - $file");

############################################################################
?>
<center>
<applet id="king_applet" code="king/Kinglet.class" archive="king.jar" <?php echo $size; ?>>
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
if($kingSize == "tiny") echo "tiny";
else echo "<a href='viewking.php?$_SESSION[sessTag]&url=$url&size=tiny'>tiny</a>";
echo " | ";
if($kingSize == "small") echo "small";
else echo "<a href='viewking.php?$_SESSION[sessTag]&url=$url&size=small'>small</a>";
echo " | ";
if($kingSize == "default") echo "default";
else echo "<a href='viewking.php?$_SESSION[sessTag]&url=$url&size=default'>default</a>";
echo " | ";
if($kingSize == "large") echo "large";
else echo "<a href='viewking.php?$_SESSION[sessTag]&url=$url&size=large'>large</a>";
echo " | ";
if($kingSize == "huge") echo "huge";
else echo "<a href='viewking.php?$_SESSION[sessTag]&url=$url&size=huge'>huge</a>";
?>
</td>
</tr></table>
</form>

<script language="JavaScript">
<!--
// {{{ This is for widening the browser window as necessary.
function widenBrowser()
{
    if(!window.outerHeight) return // only works for Netscape family

    king_applet = document.getElementById("king_applet")
    newOuterWidth = findPosX(king_applet) + king_applet.offsetWidth + 32
    if(newOuterWidth > 600 && newOuterWidth < screen.width)
        window.resizeTo(newOuterWidth, window.outerHeight)
}

// Borrowed from http://www.quirksmode.org/js/findpos.html
function findPosX(obj)
{
    var curleft = 0;
    if (obj.offsetParent)
    {
        while (obj.offsetParent)
        {
            curleft += obj.offsetLeft
            obj = obj.offsetParent;
        }
    }
    else if (obj.x)
        curleft += obj.x;
    return curleft;
}
function findPosY(obj)
{
    var curtop = 0;
    if (obj.offsetParent)
    {
        while (obj.offsetParent)
        {
            curtop += obj.offsetTop
            obj = obj.offsetParent;
        }
    }
    else if (obj.y)
        curtop += obj.y;
    return curtop;
}

window.onload = widenBrowser
// }}} -->
</script>


</center>
<?php echo mpPageFooter(); ?>
