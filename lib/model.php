<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Provides functions for manipulating models.
*****************************************************************************/

#{{{ preparePDB - cleans up a user PDB before use (adds H, fixes names, etc)
############################################################################
/**
* This function reworks a PDB to standardize it before MolProbity uses it.
* The following actions are taken, in order:
*   Linefeeds are converted to Unix (\n)
*   Old USER MOD records are removed (only the detailed ones from Reduce)
*   CNS atom names are auto-detected
*   SEG IDs without chain IDs are auto-detected
*   CNS atom names and SEG IDs are repaired by pdbcns
*   Missing hydrogens are added by Reduce; no flips are made
*
* $inpath       the full filename for the PDB file to be processed
* $outpath      the full filename for the destination PDB. Will be overwritten.
* $isCNS        true if the file is known to use CNS atom naming conventions
*               false if we should auto-detect CNS headers and/or atom names.
* $ignoreSegID  true if we should never use segIDs to create new chain IDs.
*               false if we should convert automatically, as needed.
*/
function preparePDB($inpath, $outpath, $isCNS = false, $ignoreSegID = false)
{
    // If no session is started, this should eval to NULL or '',
    // which will specify the system tmp directory.
    $tmp1   = tempnam($_SESSION['dataDir'], "tmp_pdb_");
    $tmp2   = tempnam($_SESSION['dataDir'], "tmp_pdb_");
    
    // Process file - this is the part that matters
    // Convert linefeeds to UNIX standard (\n):
    exec("scrublines < $inpath > $tmp1");
    // Remove stale USER MOD records that will confuse us later
    // We won't know which flips are old and which are new!
    exec("awk '\$0 !~ /^USER  MOD (Set|Single|Fix|Limit)/' $tmp1 > $tmp2");
    // Get PDB statistics so we know if we have CNS atom names
    $stats = pdbstat($tmp2);
    // Try to determine if we need to make chain IDs from segment IDs
    $segToChainMapping = trim(`cksegid.pl $tmp2`);
    // Old Reduce used segID for 'new ' flag for H's.
    if($ignoreSegID) $segToChainMapping = "";
    if($segToChainMapping == "")
    {
        // Don't need to do anything
    }
    elseif(preg_match("/ OK\$/", $segToChainMapping))
    {
        $parts = preg_split("/\\s+/", $segToChainMapping);
        $segToChainMapping = $parts[0];
    }
    else
    {
        echo "*** Unable to automagically correct XPLOR/CNS segIDs";
        $segToChainMapping = "";
    }
    // Run PDBCNS if we need to (tmp2 is most recent file):
    // - if we have CNS-style atom names
    // - if we have CNS-style records in the header (3 or more)
    // - if the user told us these were CNS coordinates
    if(/*has CNS atom names or*/ $stats['fromcns'] >= 3 || $isCNS)
    {
        if($segToChainMapping == "")
        {
            exec("pdbcns -c $tmp2 > $tmp1");
        }
        else
        {
            exec("pdbcns -cm '$segToChainMapping' $tmp2 > $tmp1");
            $segToChainMapping = "";
        }
    }
    else // swap tmp1 and tmp2; now tmp1 holds most recent file
    {
        $t = $tmp1;
        $tmp1 = $tmp2;
        $tmp2 = $t;
    }
    // Add missing H's without trying to optimize or fix anything
    $segmap = ($segToChainMapping == "" ? "" : "-segidmap '$segToChainMapping'");
    exec("reduce -quiet -limit".MP_REDUCE_LIMIT." $segmap -keep -noadjust -his -allalt $tmp1 > $tmp2");
    // Copy to output pdb
    copy($tmp2, $outpath);

    // Clean up temp files
    unlink($tmp1);
    unlink($tmp2);
}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################
?>
