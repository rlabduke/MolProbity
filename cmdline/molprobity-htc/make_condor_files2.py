#!/usr/bin/python
# (jEdit options) :folding=explicit:collapseFolds=1:
import sys, os, getopt, re, subprocess, shutil
from optparse import OptionParser
import time
import gzip
import pprint
import tarfile
# THIS FILE MUST BE IN THE MOLPROBITY CMDLINE DIRECTORY!!!

#number_to_run = 50

#{{{ parse_cmdline
#parse the command line--------------------------------------------------------------------------
def parse_cmdline():
  parser = OptionParser()
  parser.add_option("-l", "--limit", action="store", type="int",
    dest="total_file_size_limit", default=10000000,
    help="change total file size in each separate job")
  parser.add_option("-t", "--type", action="store", type="string",
    dest="bond_type", default="nuclear",
    help="specify hydrogen bond length for clashes (nuclear or ecloud)")
  parser.add_option("-u", "--updatescripts", action="store_true",
    dest="update_scripts", default=False,
    help="update scripts in a prexisting folder with new versions")
  parser.add_option("-s", "--sans", action="store", dest="sans_location", 
    type="string", default="none",
    help="sans parser tgz location, needed for nmrstar output")
  parser.add_option("-r", "--requirement", action="store", dest="requirement",
    type="string", default="bmrb",
    help="requirements for limiting where condor jobs get submitted (none or bmrb)")
  parser.add_option("-w", "--web", action="store", dest="update_bmrb_website", 
    type="string", default="none",
    help="use this option to auto update results on BMRB website afterwards")
  #parser.add_option("-c", "--core", action="store", dest="cyrange_location", 
  #  type="string", default="none",
  #  help="cyrange core calculation software location")
  #parser.add_option("-r", "--reduce", action="store", type="boolean",
  #  dest="run_reduce", default="false",
  #  help="run reduce to add hydrogens first (use -t to specify length)")
  #parser.add_option("-b", "--build", action="store", type="string",
  #  dest="build_type", default="default",
  #  help="specify whether to use -build or -nobuild for running reduce")
  opts, args = parser.parse_args()
  if not opts.sans_location is "none" and not os.path.isfile(opts.sans_location) and not opts.sans_location.endswith("tgz"):
    sys.stderr.write("\n**ERROR: sans location must be a gz file!\n")
    sys.exit(help())
  if opts.total_file_size_limit < 5000000:
    sys.stderr.write("\n**ERROR: -limit cannot be less than 5000000 (5M)\n")
    sys.exit(parser.print_help())
  if not (opts.bond_type == "nuclear" or opts.bond_type == "ecloud"):
    sys.stderr.write("\n**ERROR: -type must be ecloud or nuclear\n")
    sys.exit(parser.print_help())
  #if not (opts.build_type == "build" or opts.build_type == "nobuild"):
  #  sys.stderr.write("\n**ERROR: -build must be specified as build or nobuild\n")
  #  sys.exit(parser.print_help())
  if len(args) < 1:
    sys.stderr.write("\n**ERROR: User must specify input directory\n")
    sys.exit(parser.print_help())
  else:
    indir = args[0]
    if (os.path.isdir(indir)):
      return opts, indir
    else:
      sys.stderr.write("\n**ERROR: First argument must be a directory!\n")
      sys.exit(parser.print_help())
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

#{{{ split_pdbs_to_models
def split_pdbs_to_models(indir, outdir):
  #print "indir: "+indir
  if (os.path.isdir(indir)):
    files = os.listdir(indir)
    files.sort()
    #print files
    for f in files:
      #arg_file = os.path.join(arg, f)
      full_file = os.path.join(indir,f)
      #print "full_file: "+full_file
      if (not os.path.isdir(full_file)):
        root, ext = os.path.splitext(f)
        if (ext == ".pdb"):
          #print full_file
          #print os.path.join(mp_home, "cmdline", "split-models")
          #s_time = time.time()
          #subprocess.call([os.path.join(mp_home, "cmdline", "split-models"), "-q", full_file, outdir])
          split_pdb(full_file, outdir)
          #e_time = time.time()
          #print repr(e_time - s_time) + " seconds?"
        if (ext == ".gz"):
          split_pdb(full_file, outdir, True)
#}}}

#{{{ split_pdb
def split_pdb(pdb_file, outdir, origdir, gzip_file = False):
  model_files = []
  keep_lines = False
  pdb_name, ext = os.path.splitext(os.path.basename(pdb_file))
  #print pdb_name
  if pdb_name.startswith("pdb") and pdb_name.endswith(".ent"):
    pdb_name = pdb_name[3:-4]
  if "-cyranged.pdb" in pdb_name:
    pdb_name = pdb_name[:-4]
    
  if not os.path.exists(os.path.join(outdir, "results", pdb_name[1:3])):
    os.makedirs(os.path.join(outdir, "results", pdb_name[1:3]))
    
  #print pdb_name+"\n"
  if gzip_file:
    pdb_in = gzip.open(pdb_file)
  else:
    pdb_in=open(pdb_file)
  mod_num = 0
  all_file = ""
  for line in pdb_in:
    start = line[0:6]
    all_file = all_file+line
    if start == "MODEL ":
      keep_lines = True
      mod_num = int(line[5:25].strip())
      model_name = os.path.join(origdir, pdb_name+("_%03d.pdb" % (mod_num)))
      model_out = open(model_name, 'wr')
    elif start == "ENDMDL":
      keep_lines = False
      model_out.close()
    elif keep_lines:
      model_out.write(line)
  if mod_num == 0: # takes care of the case where there's only one model, so no MODEL or ENDMDL
    model_out = open(os.path.join(origdir, pdb_name+"_001.pdb"), 'wr')
    model_out.write(all_file)
    model_out.close()
  pdb_in.close()    
#}}}

