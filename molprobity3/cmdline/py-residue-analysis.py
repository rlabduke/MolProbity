#!/usr/bin/python
# (jEdit options) :folding=explicit:collapseFolds=1:
from math import log
import sys, os, getopt, re, pprint, collections
from optparse import OptionParser
import molparser
#sys.path.append('/home/vbc3/programs/sans/python')
#import bmrb

#{{{ parse_cmdline
#parse the command line--------------------------------------------------------------------------
def parse_cmdline():
  parser = OptionParser()
  parser.add_option("-q", "--quiet", action="store_true", dest="quiet",
    help="quiet mode")
  parser.add_option("-s", "--sans", action="store", dest="sans_location", 
    type="string", default="none",
    help="sans parser location, needed for nmrstar output")
  opts, args = parser.parse_args()
  if not opts.sans_location is "none" and not os.path.isdir(opts.sans_location):
    sys.stderr.write("\n**ERROR: sans location must be a directory!\n")
    sys.exit(help())
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
                              maxBfactor file
                              tau/omega file
                              disulfides file

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

#{{{ residue_analysis_old
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
    #if res in clash['clashes']:
    #  outCount += 1
    #  outCountSep += 1
    
    out = out+os.path.basename(files[0])+":"+(os.path.basename(files[0])[:-4])[:4]+":"+files[1]+":"+res
    if res in clash['clashes']:
      outCount += 1
      outCountSep += 1
      out = out+":"+repr(clash['clashes'][res])+":"+clash['clashes-with'][res]['srcatom']+":"+clash['clashes-with'][res]['dstatom']+":"+clash['clashes-with'][res]['dstcnit']
    else:
      out += "::::"
  
    if res in badCbeta:
      outCountSep += 1
      out = out+":" + repr(badCbeta[res])
    else:
      out += ":"
    
    if res in rota:
      out = out+":" + repr(rota[res]['scorePct'])
      if (rota[res]['scorePct'] <= 1.0):
        outCount += 1
        outCountSep += 1
        out += ":OUTLIER"
      else:
        out += ":" + repr(rota[res]['rotamer'])
    else:
      out += "::"
      
    if res in rama:
      outCount += 1
      outCountSep += 1
      out += ":"+repr(rama[res]['scorePct'])+":"+rama[res]['eval']+":"+rama[res]['type']
    else:
      out += ":::"
      
    if (totalRes > 0 and totalBonds > 0 and totalAngles > 0): # catches a bug with PNA residues      
      if res in bondOut:
        outCountSep += 1
        out += ":"+repr(geom[res]['bondoutCount'])+":"+geom[res]['worstbondmeasure']+":"+repr(geom[res]['worstbondvalue'])+":"+repr(geom[res]['worstbondsigma'])
      else:
        out += "::::"
      if res in angleOut:
        outCountSep += 1
        out += ":"+repr(geom[res]['angleoutCount'])+":"+geom[res]['worstanglemeasure']+":"+repr(geom[res]['worstanglevalue'])+":"+repr(geom[res]['worstanglesigma'])
      else:
        out += "::::"
    else:
      out += ":-1:-1:-1:-1:-1:-1:-1:-1"
      
    if (res in badCbeta) or (res in bondOut and geom[res]['bondoutCount'] > 0) or (res in angleOut and geom[res]['angleoutCount'] > 0):
      outCount += 1
    
    #pprint.pprint(pperp)
    pperpval = ""
    if res in badPperp:
      outCount += 1
      outCountSep += 1
      if pperp[res]['deltaOut']:
        pperpval = "OUTLIER-DELTA"
      if pperp[res]['epsilonOut']:
        pperpval = "OUTLIER-EPSILON"
    out += ":"+pperpval

    if res in suites:
      out = out+":"+repr(suites[res]['suiteness'])
      if suites[res]['isOutlier']:
        outCount += 1
        outCountSep += 1
        out += ":OUTLIER:"+suites[res]['triage']
      else:
        out += ":"+suites[res]['conformer']+":"
    else:
      out += "::"
    out += repr(outCountSep)+":"+repr(outCount)
    out += "\n"
  print out
    
