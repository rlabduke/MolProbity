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
			 
			 //dots h-bonds and clashes
			 exec("probe -mc -stdbonds -NOGroup -NOVDWOUT -quiet -noticks -self 'All' $pdb > $kinName");
			 //modifies the probe output to includ ea mc Dots master
			 //$h = fopen('probetempdata', 'r');
			 //$k = fopen($kinName, 'a');
			 //while(! feof($h))
			 //{
				 //$line = fgets($h);
				 //if(preg_match($subgroup, $line))
				 //{
				//	 fwrite($k, "@subgroup {mc Dots} master = {mc Dots}\n");
				// }
				// else
				// {
				//	 fwrite($k, $line);
				// }
			// }
			// fclose($k);
			// fclose($h);
			// unlink("probetempdata");
			 
			 //sc dots only. h-bonds and clashes
			// exec("probe -stdbonds -NOGroup -NOVDWOUT -quiet -noticks -name 'sc-x dots' -self 'alta' $pdb >> 'probetempdata'");
			 //modifies the probe output to include a sc Dots master
			// $h = fopen('probetempdata', 'r');
			// $k = fopen($kinName, 'a');
			// while(! feof($h))
			// {
			//	 $line = fgets($h);
			//	 if(preg_match($subgroup, $line))
			//	 {
			//		 fwrite($k, "@subgroup {sc Dots} master = {sc Dots}\n");
			//	 }
			//	 else
			//	 {
			//		 fwrite($k, $line);
			//	 }
			// }
			// fclose($k);
			// fclose($h);
			// unlink("probetempdata");
			 
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
			
			 exec("probe -mc -stdbonds -NOGroup -NOVDWOUT -quiet -noticks -self 'All' $pdb >> $kinName");
			 //modifies the probe output to includ ea mc Dots master
			 //$h = fopen('probetempdata', 'r');
			 //$k = fopen($kinName, 'a');
			 //while(! feof($h))
			 //{
			//	 $line = fgets($h);
			//	 if(preg_match($subgroup, $line))
			//	 {
			//		 fwrite($k, "@subgroup {mc Dots} master = {mc Dots}\n");
			//	 }
			//	 else
			//	 {
			//		 fwrite($k, $line);
			//	 }
			 //}
			 //fclose($k);
			 //fclose($h);
			 //unlink("probetempdata");
			 
			 //sc dots only. h-bonds and clashes
			 //exec("probe -stdbonds -NOGroup -NOVDWOUT -quiet -noticks -name 'sc-x dots' -self 'alta' $pdb >> 'probetempdata'");
			 //modifies the probe output to include a sc Dots master
			 //$h = fopen('probetempdata', 'r');
			 //$k = fopen($kinName, 'a');
			 //while(! feof($h))
			 //{
			//	 $line = fgets($h);
			//	 if(preg_match($subgroup, $line))
			//	 {
			//		 fwrite($k, "@subgroup {sc Dots} master = {sc Dots}\n");
			//	 }
			//	 else
			//	 {
			//		 fwrite($k, $line);
			//	 }
			 //}
			 //fclose($k);
			 //fclose($h);
			 //unlink("probetempdata");
			 
			 
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


#{{{makeChartKin - makes kin with charst derived from NMR_PACK.php and elsewhere


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