#{{{ divide_pdbs
def divide_pdbs(in_dir, outdir, size_limit):
  #print os.path.realpath(in_dir)
  if (os.path.isdir(in_dir)):
    files = os.listdir(os.path.realpath(in_dir))
    #print files
    files.sort()
    #print arg
    list_of_lists = []
    pdb_list = []
    pdb_dict = {}
    list_size = 0
    list_of_lists.append(pdb_list)
    #print files
    for f in files:
      #print f
      #arg_file = os.path.join(arg, f)
      full_file = os.path.abspath(os.path.join(in_dir, f))
      #print full_file
      # go through all files and group ones with same PDB_ID
      if (not os.path.isdir(full_file)):
        root, ext = os.path.splitext(f)
        pdb_name = ""
        if (ext == ".pdb") or (ext == ".ent"):
          pdb_name = root
        if (ext == ".gz"):
          root2, ext = os.path.splitext(root)
          if (ext == ".ent") or (ext == ".pdb"):
            pdb_name = root2
        if (pdb_name != ""):
          pdb_id = ""
          if pdb_name.startswith("pdb"):
            pdb_id = pdb_name[3:]
          if "-cyranged" in pdb_name:
            pdb_id = pdb_name[0:4]
          if pdb_id in pdb_dict:
            pdb_set = pdb_dict[pdb_id]
            pdb_set.append(full_file)
          else:
            pdb_dict[pdb_id] = [full_file]
    #pprint.pprint(pdb_dict)
    # use the dictionary of pdb_id -> multiple pdbfiles to create separated condor jobs
    pdb_keys = sorted(pdb_dict.keys())
    for key in pdb_keys:
      pdb_files = pdb_dict[key]
      if (list_size <= size_limit):
        pdb_list.extend(pdb_files)
        for pdb_file in pdb_files:
          comp_factor = 1
          if pdb_file.endswith(".gz"):
            comp_factor = 0.6 # assumming ~60%compression by gzip
          list_size = list_size+os.path.getsize(pdb_file)/comp_factor
      else:
        pdb_list = []
        pdb_list.extend(pdb_files)
        list_of_lists.append(pdb_list)
        for pdb_file in pdb_files:
          comp_factor = 1
          if pdb_file.endswith(".gz"):
            comp_factor = 0.6 # assumming ~60%compression by gzip
          list_size = os.path.getsize(pdb_file)/comp_factor
#          if (list_size <= size_limit):
#            pdb_list.append(full_file)
#            #print pdb_list
#            list_size = list_size + os.path.getsize(full_file)
#          else:
#            pdb_list = []
#            pdb_list.append(full_file)
#            list_of_lists.append(pdb_list)
#            list_size = os.path.getsize(full_file)
#        if (ext == ".gz"):
#          root2, ext = os.path.splitext(root)
#          if (ext == ".ent") or (ext == ".pdb"):
#            pdb_id = root2
#            if pdb_id.startswith("pdb") and pdb_id.endswith(".ent"):
#              pdb_id = pdb_id[3:-4]
#            if "-cyranged.pdb" in pdb_id:
#              pdb_id = pdb_id[0:4]
#            
#            if (list_size <= size_limit):
#              pdb_list.append(full_file)
#              #print pdb_list
#              list_size = list_size + os.path.getsize(full_file)/0.6 # assumming ~60%compression by gzip
#            else: 
#              pdb_list = []
#              pdb_list.append(full_file)
#              list_of_lists.append(pdb_list)
#              list_size = os.path.getsize(full_file)/0.6
    if len(list_of_lists) > 10000:
      sys.stderr.write("\n**ERROR: More than 10000 jobs needed, try choosing a larger -limit\n")
      sys.exit()
    #pprint.pprint(list_of_lists)
    return list_of_lists
    #print list_of_lists
#}}}

#{{{ split_pdbs_to_dirs
# splits pdbs into separated models in the pdb/orig directory
def split_pdbs_to_dirs(outdir, list_of_pdblists):
  for indx, pdbs in enumerate(list_of_pdblists):
    num = '{0:0>4}'.format(indx)
    numpath = os.path.join(outdir, "pdbs", num)
    origpath = os.path.join(numpath, "orig")
    rednobuildpath = os.path.join(numpath, "reduce-nobuild")
    redbuildpath = os.path.join(numpath, "reduce-build")
    os.makedirs(numpath)
    os.makedirs(origpath)
    os.makedirs(rednobuildpath)
    os.makedirs(redbuildpath)
    for pdb_file in pdbs:
      #print pdb_file
      if (not os.path.isdir(pdb_file)):
        root, ext = os.path.splitext(pdb_file)
        if (ext == ".pdb"):
          split_pdb(pdb_file, outdir, origpath)
        if (ext == ".gz"):
          split_pdb(pdb_file, outdir, origpath, True)
    tar = tarfile.open(os.path.join(origpath, num+"orig.tar.gz"), "w:gz") # creates tars of original PDB models for reduce jobs
    #print origpath
    models = sorted(os.listdir(origpath))
    cwd = os.getcwd()
    os.chdir(origpath)
    for model in models:
      if model.endswith(".pdb"):
        tar.add(model)
        os.remove(model)
    tar.close()
    os.chdir(cwd)
#}}}

#{{{ write_super_dag
def write_super_dag(outdir, list_of_pdblists):
  config_name = "supermol.config"
  config_file = os.path.join(os.path.realpath(outdir), config_name)
  config = open(config_file, 'wr')
  config.write("DAGMAN_MAX_JOBS_SUBMITTED = 5\n")
  config.write("DAGMAN_SUBMIT_DELAY = 60")
  config.close()
  out_name = "supermol.dag"
  outfile = os.path.join(os.path.realpath(outdir), out_name)
  out=open(outfile, 'wr')
  out.write("CONFIG "+ config_file+"\n\n")
  for indx, pdbs in enumerate(list_of_pdblists):
    num = '{0:0>4}'.format(indx)
    out.write("SUBDAG EXTERNAL "+num+" moldag"+num+".dag\n")
    write_mol_dag(outdir, num, pdbs)
  out.close()
