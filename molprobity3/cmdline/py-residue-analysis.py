#!/usr/bin/python
# (jEdit options) :folding=explicit:collapseFolds=1:
from math import log
import sys, os, getopt, re, pprint, collections
from optparse import OptionParser
import molparser

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
                              dna bond geometry output file
                              base ppperp output file
                              suitname output file

FLAGS:
  -h     Print this help message
"""
#}}}

#{{{ list_residues
def list_residues(model):
  res = []
  with open(model) as f:
    for line in f:
      if line.startswith("ATOM") or line.startswith("HETATM"):
        cnit = line[20:27]+line[17:20]
        if not cnit in res:
          res.append(cnit)
  return res
#}}}

#{{{ residue_analysis
def residue_analysis(files, quiet):
  list_res = list_residues(files[0])
  #print list_res
  out = ""
  
  clash = molparser.loadClashlist(files[2])
  cbdev = molparser.loadCbetaDev(files[3])
  badCbeta = molparser.findCbetaOutliers(cbdev)
  rota = molparser.loadRotamer(files[4])
  badRota = molparser.findRotaOutliers(rota);
  rama = molparser.loadRamachandran(files[5])
  geom = molparser.loadBondGeometryReport(files[6], "protein")
  geom.update(molparser.loadBondGeometryReport(files[7], "rna"))
  geom.update(molparser.loadBondGeometryReport(files[8], "dna"))
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
  #pprint.pprint(geom)
  if not (totalRes > 0 and totalBonds > 0 and totalAngles > 0):
    sys.stderr.write("No standard residues detected in "+files[0]+"!\n")

  bondOut = molparser.findBondGeomOutliers(geom)
  angleOut = molparser.findAngleGeomOutliers(geom)
  #print clash
  pperp = molparser.loadBasePhosPerp(files[9])
  badPperp = molparser.findBasePhosPerpOutliers(pperp)
  suites = molparser.loadSuitenameReport(files[10])
  badSuites = molparser.findSuitenameOutliers(suites)
  
  for res in list_res:
    outCount = 0
    outCountSep = 0
    #print res in clash['clashes']
    if res in clash['clashes']:
      outCount += 1
      outCountSep += 1
    
    out = out+os.path.basename(files[0])+":"+(os.path.basename(files[0])[:-4])[:4]+":"+files[1]+":"+res
    if res in clash['clashes']:
      out = out+":"+repr(clash['clashes'][res])+":"+clash['clashes-with'][res]['srcatom']+":"+clash['clashes-with'][res]['dstatom']+":"+clash['clashes-with'][res]['dstcnit']
    else:
      out += "::::"
  
    if res in badCbeta:
      out = out+":" + repr(badCbeta[res])
    else:
      out += ":"
    
    if res in rota:
      out = out+":" + repr(rota[res]['scorePct'])
      if (rota[res]['scorePct'] <= 1.0):
        out += ":OUTLIER"
      else:
        out += ":" + repr(rota[res]['rotamer'])
    else:
      out += "::"
      
    if res in rama:
      out += ":"+repr(rama[res]['scorePct'])+":"+rama[res]['eval']+":"+rama[res]['type']
    else:
      out += ":::"
      
    if (totalRes > 0 and totalBonds > 0 and totalAngles > 0): # catches a bug with PNA residues      
      if res in bondOut:
        out += ":"+repr(geom[res]['bondoutCount'])+":"+geom[res]['worstbondmeasure']+":"+repr(geom[res]['worstbondvalue'])+":"+repr(geom[res]['worstbondsigma'])
      else:
        out += "::::"
      if res in angleOut:
        out += ":"+repr(geom[res]['angleoutCount'])+":"+geom[res]['worstanglemeasure']+":"+repr(geom[res]['worstanglevalue'])+":"+repr(geom[res]['worstanglesigma'])
      else:
        out += "::::"
    else:
      out += ":-1:-1:-1:-1:-1:-1:-1:-1"
    
    #pprint.pprint(pperp)
    pperpval = ""
    if res in badPperp:
      if pperp[res]['deltaOut']:
        pperpval = "OUTLIER-DELTA"
      if pperp[res]['epsilonOut']:
        pperpval = "OUTLIER-EPSILON"
    out += ":"+pperpval

    if res in suites:
      out = out+":"+repr(suites[res]['suiteness'])
      if suites[res]['isOutlier']:
        out += ":OUTLIER:"+suites[res]['triage']
      else:
        out += ":"+suites[res]['conformer']+":"
    else:
      out += "::"
    out += "\n"
  print out
    
#}}}

# Takes as input a whole series of different results files from MP analysis
# e.g. clashlist, ramalyze, rotalyze, dangle, pperp, cbdev, etc.
if __name__ == "__main__":
  opts, args = parse_cmdline()
  #analyze_file(args)
  residue_analysis(args, opts.quiet)
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
