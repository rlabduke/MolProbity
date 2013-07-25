#!/usr/bin/python
# (jEdit options) :folding=explicit:collapseFolds=1:
from math import log
import sys, os, getopt, re, pprint
from optparse import OptionParser

#{{{ parse_cmdline
#parse the command line--------------------------------------------------------------------------
def parse_cmdline():
  parser = OptionParser()
  parser.add_option("-q", "--quiet", action="store_true", dest="quiet",
    help="quiet mode")
  opts, args = parser.parse_args()
  if len(args) < 11:
    sys.stderr.write("\n**ERROR: Must have 11 arguments!\n")
    sys.exit(help())
  return opts, args
  #try:
  #  opts, args = getopt.getopt(sys.argv[1:], 'h',['help'])
  #except getopt.GetoptError:
  #  help()
  #  sys.exit()
  #for o, a in opts:
  #  if o in ("-h", "--help"):
  #    help()
  #    sys.exit()
  #  if o in ("-q", "--quiet"):
  #    quiet = True
  #if len(args) < 2:
  #  sys.stderr.write("\n**ERROR: User must specify output directory and input PDB file\n")
  #  sys.exit(help())
  #return opts, args;
  #else:
  #  outdir = args[0]
  #  if (os.path.isdir(outdir)):
  #    return outdir, args[1:]
  #  else:
  #    sys.stderr.write("\n**ERROR: First argument must be a directory!\n")
  #    sys.exit(help())
#------------------------------------------------------------------------------------------------
#}}}

#{{{ help
def help():
  print """
This script parses the output files from the various programs in MP to duplicate
a set of the oneline analysis.  This script reimplements a significant portion 
of analysis.php.  

USAGE:   python molparser.py [MP output files]
  
  [MP output files] In order: pdbname (string)
                              model number
                              clashlist output file
                              cbetadev output file
                              rota output file
                              rama output file
                              protein bond geometry output file
                              rna bond geometry output file
                              base ppperp output file
                              suitname output file

FLAGS:
  -h     Print this help message
"""
#}}}
   
#{{{ debug
def debug(arg):
  print os.path.dirname(os.path.realpath(arg))
  #in_file = os.path.basename(arg)
  #in_file, ext = os.path.splitext(in_file)
  #in_file_clean = in_file+"_clean.pdb"
  #outfile = os.path.join(os.path.realpath(outdir), in_file_clean)
  #out=open(outfile, 'wr')
  #pprint.pprint(loadClashlist(arg[0]))
  #pprint.pprint(findClashOutliers(loadClashlist(arg[0])))
  #pprint.pprint(loadCbetaDev(arg[1]))
  #pprint.pprint(findCbetaOutliers(loadCbetaDev(arg[1])))
  #pprint.pprint(loadRamachandran(arg[3]))
  #pprint.pprint(findRamaOutliers(loadRamachandran(arg[3])))
  #pprint.pprint(loadRotamer(arg[2]))
  #print calcMPscore(loadClashlist(arg[0]), loadRotamer(arg[2]), loadRamachandran(arg[3]))
  #pprint.pprint(loadBasePhosPerp(arg[6]))
  #pprint.pprint(findBasePhosPerpOutliers(loadBasePhosPerp(arg[6])))
  #pprint.pprint(loadSuitenameReport(arg[7]))
  #pprint.pprint(findSuitenameOutliers(loadSuitenameReport(arg[7])))
  #pprint.pprint(loadBondGeometryReport(arg[5], "rna"))
  #pprint.pprint(findBondGeomOutliers(loadBondGeometryReport(arg[5], "rna")))
  #pprint.pprint(findAngleGeomOutliers(loadBondGeometryReport(arg[5], "rna")))
  
  #out.close()
#}}}