#}}}

#{{{ write_mpanalysis_dag
def write_mpanalysis_dag(outdir, list_of_pdblists, bondtype, sans_tgz, update_bmrb_loc="none"):
  config_name = "mpanalysisdag.config"
  config_file = os.path.join(os.path.realpath(outdir), config_name)
  config = open(config_file, 'wr')
  #config.write("DAGMAN_MAX_JOBS_SUBMITTED = 5\n")
  config.write("DAGMAN_SUBMIT_DELAY = 5")
  config.close()
  out_name = "mpanalysisdag.dag"
  outfile = os.path.join(os.path.realpath(outdir), out_name)
  out=open(outfile, 'wr')
  out.write("CONFIG "+ config_file+"\n\n")
  out.write("NODE_STATUS_FILE mpanalysisdag.status 3600\n")
  out.write("\n")
  
  #out.write("Job splitter split.sub\n")
  #out.write("VARS splitter DIRS= \".. split/\"\n\n") # .. should be directory of PDBs
  
  out_pdb_dir = os.path.join(os.path.realpath(outdir), "pdbs")
  
  postjobs = "PARENT "
  sleep_seconds = 0
  #pprint.pprint(list_of_pdblists)
  for indx, pdbs in enumerate(list_of_pdblists):
    num = '{0:0>4}'.format(indx)
    #out.write("SUBDAG EXTERNAL "+num+" moldag"+num+".dag\n")
    #out.write("Jobstate_log logs/mol"+num+".jobstate.log\n")
  
    for buildtype in ("orig", "build", "nobuild"):
      if buildtype != "orig":
        build_flag = "build"
        if buildtype == "nobuild":
          build_flag = "nobuild9999"
        out.write("Job reducer"+buildtype+num+" reduce.sub\n")
        out.write("VARS reducer"+buildtype+num+" ARGS= \""+" ".join((repr(sleep_seconds), build_flag, bondtype, num+"orig.tar.gz"))+"\"\n")
        out.write("VARS reducer"+buildtype+num+" PDBTARPATH= \""+os.path.join(out_pdb_dir, num, "orig", num+"orig.tar.gz")+"\"\n")
        out.write("VARS reducer"+buildtype+num+" NUMBER=\""+buildtype+num+"\"\n")
        out.write("VARS reducer"+buildtype+num+" OUTPUTFILES=\""+num+build_flag+".tar.gz"+"\"\n")
        out.write("VARS reducer"+buildtype+num+" REMAPS=\""+num+build_flag+".tar.gz="+os.path.join("pdbs", num, "reduce-"+buildtype, num+buildtype+".tar.gz")+"\"\n")
        out.write("RETRY reducer"+buildtype+num+" 3\n\n")

        if sleep_seconds > 30:
          sleep_seconds = sleep_seconds + .1
        else:
          sleep_seconds = sleep_seconds + .5
        if sleep_seconds > 300:
          sleep_seconds = 300

      parent_childs = "" # create parent_childs part of dag file now so only have to loop once thru pdbs
      oneline_remaps = [] # list of all outputs and their mappings to destination directories
      residuer_remaps = [] # list of all outputs and their mappings to destination directories
      oneline_outputs = [] # simple list of all the names of output tar.gz
      residuer_outputs = [] # simple list of all the names of output tar.gz
      #relative_pdbs = []
      # this is for the remaps to transfer analysis results files to the correct directories
      pdb_id_set = set()
      for pdb_file in pdbs:
        base_pdb = os.path.basename(pdb_file)
        if base_pdb.startswith("pdb"):
          base_pdb = base_pdb[3:]
        if ".ent" in base_pdb:
          base_pdb = base_pdb.split(".ent")[0]
        elif ".pdb" in base_pdb:
          base_pdb = base_pdb.split(".pdb")[0]
        else:
          sys.stderr.write("Could not detect .ent or .pdb in "+pdb_file+" file name; this should not happen!\\n")
          sys.exit(1)
        pdb_id_set.add(base_pdb[0:4])
      
      for pdb_id in pdb_id_set:
        oneline_remaps.append(pdb_id+"-"+buildtype+"oneline.tar.gz"+os.path.join("=results",pdb_id[1:3],pdb_id+"-"+buildtype+"oneline.tar.gz")) # for transferring output to results folder
        residuer_remaps.append(pdb_id+"-"+buildtype+"residue.tar.gz"+os.path.join("=results",pdb_id[1:3],pdb_id+"-"+buildtype+"residue.tar.gz"))
        residuer_remaps.append(pdb_id+"-"+buildtype+"-residue.csv"+os.path.join("=results",pdb_id[1:3],pdb_id+"-"+buildtype+"-residue.csv"))
        oneline_outputs.append(pdb_id+"-"+buildtype+"oneline.tar.gz")
        residuer_outputs.append(pdb_id+"-"+buildtype+"residue.tar.gz")
        residuer_outputs.append(pdb_id+"-"+buildtype+"-residue.csv")
        if sans_tgz != "none":
          # oneline generates one str file per PDB ID
          oneline_remaps.append(pdb_id+"-"+buildtype+"oneline.str"+os.path.join("=results",pdb_id[1:3],pdb_id+"-"+buildtype+"oneline.str")) 
          oneline_outputs.append(pdb_id+"-"+buildtype+"oneline.str")
          # residuer generates one str file per model
          residuer_remaps.append(pdb_id+"-"+buildtype+"-residue-str.csv"+os.path.join("=results",pdb_id[1:3],pdb_id+"-"+buildtype+"-residue-str.csv")) 
          residuer_outputs.append(pdb_id+"-"+buildtype+"-residue-str.csv")
          residuer_remaps.append(pdb_id+"-"+buildtype+"-residue-str.csv.header"+os.path.join("=results",pdb_id[1:3],pdb_id+"-"+buildtype+"-residue-str.csv.header")) 
          residuer_outputs.append(pdb_id+"-"+buildtype+"-residue-str.csv.header")
          
      out.write("Job oneline"+buildtype+num+" oneline.sub\n")
      out.write("VARS oneline"+buildtype+num+" PDBTAR=\""+num+buildtype+".tar.gz"+"\"\n")
      if sans_tgz != "none":
        out.write("VARS oneline"+buildtype+num+" SANSTAR=\""+os.path.basename(sans_tgz)+"\"\n")
        #out.write("VARS oneline"+buildtype+num+" SANSOUTTAR=\""+_____________+"\"\n")
      out.write("VARS oneline"+buildtype+num+" NUMBER=\""+buildtype+num+"\"\n")
      if buildtype == "orig":
        out.write("VARS oneline"+buildtype+num+" BUILD=\"orig\"\n")
        out.write("VARS oneline"+buildtype+num+" PDBTARPATH=\""+os.path.join(out_pdb_dir, num, buildtype, num+buildtype+".tar.gz")+"\"\n")
      else:
        out.write("VARS oneline"+buildtype+num+" BUILD=\""+buildtype+"\"\n")
        out.write("VARS oneline"+buildtype+num+" PDBTARPATH=\""+os.path.join(out_pdb_dir, num, "reduce-"+buildtype, num+buildtype+".tar.gz")+"\"\n")
      out.write("VARS oneline"+buildtype+num+" OUTPUTFILES=\""+",".join(oneline_outputs)+"\"\n")
      out.write("VARS oneline"+buildtype+num+" REMAPS=\""+";".join(oneline_remaps)+"\"\n")
      if buildtype != "orig":
        out.write("PARENT reducer"+buildtype+num+" CHILD oneline"+buildtype+num+"\n")
      out.write("RETRY oneline"+buildtype+num+" 3\n\n")
      postjobs = postjobs+"oneline"+buildtype+num + " "
      
      out.write("Job residuer"+buildtype+num+" residuer.sub\n")
      out.write("VARS residuer"+buildtype+num+" PDBTAR=\""+num+buildtype+".tar.gz"+"\"\n")
      if sans_tgz != "none":
        out.write("VARS residuer"+buildtype+num+" SANSTAR=\""+os.path.basename(sans_tgz)+"\"\n")
      #out.write("VARS residuer"+buildtype+num+" DIR=\""+os.path.join(out_pdb_dir, num, "reduce-"+buildtype)+"\"\n")
      out.write("VARS residuer"+buildtype+num+" NUMBER=\""+buildtype+num+"\"\n")
      if buildtype == "orig":
        out.write("VARS residuer"+buildtype+num+" BUILD=\"orig\"\n")
        out.write("VARS residuer"+buildtype+num+" PDBTARPATH=\""+os.path.join(out_pdb_dir, num, buildtype, num+buildtype+".tar.gz")+"\"\n")
      else:             
        out.write("VARS residuer"+buildtype+num+" BUILD=\""+buildtype+"\"\n")
        out.write("VARS residuer"+buildtype+num+" PDBTARPATH=\""+os.path.join(out_pdb_dir, num, "reduce-"+buildtype, num+buildtype+".tar.gz")+"\"\n")
      out.write("VARS residuer"+buildtype+num+" OUTPUTFILES=\""+",".join(residuer_outputs)+"\"\n")
      out.write("VARS residuer"+buildtype+num+" REMAPS=\""+";".join(residuer_remaps)+"\"\n")
      if buildtype != "orig":
        out.write("PARENT reducer"+buildtype+num+" CHILD residuer"+buildtype+num+"\n")
      out.write("RETRY residuer"+buildtype+num+" 3\n\n")
      postjobs = postjobs+"residuer"+buildtype+num + " "
    
    #out.write("Job onelineorig"+num+" oneline.sub\n")
    ##out.write("VARS oneline"+buildtype+num+" PDBS=\""+" ".join(pdbs)+"\"\n")
    #out.write("VARS onelineorig"+num+" PDBTAR=\""+num+"orig.tar.gz"+"\"\n")
    #if not sans_tgz is "none":
    #  out.write("VARS onelineorig"+num+" SANSTAR=\""+os.path.basename(sans_tgz)+"\"\n")
    #out.write("VARS onelineorig"+num+" PDBTARPATH=\""+os.path.join(out_pdb_dir, num, "orig", num+"orig.tar.gz")+"\"\n")
    ##out.write("VARS onelineorig"+num+" DIR=\""+os.path.join(out_pdb_dir, num, "orig")+"\"\n")
    #out.write("VARS onelineorig"+num+" NUMBER=\"orig"+num+"\"\n")
    #out.write("VARS onelineorig"+num+" BUILD=\"na\"\n")
    #out.write("VARS onelineorig"+num+" OUTPUTFILES=\""+",".join(oneline_outputs)+"\"\n")
    #out.write("VARS onelineorig"+num+" REMAPS=\""+";".join(oneline_remaps)+"\"\n")
    #out.write("RETRY onelineorig"+num+" 3\n\n")
    #postjobs = postjobs+"onelineorig"+num + " "
    
    #out.write("PARENT onelineorig"+num+" CHILD onelinebuild"+num+"\n")
    #out.write("PARENT onelinebuild"+num+" CHILD onelinenobuild"+num+"\n\n")
    