#}}}

#{{{ setup_nmrstar
def setup_nmrstar(header, output_str, filename, sans_loc):
  sys.path.append(sans_loc)
  import bmrb
  #if os.path.isfile(output_str):
  #  saver = bmrb.saveframe.fromFile(output_str)
  #  loop = saver.getLoopByCategory("Residue_analysis")
  #else:

  if not os.path.isfile(output_str):
    saver = bmrb.saveframe.fromScratch("Structure_validation_residue", "Structure_validation_residue")
    saver.addTag("Sf_category", "?")
    saver.addTag("Sf_framecode", "?")
    saver.addTag("Entry_ID", "?")
    saver.addTag("List_ID", "?")
    saver.addTag("Software_label", "molprobity")
    saver.addTag("Software_version", "4.0")
    saver.addTag("File_name", os.path.basename(filename))
    saver.addTag("PDB_ID", (os.path.basename(filename)[:-4])[:4])
    with open(output_str+".header", "a+") as str_write:
      str_write.write(saver.getDataAsCSV())
  loop = bmrb.loop.fromScratch(category="Residue_analysis")
  loop.addColumn(header)
  return loop
#}}}

#{{{ write_nmrstar
def write_nmrstar(output_str, loop):
  print_header = False
  if not os.path.isfile(output_str):
    print_header = True
  #  saver.addLoop(loop)
  with open(output_str, 'a+') as str_write:
    str_write.write(loop.getDataAsCSV(print_header, True))
  #print saver
#}}}