#{{{ loadClashlist
#
# Returns a dict with the following keys:
#   scoreAll        the overall clashscore
#   scoreBlt40      the score for atoms with B < 40
#   clashes         a dict with 'cnnnnittt' residue names as keys
#                   (see loadCbetaDev() for explanation of naming)
#                   and maximum clashes as values (positive Angstroms).
#                   NB: only clashes >= 0.40A are currently listed.
#   clashes-with    same keys as "clashes", values are:
#                       'srcatom' => atom from this residue making bigest clash
#                       'dstatom' => atom it clashes with
#                       'dstcnit' => chain/residue it clashes with
# 
def loadClashlist(datafile):
  datafile = open(datafile).readlines()
  sumArray = datafile[-2:]
  scores = sumArray[0].split(":")
  ret = {}
  if (sumArray[0].startswith("#sum2")):
    ret["scoreAll"] = float(scores[2].split()[0]);
    ret["scoreBlt40"] = float(scores[3].split()[0]);
  
  clashes = {}
  clasheswith = {}
  for datam in datafile:
    if (datam.startswith(":")):
      line = datam.split(":")
      res1 = line[2][0:10]
      atm1 = line[2][11:15]
      res2 = line[3][0:10]
      atm2 = line[3][11:15]
      dist = abs(float(line[4].strip()))
      if(res1 not in clashes) or (clashes[res1] < dist):
        clashes[res1] = dist
        clasheswith[res1] = {'srcatom': atm1, 'dstatom': atm2, 'dstcnit': res2}    
      if(res2 not in clashes) or (clashes[res2] < dist):
        clashes[res2] = dist
        clasheswith[res2] = {'srcatom': atm2, 'dstatom': atm1, 'dstcnit': res1}
  ret["clashes"] = clashes;
  ret["clashes-with"] = clasheswith;
      
  return ret;
#}}}

#{{{ loadCbetaDev - loads Prekin cbdevdump output into an array
############################################################################
#
# Returns an array of entries, one per residue. Their keys:
#   altConf         alternate conformer flag, or ' ' for none
#   resName         a formatted name for the residue: 'ccnnnnittt'
#                       c: Chain ID, space for none
#                       n: sequence number, right justified, space padded
#                       i: insertion code, space for none
#                       t: residue type (ALA, LYS, etc.), all caps,
#                          left justified, space padded
#   resType         3-letter residue code (e.g. ALA)
#   chainID         2-letter chain ID or '  '
#   resNum          residue number
#   insCode         insertion code or ' '
#   dev             deviation distance, in Angstroms
#   dihedral        N-CA-idealCB-actualCB angle, in degrees
#   occ             occupancy, between 0 and 1
#
def loadCbetaDev(datafile):
  data = open(datafile)
  ret = []
  for line in data:
    line = line.strip()
    if(line != "" and not line.startswith('pdb:alt:res:')):
      splitline = line.split(':')
      entry = {}
      entry['altConf']   = splitline[1].upper()
      entry['resType']   = splitline[2].upper()
      entry['chainID']   = splitline[3].upper()
      entry['resNum']    = int(splitline[4][0:-1].strip())
      entry['insCode']   = splitline[4][-1:]
      entry['dev']       = float(splitline[5])
      entry['dihedral']  = float(splitline[6])
      entry['occ']       = float(splitline[7])
      if(entry['chainID'] == ""):
        entry['chainID'] = "  "
      entry['resName']   = recomposeResName(entry['chainID'], entry['resNum'], entry['insCode'], entry['resType'])
      ret.append(entry);
  return ret;
#}}}########################################################################

