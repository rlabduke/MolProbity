<?php # (jEdit options) :folding=explicit:collapseFolds=1:

// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
    require_once(MP_BASE_DIR.'/lib/analyze.php');
    require_once(MP_BASE_DIR.'/lib/visualize.php');
    
      
//Functions for ROTAMER OUTLIERS
#{{{ makeBadRotamerKinNMR
function makeBadRotamerKinNMR($infile, $outfile, $rota, $color = 'orange', $cutoff = 1.0)
{
	foreach($rota as $res)
	{
		if($res['scorePct'] <= $cutoff)
		$worst[] = $res['resName'];
	}
	$sc = resGroupsForPrekin(groupAdjacentRes($worst));
	
	$h = fopen($outfile, 'a');
	// changed to @subgroup
	fwrite($h, "@subgroup {bad rotamers} dominant\n");
	fclose($h);
	foreach($sc as $scRange)
	exec("prekin -quiet -append -nogroup -listmaster 'Rota Outliers' -bval -scope $scRange -show 'sc($color)' $infile >> $outfile");
}
#}}}
//Functions for Ramachandran Outliers
#{{{ makeBadRamaKinNMR
function makeBadRamaKinNMR($infile, $outfile, $rama, $color = 'red')
{
    foreach($rama as $res)
    {
        if($res['eval'] == 'OUTLIER')
            $worst[] = $res['resName'];
    }
    $mc = resGroupsForPrekin(groupAdjacentRes($worst));
    
    $h = fopen($outfile, 'a');
    //changed to @subgroup, added @vectorlist, and changed color to red for rama outliers
    fwrite($h, "@subgroup {Rama outliers} \n@vectorlist {Rama outliers} color= red \n");
    fclose($h);
    foreach($mc as $mcRange)
        exec("prekin -append -nogroup -listmaster 'Rama Outliers' -scope $mcRange -show 'mc($color)' $infile >> $outfile");
}
#}}}

#{{{ nmrMultiKin - rot, rama, noe, clashes all in subgroups, multimodel support

// $pdbname - array of pdbs to be iterated and subsequently appended into the kin (all same file)
// $constraints - NOE constraints file, must not include dihedral angle constraints.  
// $kinName - name of output .kin file, should make consistent with input pdb name (parts are appended).