#    out.write("Job residuerorig"+num+" residuer.sub\n")
#    #out.write("VARS oneline"+buildtype+num+" PDBS=\""+" ".join(pdbs)+"\"\n")
#    out.write("VARS residuerorig"+num+" DIR=\""+os.path.join(out_pdb_dir, num, "orig")+"\"\n")
#    out.write("VARS residuerorig"+num+" NUMBER=\"orig"+num+"\"\n")
#    out.write("VARS residuerorig"+num+" BUILD=\"na\"\n")
#    out.write("PARENT onelineorig"+num+" CHILD residuerorig"+num+"\n")
#    out.write("RETRY residuerorig"+num+" 3\n\n")
#    postjobs = postjobs+"residuerorig"+num + " "
#    
#    #out.write("PARENT residuerorig"+num+" CHILD residuerbuild"+num+"\n")
#    out.write("PARENT residuerorig"+num+" residuerbuild"+num+" CHILD residuernobuild"+num+"\n\n")
#
#    if sans_exists:
#      out.write("Job starwrite"+num+" starwrite.sub\n")
#      out.write("VARS starwrite"+num+" DIR=\""+os.path.join(out_pdb_dir, num, "orig")+"\"\n")
#      out.write("VARS starwrite"+num+" NUMBER=\""+num+"\"\n")
#      out.write("PARENT onelinernobuild"+num+" residuernobuild"+num+" CHILD starwrite"+num+"\n\n")
#      postjobs = postjobs+"starwrite"+num+" "
#    
#  #post processing 
#  out.write("JOB post post_process.sub\n")
#  out.write(postjobs+"CHILD post\n")
  
  #sync results to the BMRB ftp 