#{{{ residue_analysis
def residue_analysis(files, quiet, sans_location):
  list_res = list_residues(files[0])
  #print list_res
  #out = []
  #need h position, flip info, 
  header = ["Filename",
            "PDB_accession_code",
            "PDB_model_num",
            "Hydrogen_positions",
            "MolProbity_flips",
            "Two_letter_chain_ID",
            "PDB_strand_ID",
            "PDB_residue_no",
            "PDB_ins_code",
            "PDB_residue_name",
            "Assembly_ID",
            "Entity_assembly_ID",
            "Entity_ID",
            "Comp_ID",
            "Comp_index_ID",
            "Clash_value",
            "Clash_source_PDB_atom_name",
            "Clash_destination_PDB_atom_name",
            "Clash_destination_PDB_strand_ID",
            "Clash_destination_PDB_residue_no",
            "Clash_destination_PDB_ins_code",
            "Clash_destination_PDB_residue_name",
            "Cbeta_deviation_value",
            "Rotamer_score",
            "Rotamer_name",
            "Ramachandran_phi",
            "Ramachandran_psi",
            "Ramachandran_score",
            "Ramachandran_evaluation",
            "Ramachandran_type",
            "Bond_outlier_count",
            "Worst_bond",
            "Worst_bond_value",
            "Worst_bond_sigma",
            "Angle_outlier_count",
            "Worst_angle",
            "Worst_angle_value",
            "Worst_angle_sigma",
            "RNA_phosphate_perpendicular_outlier",
            "RNA_suitness_score",
            "RNA_suite_conformer",
            "RNA_suite_triage",
            "Max_b_factor",
            "Tau_angle",
            "Omega_dihedral",
            "Disulfide_chi1",
            "Disulfide_chi2",
            "Disulfide_chi3",
            "Disulfide_ss_angle",
            "Disulfide_ss",
            "Disulfide_ss_angle_prime",
            "Disulfide_chi2prime",
            "Disulfide_chi1prime",
            "Outlier_count_separate_geometry",
            "Outlier_count",
            "Entry_ID",
            "Structure_validation_residue_list_ID"
  ]
  
  output_dir = files[14]
  if sans_location is not "none":
    output_str = os.path.join(output_dir, (os.path.basename(files[0])[:-4])[:4]+"-residue-str.csv")
    loop = setup_nmrstar(header[2:], output_str, files[0], sans_location)
  
  clash = molparser.loadClashlist(files[2])
  cbdev = molparser.loadCbetaDev(files[3])
  badCbeta = molparser.findCbetaOutliers(cbdev)
  rota = molparser.loadRotamer(files[4])
  badRota = molparser.findRotaOutliers(rota);
  rama = molparser.loadRamachandran(files[5])
  geom = molparser.loadBondGeometryReport(files[6], "protein")
  geom.update(molparser.loadBondGeometryReport(files[7], "rna"))
  geom.update(molparser.loadBondGeometryReport(files[8], "dna"))
  bfactor = molparser.loadMaxBfactor(files[11])
  tauomega = molparser.loadTauOmegaReport(files[12])
  disulf = molparser.loadDisulfidesReport(files[13])
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
  
  csv_out = ""
  if not quiet:
    csv_out = "#"+(":".join(header))
    
  for res in list_res:
    out = []
    outCount = 0
    outCountSep = 0
    #print res in clash['clashes']
    #if res in clash['clashes']:
    #  outCount += 1
    #  outCountSep += 1
    
    out.append(os.path.basename(files[0])) # filename
    out.append((os.path.basename(files[0])[:-4])[:4]) # pdbID
    out.append(files[1]) # model num
    flips_used = files[16]
    #print flips_used
    #print type(flips_used)
    #print flips_used == "na"
    if flips_used == "na":
      out.append("original")
    else:
      out.append(files[15]) # Hydrogen_positions
    out.append(files[16]) # MolProbity_flips
    out.append(res[0:2]) # Two_letter_chain_ID
    out.append(res[1:2]) # PDB_strand_ID
    out.append(res[2:6]) # PDB_residue_no
    out.append(res[6:7]) # PDB_ins_code
    out.append(res[7:10]) # PDB_residue_name
    
    out.append("?") # "Assembly_ID",       bmrb specific, to be filled in later
    out.append("?") # "Entity_assembly_ID",bmrb specific, to be filled in later
    out.append("?") # "Entity_ID",         bmrb specific, to be filled in later
    out.append("?") # "Comp_ID",           bmrb specific, to be filled in later
    out.append("?") # "Comp_index_ID",     bmrb specific, to be filled in later
    
    if res in clash['clashes']:
      outCount += 1
      outCountSep += 1
      #out = out+":"+repr(clash['clashes'][res])+":"+clash['clashes-with'][res]['srcatom']+":"+clash['clashes-with'][res]['dstatom']+":"+clash['clashes-with'][res]['dstcnit']
      out.append(clash['clashes'][res])
      out.append(clash['clashes-with'][res]['srcatom'])
      out.append(clash['clashes-with'][res]['dstatom'])
      dest_cnit = clash['clashes-with'][res]['dstcnit']
      out.append(dest_cnit[0:2])
      out.append(dest_cnit[2:6])
      out.append(dest_cnit[6:7])
      out.append(dest_cnit[7:10])   
    else:
      out.extend(["","","","","","",""])
  
    if res in badCbeta:
      outCountSep += 1
      out.append(badCbeta[res])
    else:
      out.append("")
    
    if res in rota:
      out.append(rota[res]['scorePct'])
      if (rota[res]['scorePct'] <= 1.0):
        outCount += 1
        outCountSep += 1
        out.append("OUTLIER")
      else:
        out.append(rota[res]['rotamer'])
    else:
      out.extend(["",""])
      
    if res in rama:
      outCount += 1
      outCountSep += 1
      out.append(rama[res]['phi'])
      out.append(rama[res]['psi'])
      out.append(rama[res]['scorePct'])
      out.append(rama[res]['eval'])
      out.append(rama[res]['type'])
    else:
      out.extend(["","","","",""])
      
    if (totalRes > 0 and totalBonds > 0 and totalAngles > 0): # catches a bug with PNA residues      
      if res in bondOut:
        outCountSep += 1
      if res in geom and 'bondoutCount' in geom[res]:
        out.append(geom[res]['bondoutCount'])
        out.append(geom[res]['worstbondmeasure'])
        out.append(geom[res]['worstbondvalue'])
        out.append(geom[res]['worstbondsigma'])
      else:
        out.extend(['','','',''])
      if res in angleOut:
        outCountSep += 1
      if res in geom and 'angleoutCount' in geom[res]:
        out.append(geom[res]['angleoutCount'])
        out.append(geom[res]['worstanglemeasure'])
        out.append(geom[res]['worstanglevalue'])
        out.append(geom[res]['worstanglesigma'])
      else:
        out.extend(['','','',''])
    else:
      out.extend([-1,-1,-1,-1,-1,-1,-1,-1])
      
    if (res in badCbeta) or (res in bondOut and geom[res]['bondoutCount'] > 0) or (res in angleOut and geom[res]['angleoutCount'] > 0):
      outCount += 1
    
    #pprint.pprint(pperp)
    pperpval = ""
    if res in badPperp:
      outCount += 1
      outCountSep += 1
      if pperp[res]['deltaOut']:
        pperpval = "OUTLIER-DELTA"
      if pperp[res]['epsilonOut']:
        pperpval = "OUTLIER-EPSILON"
    out.append(pperpval)

    if res in suites:
      out.append(suites[res]['suiteness'])
      if suites[res]['isOutlier']:
        outCount += 1
        outCountSep += 1
        out.append("OUTLIER")
        out.append(suites[res]['triage'])
      else:
        out.append(suites[res]['conformer'])
        out.append("")
    else:
      out.extend(["","",""])
    
    if res in bfactor:
      out.append(repr(bfactor[res]['maxB']))
    else:
      out.append("")
      
    if res in tauomega:
      out.extend([tauomega[res]['tau'], tauomega[res]['omega']])
    else:
      out.extend(["",""])
      
    if res in disulf:
      out.extend([disulf[res]['chi1'],disulf[res]['chi2'],disulf[res]['chi3'],disulf[res]['cb-s-s'],disulf[res]['s-s'],disulf[res]['s-s-cbprime'],disulf[res]['chi2prime'],disulf[res]['chi1prime']])
    else:
      out.extend(["","","","","","","",""])
    
    out.extend([outCountSep,outCount])

    # This is for the entry_ID and list_ID
    out.extend(["?","?"])
    
    csv_out = csv_out+'\n'+":".join(str(e) for e in out)
    if sans_location is not "none":
      loop.addData(["." if x=="" else x for x in out][2:])

  output_file = os.path.join(output_dir, (os.path.basename(files[0])[:-4])[:4]+"-residue.csv")

  #if os.path.isfile(output_file):
  #  sys.stderr.write("output_file: "+output_file+' exists\n')
  with open(output_file, "a+") as out_write:
    out_write.write(csv_out)
    out_write.write('\n')
    
  #sys.stderr.write(" ".join(os.listdir(os.path.dirname(output_str))))
  if sans_location is not "none":
    write_nmrstar(output_str, loop)

    
#}}}

# for testing
# py-residue-analysis.py -s '/home/vbc3/programs/sans/python' ../../../1ubqH.pdb 1 1ubqH_001-clashlist 1ubqH_001-cbdev 1ubqH_001-rotalyze 1ubqH_001-ramalyze 1ubqH_001-dangle_protein 1ubqH_001-dangle_rna 1ubqH_001-dangle_dna 1ubqH_001-prekin_pperp 1ubqH_001-suitename 1ubqH_001-dangle_maxb 1ubqH_001-dangle_tauomega 1ubqH_001-dangle_ss .. cmd1 cmd2

# Takes as input a whole series of different results files from MP analysis
# e.g. clashlist, ramalyze, rotalyze, dangle, pperp, cbdev, etc.
if __name__ == "__main__":
  opts, args = parse_cmdline()
  #analyze_file(args)
  residue_analysis(args, opts.quiet, opts.sans_location)
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
