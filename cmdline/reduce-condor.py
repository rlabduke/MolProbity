#!/usr/bin/python
# (jEdit options) :folding=explicit:collapseFolds=1:
import sys, os, getopt, re, subprocess, shutil
from optparse import OptionParser
import time

# THIS FILE MUST BE IN THE MOLPROBITY CMDLINE DIRECTORY!!!

#number_to_run = 50


#{{{ parse_cmdline
#parse the command line--------------------------------------------------------------------------
def parse_cmdline():
  parser = OptionParser()
  parser.add_option("-l", "--limit", action="store", type="int", 
    dest="total_file_size_limit", default=50000000,
    help="change total file size in each separate job")
  parser.add_option("-t", "--type", action="store", type="string", 
    dest="bond_type", default="default",
    help="specify hydrogen bond length for clashes (nuclear or ecloud)")
  parser.add_option("-b", "--build", action="store", type="string",
    dest="build_type", default="default",
    help="specify whether to use -build or -nobuild for running reduce")
  opts, args = parser.parse_args()
  if opts.total_file_size_limit < 1000000:
    sys.stderr.write("\n**ERROR: -limit cannot be less than 1000000 (1M)\n")
    sys.exit(parser.print_help())
  if not (opts.bond_type == "nuclear" or opts.bond_type == "ecloud"):
    sys.stderr.write("\n**ERROR: -type must be specified as ecloud or nuclear\n")
    sys.exit(parser.print_help())
  if not (opts.build_type == "build" or opts.build_type == "nobuild"):
    sys.stderr.write("\n**ERROR: -build must be specified as build or nobuild\n")
    sys.exit(parser.print_help())
  if len(args) < 1:
    sys.stderr.write("\n**ERROR: User must specify input directory\n")
    sys.exit(help())
  else:  
    indir = args[0]
    if (os.path.isdir(indir)):
      return opts, indir
    else:
      sys.stderr.write("\n**ERROR: First argument must be a directory!\n")
      sys.exit(help())
  #global total_file_size_limit
  #global bond_type
  #total_file_size_limit = 10000000 # 10 MB by default
  #bond_type = "nuclear"
  #try:
  #  #opts, args = getopt.getopt(sys.argv[1:], 'hn:',['help', 'number='])
  #  opts, args = getopt.getopt(sys.argv[1:], 'hlt',['help', 'limit=', 'type='])
  #except getopt.GetoptError as err:
  #  print str(err)
  #  help()
  #  sys.exit()
  #for o, a in opts:
  #  if o in ("-h", "--help"):
  #    help()
  #    sys.exit()
  #  elif o in ("-l", "--limit"):
  #    if a > 1000000: # no less than 1 MB
  #      total_file_size_limit = a
  #    else:
  #      sys.stderr.write("\n**ERROR: -limit cannot be less than 1000000\n")
  #      sys.exit(help())
  #  elif o in ("-t", "--type"):
  #    if a == "ecloud":
  #      bond_type = "ecloud"
  #    elif not a == "nuclear":
  #      sys.stderr.write("\n**ERROR: -type must be ecloud or nuclear\n")
  #      sys.exit(help())
  #return args
  #if len(args) < 1:
  #  sys.stderr.write("\n**ERROR: User must specify input directory\n")
  #  sys.exit(help())
  #else:
  #  indir = args[0]
  #  if (os.path.isdir(indir)):
  #    return indir
  #  else:
  #    sys.stderr.write("\n**ERROR: First argument must be a directory!\n")
  #    sys.exit(help())
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

#{{{ divide_pdbs
def divide_pdbs(in_dir, size_limit):
  #print os.path.realpath(in_dir)
  if (os.path.isdir(in_dir)):
    files = os.listdir(os.path.realpath(in_dir))
    #print files
    files.sort()
    #print arg
    list_of_lists = []
    pdb_list = []
    list_size = 0
    list_of_lists.append(pdb_list)
    #print files
    for f in files:
      #print f
      #arg_file = os.path.join(arg, f)
      full_file = os.path.abspath(os.path.join(in_dir, f))
      #print full_file
      if (not os.path.isdir(full_file)):
        root, ext = os.path.splitext(f)
        if (ext == ".pdb"):
          #print f
          if (list_size <= size_limit):
            pdb_list.append(full_file)
            #print pdb_list
            list_size = list_size + os.path.getsize(full_file)
          else:
            pdb_list = []
            pdb_list.append(full_file)
            list_of_lists.append(pdb_list)
            list_size = os.path.getsize(full_file)
    if len(list_of_lists) > 1000:
      sys.stderr.write("\n**ERROR: More than 1000 jobs needed, try choosing a larger -limit\n")
      sys.exit()
    return list_of_lists
    #print list_of_lists