#{{{ loadRotamer - loads Rotamer output into an array
############################################################################
#
# Returns an array of entries keyed on CNIT name, one per residue.
# Each entry is an array with these keys:
#   resName         a formatted name for the residue: 'cnnnnittt'
#                       c: Chain ID, space for none
#                       n: sequence number, right justified, space padded
#                       i: insertion code, space for none
#                       t: residue type (ALA, LYS, etc.), all caps,
#                          left justified, space padded
#   resType         3-letter residue code (e.g. ALA)
#   chainID         1-letter chain ID or ' '
#   resNum          residue number
#   insCode         insertion code or ' '
#   scorePct        the percentage score from 0 (bad) to 100 (good)
#   chi1            the chi-1 angle
#   chi2            the chi-2 angle ("" for none)
#   chi3            the chi-3 angle ("" for none)
#   chi4            the chi-4 angle ("" for none)
#   rotamer         the rotamer name from the Penultimate Rotamer Library
#
def loadRotamer(datafile):
  data = file(datafile).readlines()[1:] # drop first line
  ret = {}
  for line in data:
    line = line.rstrip().split(":")
    cnit = line[0]
    decomp = decomposeResName(cnit)
    ret[cnit] = {
      'resName'  : cnit,
      'resType'  : decomp['resType'],
      'chainID'  : decomp['chainID'],
      'resNum'   : decomp['resNum'],
      'insCode'  : decomp['insCode'],
      'scorePct' : float(line[1]),
      'chi1'     : line[2],
      'chi2'     : line[3],
      'chi3'     : line[4],
      'chi4'     : line[5],
      'rotamer'  : line[6]
    }
    #pprint.pprint(ret)
    # This converts numbers to numbers and leaves "" as it is.
    if(ret[cnit]['chi1'] != ''): float(ret[cnit]['chi1']);
    if(ret[cnit]['chi2'] != ''): float(ret[cnit]['chi2']);
    if(ret[cnit]['chi3'] != ''): float(ret[cnit]['chi3']);
    if(ret[cnit]['chi4'] != ''): float(ret[cnit]['chi4']);
    
  return ret;

#}}}########################################################################

#{{{ loadRamachandran - loads Ramachandran output into an array
############################################################################
#
# Returns an array of entries, one per residue. Their keys:
#   resName         a formatted name for the residue: 'cnnnnittt'
#                       c: Chain ID, space for none
#                       n: sequence number, right justified, space padded
#                       i: insertion code, space for none
#                       t: residue type (ALA, LYS, etc.), all caps,
#                          left justified, space padded
#   resType         3-letter residue code (e.g. ALA)
#   chainID         1-letter chain ID or ' '
#   resNum          residue number
#   insCode         insertion code or ' '
#   scorePct        the percentage score from 0 (bad) to 100 (good)
#   phi             the phi angle
#   psi             the psi angle
#   eval            "Favored", "Allowed", or "OUTLIER"
#   type            "General case", "Glycine", "Proline", or "Pre-proline"
#
def loadRamachandran(datafile):
  data = file(datafile).readlines()[1:] # drop first line
  ret = {};
  for line in data:
    splitline = line.split(":") 
    cnit = splitline[0]
    #print cnit
    decomp = decomposeResName(cnit)
    #print decomposeResName("CC9999ITTT")
    ret[cnit] = {
      'resName' : cnit,
      'resType' : decomp['resType'],
      'chainID' : decomp['chainID'],
      'resNum'  : decomp['resNum'],
      'insCode' : decomp['insCode'],
      'scorePct': float(splitline[1]),
      'phi'     : float(splitline[2]),
      'psi'     : float(splitline[3]),
      'eval'    : splitline[4],
      'type'    : splitline[5]
    }
  return ret
#}}}########################################################################

#{{{ loadBasePhosPerp - load base-phos perp data into an array
############################################################################
#
# Returns an array of entries, one per residue. Their keys:
#   resName         a formatted name for the residue: 'cnnnnittt'
#                       c: Chain ID, space for none
#                       n: sequence number, right justified, space padded
#                       i: insertion code, space for none
#                       t: residue type (ALA, LYS, etc.), all caps,
#                          left justified, space padded
#   resType         3-letter residue code (e.g. ALA)
#   chainID         1-letter chain ID or ' '
#   resNum          residue number
#   insCode         insertion code or ' '
#   5Pdist          distance from the base to the 5' phosphate (?)
#   3Pdist          distance from the base to the 3' phosphate (?)
#   delta           delta angle of the sugar ring
#   deltaOut        true if the sugar pucker (delta) doesn't match dist to 3' P
#   epsilon         epsilon angle of the backbone
#   epsilonOut      true if the epsilon angle is out of the allowed range
#   outlier         (deltaOut || epsilonOut)
#
def loadBasePhosPerp(datafile):
  data = open(datafile)
  ret = []
  for line in data:
    line = line.strip();
    if(line != "" and not line.startswith(':pdb:res:')):
      splitline = line.split(':')
      deltaOut = (splitline[8].strip() != "")
      epsilonOut = (splitline[10].strip() != "")
      entry = {
        'resType'   : splitline[2][1:-1].upper(),
        'chainID'   : splitline[3][1:-1].upper(),
        'resNum'    : int(splitline[4][0:-2].strip()),
        'insCode'   : splitline[4][-2:-1].upper(),
        '5Pdist'    : float(splitline[5]),
        '3Pdist'    : float(splitline[6]),
        'delta'     : float(splitline[7]),
        'deltaOut'  : deltaOut,
        'epsilon'   : float(splitline[9]),
        'epsilonOut': epsilonOut,
        'outlier'   : (deltaOut or epsilonOut)
      }
      entry['resName'] = recomposeResName(entry['chainID'], entry['resNum'], entry['insCode'], entry['resType'])
      ret.append(entry)
  return ret
