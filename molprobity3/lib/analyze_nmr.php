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

#{{{ probestats - parses probe unformatted and does some counting
############################################################################
/**
* This function will take probe unformatted output from individual models, and explode
then count different parts of it.  These numbers will be drawn upon by dotstats.php to
derive some statistical measures for packing assessment of models The probe unformated looks like this
name:pat:type:srcAtom:targAtom:min-gap:gap:spX:spY:spZ:spikeLen:score:stype:ttype:x:y:z:sBval:tBval
mc-mc dots:1->1:so:    7 PRO  C   :    9 SER  H   :-0.245:-0.096:-9.656:3.650:-11.464:0.048:-0.0300:C:N:-9.6
45:3.670:-11.505:0.00:0.00
*/
probestats($infile) 
{
	
	// mc unformated probe output command
	
	exec("probe -Unformated -stdbonds -quiet -noticks -name 'mc-mc dots' -mc -self 'mc alta' $pdb > 'mcprobeunformated'");
	
	//sc unformated probe output command
	// exec("probe -Unformated -stdbonds -quiet -noticks -name 'sc-x dots' -self 'alta' $pdb >> 'scprobeunformated'");
	
	
	$infile = 'mcprobeunformated';
	
	$h = fopen($infile, 'r');
	while(! feof($h))
	{
		//read the line, explode it into an array
		$line = fgets($h);
		$fields = explode(':',$line);
		if $fields[] = 
		{
		
		
		}
		
	
		
		//start counting different parts
		
	}



}
#}}}########################################################################

