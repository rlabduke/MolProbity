#
# This is an AWK program that compares the clashscore
# of a given structure to the scores of other structures
# at similar resolution.
#
# It returns the following four fields
#   min_res:max_res:n_samples:percentile_rank:pct_rankB<40
# where min_res and max_res define the window of resolutions
# considered to be "comparable", n_samples is the number
# of other structures in that resolution range,
# and percentile_rank is the percentage of structures that are
# worse than or equal to this structure (from 0 to 100, no % symbol).
# min_res may be 0 and max_res may be "9999" if this
# structure is out of range relative to the database.
#
# It expects the variables cs (clashscore of query structure) and
# res (resolution in Angstroms of query structure) to be defined
# on the command line with the -v switch. It also expects to be
# supplied with "clashlist.db", a tab file produced from the Top500
# and from MedRes.
#
#   awk -v res=<resolution> -v cs=<clashscore> -f cs-rank.awk clashlist.db
#
# 19 Feb 2003, IWD
# 29 Oct 2004, IWD: updated to use database from SCOP 2000 dataset.
#
BEGIN {
    windowHalfWidth = 0.25
    
    nSamples = 0
    nWorse   = 0
    nWorse40 = 0
    if(res + 0 == 0) {
        # Resol. not defined -- e.g. NMR structures. Compare to full DB.
        minres = 0
        maxres = 9999
    } else if(res - windowHalfWidth < 0.65) {
        minres = 0
        maxres = max(0.65, res) + windowHalfWidth
    } else if(res + windowHalfWidth > 3.25) {
        minres = min(3.25, res) - windowHalfWidth
        maxres = 9999
    } else {
        minres = res - windowHalfWidth
        maxres = res + windowHalfWidth
    }
    
    FS  = ":"
    OFS = ":"
}
$0 !~ /^\#/ && minres <= $2 && $2 <= maxres {
    nSamples++
    if($3 >= cs) { nWorse++ }
    if($4 >= cs) { nWorse40++ }
}
END {
    pctRank = int(100.0*nWorse / nSamples)
    pctRank40 = int(100.0*nWorse40 / nSamples)
    print minres, maxres, nSamples, pctRank, pctRank40
}
function max(a, b) {
    if(b > a) return b;
    else return a;
}
function min(a, b) {
    if(b < a) return b;
    else return a;
}