#  weekly_run_dir = os.path.dirname(os.path.dirname(os.path.dirname(os.path.realpath(outdir))))
#  if not update_bmrb_loc == "none":
#    out.write("\nJob mpsync "+os.path.realpath(update_bmrb_loc)+"\n")
#    out.write("VARS mpsync DIR=\""+weekly_run_dir+"\"\n")
#    out.write("PARENT post CHILD mpsync\n")
  
  out.close()
#}}}

#{{{ write_reduce_sh
reduce_sh = """#!/bin/sh

buildtype=$2
bondtype=$3
tarfile=$4

# Get the number of seconds to sleep before running
sleepseconds=$1

sleep $sleepseconds

#removes first argument
shift
shift
shift
shift
#shopt -s nullglob
#for dir in "$@"
#do
echo `ls` >&2
echo $tarfile >&2
tar -xvf $tarfile # untar files in the local condor directory.
echo `ls` >&2
for pdb in ./*.pdb
do
sleep 3
echo $pdb >&2
pdbbase=`basename $pdb .pdb` #should be just the name of the pdb without the .pdb extension
echo $pdbbase >&2
if [ $buildtype = "build" ]
  then
    pdbbase="${pdbbase}F"
fi
# deletes file if it already exists, I think some jobs were failing because
# condor would evict reduce jobs and some empty PDB files were getting made
# but then wouldn't ever get fixed.
#if [ -f ${outdir}/${pdbbase}H.pdb ]
#  then
#    echo "${outdir}/${pdbbase}H.pdb seems to exist, deleting to make sure" >&2
#    filesize=$(wc -c < ${outdir}/${pdbbase}H.pdb)
#    #if [ $filesize -le 1000 ]
#    #  then
#    #    echo "${outdir}/${pdbbase}H.pdb seems to be very small, deleting" >&2
#    rm ${outdir}/${pdbbase}H.pdb
#    #fi
#fi
if [ ! -f ${pdbbase}H.pdb ]
  then
    echo "Error code pre reduce: $?" >&2
    echo "trying to write PDB ${pdbbase}H.pdb" >&2
    ./reduce -q -trim $pdb | ./reduce -q -${buildtype} -${bondtype} - 1> ${pdbbase}H.pdb 2>&2
    echo "Error code post reduce: $?" >&2
    rm $pdb
    trimsize=$(./reduce -q -trim $pdb | wc -c)
    if [ $trimsize == 0 ]
      then
        echo "reduce trim seems to have created an empty file for ${pdbbase}H.pdb, please check" >&2
        exit 5
    fi
    hsize=$(wc -c < ${pdbbase}H.pdb)
    if [ $hsize == 0 ]
      then
        echo "reduce seems to have created an empty file: ${outdir}/${pdbbase}H.pdb, please check" >&2 
        exit 6
    fi
fi
done
basenum=`basename $tarfile orig.tar.gz`
echo $basenum >&2
tar -zcf "${basenum}${buildtype}.tar.gz" *.pdb >&2
rm *.pdb
#done
"""
#}}}

#{{{ reduce_sub
reduce_sub = """universe = vanilla

Notify_user  = vbchen@bmrb.wisc.edu
notification = Error

{req}

Executable     = reduce.sh
should_transfer_files = YES
when_to_transfer_output = ON_EXIT
transfer_input_files = {0}/bin/linux/reduce,{0}/lib/reduce_wwPDB_het_dict.txt,$(PDBTARPATH)
transfer_output_files = $(OUTPUTFILES)
transfer_output_remaps = "$(REMAPS)"

copy_to_spool   = False
priority        = 0

Arguments       = $(ARGS)
log         = logs/reduce$(NUMBER).log
error      = logs/reduce$(NUMBER).err
queue
"""
#}}}

#{{{ write_file
def write_file(outdir, out_name, file_text, permissions=0644):
  outfile = os.path.join(os.path.realpath(outdir), out_name)
  out=open(outfile, 'wr')
  out.write(file_text)
  out.close()
  os.chmod(outfile, permissions)
#}}}

#{{{ write_prepare_results_dir
results_dir = """#!/usr/bin/python

"""
#}}}

