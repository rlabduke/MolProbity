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
    dest="bond_type", default="nuclear",
    help="specify hydrogen bond length for clashes (nuclear or ecloud)")
  opts, args = parser.parse_args()
  if opts.total_file_size_limit < 10000000:
    sys.stderr.write("\n**ERROR: -limit cannot be less than 10000000 (10M)\n")
    sys.exit(parser.print_help())
  if not (opts.bond_type == "nuclear" or opts.bond_type == "ecloud"):
    sys.stderr.write("\n**ERROR: -type must be ecloud or nuclear\n")
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
    if len(list_of_lists) > 500:
      sys.stderr.write("\n**ERROR: More than 500 jobs needed, try choosing a larger -limit\n")
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

# Get the pdb file location from args
#pdb=$1

for pdb in "$@"
do
pdbbase=`basename $pdb .pdb` #should be just the name of the pdb without the .pdb extension

{0}/reduce -nobuild -nuclear $pdb > pdbs/${pdbbase}-trim.pdb

done
"""
#}}}

#{{{ write_reducesub
reduce_sub = """universe = vanilla

Notify_user  = vbchen@bmrb.wisc.edu
notification = Error

#requirements = ((TARGET.FileSystemDomain == "bmrb.wisc.edu") || (TARGET.FileSystemDomain == ".bmrb.wisc.edu"))
#requirements = (machine == inspiron17)

next_job_start_delay = 60

#Executable     = /condor/vbchen/molprobity_runs/condor/reduce.sh
Executable     = reduce.sh

log             = logs/reduce.log
output          = results/reduce.out
error           = logs/reduce.err
copy_to_spool   = False
priority        = 0

"""

def make_reduce_sub(list_of_lists):
  args = ""
  #print list_of_lists
  for indx, pdbs in enumerate(list_of_lists):
    args = args+"Arguments       = "+" ".join(pdbs)+"\n"
    args = args+"queue\n\n"
  return reduce_sub+args
    
#}}}

if __name__ == "__main__":
  molprobity_home = os.path.dirname(os.path.dirname(os.path.realpath(__file__)))

  opts, indir = parse_cmdline()
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
    list_of_lists = divide_pdbs(indir, opts.total_file_size_limit)
    #write_super_dag(outdir, list_of_lists)
    #write_file(outdir, "local_run.sh", local_run.format(molprobity_home, pdbbase="{pdbbase}"), 0755)
    write_file(outdir, "reduce.sh", reduce_sh.format(os.path.join(molprobity_home, "bin", "linux"), pdbbase="{pdbbase}"), 0755)
    write_file(outdir, "reduce.sub", make_reduce_sub(list_of_lists))
  else:
    sys.stderr.write(indir + " does not seem to exist!\n")