#}}}########################################################################

#{{{ loadSuitenameReport - loads Suitename's -report output into an array
############################################################################
#
# Returns an array of entries keyed on CNIT name, one per residue.
# Each entry is an array with these keys:
#   resName         a formatted name for the residue: 'cnnnnittt'
#                       c: Chain ID, space for none
#                       n: sequence number, right justified, space padded
#                       i: insertion code, space for none
#                       t: residue type (ALA, LYS, etc.), all caps,
#                          left justified, space padded
#   conformer       two letter code (mixed case -- '1L' is legal) or "__"
#   suiteness       real number, 0 - 1
#   bin             "inc " (incomplete), "trig" (triaged), or something like "23 p"
#   triage          contains details about the reason for triage  rmi 070827
#   isOutlier       true if conformer = !!
#
def loadSuitenameReport(datafile):
# 404d.pdb:1:A:   1: :  G inc  __ 0.000
# 404d.pdb:1:A:   2: :  A 33 p 1a 0.544
# 404d.pdb:1:A:   3: :  A trig !! 0.000 alpha
# 404d.pdb:1:A:   4: :  G 33 t 1c 0.135
# 404d.pdb:1:A:   5: :  A 33 p 1a 0.579
# 404d.pdb:1:A:   6: :  G 33 t 1c 0.180
# 404d.pdb:1:A:   7: :  A 33 p 1a 0.458
# 404d.pdb:1:A:   8: :  A 33 t !! 0.000 7D dist 1c
# 404d.pdb:1:A:   9: :  G 33 p 1a 0.294
# 404d.pdb:1:A:  10: :  C 33 p 1a 0.739
  data = open(datafile)
  #$ret = array(); # needs to return null if no data!
  ret = {}
  for line in data:
    if(line.startswith(" all general case widths")): 
      break
    splitline = line.rstrip().split(':')
    if (len(splitline)==5): # missing colon due to name length issue in suitename
      linestart = [splitline[0][0:32], splitline[0][32:]]
      splitline = linestart + splitline[1:]
      #print splitline
    if (len(splitline) > 1):
      cnit = splitline[2]+splitline[3]+splitline[4]+splitline[5][0:3]
      #    //$decomp = decomposeResName($cnit);
      conf = splitline[5][9:11]
      isout = (conf == "!!")
      ret[cnit] = {
          'resName'   : cnit,
          #'resType'   : $decomp['resType'],
          #'chainID'   : $decomp['chainID'],
          #'resNum'    : $decomp['resNum'],
          #'insCode'   : $decomp['insCode'],
          'conformer' : conf,
          'suiteness' : float(splitline[5][12:17]),
          'bin'       : splitline[5][4:8],
          'triage'    : splitline[5][18:],
          'isOutlier' : isout
      }
  return ret
#}}}########################################################################

