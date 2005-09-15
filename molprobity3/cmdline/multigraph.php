<?php
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/visualize.php');
    require_once(MP_BASE_DIR.'/lib/visualize_nmr.php');
    require_once(MP_BASE_DIR.'/lib/analyze_nmr.php');
    require_once(MP_BASE_DIR.'/lib/pdbstat.php');
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
	    //elseif(!isset($inconstraint))
		//$inconstraint = $arg;
	    elseif(!isset($ChartKin))
		$ChartKin = $arg;
	    else
	    	die("Too many or unrecognized arguments: '$arg'\n");
	}
}

if(! isset($inpdbdir))
    die("No input pdb directory specified.\n");
//elseif(! isset($inconstraint))
    //die("No constraint file specified.\n");
elseif(! isset($ChartKin))
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


makeChartKin($pdbname, $ChartKin);

/*
Multigraph:

Plotting in multiple ways the data outputs from MolProbity Validation web-service.

In the X, Y plane:

(+)X direction: linearly validated quality measures
- crystallographic resolution
- NMR constrains / residue


(+)Y direction: MolProbity global measures evaluated over an entire model
- clashscore
- mainchain H-bond score
- % rotamer outliers or raw number
- % rama outliers or raw number

In the -X, -Z plane:

(-)Z direction: model number or name incremented by integer along the Z direction
- in order for NMR ensembles, and by name for comparisons or large evaluations of datasets

(-X) direction: residue number for the model
- in order from 1 at -1 and going on up


For a hypothetical two models a and b, the kinemage pseudo code for the plotted data 
is the following:

grids and labels etc

group a 

dot list rotamer outlier % (rota % master)
x,y,z (typical x, y plot for global measures)
resolution (1.7), value (24.54), model # slice placement (1)
{    24.54%} 170.00 24.54 -10.0

dot list romater outliers (rota outliers)
x,y,z (typical -x, -z plot for tracking along a model and comparing b/w models) 
res# (a neg number), 0, model # slice placement (neg number)
{    12 ASN} -12.000 0.000 -10.0
{    76 ILE} -76.000 0.000 -10.0

other measures will be similar... 


*/


?>
