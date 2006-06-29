<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Provides functions for calculating "effective resolution", a single-score
    validation number based on the correlation of multiple criteria with
    crystallographic resolution.
    
    Developed by IWD with help from Scott Schmidler (Duke Stats Dept).
    
    10 Mar 2006:  First-pass linear model to predict resolution based on three
    scores that should be available for *any* macromolecular model, including
    homology models, NMR structures, etc.
    
    # Scott Schmidler, SCOP 2000 -- has bias by real resolution
    MolProbity Effection Resolution (MER) =
        0.24907 * log(1 + clashscoreAllAtoms)
      + 0.16893 * log(1 + pctRotamersLessThan_1pct)
      + 0.18946  * log(1 + 100-pctRamachandranFavored)
      + 0.62224
    ("log" is the natural logarithm, not base 10)
    
    # Ian Davis, all-PDB -- fit to quartile points
    # Could add 1.0 to get a ~ "best possible" score.
    MolProbity Effection Resolution (MER) =
        0.4548 * log(1 + clashscoreAllAtoms)
      + 0.4205 * log(1 + pctRotamersLessThan_1pct)
      + 0.3186  * log(1 + 100-pctRamachandranFavored)
      - 0.5001
    ("log" is the natural logarithm, not base 10)
    
    When contrasted to the actual crystallographic resolution (AXR), this
    should provide a reasonable measure of the global quality of the structure.
*****************************************************************************/
//require_once(MP_BASE_DIR.'/lib/pdbstat.php');

/**
* clash, rota, rama are from the corresponding loadXXX() functions
*/
function getEffectiveResolution($clash, $rota, $rama)
{
    // Use of count(...) may occasionally lead to divide-by-zero if you have
    // non-protein PDB files.  I don't try to trap it b/c the PHP error msg
    // then becomes a useful diagnostic.  (Then $ro or $ra evaluates to unset,
    // and so acts as zero in arithmetic expressions, FWIW.)
    $cs = $clash['scoreAll'];
    $ro = 100.0 * count(findRotaOutliers($rota)) / count($rota);
    $ramaScore = array();
    foreach($rama as $r)
        $ramaScore[ $r['eval'] ] += 1;
    $ra = 100.0 - (100.0 * $ramaScore['Favored'] / count($rama));
    
    //echo " cs=$cs, ro=$ro, ra=$ra ";
    //return 0.4548*log(1+$cs) + 0.4205*log(1+$ro) + 0.3186*log(1+$ra) - 0.5001;
    
    // We are now working based on a "best feasible structure for your resolution" scale,
    // so all scores = 0 corresponds to a resolution of 0.5 A
    return 0.4548*log(1+$cs) + 0.4205*log(1+$ro) + 0.3186*log(1+$ra) + 0.5;
}

function getEffectiveResolutionPercentile($effRes, $actualRes = 0)
{
    $windowHalfwidth = 0.25;
    if($actualRes)
    {
        $minRes = min($actualRes, 3.50) - $windowHalfwidth;
        $maxRes = max($actualRes, 0.75) + $windowHalfwidth;
    }
    else
    {
        $minRes = 0;
        $maxRes = 99;
    }
    
    $nSamples = 0;
    $nWorse = 0;
    $in = fopen(MP_BASE_DIR.'/lib/eff_resol_lib.csv', 'rb');
    while(!feof($in))
    {
        
        $x = fgets($in, 1024);
        if($x{0} == '#') continue;
        
        $x = explode(',', $x);
        $ar = $x[1]+0;
        $er = $x[2]+0;
        
        if($minRes <= $ar && $ar <= $maxRes)
        {
            $nSamples++;
            if($er > $effRes) $nWorse++;
        }
    }
    fclose($in);
    
    return array(
        'minresol' => $minRes,
        'maxresol' => $maxRes,
        'n_samples' => $nSamples,
        'pct_rank' => round(100 * $nWorse / $nSamples),
    );
}
?>
