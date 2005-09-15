<?php
/* Pseudo-code for creation of a useful NMR related kinemage.
for a set of PDB files split out from an ensemble
put all names in array
open first one
run prekin mainchain and h-bonds
append as subgroup run noe-display to get violations only with r^6 summation added
append as subgroup run rotamer outliers
append as subgroup run probe bad clashes and h-bonds
append as subgroup run ramachandran outliers.
for subsequent in array
run with @kinemage suppressed and make animatable for prekin
*/

error_reporting(E_ALL ^ E_NOTICE);

// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/visualize.php');
    require_once(MP_BASE_DIR.'/lib/visualize_nmr.php');
// 5. Set up reasonable values to emulate CLI behavior if we're CGI
    set_time_limit(0); // don't want to bail after 30 sec!


   
// First argument is the name of this script...
if(is_array($_SERVER['argv']))
{
	// array_slice takes off the script name from beginning
	foreach(array_slice($_SERVER['argv'], 1) as $arg)
	
	{
	    if(!isset($inpdbdir))
		$inpdbdir = $arg;
	     elseif(!isset($outfile))
		$outfile = $arg;
	     elseif(!isset($inconstraint))
		$inconstraint = $arg;
	     else
	    	die("Too many or unrecognized arguments: '$arg'\n");
	}
}

if(! isset($inpdbdir))
    die("No input pdb directory specified.\n");
elseif(! isset($outfile))
    die("No output file specified. \n");
  

//Iterate through the current directory for .pdb files to use
$ending = ".pdb";
$dirHandle = opendir($inpdbdir);

while(($file = readdir($dirHandle)) !== false)
{
	//make filename include designated directory + name
$file= $inpdbdir."/".$file;
	if(is_file($file) && $ending == substr($file, -strlen($ending)))
	{
		$pdbname[] = $file;
	}
} 
closedir($dirHandle);
print_r($pdbname);
//nmrMultiKin($pdbname, $inconstraint, $outfile);
        $mcKinOpts = array(
            'ribbons'  =>  true,
            'altconf'   =>  true,
            'rama'      =>  true,
            'rota'      =>  true,
            'cbdev'     =>  true,
            'dots'      =>  true,
            'hbdots'    =>  true,
            'vdwdots'   =>  false
	    
	    
        );
        makeMulticritKin($pdbname, $outfile, $mcKinOpts, $inconstraint);

 ?>
 