#}}}

#{{{ write_file
def write_file(outdir, out_name, file_text, permissions=0644):
  outfile = os.path.join(os.path.realpath(outdir), out_name)
  out=open(outfile, 'wr')
  out.write(file_text)
  out.close()
  os.chmod(outfile, permissions)
#}}}

#{{{ write_reducesh
reduce_sh = """#!/bin/sh

# Get the number of seconds to sleep before running
sleepseconds=$1

sleep $sleepseconds

#removes first argument
shift
for pdb in "$@"
do
sleep 1
pdbbase=`basename $pdb .pdb` #should be just the name of the pdb without the .pdb extension

{0}/reduce -q -{buildtype} -{bondtype} $pdb > pdbs/${pdbbase}H.pdb

done
"""
#}}}

#{{{ write_reducesub
reduce_sub = """universe = vanilla

Notify_user  = vbchen@bmrb.wisc.edu
notification = Error

#requirements = ((TARGET.FileSystemDomain == "bmrb.wisc.edu") || (TARGET.FileSystemDomain == ".bmrb.wisc.edu"))
#requirements = (machine == inspiron17)

#Executable     = /condor/vbchen/molprobity_runs/condor/reduce.sh
Executable     = reduce.sh

copy_to_spool   = False
priority        = 0

"""

def make_reduce_sub(list_of_lists):
  args = ""
  #print list_of_lists
  sleep_seconds = 0
  for indx, pdbs in enumerate(list_of_lists):
    args = args+"Arguments       = "+repr(sleep_seconds)+" "+" ".join(pdbs)+"\n"
    args = args+"log         = logs/reduce"+repr(indx)+".log\n"
    args = args+"error      = logs/reduce"+repr(indx)+".err\n"
    args = args+"queue\n\n"
    if sleep_seconds > 300:
      sleep_seconds = sleep_seconds + 5
    else:
      sleep_seconds = sleep_seconds + 30
  return reduce_sub+args
    
#}}}

#{{{ make_reduce_files
def make_reduce_files(indir, file_size_limit, build_type, bond_type):
  molprobity_home = os.path.dirname(os.path.dirname(os.path.realpath(__file__)))
  if os.path.exists(indir):
    outdir = os.path.join(indir, "reduce_condor_files")
    if not os.path.exists(outdir):
      os.makedirs(outdir)
      os.makedirs(os.path.join(outdir,"logs"))
      os.makedirs(os.path.join(outdir,"results"))
      os.makedirs(os.path.join(outdir,"pdbs"))
    else:
      sys.stderr.write("\"reduce_condor_files\" directory detected in \""+indir+"\", please delete it before running this script\n")
      sys.exit()
    #split_pdbs_to_models(molprobity_home, indir, os.path.join(outdir, "pdbs"))
    #print opts.total_file_size_limit
    list_of_lists = divide_pdbs(indir, file_size_limit)
    #write_super_dag(outdir, list_of_lists)
    #write_file(outdir, "local_run.sh", local_run.format(molprobity_home, pdbbase="{pdbbase}"), 0755)
    pdb = "{pdbbase}F"
    build = "nobuild"
    if build_type=="build":
      pdb = "{pdbbase}F"
      build = "build"
    write_file(outdir, "reduce.sh", reduce_sh.format(os.path.join(molprobity_home, "bin", "linux"), buildtype=build, bondtype=bond_type, pdbbase=pdb), 0755)
    write_file(outdir, "reduce.sub", make_reduce_sub(list_of_lists))
  else:
    sys.stderr.write(indir + " does not seem to exist!\n")
#}}}


if __name__ == "__main__":

  opts, indir = parse_cmdline()
  make_reduce_files(indir, opts.total_file_size_limits, opts.build_type)