//$pdbname is an array of pdb file names, 
//$Chartkin is the output kin file which is overwritten
function makeChartKin($pdbname, $ChartKin)
{
	$caption = "caption goes here";
	
	$out = fopen ($ChartKin, 'w');
	
	fwrite ($out,"@kinemage \n");

	fwrite ($out," \n");

	fwrite ($out," \n");

	fwrite ($out,"@onewidth \n");
	fwrite ($out,"@viewid {Overview} \n");
	fwrite ($out,"@zoom 1.00 \n");
	fwrite ($out,"@zslab 200 \n");
	fwrite ($out,"@ztran 0 \n");
	fwrite ($out,"@center -16.500 -11.500 0.122 \n");
	fwrite ($out,"@matrix \n");
	fwrite ($out,"1.000000 -0.000000 0.000000 0.000000 1.000000 0.000000 0.000000 -0.000000 1.000000 \n");
	fwrite ($out,"@master {Rotamer Outliers} \n");
	fwrite ($out,"@master {Rotamer % Outliers} \n");
	fwrite ($out,"@master {Global Measure Chart} \n");
	fwrite ($out,"@master {Residue Chart} \n");

	fwrite ($out,"@labellist {label} color= white master= {Global Measure Chart} nobutton \n");
	fwrite ($out,"{Resolution or Constrains per Residue}100.000 -30.000 0.000 \n");
	fwrite ($out,"{Value} -30.000 125.000 30.000 \n");

	fwrite ($out,"@vectorlist {edge} color= gray width= 1 master= {Global Measure Chart} nobutton \n");
	fwrite ($out,"{plot edge}P 0.000 250.000 0.000 \n");
	fwrite ($out,"{plot edge}0.000 0.000 0.000 \n");
	fwrite ($out,"{plot edge}250.000 0.000 0.000 \n");
	fwrite ($out,"{plot edge}250.000 250.000 0.000 \n");
	fwrite ($out,"{plot edge}0.000 250.000 0.000 \n");

	fwrite ($out,"@labellist {label} color= white master= {Residue Chart} nobutton \n");
	fwrite ($out,"{Model} 30.000 -30.000 -125.000 \n");
	fwrite ($out,"{Residue Number} -125.000 -30.000 30.000 \n");

	fwrite ($out,"@vectorlist {edge} color= gray width= 1 master= {Residue Chart} nobutton \n");
	fwrite ($out,"{plot edge}P -250.000  0.000 -250.000 \n");
	fwrite ($out,"{plot edge}0.000 0.000 -250.000 \n");
	fwrite ($out,"{plot edge}0.000 0.000 0.000 \n");
	fwrite ($out,"{plot edge}-250.000 0.000 0.000 \n");
	fwrite ($out,"{plot edge}-250.000 0.000 -250.000 \n");

	fwrite ($out,"@vectorlist {residue} color= gray width= 1 master= {Residue Chart} nobutton \n");
	fwrite ($out,"{tick}P 0.000 0.000 0.000 \n");
	fwrite ($out,"{\"}0.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -5.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-5.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -10.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-10.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -15.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-15.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -20.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-20.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -25.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-25.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -30.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-30.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -35.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-35.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -40.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-40.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -45.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-45.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -50.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-50.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -55.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-55.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -60.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-60.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -65.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-65.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -70.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-70.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -75.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-75.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -80.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-80.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -85.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-85.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -90.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-90.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -95.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-95.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -100.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-100.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -105.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-105.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -110.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-110.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -115.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-115.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -120.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-120.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -125.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-125.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -130.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-130.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -135.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-135.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -140.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-140.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -145.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-145.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -150.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-150.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -155.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-155.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -160.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-160.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -165.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-165.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -170.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-170.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -175.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-175.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -180.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-180.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -185.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-185.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -190.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-190.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -195.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-195.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -200.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-200.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -205.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-205.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -210.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-210.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -215.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-215.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -220.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-220.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -225.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-225.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -230.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-230.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -235.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-235.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -240.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-240.000 0.000 10.000 \n");
	fwrite ($out,"{\"}P -245.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-245.000 0.000 5.000 \n");
	fwrite ($out,"{\"}P -250.000 0.000 0.000 \n");
	fwrite ($out,"{\"}-250.000 0.000 10.000 \n");


	fwrite ($out,"@vectorlist {Model} color= gray width= 1 master= {Residue Chart} nobutton \n");
	fwrite ($out,"{tick}P 0.000 0.000 0.000 \n");
	fwrite ($out,"{\"}10.000 0.000 0.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -10.000 \n");
	fwrite ($out,"{\"}5.000 0.000 -10.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -20.000 \n");
	fwrite ($out,"{\"}5.000 0.000 -20.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -30.000 \n");
	fwrite ($out,"{\"}5.000 0.000 -30.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -40.000 \n");
	fwrite ($out,"{\"}5.000 0.000 -40.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -50.000 \n");
	fwrite ($out,"{\"}10.000 0.000 -50.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -60.000 \n");
	fwrite ($out,"{\"}5.000 0.000 -60.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -70.000 \n");
	fwrite ($out,"{\"}5.000 0.000 -70.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -80.000 \n");
	fwrite ($out,"{\"}5.000 0.000 -80.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -90.000 \n");
	fwrite ($out,"{\"}5.000 0.000 -90.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -100.000 \n");
	fwrite ($out,"{\"}10.000 0.000 -100.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -110.000 \n");
	fwrite ($out,"{\"}5.000 0.000 -110.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -120.000 \n");
	fwrite ($out,"{\"}5.000 0.000 -120.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -130.000 \n");
	fwrite ($out,"{\"}5.000 0.000 -130.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -140.000 \n");
	fwrite ($out,"{\"}5.000 0.000 -140.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -150.000 \n");
	fwrite ($out,"{\"}10.000 0.000 -150.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -160.000 \n");
	fwrite ($out,"{\"}5.000 0.000 -160.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -170.000 \n");
	fwrite ($out,"{\"}5.000 0.000 -170.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -180.000 \n");
	fwrite ($out,"{\"}10.000 0.000 -180.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -190.000 \n");
	fwrite ($out,"{\"}5.000 0.000 -190.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -200.000 \n");
	fwrite ($out,"{\"}10.000 0.000 -200.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -210.000 \n");
	fwrite ($out,"{\"}5.000 0.000 -210.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -220.000 \n");
	fwrite ($out,"{\"}5.000 0.000 -220.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -230.000 \n");
	fwrite ($out,"{\"}5.000 0.000 -230.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -240.000 \n");
	fwrite ($out,"{\"}5.000 0.000 -240.000 \n");
	fwrite ($out,"{\"}P 0.000 0.000 -250.000 \n");
	fwrite ($out,"{\"}10.000 0.000 -250.000 \n");

//for each pdb and iterate

//in the beginning.. there was a model!
$modelcounter = 1;

foreach($pdbname as $pdb)
{
//overall stats needed for each pdb file

// crystallographic resolution of the model
$stats = pdbstat($pdb); 
$resolution = $stats['resolution'];

/*
$ConstPerRes =   
// NMR constraints per Residue in the model
*/

//number of res in the model
$numRes = $stats['residues'];

// plotting of rotamer outliers along for each model along vs. residue number
fwrite ($out,"@group {" . basename($pdb,".pdb") . "} animate dominant \n");

// plot of rotamer outliers
fwrite ($out,"@balllist {Rotamer Outliers} color= white radius= 0.5 master= {Rotamer Outliers} nohilite \n");

// create unique temp files in current directory
$tmp1 = tempnam(MP_BASE_DIR."/tmp", 'graphtemp');

// calculations for rotamer data
runRotamer($pdb, $tmp1);
$rota= loadrotamer($tmp1);

//plotting rotamer outliers, determining the x (res), y (score), and z (model) values
//followed by writing them out in the balllist
$RotaOut = findRotaOutliers($rota);

// $RotaOut is the the list of outliers and their scores
foreach ($RotaOut as $ResName => $score)
{
	$x = ($rota[$ResName]['resNum']) * -1.000;
	$y = round($rota[$ResName]['scorePct'],2);
	$z = $modelcounter * -10.000;
	
	// if x = 0, go to next one...
	if($x == 0)
	{
	continue;	
	}
	
	fwrite ($out,"{". basename($pdb,".pdb"). " " . $ResName ."}$x $y $z \n");
	
}

// plotting % rotamer outlier data
// x is what you plot against, resolution / restraints per res etc
// y is the percentage of rotamer outliers in the entire model
// plots the model

$numRotaOut = count( findRotaOutliers($rota) );
// can include a factor here 
$percentoutlier = round( (($numRotaOut / $numRes) * 100), 2);
// multiplication by 100 is the scaleing 'fudge' factor
$x = $resolution * 100;
// -10.00 another scaling factor
$z = $modelcounter * -10.000;

fwrite ($out,"@balllist {Rotamer % Outliers} color= white radius= 1.0 master= {Rotamer % Outliers} nohilite \n");

fwrite ($out,"{" . basename($pdb,".pdb") . " " . $resolution . "A , ". $percentoutlier ."%" . "} $x $percentoutlier $z \n");

unlink ($tmp1);

//change the Z number which gets multiplied (and hereafter we added another)
$modelcounter= $modelcounter + 1;

//end of the for each loop going through the PDB files
}

	
	fclose ($out);
	
	
}



#}}}

?>