#{{{ loadBondGeometryReport - loads Dangle's geometry statistics
############################################################################
#**
# Combines loadValidationAngleReport and loadValidationBondReport into one
# Returns an array of entries keyed on CNIT name, one per residue.
# Each entry is an array with these keys:
#   resName         a formatted name for the residue: 'cnnnnittt'
#                       c: Chain ID, space for none
#                       n: sequence number, right justified, space padded
#                       i: insertion code, space for none
#                       t: residue type (ALA, LYS, etc.), all caps,
#                          left justified, space padded
#   worstanglemeasure      worst angle (A-B-C)
#   worstanglevalue        value of the worst angle measurement
#   worstanglesigma        deviation from ideality
#   worstbondmeasure       worst bond (A--B)
#   worstbondvalue         value of the worst bond measurement
#   worstbondsigma         deviation from ideality
#   angleCount             number of angles analyzed
#   bondCount              number of bonds analyzed
#   angleoutCount          number of angles with >4sigma
#   bondoutCount           number of bonds with >4sigma
#   isangleOutlier         does residue have at least one angle outlier?
#   isbondOutlier          does residue have at least one bond outlier?
#/
def loadBondGeometryReport(datafile, moltype):
  #1TC6.pdb:1: A: 250: :VAL:N-CA-C:99.511:-4.255
  data = open(datafile)
  #/$ret = array(); // needs to return null if no data!
  ret = {}
  for line in data:
    if(line.startswith("#")):
      continue
    splitline = line.strip().split(':')
    cnit = splitline[2]+splitline[3]+splitline[4]+splitline[5]
    #$decomp = decomposeResName($cnit);
    measure = splitline[6];
    value = float(splitline[7]);
    sigma = float(splitline[8]);
    if not cnit in ret:
      ret[cnit] = {
        'resName' : cnit,
        'type'    : moltype
      }
    if "--" in measure:
      if not "bondCount" in ret[cnit]:
        ret[cnit]['worstbondmeasure'] = measure
        ret[cnit]['worstbondvalue']   = value
        ret[cnit]['worstbondsigma']   = sigma
        ret[cnit]['bondCount']        = 1
        ret[cnit]['bondoutCount']     = 0
        if (abs(sigma) > 4):
          ret[cnit]['isbondOutlier'] = True
          ret[cnit]['bondoutCount'] = 1
        else:
          ret[cnit]['isbondOutlier'] = False
      else:
        old_sigma_bond = ret[cnit]['worstbondsigma']
        if (abs(sigma) > abs(old_sigma_bond)):
          ret[cnit]['worstbondmeasure'] = measure
          ret[cnit]['worstbondvalue'] = value
          ret[cnit]['worstbondsigma'] = sigma
        
        if (abs(sigma) > 4):
          ret[cnit]['bondoutCount'] = ret[cnit]['bondoutCount'] + 1
          ret[cnit]['isbondOutlier'] = True
          
        ret[cnit]['bondCount'] = ret[cnit]['bondCount'] + 1
    elif re.search("-.+-", measure):
      if not "angleCount" in ret[cnit]:
        ret[cnit]['worstanglemeasure'] = measure
        ret[cnit]['worstanglevalue']   = value
        ret[cnit]['worstanglesigma']   = sigma
        ret[cnit]['angleCount']        = 1
        ret[cnit]['angleoutCount']     = 0
        if (abs(sigma) > 4):
          ret[cnit]['isangleOutlier'] = True
          ret[cnit]['angleoutCount'] = 1
        else:
          ret[cnit]['isangleOutlier'] = False
      else:
        old_sigma_angle = ret[cnit]['worstanglesigma']
        if (abs(sigma) > abs(old_sigma_angle)):
          ret[cnit]['worstanglemeasure'] = measure
          ret[cnit]['worstanglevalue'] = value
          ret[cnit]['worstanglesigma'] = sigma
        
        if (abs(sigma) > 4):
          ret[cnit]['angleoutCount'] = ret[cnit]['angleoutCount'] + 1
          ret[cnit]['isangleOutlier'] = True
          
        ret[cnit]['angleCount'] = ret[cnit]['angleCount'] + 1
  return ret
#}}}########################################################################

