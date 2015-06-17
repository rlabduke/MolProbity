#!/usr/bin/python
# (jEdit options) :folding=explicit:collapseFolds=1:
import sys, os, getopt, re, subprocess, shutil
from optparse import OptionParser


#{{{ parse_cmdline
#parse the command line--------------------------------------------------------------------------
def parse_cmdline():
  parser = OptionParser()
  #parser.add_option("-l", "--limit", action="store", type="int", 
  #  dest="total_file_size_limit", default=10000000,
  #  help="change total file size in each separate job")
  #parser.add_option("-t", "--type", action="store", type="string", 
  #  dest="bond_type", default="nuclear",
  #  help="specify hydrogen bond length for clashes (nuclear or ecloud)")
  opts, args = parser.parse_args()
  #if opts.total_file_size_limit < 5000000:
  #  sys.stderr.write("\n**ERROR: -limit cannot be less than 5000000 (5M)\n")
  #  sys.exit(parser.print_help())
  #if not (opts.bond_type == "nuclear" or opts.bond_type == "ecloud"):
  #  sys.stderr.write("\n**ERROR: -type must be ecloud or nuclear\n")
  #  sys.exit(parser.print_help())
  if len(args) < 2:
    sys.stderr.write("\n**ERROR: User must specify input and output directory\n")
    sys.exit(help())
  else:  
    indir = args[0]
    outdir = args[1]
    if (os.path.isdir(indir) and os.path.isdir(outdir)):
      return opts, indir, outdir
    else:
      sys.stderr.write("\n**ERROR: First two arguments must be directories!\n")
      sys.exit(help())
#------------------------------------------------------------------------------------------------
#}}}

#{{{ help
def help():
  print """USAGE:   python strip_remarks.py [input_directory_of_pdbs] [output_directory]
  
  Takes as input a directory containing pdbs, and only outputs lines which start with 
  ATOM, ANISOU, HETATM, TER, MODEL, ENDMDL, or END.
  
FLAGS:
  -h     Print this help message
"""
#}}}

def keep_atom_recs(pdb_file, outdir):
  pdb_name, ext = os.path.splitext(os.path.basename(pdb_file))
  #print "doing "+pdb_name
  pdb_in=open(pdb_file)
  out_name = os.path.join(outdir, pdb_name+"_norem.pdb")
  #print " to "+out_name
  pdb_out = open(out_name, 'wr')
  for line in pdb_in:
    if (line.startswith("ATOM") or 
        line.startswith("ANISOU") or 
        line.startswith("HETATM") or
        line.startswith("TER") or 
        line.startswith("MODEL") or 
        line.startswith("ENDMDL") or 
        line.startswith("END")):
      pdb_out.write(line)
  pdb_out.close()
  pdb_in.close()



if __name__ == "__main__":
  molprobity_home = os.path.dirname(os.path.dirname(os.path.realpath(__file__)))

  opts, indir, outdir = parse_cmdline()
  if (os.path.isdir(indir)):
    files = os.listdir(indir)
    files.sort()
    #print files
    for f in files:
      full_file = os.path.realpath(f)
      #print full_file
      if (not os.path.isdir(full_file)):
        keep_atom_recs(full_file, outdir)