#{{{ write_analysis_py
#From Jon Wedell
analysis_py = """#!/usr/bin/python

import sys
import subprocess
import os
import time
import tarfile

# Run a command without blocking
def syscmd(outfile, *commands):
    if outfile != subprocess.PIPE:
        outfile = open(outfile, "w")
    return subprocess.Popen(list(commands),stdout=outfile,stderr=subprocess.PIPE, stdin=subprocess.PIPE)

# Wait for a subprocess to finish and print it's stderr if it exists
def reap(the_cmd, pdb):
    the_cmd.wait()
    err = the_cmd.stderr.read()
    if err != "":
        sys.stderr.write(pdb+" had the following error in reap\\n"+err)

#apparently if the output gets too long the system can hang due to the pipes overflowing
def long_reap(the_cmd, pdb):
    results = the_cmd.communicate()
    err = results[1]
    if err != "":
        sys.stderr.write(pdb+" had the following error in long reap\\n"+err)
    return results[0]

if not os.path.exists("results"):
    os.makedirs("results")

#build_type = "\\""+sys.argv[1]+"\\""
build_type = sys.argv[1]
#print build_type
#print type(build_type)

for pdbtarname in sys.argv[2:]: #should also untar the sans parser if that option is being used
    pdbtar = tarfile.open(pdbtarname, "r:gz")
    pdbtar.extractall()
    pdbtar.close()

pdb_dict = dict()

sys.stderr.write("before: "+";".join(os.listdir("."))+"\\n")

for pdb in sorted([f for f in os.listdir('.') if f.endswith(".pdb")]):
    #base_con_dir = os.path.dirname(os.path.dirname(os.path.dirname(dir_name)))
    sys.stderr.write("starting "+pdb+" {script} analysis\\n")
    s_time = time.time()
    pdbbase = pdb[:-4]
    model_num = pdbbase.split("_")[1][0:3]
    pdb_code = pdbbase[:4]
    if pdb_code in pdb_dict:
        pdb_dict[pdb_code].append(pdbbase)
    else:
        pdb_dict[pdb_code] = [pdbbase]
    
    #if not os.path.exists("results/"+pdb_code):
    #    os.makedirs("results/"+pdb_code)
    
    reap(syscmd(pdbbase+"-clashlist", "./clashlist-htcondor", pdb, '40', '10', '{bondtype}'), pdbbase)
    reap(syscmd(pdbbase+"-ramalyze","java","-cp","./chiropraxis.jar", "chiropraxis.rotarama.Ramalyze", "-raw", "-quiet", pdb), pdbbase)
    reap(syscmd(pdbbase+"-rotalyze","java","-cp","./chiropraxis.jar", "chiropraxis.rotarama.Rotalyze", pdb), pdbbase)
    reap(syscmd(pdbbase+"-dangle_rna","java","-cp","./dangle.jar", "dangle.Dangle", "-rna", "-validate", "-outliers", "-sigma=0.0", pdb), pdbbase)
    reap(syscmd(pdbbase+"-dangle_protein","java","-cp","./dangle.jar", "dangle.Dangle", "-protein", "-validate", "-outliers", "-sigma=0.0", pdb), pdbbase)
    reap(syscmd(pdbbase+"-dangle_dna","java","-cp","./dangle.jar", "dangle.Dangle", "-dna", "-validate", "-outliers", "-sigma=0.0", pdb), pdbbase)
    reap(syscmd(pdbbase+"-dangle_ss","java","-cp","./dangle.jar", "dangle.Dangle", "ss", pdb), pdbbase)
    reap(syscmd(pdbbase+"-dangle_tauomega","java","-cp","./dangle.jar", "dangle.Dangle", "tau", "omega", pdb), pdbbase)
    reap(syscmd(pdbbase+"-dangle_maxb","java","-cp","./dangle.jar", "dangle.Dangle", "maxb maxB /..../", pdb), pdbbase)
    reap(syscmd(pdbbase+"-prekin_pperp","./prekin-static", "-pperptoline", "-pperpdump", pdb), pdbbase)
    reap(syscmd(pdbbase+"-cbdev", "./prekin-static", "-cbdevdump", pdb), pdbbase)
    
    cmd1 = syscmd(subprocess.PIPE, "java","-Xmx512m", "-cp","./dangle.jar", "dangle.Dangle", "rnabb", pdb)
    cmd1_out = cmd1.stdout.read()
    cmd1.wait()
    cmd2 = syscmd(pdbbase+"-suitename", "./suitename", "-report")
    cmd2.stdin.write(cmd1_out)
    cmd2.stdin.flush()
    cmd2.stdin.close()
    reap(cmd1, pdbbase)
    reap(cmd2, pdbbase)
    
    #full_path_results = os.path.join(base_con_dir, "results/")
    
    cmd9 = syscmd(subprocess.PIPE, "./{script}", "-q",{analyze_opt} pdb, model_num,
                  pdbbase+"-clashlist", 
                  pdbbase+"-cbdev", 
                  pdbbase+"-rotalyze", 
                  pdbbase+"-ramalyze", 
                  pdbbase+"-dangle_protein", 
                  pdbbase+"-dangle_rna", 
                  pdbbase+"-dangle_dna", 
                  pdbbase+"-prekin_pperp", 
                  pdbbase+"-suitename",
                  pdbbase+"-dangle_maxb",
                  pdbbase+"-dangle_tauomega",
                  pdbbase+"-dangle_ss",
                  pdb_code[1:3],
                  "{bondtype}",
                  build_type)
    print long_reap(cmd9, pdbbase).strip()
    #print cmd9.stdout.read().strip()

    e_time = time.time()
    sys.stderr.write(repr(e_time - s_time) + " seconds for {script} of "+pdbbase+"\\n")

#script_name = os.path.basename("{script}")
for pdb_code in iter(pdb_dict):
    tar = tarfile.open(pdb_code+"-"+build_type+"{script_type}.tar.gz", "w:gz")
    if os.path.isfile(pdb_code+"-{script_type}.str"):
        tar.add(pdb_code+"-{script_type}.str")
    for pdbbase in pdb_dict[pdb_code]:
        try:
            tar.add(pdbbase+"-clashlist")
            tar.add(pdbbase+"-cbdev")
            tar.add(pdbbase+"-rotalyze")
            tar.add(pdbbase+"-ramalyze")
            tar.add(pdbbase+"-dangle_protein")
            tar.add(pdbbase+"-dangle_rna")
            tar.add(pdbbase+"-dangle_dna")
            tar.add(pdbbase+"-prekin_pperp")
            tar.add(pdbbase+"-suitename")
            tar.add(pdbbase+"-dangle_maxb")
            tar.add(pdbbase+"-dangle_tauomega")
            tar.add(pdbbase+"-dangle_ss")
            # python seems to be ok with removing files before the tar file is closed.
            os.remove(pdbbase+"-clashlist")
            os.remove(pdbbase+"-cbdev")
            os.remove(pdbbase+"-rotalyze")
            os.remove(pdbbase+"-ramalyze")
            os.remove(pdbbase+"-dangle_protein")
            os.remove(pdbbase+"-dangle_rna")
            os.remove(pdbbase+"-dangle_dna")
            os.remove(pdbbase+"-prekin_pperp")
            os.remove(pdbbase+"-suitename")
            os.remove(pdbbase+"-dangle_maxb")
            os.remove(pdbbase+"-dangle_tauomega")
            os.remove(pdbbase+"-dangle_ss")
        except OSError as e:
            sys.stderr.write("A {script} result file seems to be missing for "+pdbbase+"\\n")
            sys.stderr.write(e)
    tar.close()
sys.stderr.write("after run: "+";".join(os.listdir("."))+"\\n")
"""
#}}}

