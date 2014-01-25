#!/usr/bin/python
# (jEdit options) :folding=explicit:collapseFolds=1:
import sys, os, getopt, re, subprocess, shutil
from optparse import OptionParser
import time
import imp
splitter = imp.load_source("split_pdbs_to_models", os.path.join(os.path.dirname(os.path.realpath(__file__)),"make_condor_files2.py"))

# THIS FILE MUST BE IN THE MOLPROBITY CMDLINE DIRECTORY!!!

#number_to_run = 50


#{{{ parse_cmdline
#parse the command line--------------------------------------------------------------------------
def parse_cmdline():
  parser = OptionParser()
  #parser.add_option("-o", "--outdir", action="store", type="string", 
  #  dest="out_directory", default="split",
  #  help="output directory for split files")
  opts, args = parser.parse_args()
  if len(args) < 2:
    sys.stderr.write("\n**ERROR: User must specify input and output directories\n")
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
  print """USAGE:   python make_condor_files.py [input_directory_of_pdbs]
  
  Takes as input a directory containing pdbs, and generates a directory 'condor_sub_files' 
  within that directory containing all the scripts needed to run molprobity analysis on a 
  HTCondor cluster.
  
FLAGS:
  -h     Print this help message
"""
#}}}

#{{{ split_all
def split_all(indir, outdir):
  if os.path.exists(indir) and os.path.exists(outdir):
    outdirfull = os.path.realpath(outdir)
    #else:
    #  sys.stderr.write("\""+outdir+"\" directory detected in \""+indir+"\", please delete it before running this script\n")
    #  sys.exit()
    splitter.split_pdbs_to_models(indir, outdirfull)
  else:
    sys.stderr.write(indir+" or "+outdir+" does not seem to exist!\n")
#}}}

if __name__ == "__main__":
  molprobity_home = os.path.dirname(os.path.dirname(os.path.realpath(__file__)))

  opts, indir, outdir = parse_cmdline()
  split_all(indir, outdir)
