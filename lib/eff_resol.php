<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Provides functions for calculating "effective resolution", a single-score
    validation number based on the correlation of multiple criteria with
    crystallographic resolution.
    
    Developed by IWD with help from Scott Schmidler (Duke Stats Dept).
    
    10 Mar 2006:  First-pass linear model to predict resolution based on three
    scores that should be available for *any* macromolecular model, including
    homology models, NMR structures, etc.
    
    MolProbity Effection Resolution (MER) =
        0.24907 * log(1 + clashscoreAllAtoms)
      + 0.16893 * log(1 + pctRotamersLessThan_1pct)
      + 0.18946  * log(1 + 100-pctRamachandranFavored)
      + 0.62224
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
    $cs = $clash['scoreAll'];
    $ro = 100.0 * count(findRotaOutliers($rota)) / count($rota);
    $ramaScore = array();
    foreach($rama as $r)
        $ramaScore[ $r['eval'] ] += 1;
    $ra = 100.0 - (100.0 * $ramaScore['Favored'] / count($rama));
    
    echo " cs=$cs, ro=$ro, ra=$ra ";
    
    return 0.24907*log(1+$cs) + 0.16893*log(1+$ro) + 0.18946*log(1+$ra) + 0.62224;
}
?>