#{{{ findClashOutliers - evaluates residues for bad score
############################################################################
#**
# Returns an array of 9-char residue names for residues that
# fall outside the allowed boundaries for this criteria.
# Inputs are from appropriate loadXXX() function above.
#/
def findClashOutliers(clash):
  worst = {}
  if(len(clash) > 0):
    for res, dist in clash['clashes'].iteritems():
      if(dist >= 0.4): 
        worst[res] = dist
  #ksort($worst); // Put the residues into a sensible order
  return worst;
#}}}########################################################################

#{{{ findCbetaOutliers - evaluates residues for bad score
############################################################################
#**
# Returns an array of 9-char residue names for residues that
# fall outside the allowed boundaries for this criteria.
# Inputs are from appropriate loadXXX() function above.
#/
def findCbetaOutliers(cbdev):
  worst = {}
  if(len(cbdev) > 0):
    for res in cbdev:
      if(res['dev'] >= 0.25):
        worst[res['resName']] = res['dev']
  #ksort($worst); // Put the residues into a sensible order
  return worst
#}}}########################################################################

#{{{ findRotaOutliers - evaluates residues for bad score
############################################################################
#**
# Returns an array of 9-char residue names for residues that
# fall outside the allowed boundaries for this criteria.
# Inputs are from appropriate loadXXX() function above.
#/
def findRotaOutliers(rota):
  worst = {}
  if(len(rota) > 0):
    for res, data in rota.iteritems():
      if(data['rotamer'] == 'OUTLIER'):
        worst[data['resName']] = data['scorePct']
    #ksort(worst);
  return worst;
#}}}########################################################################

#{{{ findRamaOutliers - evaluates residues for bad score
############################################################################
#**
# Returns an array of 9-char residue names for residues that
# fall outside the allowed boundaries for this criteria.
# Inputs are from appropriate loadXXX() function above.
#/
def findRamaOutliers(rama):
  worst = {}
  if(len(rama) > 0):
    for res, data in rama.iteritems():
      if(data['eval'] == 'OUTLIER'):
        worst[data['resName']] = data['eval']
  #ksort($worst); // Put the residues into a sensible order
  return worst
#}}}########################################################################

#{{{ findSuitenameOutliers - evaluates residues for bad score
############################################################################
#**
# Returns an array of 9-char residue names for residues that
# fall outside the allowed boundaries for this criteria.
# Inputs are from appropriate loadXXX() function above.
#/
def findSuitenameOutliers(suites):
  worst = {}
  if(len(suites) > 0):
    for res, data in suites.iteritems():
      if(data['isOutlier']):
        worst[data['resName']] = data['suiteness']
  #ksort($worst); // Put the residues into a sensible order
  return worst
#}}}########################################################################

#{{{ findBasePhosPerpOutliers - evaluates residues for bad score
############################################################################
#**
# Returns an array of 9-char residue names for residues that
# fall outside the allowed boundaries for this criteria.
# Inputs are from appropriate loadXXX() function above.
#/
def findBasePhosPerpOutliers(pperps):
  worst = {}
  if(len(pperps)>0):
    for data in pperps:
      if(data['outlier']):
        worst[data['resName']] = delta_or_epsilon(data)
  #ksort($worst); // Put the residues into a sensible order
  return worst
  
def delta_or_epsilon(data):
  if data['deltaOut'] and data['epsilonOut']: return 'both'
  if data['epsilonOut']: return 'epsilon'
  if data['deltaOut']: return 'delta'
  return 'none'
#}}}########################################################################

#{{{ findGeomOutliers - evaluates residues for bad score
############################################################################
#**
# Returns an array of 9-char residue names for residues that
# fall outside the allowed boundaries for this criteria.
# Inputs are from appropriate loadXXX() function above.
#/
def findGeomOutliers(geom, b_or_angle):
  worst = {}
  if(len(geom) > 0):
    for res, data in geom.iteritems():
      #print data
      if b_or_angle == 'bond' or b_or_angle =='angle':
        if 'is'+b_or_angle+'Outlier' in data: # catches case where a residue doesnt have any bonds and/or angles, like in some hets
          if(data['is'+b_or_angle+'Outlier']):
            worst[data['resName']] = data[b_or_angle+'outCount']
        else:
          sys.stderr.write("Odd geometry data detected; possibly het residues or missing residues\n")
          
  #ksort($worst); // Put the residues into a sensible order
  return worst
  
