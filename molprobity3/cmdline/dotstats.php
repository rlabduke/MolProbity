<?php

// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/visualize_nmr.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpInitEnvirons();       // use std PATH, etc.
    //mpStartSession(true);   // create session dir
// 5. Set up reasonable values to emulate CLI behavior if we're CGI
    set_time_limit(0); // don't want to bail after 30 sec!


// ratios for dot statistics
// useful defined variables


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

foreach()
{
// total residues in model
$pdbstats = pdbstat("myfile.pdb");
// $probestats = probestat("myfile.pdb"); (probe parser needs to be built probestat.php)
$res = $pdbstats['residues'];

//total atoms in model
// $atoms = $pdbstats['atoms'];  (needs to be built in pdbstat)

// total # of h-bond dots
$hbcont = ;

// total number of h-bonds
$hbondct = ;

// total number of residues with h-bonds
$reshb = ;

// total number of bad overlap dots
$badcont = ;

// total number of residues with bad overlaps
$resbad = ;

// total number of small overlap dots
$smallcont ;

// total number of residues with small overlaps
$ressmall ;

// total number of close contact dots
$closecont ;

// total number of wide contact dots
$widecont ;


// H-BOND STATS
if ($hbondct == $reshb)
{
	$avghbd1to1 = $hbcont / $hbondct;
//	echo "Number of total h-bond dots / total number of h-bonds ";
//	echo $avghbd1to1;
//	echo " .\n";
}
else
{  
	$avghbddot2ct = $hbcont / $hbondct;
//	echo "Number of total h-bond dots / total number of h-bonds
//	echo $avghbddot2ct;
//	echo " .\n";
	$avghbddot2res = $hbcont / $reshb;
//	echo "Number of total h-bond dots / total number of res involved in h-bonds ";
//	echo $avghbddot2res;
//	echo " .\n";
	$avghbddot2atom = $hbcont / $atoms;
//	echo "Number of total h-bond dots / total number of atoms in model";
//	echo $avghbddot2atom;
//	echo " .\n";

}
// avg number of h-bond overlaps per res over whole structure
$avghbwhole = $hbcont / $res;

// BAD OVERLAP STATS
// avg bad contacts per residue of bad contacts
$avgbad = $badcont / $resbad;
// avg number of bad overlaps per res over whole structure
$avgbadwhole = $badcont / $res;


// SMALL OVERLAP STATS
// avg number of small overlaps per res with small overlaps
$avgsmall = $smallcont / $ressmall;
// avg number of small overlaps per res over whole structure
$avgsmallwhole = $smallcont / $res;


// CLOSE CONTACT STATS
// avg number of close contacts per res over whole strucutre
$avgclosewhole = $clonecont / $res;

// WIDE CONTACT STATS
// avg number of wide contacts per res over whole structure
$avgwidewhole = $widecont / $res;

// OTHER STATISTICS
// total number of good contacts
$goodcont = $hbdcont + $closecont + $widecont;

// avg number of good contacts per res over whole structure
$avggoodwhole = $goodcont / $res;
}

?>	
