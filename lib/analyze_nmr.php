<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Provides functions for producing analysis data from outside programs
    and for loading and interpretting that data.

    Many functions work with a column-formatted residue name
    stored in exactly 9 characters, like this: 'cnnnnittt'
        c: Chain ID, space for none
        n: sequence number, right justified, space padded
        i: insertion code, space for none
        t: residue type (ALA, LYS, etc.), all caps, left justified, space padded
*****************************************************************************/
require_once(MP_BASE_DIR.'/lib/strings.php');
require_once(MP_BASE_DIR.'/lib/model.php');     // for running Reduce as needed
require_once(MP_BASE_DIR.'/lib/visualize.php'); // for making kinemages
require_once(MP_BASE_DIR.'/lib/labbook.php');
require_once(MP_BASE_DIR.'/lib/pdbstat.php');  //for getting number of atoms

//{{{ runEnsembleAnalysis
function runEnsembleAnalysis($ensemble, $opts) {
  $labbookEntry .= "<script type='text/javascript'>\n";
  $labbookEntry .= file_get_contents(MP_BASE_DIR.'/public_html/js/mptabs.js');
  $labbookEntry .= "\n</script>\n";
  $labbookEntry .= "<div class=\"tab\">\n";
  //var_dump($ensemble);
  //use only a portion of the ensemble to limit the number of models analyzed to 50 first models
  $ensembleModelsToAnalyze = array_slice($ensemble['models'], 0, 50);
  foreach ($ensembleModelsToAnalyze as $modelID) {
    $labbookEntry .= "  <button class=\"tablinks\" onclick=\"openTab(event, '$modelID')\"><b>Model ".extractModelNumber($modelID)."</b></button>\n";
  }
  $labbookEntry .= "</div>\n";
  $doAAC = ($opts['doKinemage'] && ($opts['kinClashes'] || $opts['kinHbonds'] || $opts['kinContacts']))
    || ($opts['doCharts'] && ($opts['chartClashlist']));
  //echo "infiles:".$infiles."\n";
  foreach ($ensembleModelsToAnalyze as $modelID) {
    $model = $_SESSION['models'][$modelID];
    //echo "This is an model test:".$modelID."\n";
    $modelResults = runAnalysis($modelID, $opts);
    $labbookEntry .= "<div id=\"$modelID\" class=\"tabcontent\">\n";
    $labbookEntry .= $modelResults;
    $labbookEntry .= "</div>\n";
    
    $_SESSION['bgjob']['labbookEntry'] = addLabbookEntry(
    "Analysis output: ".($doAAC ? "all-atom contacts and " : "")."geometry for $model[pdb]",
    $modelResults,
    $modelID,
    "auto",
    ($doAAC ? "clash_rama.png" : "ramaplot.png")
);
  }
  return $labbookEntry;
}
//}}}

//{{{ extractModelNumber
function extractModelNumber($modelID) {
  $exploded = explode("_", $modelID);
  $modelNumber = ltrim($exploded[0], "m0");
  return $modelNumber;
}
//}}}


#{{{ packingStats - reads in probe -ONELINE and calculates other values

