<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Processes a directory full of PDB files non-recursively and outputs
    a one-line summary of how many Asn/Gln/His flips there were
    
 -> We assume all files have already been processed with Reduce -build or -fix <-

INPUTS (via $_SERVER['argv']):
    the path to a directory; *.pdb will be processed

OUTPUTS:

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/model.php');
    require_once(MP_BASE_DIR.'/lib/analyze.php');
// 5. Set up reasonable values to emulate CLI behavior if we're CGI
    set_time_limit(0); // don't want to bail after 30 sec!

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

# MAIN - the beginning of execution for this page
############################################################################
// First argument is the name of this script...
if(is_array($_SERVER['argv'])) foreach(array_slice($_SERVER['argv'], 1) as $arg)
{
    if(!isset($pdbFolder))
        $pdbFolder = $arg;
    else
        die("Too many or unrecognized arguments: '$arg'\n");
}

if(! isset($pdbFolder))
    die("No input directory specified.\n");
elseif(! is_dir($pdbFolder))
    die("Input directory '$pdbFolder' does not exist or is not a directory.\n");
    
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
// This way, we don't create a session unless our input is semi-valid.
mpStartSession(true); // create a new session

// Describe the output of this script
echo "#pdbFileName:nqhTotalFlipped:nqhTotalKept:nqhClashFlipped:nqhClashKept:nqhError\n";

// Loop through all PDBs in the provided directory
$h = opendir($pdbFolder);
while(($infile = readdir($h)) !== false)
{
    $infile = "$pdbFolder/$infile";
    if(is_file($infile) && endsWith($infile, ".pdb"))
    {
        $filename = basename($infile);
        echo $filename;
        
        // Run analysis; load data
        $nqh = decodeReduceUsermods($infile);
        
        unset($cnt);
        if(is_array($nqh)) foreach($nqh[4] as $label)
            $cnt[$label]++;
        echo ":".(0+$cnt['FLIP']+$cnt['CLS-FL']).":".(0+$cnt['KEEP']+$cnt['CLS-KP']+$cnt['UNSURE']).":".(0+$cnt['CLS-FL']).":".(0+$cnt['CLS-KP']).":".(0+$cnt['???']);

        echo "\n"; // end of this line
    }
}
closedir($h);

############################################################################
// Clean up and go home
mpDestroySession();
?>