def findBondGeomOutliers(geom):
  return findGeomOutliers(geom, 'bond')
  
def findAngleGeomOutliers(geom):
  return findGeomOutliers(geom, 'angle')
#}}}########################################################################

#{{{ calcMPscore
"""*****************************************************************************
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

    # Ian Davis, all-PDB "clip12" -- fit to quartile points.
    # Does not reward structures for less than 1% rotamer outliers
    # or more than 98% Ramachandran favored.
    # Use 0.5 as the intercept to get a ~ "best possible" score.
    MolProbity Effection Resolution (MER) =
        0.42574 * ln(1 + clashscoreAllAtoms)
      + 0.32996 * ln(1 + max(0, pctRotamersLessThan_1pct - 1))
      + 0.24979 * ln(1 + max(0, 100-pctRamachandranFavored - 2))
      + 0.08755

    When contrasted to the actual crystallographic resolution (AXR), this
    should provide a reasonable measure of the global quality of the structure.
*****************************************************************************"""
def calcMPscore(clash, rota, rama):
  if (len(rota) == 0) or (len(rama) == 0):
    return -1
  else:
    cs = clash['scoreAll']
    if (cs == -1):
      return -1
    ro = 100.0 * len(findRotaOutliers(rota)) / len(rota)
    ramaScore = {};
    for r in rama.itervalues():
      evalu = r['eval']
      if not evalu in ramaScore:
        ramaScore[evalu] = 0
        #sys.stderr.write("Odd rama data detected; maybe het residues or missing residues\n")
      ramaScore[evalu] = ramaScore[evalu]+1
    if 'Favored' not in ramaScore:
      ra = 0
    else:
      ra = 100.0 - (100.0 * ramaScore['Favored'] / len(rama))

    return 0.42574*log(1+cs) + 0.32996*log(1+max(0,ro-1)) + 0.24979*log(1+max(0,ra-2)) + 0.5;

#}}}

#{{{ decomposeResName - breaks a 9-character packed name into pieces
############################################################################
#
# Decomposes this:
#   resName         a formatted name for the residue: 'ccnnnnittt'
#                       c: Chain ID, space for none
#                       n: sequence number, right justified, space padded
#                       i: insertion code, space for none
#                       t: residue type (ALA, LYS, etc.), all caps,
#                          left justified, space padded
#
# Into this (as an array):
#   resType         3-letter residue code (e.g. ALA)
#   chainID         2-letter chain ID or '  '
#   resNum          residue number
#   insCode         insertion code or ' '
#
def decomposeResName(name):
  return {
    'resType' : name[-3:],
    'chainID' : name[0:2],
    'resNum'  : int((name[2:6]).strip()),
    'insCode' : name[6:7]
  }
#}}}########################################################################

#{{{ recomposeResName - puts the pieces back together into a single name
# takes a chain id, residue number (int), insertion code, and residue type
# returns: 'ccnnnnittt'
def recomposeResName(chain, resnum, inscode, restype):
  return ''.join([chain.rjust(2), repr(resnum).rjust(4), inscode, restype.ljust(3)])
#}}}

