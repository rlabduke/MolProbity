<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file is on the receiving side of the upload tab. The relevant file(s)
    are processed/copied immediately, and the results displayed to the user.

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
// Remove the old het dictionary:
if(isset($_SESSION['hetdict']))
{
    unlink($_SESSION['hetdict']);
    unset($_SESSION['hetdict']);
}

$dictName = "user_het_dict.txt";
$dictPath = "$_SESSION[dataDir]/$dictName";
if( !$_FILES['uploadFile']['error'] && $_FILES['uploadFile']['size'] > 0
&& move_uploaded_file($_FILES['uploadFile']['tmp_name'], $dictPath))
{
    // Uploaded file probably has restrictive permissions
    chmod($dictPath, (0666 & ~MP_UMASK));
    exec("echo >> $dictPath"); // adds a blank line
    exec("cat ".MP_REDUCE_HET_DICT." >> $dictPath"); // appends the std dict
    $_SESSION['hetdict'] = $dictName;
    mpLog("hetdict-upload:User uploaded an custom het dictionary file");
}
else
{
    echo mpPageHeader("Sorry!");
    if($_FILES['uploadFile']['error'])
        echo("File upload failed with error code {$_FILES[uploadFile][error]}.");
    elseif($_FILES['uploadFile']['size'] <= 0)
        echo("File upload failed because of zero file size (no contents).");
    else
        echo("File upload failed for an unknown reason.");
    echo "\n<p><a href='upload_tab.php?$_SESSION[sessTag]'>Try again</a>\n";
    echo mpPageFooter();
    die();
}

// Reach this point only if the map was uploaded successfully.
echo mpPageHeader("Custom het dictionary added", "upload");
echo "<p>Your custom heterogen dictionary will be used for all future work in this session.</p>\n";
echo "\n<p><a href='upload_tab.php?$_SESSION[sessTag]'>Upload more files</a></p>\n";
############################################################################
?>


<?php echo mpPageFooter(); ?>
