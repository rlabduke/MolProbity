<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Allows user to make some choices about how to run the analysis.
    
INPUTS (via Get or Post):
    model           ID code for model to process

OUTPUTS (via Post):
    model           ID code for model to process
    makeFlipkin     true if the user wants a Flipkin made

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    #mpSessReadOnly();

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

# MAIN - the beginning of execution for this page
############################################################################
// Start the page: produces <HTML>, <HEAD>, <BODY> tags
echo mpPageHeader("Add H with Reduce -build", "improve");


$model = $_REQUEST['model'];
############################################################################
?>

<p>Reduce is a program for adding hydrogens to a Protein DataBank (PDB) molecular structure file.
Hydrogens are added in standardized geometry with combinatorial optimization of the orientations
of OH, SH, NH<sub>3</sub><sup>+</sup>, Met methyls, Asn and Gln sidechain amides, and His rings.
Both proteins and nucleic acids can be processed.
HET groups can also be processed as long as the atom connectivity is provided;
a slightly modified version of the connectivity table published by the PDB - reduce_het_dict.txt - is included.
The program is described in
<a href="http://www.ncbi.nlm.nih.gov:80/entrez/query.fcgi?cmd=Retrieve&amp;db=PubMed&amp;list_uids=9917408&amp;dopt=Abstract" target="_blank">Word, et al. (1999)</a>
"Asparagine and glutamine: using hydrogen atom contacts in the choice of sidechain amide orientation" J. Mol. Biol. <b>285</b>, 1733-1745.
</p>

<p>A stand-alone version of Reduce, along with the source code, can be obtained for free from
<a href="http://kinemage.biochem.duke.edu/software/reduce.php" target="_blank">kinemage.biochem.duke.edu</a>.
</p>

<form method='post' action='improve_reduce_launch.php'>
<?php
    echo postSessionID();
    echo "<input type='hidden' name='model' value='$model'>\n";
?>
<p><input type='checkbox' name='makeFlipkin' value='1' checked>
    Make Flipkin kinemages illustrating any Asn, Gln, and His flips
<p><input type='submit' name='cmd' value='Start adding H'>
</form>

<?php echo mpPageFooter(); ?>