#{{{ oneline_analysis
def oneline_analysis(files, quiet):
  if (not quiet):
    print "#pdbFileName:model:clashscore:clashscoreB<40:cbeta>0.25:numCbeta:rota<1%:numRota:ramaOutlier:ramaAllowed:ramaFavored:numRama:numbadbonds:numbonds:pct_badbonds:pct_resbadbonds:numbadangles:numangles:pct_badangles:pct_resbadangles:MolProbityScore:numPperpOutliers:numPperp:numSuiteOutliers:numSuites"
  
  out = files[0]+":"+files[1]
  
  clash = loadClashlist(files[2])
  out = out+":" + ("%.2f" % (clash['scoreAll'])) + ":" + ("%.2f" % (clash['scoreBlt40']))
  
  cbdev = loadCbetaDev(files[3])
  badCbeta = findCbetaOutliers(cbdev)
  out = out+":" + repr(len(badCbeta)) + ":" + repr(len(cbdev))
  
  rota = loadRotamer(files[4])
  badRota = findRotaOutliers(rota);
  out = out+":" + repr(len(badRota)) + ":" + repr(len(rota))
  
  rama = loadRamachandran(files[5])
  #pprint.pprint(rama)
  ramaScore = {}
  ramaScore['OUTLIER'] = 0
  ramaScore['Allowed'] = 0
  ramaScore['Favored'] = 0
  for res, r in rama.iteritems():
    if r['eval'] in ramaScore:
      ramaScore[ r['eval'] ] = ramaScore[r['eval']] + 1
    else:
      ramaScore[ r['eval'] ] = 1
  if ramaScore['OUTLIER'] == 0 and ramaScore['Allowed'] == 0 and ramaScore['Favored'] == 0:
    out = out+"::::"
  else:
    out = out+":" + (repr(ramaScore['OUTLIER'])) + ":" + (repr(ramaScore['Allowed'])) + ":" + (repr(ramaScore['Favored'])) + ":" + repr(len(rama))
  
  geom = loadBondGeometryReport(files[6], "protein")
  geom.update(loadBondGeometryReport(files[7], "rna"))
  geom.update(loadBondGeometryReport(files[8], "dna"))
  #pprint.pprint(geom)
  bondOut = findBondGeomOutliers(geom)
  angleOut = findAngleGeomOutliers(geom)
  outBondResCount = len(bondOut) # residues with at least one bond outlier
  outAngleResCount = len(angleOut)
  totalRes = len(geom) # total residues
  outBondCount = 0
  outAngleCount = 0
  totalBonds = 0
  totalAngles = 0
  for res, data in geom.iteritems():
    if 'isbondOutlier' in data:
      if(data['isbondOutlier']):
        outBondCount += data['bondoutCount']
      totalBonds += data['bondCount']
    if 'isangleOutlier' in data:
      if(data['isangleOutlier']):
        outAngleCount += data['angleoutCount']
      totalAngles += data['angleCount']
  if (totalRes > 0):
    out = out+":"+repr(outBondCount)+":"+repr(totalBonds)+":"+("%.2f" % (100.0 * outBondCount / totalBonds))
    out = out+":"+("%.2f" % (100.0 * outBondResCount / totalRes))
    out = out+":"+repr(outAngleCount)+":"+repr(totalAngles)+":"+("%.2f" % (100.0 * outAngleCount / totalAngles))
    out = out+":"+("%.2f" % (100.0 * outAngleResCount / totalRes))
  else:
    out = out+":-1:-1:-1:-1:-1:-1:-1:-1"
    sys.stderr.write("No standard residues detected!\n")
    
  if ((len(rota) != 0) and (len(rama) != 0)):
    mps = calcMPscore(clash, rota, rama)
    out = out+(":%.2f" % mps)
  else:
    out = out+"::"
     
  pperp = loadBasePhosPerp(files[9])
  badPperp = findBasePhosPerpOutliers(pperp)
  out = out+":"+repr(len(badPperp))+":"+repr(len(pperp))

  suites = loadSuitenameReport(files[10])
  badSuites = findSuitenameOutliers(suites)
  out = out+":"+repr(len(badSuites))+":"+repr(len(suites))

  print out
#}}}

if __name__ == "__main__":
  opts, args = parse_cmdline()
  #analyze_file(args)
  oneline_analysis(args, opts.quiet)
  #for arg in args:
  #  if os.path.exists(arg):
  #    if (os.path.isfile(arg)):
  #      analyze_file(arg)
        #files = os.listdir(arg)
        #print arg
        #for f in files:
        #  arg_file = os.path.join(arg, f)
        #  if (not os.path.isdir(os.path.realpath(arg_file))):
        #    #print os.path.abspath(arg_file)
        #    #print os.path.join(arg,f)
        #    analyze_file(outdir, arg_file)
      #else:
      #  analyze_file(outdir, arg)
   # else:
   #   print "trouble opening " + arg
