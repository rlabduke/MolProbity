<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This file runs the user-configured AutoFix commands on an existing
    model in this session and creates a new PDB in this model.
    
INPUTS (via $_SESSION['bgjob']):
    modelID         ID code for model to process
    dofix[]        an array of booleans

OUTPUTS (via $_SESSION['bgjob']):
    modelID         ID code for model that was processed

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/model.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    session_id( $_SERVER['argv'][1] );
    mpStartSession();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    #mpSessReadOnly();
// 5. Set up reasonable values to emulate CLI behavior if we're CGI
    set_time_limit(0); // don't want to bail after 30 sec!
// 6. Record this PHP script's PID in case it needs to be killed.
    $_SESSION['bgjob']['processID'] = posix_getpid();
    mpSaveSession();
    
#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

# MAIN - the beginning of execution for this page
############################################################################
$dofix = $_SESSION['bgjob']['dofix'];
$modelID = $_SESSION['bgjob']['modelID'];
$model = $_SESSION['models'][$modelID];
$pdb = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$model['pdb'];

// Read the USER  MOD FIX records into a hash
$pdbFile = fopen($pdb,"rb");

while(!feof($pdbFile) and ($userModFix = fgets($pdbFile,1024)))
{
   if ( eregi("^USER  MOD FIX", $userModFix) ) 
   {
      $field = explode(":", $userModFix);
    // CNIT:       CNNNNITTT
      preg_match('/^(.)(....)(.)(...)(.)/', $field[1], $f1);
      $chain = $f1[1];
      $resid = $f1[2];
      $ins   = $f1[3]; 
      $resn  = $f1[4];
      $key   = $chain." ".$resn.$resid.$ins; 
      $userMod_hash[$key] = $userModFix;
   }
   else { break; }
}
fclose($pdbFile);

$dataDir    = $_SESSION['dataDir'].'/'.MP_DIR_RAWDATA;
$autoFixStats = $dataDir."/$model[parent]_stats";

$changes = decodeAutoFixUsermods($autoFixStats,$pdb);
$n = count($changes[0]); // How many changes are in the table?

// If all changes were accepted, we will not need to re-run AutoFix.
$rerun = false;
// Re-write the coot script for AutoFix
$rawDir = $_SESSION['dataDir'].'/'.MP_DIR_RAWDATA;
if(!file_exists($rawDir)) mkdir($rawDir, 0777); // shouldn't happen
$cootScript = "$rawDir/$model[parent]_coot_fix_VTLR.scm_all";
$cootScriptMP = "$rawDir/$model[parent]_user_coot_fix_VTLR.scm";
$newcootScript = "/sw/share/coot/scheme/bob_molprobity.scm"; 
$autoFixUserMod =  "$rawDir/$model[parent]_mod_header.txt";

// Read the coot commands into a hash
$fp = fopen($cootScript, "rb");
$out = fopen($newcootScript, "wb");
$autofixHeader = fopen($autoFixUserMod, "wb");

$hash_val="";

# Read the coot commands into an array
while(!feof($fp) and ($line = fgets($fp, 200)))
{
   if ( eregi("DECOY",$line) || eregi("BAD",$line) || eregi("BORDERLINE",$line) ) {
      $s = trim($line, "\n");
      $key   = substr($s, -10);
      for ($i=0; $i<7; $i++) {
         $hash_val .= $line;
         $line= fgets($fp,200);
      }
   }
   elseif (eregi("save-coordinates",$line)) {
      break;
   }
   else {
      fwrite($out, $line); // gets script header
      continue; 
   }
   $script_hash[$key]=$hash_val."\n";
   $hash_val="";
}
fclose($fp);

for($c = 0; $c < $n; $c++)
{
    $resid = sprintf("%4s", $changes[2][$c]);
    $key = $changes[1][$c]." ".$changes[4][$c].$resid.$changes[3][$c]; 
    $command = $script_hash[$key]; 
    $header  = $userMod_hash[$key];
    if($dofix[$c]) 
    { 
       fwrite($out, $command); 
       fwrite($autofixHeader, $header);
    }

    // Expect checks for ones flipped originally; expect no check for ones not flipped.
    $expected = eregi("FLIP ACCEPTED", $changes[17][$c]);
    if($dofix[$c] != $expected) { $rerun = true; }
}

$outname    = $model['pdb']; // Just overwrite the default AutoFix pdb
$outpath    = $_SESSION['dataDir'].'/'.MP_DIR_MODELS;
if(!file_exists($outpath)) mkdir($outpath, 0777); // shouldn't ever happen, but might...
$autoFixPdb = $outpath.'/'.$outname;

//fwrite($fp, "O:" . $changes[0][$c] . "\n");
fwrite($out, "(save-coordinates 0 \"" . $autoFixPdb . "\")\n");
fwrite($out, "(coot-real-exit 1)\n");

fclose($out);
fclose($autofixHeader);


copy($newcootScript, $cootScriptMP);

if(! $rerun)
    setProgress(array("No additional changes made to model"), null);
else
{
    $tasks['autoFix'] = "Fix residues with <code>autoFix</code>";
    setProgress($tasks, 'autoFix');
    
    // input should be from parent model or we'll be double flipped!
    // copied from old script
    $parentID = $model['parent'];
    $parent = $_SESSION['models'][$parentID];
    $parentPDB = $_SESSION['dataDir'].'/'.MP_DIR_MODELS.'/'.$parent['pdb'];

    //autoFixRerun($parentPDB, $autoFixPdb, $newcootScript);

      autoFixRerun($parentPDB, $autoFixPdb, $newcootScript, $autoFixUserMod);
    
    setProgress($tasks, null); // all done
}

############################################################################
// Clean up and go home
unset($_SESSION['bgjob']['processID']);
$_SESSION['bgjob']['endTime']   = time();
$_SESSION['bgjob']['isRunning'] = false;
?>