function packingStats($pdboneline, $onelinemod)
{


	$out = fopen ($onelinemod, 'w');

	$in = fopen ($pdboneline, 'r');

	while (!feof ($in) )
	{
		$inline = trim(fgets ($in, 10000)); // trim() removes trailing newline
		$inline = substr($inline, 1); // removes first character
		if($inline == "") continue; // jumps back to start of loop

		fwrite ($out, $inline);

		$linefields = explode (':', $inline);

		//H-bond values 33 - 37
		$avgHbondperatomSUM = round(($linefields [29] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgHbondperatomSUM);

		$avgHbondperatomMCMC = round(($linefields [5] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgHbondperatomMCMC);

		$avgHbondperatomSCSC = round(($linefields [11] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgHbondperatomSCSC);

		$avgHbondperatomMCSC = round(($linefields [17] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgHbondperatomMCSC);

		$avgHbondperatomOTHER = round(($linefields [23] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgHbondperatomOTHER);

		//Bad contact values 38 - 42
		$avgBadperatomSUM = round(($linefields [28] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgBadperatomSUM);

		$avgBadperatomMCMC = round(($linefields [4] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgBadperatomMCMC);

		$avgBadperatomSCSC = round(($linefields [10] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgBadperatomSCSC);

		$avgBadperatomMCSC = round(($linefields [16] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgBadperatomMCSC);

		$avgBadperatomOTHER = round(($linefields [22] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgBadperatomOTHER);

		//Small overlaps 43 - 47

		$avgSMperatomSUM = round(($linefields [27] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgSMperatomSUM);

		$avgSMperatomMCMC = round(($linefields [3] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgSMperatomMCMC);

		$avgSMperatomSCSC = round(($linefields [9] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgSMperatomSCSC);

		$avgSMperatomMCSC = round(($linefields [15] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgSMperatomMCSC);

		$avgSMperatomOTHER = round(($linefields [21] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgSMperatomOTHER);

		//Wide Contact Values 48 - 52

		$avgWperatomSUM = round(($linefields [25] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgWperatomSUM);

		$avgWperatomMCMC = round(($linefields [1] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgWperatomMCMC);

		$avgWperatomSCSC = round(($linefields [7] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgWperatomSCSC);

		$avgWperatomMCSC = round(($linefields [13] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgWperatomMCSC);

		$avgWperatomOTHER = round(($linefields [19] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgWperatomOTHER);

		//Close Contact values 53 - 57

		$avgCperatomSUM = round(($linefields [2] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgCperatomSUM);

		$avgCperatomMCMC = round(($linefields [8] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgCperatomMCMC);

		$avgCperatomSCSC = round(($linefields [14] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgCperatomSCSC);

		$avgCperatomMCSC = round(($linefields [20] / ($linefields [31] * 0.001)), 1);
		fwrite ($out, ':'.$avgCperatomMCSC);

		$avgCperatomOTHER = round(($linefields [26] / ($linefields [31] * 0.001)) , 1);
		fwrite ($out, ':'.$avgCperatomOTHER);


		//Other contacts(hets, and waters)



		//other measures 58, 59

		$avgGOODperatomSUM = round( (( $linefields [25] + $linefields [26] + $linefields [29] ) / ($linefields [31] * 0.001)) , 1);
		fwrite ($out, ':'.$avgGOODperatomSUM);

		$percentGOODdotSUM = round(( (($linefields [25] + $linefields [26] + $linefields [29]) / $linefields [30] ) * 100 ), 1);
		fwrite ($out, ':'.$percentGOODdotSUM);

		fwrite($out, "\n"); // put back the newline we trim() 'd
	}

	fclose ($in);
	fclose ($out);

}


#}}}


#{{{ onelinepack - dots all in subgroups, multimodel support

// $pdbname - array of pdbs to be iterated and subsequently appended into the kin (all same file)
// $pdbproberesult - name of output file



function onelinepack($pdbname, $pdbproberesult)
{

	$first = true;
	 foreach($pdbname as $pdb)
	 {
		$pdbstatresults = pdbstat($pdb);

		// call out and figure out how many atoms are in the current model
		$numAtoms = $pdbstatresults['heavyatoms'] + $pdbstatresults['hydrogens'];

		// call out and get number of residues
		$numres = $pdbstatresults['residues'];

		//dots numbers
		$proberesult = shell_exec("phenix.probe -mc -self 'ALL' -ONELINE $pdb ");
		//$proberesult = shell_exec("probe -mc -self 'ALL' -ONELINE $pdb ");
		//$proberesult = shell_exec("probe -mc  -stdbonds -self 'ALL' -ONELINE $pdb ");

		// open up the oneline and add in number of atoms and number of residues

		$proberesult = trim($proberesult) . $numAtoms . ':' . $numres . "\n";

		$out = fopen ($pdbproberesult, 'a');

		fwrite ($out , $proberesult);

		fclose ($out);

	 }


} //end of onelinepack
#}}}
