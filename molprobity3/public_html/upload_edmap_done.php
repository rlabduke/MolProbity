<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file is on the receiving side of the upload tab. The relevant file(s)
    are transferred to temporary locations if necessary, and then a background
    job is launched for further processing of them.

INPUTS (via Get or Post):
    cmd             "Upload this file"
    uploadFile      the uploaded file (data in $_FILES['uploadFile'][...])

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
    #mpSessReadOnly();

# MAIN - the beginning of execution for this page
############################################################################
$mapName = censorFileName($_FILES['uploadFile']['name']); // make sure no spaces, etc.
$mapPath = "$_SESSION[dataDir]/$mapName";
if( !$_FILES['uploadFile']['error'] && $_FILES['uploadFile']['size'] > 0
&& !file_exists($mapPath) && move_uploaded_file($_FILES['uploadFile']['tmp_name'], $mapPath))
{
    // Uploaded file probably has restrictive permissions
    chmod($mapPath, (0666 & ~MP_UMASK));
    $_SESSION['edmaps'][$mapName] = $mapName;
    mpLog("edmap-upload:User uploaded an electron density map file");
}
else
{
    echo mpPageHeader("Sorry!");
    if($_FILES['uploadFile']['error'])
        echo("File upload failed with error code {$_FILES[uploadFile][error]}.");
    elseif(file_exists($mapPath))
        echo("File upload failed because another file of the same name already exists.");
    elseif($_FILES['uploadFile']['size'] <= 0)
        echo("File upload failed because of zero file size (no contents).");
    else
        echo("File upload failed for an unknown reason.");
    echo "\n<p><a href='upload_tab.php?$_SESSION[sessTag]'>Try again</a>\n";
    echo mpPageFooter();
    die();
}

// Reach this point only if the map was uploaded successfully.
echo mpPageHeader("Map $mapName added", "upload");
echo "<p>The following electron density maps are now available:\n";
echo "<ul>\n";
foreach($_SESSION['edmaps'] as $map)
{
    $mapPath = "$_SESSION[dataDir]/$map";
    echo "<li><b>$map</b> (".formatFilesize(filesize($mapPath)).")</li>\n";
}
echo "</ul>\n</p>\n";
echo "\n<p><a href='upload_tab.php?$_SESSION[sessTag]'>Upload more files</a></p>\n";
############################################################################
?>


<?php echo mpPageFooter(); ?>