#{{{ write_analyze_sub
analyze_sub = """universe = vanilla

Notify_user  = vbchen@bmrb.wisc.edu
notification = Error

{req}

Executable  = {script_type}.py
Arguments   = $(BUILD) $(PDBTAR) $(SANSTAR)

should_transfer_files = YES
when_to_transfer_output = ON_EXIT
transfer_input_files = $(PDBTARPATH),{0}/bin/linux/probe,{0}/bin/linux/cluster,{0}/cmdline/molprobity-htc/clashlist-htcondor,{0}/lib/chiropraxis.jar,{0}/lib/dangle.jar,{0}/bin/linux/prekin-static,{0}/bin/linux/suitename,{0}/cmdline/molprobity-htc/{script},{sans_transfer}
transfer_output_files = $(OUTPUTFILES)
transfer_output_remaps = "$(REMAPS)"

#limits job run times to 6 hours
maxRunTime = 21600
periodic_remove = JobStatus == 2 && \\
 (((CurrentTime - EnteredCurrentStatus) + \\
   (RemoteWallClockTime - CumulativeSuspensionTime)) > $(maxRunTime) )

log         = logs/{script_type}$(NUMBER).log
output      = logs/{script_type}$(NUMBER).out
error       = logs/{script_type}$(NUMBER).err
copy_to_spool   = False
priority    = 0

queue
"""
#}}}

#{{{ write_post_star_sub
post_star_sub = """universe = vanilla

Notify_user  = vbchen@bmrb.wisc.edu
notification = Error

{req}

Executable     = starwrite.py

copy_to_spool   = False
priority        = 0

Arguments       = $(DIR)
log         = logs/starwrite$(NUMBER).log
error      = logs/starwrite$(NUMBER).err
queue
"""
#}}}

#{{{ write_post_star
post_star_writer = """#!/usr/bin/python

import sys
import subprocess
import os
import time
sys.path.append('{sans_loc}')
import bmrb

pdb_set = set()
for dir_name in sys.argv[1:]:
    for file_name in sorted(os.listdir(dir_name)):
        pdb = os.path.join(dir_name, file_name)
        pdbbase = os.path.basename(pdb)[:-4]
        model_num = pdbbase.split("_")[1][0:3]
        pdb_code = pdbbase[:4]
        pdb_set.add(pdb_code)
        print pdb_set
        
for pdb_code in pdb_set:
    s_time = time.time()
    if os.path.exists(os.path.join("results", pdb_code)):
        base_path = os.path.join("results", pdb_code)
        star_csv = os.path.join(base_path, pdb_code+"-residue-str.csv")
        star_save = os.path.join(base_path, pdb_code+"-residue-str.csv.header")
        if os.path.exists(star_save):
            saver = bmrb.saveframe.fromFile(star_save, True)
        else:
            sys.stderr.write("# ERROR: saveframe header file missing for: " + pdb_code+"\\n")
            sys.exit(1)
        if os.path.exists(star_csv):
            loop = bmrb.loop.fromFile(star_csv, True)
            saver.addLoop(loop)
        else:
            sys.stderr.write("# ERROR: loop file missing for: " + pdb_code+"\\n")
            sys.exit(1)
        with open(os.path.join(base_path, pdb_code+"-residue.str"), 'a+') as str_write:
            str_write.write(str(saver))
            #str_write.write(str(loop))
    else:
        sys.stderr.write("# ERROR: results missing for: " + pdb_code+"\\n")
        sys.exit(1)
    e_time = time.time()
    sys.stderr.write(repr(e_time - s_time) + " seconds(?) for star write of "+pdb_code+"\\n")
"""
#}}}

#{{{ post_sub
post_sub = """universe = local

Notify_user  = vbchen@bmrb.wisc.edu
notification = Error

Executable  = post_process.sh

log     = logs/post.log
error       = logs/post.err
copy_to_spool   = False
priority    = 0

queue
"""
#}}}

#{{{ write_post_sh
post_sh = """#!/bin/sh

cat logs/onelineorig*.out > logs/allonelineorig.out.csv
cat logs/onelineorig*.err > logs/allonelineorig.err
cat logs/onelinenobuild*.out > logs/allonelinenobuild.out.csv
cat logs/onelinenobuild*.err > logs/allonelinenobuild.err
cat logs/onelinebuild*.out > logs/allonelinebuild.out.csv
cat logs/onelinebuild*.err > logs/allonelinebuild.err
cat logs/residuerorig*.out > logs/allresiduerorig.out.csv
cat logs/residuerorig*.err > logs/allresiduerorig.err
cat logs/residuernobuild*.out > logs/allresiduernobuild.out.csv
cat logs/residuernobuild*.err > logs/allresiduernobuild.err
cat logs/residuerbuild*.out > logs/allresiduerbuild.out.csv
cat logs/residuerbuild*.err > logs/allresiduerbuild.err
tar -zcf pdbs.tgz pdbs/
rm -rf pdbs/
"""
#}}}