function nmrMultiKin($pdbname, $constraints, $kinName)
{
	$subgroup = '/^@subgroup/';
	$first = true;
	 foreach($pdbname as $pdb)
	 {
		 if($first)
		 {
			 //commands run on the first .pdb opened
			 
			 //create a base kin w/ mainchain and h-bonds
			 echo("prekin -mchb -lots -animate -show 'mc(white),sc(blue)' $pdb > $kinName");
			 exec("prekin -mchb -lots -animate -show 'mc(white),sc(blue)' $pdb > $kinName");
			 
			 //mc dots only.  h-bonds and clashes
			 exec("probe -stdbonds -NOGroup -NOVDWOUT -quiet -noticks -name 'mc-mc dots' -mc -self 'mc alta' $pdb > 'probetempdata'");
			 //modifies the probe output to includ ea mc Dots master
			 $h = fopen('probetempdata', 'r');
			 $k = fopen($kinName, 'a');
			 while(! feof($h))
			 {
				 $line = fgets($h);
				 if(preg_match($subgroup, $line))
				 {
					 fwrite($k, "@subgroup {mc Dots} master = {mc Dots}\n");
				 }
				 else
				 {
					 fwrite($k, $line);
				 }
			 }
			 fclose($k);
			 fclose($h);
			 unlink("probetempdata");
			 
			 //sc dots only. h-bonds and clashes
			 exec("probe -stdbonds -NOGroup -NOVDWOUT -quiet -noticks -name 'sc-x dots' -self 'alta' $pdb >> 'probetempdata'");
			 //modifies the probe output to include a sc Dots master
			 $h = fopen('probetempdata', 'r');
			 $k = fopen($kinName, 'a');
			 while(! feof($h))
			 {
				 $line = fgets($h);
				 if(preg_match($subgroup, $line))
				 {
					 fwrite($k, "@subgroup {sc Dots} master = {sc Dots}\n");
				 }
				 else
				 {
					 fwrite($k, $line);
				 }
			 }
			 fclose($k);
			 fclose($h);
			 unlink("probetempdata");
			 
			 //append as subgroup the violations only from noe-display set w/ r^6 summation
			 echo("noe-display -cv -s viol -ds+ -fs -k $pdb $constraints < /dev/null >> $kinName");
			 exec("noe-display -cv -s viol -ds+ -fs -k $pdb $constraints < /dev/null >> $kinName");
			 //run rotamer analysis and create kin as subgroup (mods in functions listed above)
			 runRotamer($pdb, "runRotTemp.data");
			 $loadRotOut = loadRotamer("runRotTemp.data");
			 makeBadRotamerKinNMR($pdb, $kinName, $loadRotOut);
			 //run ramachandran anlysis and create kin as subgroup (mods in functions listed above)
			 runRamachandran($pdb, "runRamaTemp.data");
			 $loadRamaOut = loadRamachandran("runRamaTemp.data");
			 makeBadRamaKinNMR($pdb, $kinName, $loadRamaOut);
			 
			 //separates so not running this 'first' on any others... (for supressing @kinemage)
			 $first = false;
			 
		 }
		 else
		 {
			 //commands run on all subsequent .pdb files opened
			 
			 //create a base kin w/ mainchain and h-bonds and colors them
			 echo("prekin -mchb -lots -append -animate -show 'mc(white),sc(blue)' $pdb >> $kinName");
			 exec("prekin -mchb -lots -append -animate -show 'mc(white),sc(blue)' $pdb >> $kinName");
			 
			 //mc dots only.  h-bonds and clashes
			
			 exec("probe -stdbonds -NOGroup -NOVDWOUT -quiet -noticks -name 'mc-mc dots' -mc -self 'mc alta' $pdb >> 'probetempdata'");
			 //modifies the probe output to includ ea mc Dots master
			 $h = fopen('probetempdata', 'r');
			 $k = fopen($kinName, 'a');
			 while(! feof($h))
			 {
				 $line = fgets($h);
				 if(preg_match($subgroup, $line))
				 {
					 fwrite($k, "@subgroup {mc Dots} master = {mc Dots}\n");
				 }
				 else
				 {
					 fwrite($k, $line);
				 }
			 }
			 fclose($k);
			 fclose($h);
			 unlink("probetempdata");
			 
			 //sc dots only. h-bonds and clashes
			 exec("probe -stdbonds -NOGroup -NOVDWOUT -quiet -noticks -name 'sc-x dots' -self 'alta' $pdb >> 'probetempdata'");
			 //modifies the probe output to include a sc Dots master
			 $h = fopen('probetempdata', 'r');
			 $k = fopen($kinName, 'a');
			 while(! feof($h))
			 {
				 $line = fgets($h);
				 if(preg_match($subgroup, $line))
				 {
					 fwrite($k, "@subgroup {sc Dots} master = {sc Dots}\n");
				 }
				 else
				 {
					 fwrite($k, $line);
				 }
			 }
			 fclose($k);
			 fclose($h);
			 unlink("probetempdata");
			 
			 
			 //append as subgroup the violations only from noe-display set w/ r^6 summation
			 echo("noe-display -cv -s viol -ds+ -fs -k $pdb $constraints < /dev/null >> $kinName");
			 exec("noe-display -cv -s viol -ds+ -fs -k $pdb $constraints < /dev/null >> $kinName");
			 //run rotamer analysis and create kin as subgroup (mods in functions listed above)
			 runRotamer($pdb, "runRotTemp.data");
			 $loadRotOut = loadRotamer("runRotTemp.data");
			 makeBadRotamerKinNMR($pdb, $kinName, $loadRotOut);
			 //run ramachandran anlysis and create kin as subgroup (mods in functions listed above
			 runRamachandran($pdb, "runRamaTemp.data");
			 $loadRamaOut = loadRamachandran("runRamaTemp.data");
			 makeBadRamaKinNMR($pdb, $kinName, $loadRamaOut);
			 
		 }
		 unlink("runRamaTemp.data");
		 unlink("runRotTemp.data");
		
	 }
	 
} //end of nmrMultiKin
#}}}



?>