#{{{ prep_dirs
def prep_dirs(indir, update_scripts=False):
  if not os.path.exists(indir):
    sys.stderr.write(indir + " does not seem to exist!\n")
  else:
    indir_base = os.path.basename(os.path.realpath(indir))
    outdir = os.path.join(indir, "condor_sub_files_"+indir_base)
    #print update_scripts
    if os.path.exists(outdir) and not update_scripts:
      shutil.rmtree(outdir)
    if not os.path.exists(outdir):
      os.makedirs(outdir)
      os.makedirs(os.path.join(outdir,"logs"))
      os.makedirs(os.path.join(outdir,"results"))
      os.makedirs(os.path.join(outdir,"pdbs"))
    elif update_scripts:
      for tst_file in os.listdir(outdir):
        if tst_file.endswith(".sh") or tst_file.endswith(".py") or tst_file.endswith(".sub") or "dag" in tst_file:
          os.remove(os.path.join(outdir, tst_file))
      for tst_dir in os.listdir(os.path.join(outdir,"results")):
        shutil.rmtree(os.path.join(outdir,"results", tst_dir))
      for tst_file in os.listdir(os.path.join(outdir,"logs")):
        os.remove(os.path.join(outdir,"logs", tst_file))
      #sys.exit()
    #else:
    #  sys.stderr.write("\"condor_sub_files\" directory detected in \""+indir+"\", please delete it before running this script\n")
    #  sys.exit()
    return outdir
#}}}

#{{{ make_files
def make_files(indir, outdir, file_size_limit, bond_type, sans_location, do_requirement, update_scripts=False, update_bmrb_loc="none"):
  molprobity_home = os.path.dirname(os.path.dirname(os.path.dirname(os.path.realpath(__file__))))
  #print "mp home: " + molprobity_home
  #print "indir: " + indir
  #print update_bmrb
  #print(outdir)
  list_of_lists = divide_pdbs(indir, outdir, file_size_limit)
  #print(list_of_lists)

  if not update_scripts:
    print("splitting")
    split_pdbs_to_dirs(outdir, list_of_lists)

  #pdb = "{pdbbase}"
  #build = "nobuild"
  #if build_type=="build":
  #  pdb = "{pdbbase}F"
  #  build = "build"
  #print do_requirement
  if do_requirement == "bmrb":
    reduce_req = "requirements = ((TARGET.FileSystemDomain == \"bmrb.wisc.edu\") || (TARGET.FileSystemDomain == \".bmrb.wisc.edu\"))"
    analysis_req = "requirements = ((TARGET.FileSystemDomain == \"bmrb.wisc.edu\") || (TARGET.FileSystemDomain == \".bmrb.wisc.edu\") && HasJava)"
  else:
    reduce_req = "requirements = (OpSys == \"LINUX\" && Arch == \"X86_64\")"
    analysis_req = "requirements = (HasJava && OpSys == \"LINUX\" && Arch == \"X86_64\")"
  #print condor_req+" is req"
  ana_opt = ""
  sans_file_transfer = ""
  sans_exists = not sans_location is "none"
  if sans_exists: # inject sans-python client-side directory name into scripts.
    ana_opt = " \"-s\", './python',"
    sans_file_transfer = sans_location
  write_mpanalysis_dag(outdir, list_of_lists, bond_type, sans_location, update_bmrb_loc)
  write_file(outdir, "reduce.sh", reduce_sh.format(molprobity_home, buildtype="{buildtype}", bondtype="{bondtype}", outdir="{outdir}", pdbbase="{pdbbase}", basenum="{basenum}"), 0755)
  write_file(outdir, "reduce.sub", reduce_sub.format(molprobity_home, req=reduce_req))
  write_file(outdir, "oneline.py", analysis_py.format(molprobity_home, bondtype=bond_type, analyze_opt=ana_opt, sans_transfer=sans_file_transfer, script="molparser.py", script_type="oneline"), 0755)
  write_file(outdir, "residuer.py", analysis_py.format(molprobity_home, bondtype=bond_type, analyze_opt=ana_opt, script="py-residue-analysis.py", script_type="residue"), 0755)
  write_file(outdir, "oneline.sub", analyze_sub.format(molprobity_home, req=analysis_req, sans_transfer=sans_file_transfer, script="molparser.py", script_type="oneline"))
  #this one includes molparser.py (for file transfer) since residuer requires molparser
  write_file(outdir, "residuer.sub", analyze_sub.format(molprobity_home, req=analysis_req, sans_transfer=sans_file_transfer, script="py-residue-analysis.py,"+molprobity_home+"/cmdline/molprobity-htc/molparser.py", script_type="residuer"))
  if sans_exists:
    write_file(outdir, "starwrite.sub", post_star_sub.format(req=reduce_req))
    write_file(outdir, "starwrite.py", post_star_writer.format(sans_loc=sans_location), 0755)
  write_file(outdir, "post_process.sh", post_sh, 0755)
  #write_file(outdir, "local_run.sh", local_run.format(molprobity_home, pdbbase="{pdbbase}"), 0755)
  #write_file(outdir, "local_run.py", local_run_py.format(molprobity_home), 0755)
  write_file(outdir, "post_process.sub", post_sub)
  #write_file(outdir, "clashlist.sh", clash_sh.format(bondtype=opts.bond_type, pdb="{pdb}", pdbbase="{pdbbase}"), 0755)
  #write_file(outdir, "clashlist.sub", clash_sub.format(molprobity_home))
    

#}}}

if __name__ == "__main__":

  opts, indir = parse_cmdline()
  outdir = prep_dirs(indir, opts.update_scripts)
  make_files(indir, outdir, opts.total_file_size_limit, opts.bond_type, opts.sans_location, opts.requirement, opts.update_scripts, opts.update_bmrb_website)
