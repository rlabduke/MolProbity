#!/usr/bin/python
# (jEdit options) :folding=explicit:collapseFolds=1:
import sys, os, getopt, re, subprocess, shutil
from optparse import OptionParser
import time
import gzip
import pprint
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
    dest="update_scripts", default="false",
    help="update scripts in a prexisting folder with new versions")
  #parser.add_option("-r", "--reduce", action="store", type="boolean",
  #  dest="run_reduce", default="false",
  #  help="run reduce to add hydrogens first (use -t to specify length)")
  #parser.add_option("-b", "--build", action="store", type="string",
  #  dest="build_type", default="default",
  #  help="specify whether to use -build or -nobuild for running reduce")
  opts, args = parser.parse_args()
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
  #print pdb_name+"\n"
  if pdb_name.startswith("pdb") and pdb_name.endswith(".ent"):
    pdb_name = pdb_name[3:-4]
    
  if not os.path.exists(os.path.join(outdir, "results", pdb_name[:4])):
    os.makedirs(os.path.join(outdir, "results", pdb_name[:4]))
    
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
        if (ext == ".gz"):
          root2, ext = os.path.splitext(root)
          if (ext == ".ent"):
            if (list_size <= size_limit):
              pdb_list.append(full_file)
              #print pdb_list
              list_size = list_size + os.path.getsize(full_file)/0.6 # assumming ~60%compression by gzip
            else: 
              pdb_list = []
              pdb_list.append(full_file)
              list_of_lists.append(pdb_list)
              list_size = os.path.getsize(full_file)/0.6
    if len(list_of_lists) > 10000:
      sys.stderr.write("\n**ERROR: More than 10000 jobs needed, try choosing a larger -limit\n")
      sys.exit()
    #pprint.pprint(list_of_lists)
    return list_of_lists
    #print list_of_lists
#}}}

#{{{ split_pdbs_to_dirs
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
def write_mpanalysis_dag(outdir, list_of_pdblists, bondtype):
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
  pprint.pprint(list_of_pdblists)
  for indx, pdbs in enumerate(list_of_pdblists):
    num = '{0:0>4}'.format(indx)
    #out.write("SUBDAG EXTERNAL "+num+" moldag"+num+".dag\n")
    #out.write("Jobstate_log logs/mol"+num+".jobstate.log\n")
  
    for buildtype in ("build", "nobuild"):
      out.write("Job reducer"+buildtype+num+" reduce.sub\n")
      out.write("VARS reducer"+buildtype+num+" ARGS= \""+" ".join((repr(sleep_seconds), os.path.join(out_pdb_dir, num, "reduce-"+buildtype), buildtype, bondtype, os.path.join(out_pdb_dir, num, "orig")))+"\"\n")
      out.write("VARS reducer"+buildtype+num+" NUMBER=\""+buildtype+num+"\"\n\n")

      if sleep_seconds > 120:
        sleep_seconds = sleep_seconds + 1
      else:
        sleep_seconds = sleep_seconds + 4

      parent_childs = "" # create parent_childs part of dag file now so only have to loop once thru pdbs
      base_pdbs = []
      #pdb_remaps = []
      #relative_pdbs = []
      for pdb_file in pdbs:
        base_pdbs.append(os.path.basename(pdb_file))
        base_pdb, ext = os.path.splitext(os.path.basename(pdb_file))
        
      out.write("Job oneline"+buildtype+num+" oneline.sub\n")
      #out.write("VARS oneline"+buildtype+num+" PDBS=\""+" ".join(pdbs)+"\"\n")
      out.write("VARS oneline"+buildtype+num+" DIR=\""+os.path.join(out_pdb_dir, num, "reduce-"+buildtype)+"\"\n")
      out.write("VARS oneline"+buildtype+num+" NUMBER=\""+buildtype+num+"\"\n")
      out.write("VARS oneline"+buildtype+num+" BUILD=\""+buildtype+"\"\n")
      out.write("PARENT reducer"+buildtype+num+" CHILD oneline"+buildtype+num+"\n")
      out.write("RETRY oneline"+buildtype+num+" 3\n\n")
      postjobs = postjobs+"oneline"+buildtype+num + " "
      
      out.write("Job residuer"+buildtype+num+" residuer.sub\n")
      out.write("VARS residuer"+buildtype+num+" DIR=\""+os.path.join(out_pdb_dir, num, "reduce-"+buildtype)+"\"\n")
      out.write("VARS residuer"+buildtype+num+" NUMBER=\""+buildtype+num+"\"\n")
      out.write("VARS residuer"+buildtype+num+" BUILD=\""+buildtype+"\"\n")
      out.write("PARENT reducer"+buildtype+num+" oneline"+buildtype+num+" CHILD residuer"+buildtype+num+"\n")
      out.write("RETRY residuer"+buildtype+num+" 3\n\n")
      postjobs = postjobs+"residuer"+buildtype+num + " "
    
    out.write("Job onelineorig"+num+" oneline.sub\n")
    #out.write("VARS oneline"+buildtype+num+" PDBS=\""+" ".join(pdbs)+"\"\n")
    out.write("VARS onelineorig"+num+" DIR=\""+os.path.join(out_pdb_dir, num, "orig")+"\"\n")
    out.write("VARS onelineorig"+num+" NUMBER=\"orig"+num+"\"\n")
    out.write("VARS onelineorig"+num+" BUILD=\"na\"\n")
    out.write("RETRY onelineorig"+num+" 3\n\n")
    postjobs = postjobs+"onelineorig"+num + " "
    
    out.write("Job residuerorig"+num+" residuer.sub\n")
    #out.write("VARS oneline"+buildtype+num+" PDBS=\""+" ".join(pdbs)+"\"\n")
    out.write("VARS residuerorig"+num+" DIR=\""+os.path.join(out_pdb_dir, num, "orig")+"\"\n")
    out.write("VARS residuerorig"+num+" NUMBER=\"orig"+num+"\"\n")
    out.write("VARS residuerorig"+num+" BUILD=\"na\"\n")
    out.write("PARENT onelineorig"+num+" CHILD residuerorig"+num+"\n")
    out.write("RETRY residuerorig"+num+" 3\n\n")
    postjobs = postjobs+"residuerorig"+num + " "
    
  out.write("JOB post post_process.sub\n")
  out.write(postjobs+"CHILD post\n")
  out.close()
#}}}

#{{{ write_reduce_sh
reduce_sh = """#!/bin/sh

outdir=$2
buildtype=$3
bondtype=$4

# Get the number of seconds to sleep before running
sleepseconds=$1

sleep $sleepseconds

#removes first argument
shift
shift
shift
shift
#shopt -s nullglob
for dir in "$@"
do
for pdb in "$dir"/*.pdb
do
sleep 1
pdbbase=`basename $pdb .pdb` #should be just the name of the pdb without the .pdb extension
if [ $buildtype = "build" ]
  then
    pdbbase="${pdbbase}F"
fi
if [ ! -f ${outdir}/${pdbbase}H.pdb ]
  then
    echo "Error code pre reduce: $? \\n" >&2
    ./reduce -q -trim $pdb | ./reduce -q -${buildtype} -${bondtype} - > ${outdir}/${pdbbase}H.pdb
    echo "Error code post reduce: $? \\n" >&2
fi
done
done
"""
#}}}

#{{{ reduce_sub
reduce_sub = """universe = vanilla

Notify_user  = vbchen@bmrb.wisc.edu
notification = Error

#requirements = ((TARGET.FileSystemDomain == "bmrb.wisc.edu") || (TARGET.FileSystemDomain == ".bmrb.wisc.edu"))
#requirements = (machine == inspiron17)

Executable     = reduce.sh
should_transfer_files = YES
transfer_input_files = {0}/bin/linux/reduce,{0}/lib/reduce_wwPDB_het_dict.txt

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
        sys.stderr.write(pdb+" had the following error\\n"+err)

#apparently if the output gets too long the system can hang due to the pipes overflowing
def long_reap(the_cmd, pdb):
    results = the_cmd.communicate()
    err = results[1]
    if err != "":
        sys.stderr.write(pdb+" had the following error\\n"+err)
    return results[0]

if not os.path.exists("results"):
    os.makedirs("results")

#build_type = "\\""+sys.argv[1]+"\\""
build_type = sys.argv[1]
#print build_type
#print type(build_type)

for dir in sys.argv[2:]:
    base_con_dir = os.path.dirname(os.path.dirname(os.path.dirname(dir)))
    for file in sorted(os.listdir(dir)):
        pdb = os.path.join(dir, file)
        sys.stderr.write(pdb+" {script} analysis\\n")
        s_time = time.time()
        pdbbase = os.path.basename(pdb)[:-4]
        model_num = pdbbase.split("_")[1][0:3]
        pdb_code = pdbbase[:4]
        
        if not os.path.exists("results/"+pdb_code):
            os.makedirs("results/"+pdb_code)
        
        if not os.path.exists("results/"+pdb_code+"/"+pdbbase+"-clashlist"):
            reap(syscmd("results/"+pdb_code+"/"+pdbbase+"-clashlist", "./clashlist", pdb, '40', '10', '{bondtype}'), pdbbase)
        if not os.path.exists("results/"+pdb_code+"/"+pdbbase+"-ramalyze"):
            reap(syscmd("results/"+pdb_code+"/"+pdbbase+"-ramalyze","java","-cp","{0}/lib/chiropraxis.jar", "chiropraxis.rotarama.Ramalyze", "-raw", "-quiet", pdb), pdbbase)
        if not os.path.exists("results/"+pdb_code+"/"+pdbbase+"-rotalyze"):
            reap(syscmd("results/"+pdb_code+"/"+pdbbase+"-rotalyze","java","-cp","{0}/lib/chiropraxis.jar", "chiropraxis.rotarama.Rotalyze", pdb), pdbbase)
        if not os.path.exists("results/"+pdb_code+"/"+pdbbase+"-dangle_rna"):
            reap(syscmd("results/"+pdb_code+"/"+pdbbase+"-dangle_rna","java","-cp","{0}/lib/dangle.jar", "dangle.Dangle", "-rna", "-validate", "-outliers", "-sigma=0.0", pdb), pdbbase)
        if not os.path.exists("results/"+pdb_code+"/"+pdbbase+"-dangle_protein"):
            reap(syscmd("results/"+pdb_code+"/"+pdbbase+"-dangle_protein","java","-cp","{0}/lib/dangle.jar", "dangle.Dangle", "-protein", "-validate", "-outliers", "-sigma=0.0", pdb), pdbbase)
        if not os.path.exists("results/"+pdb_code+"/"+pdbbase+"-dangle_dna"):
            reap(syscmd("results/"+pdb_code+"/"+pdbbase+"-dangle_dna","java","-cp","{0}/lib/dangle.jar", "dangle.Dangle", "-dna", "-validate", "-outliers", "-sigma=0.0", pdb), pdbbase)
        if not os.path.exists("results/"+pdb_code+"/"+pdbbase+"-dangle_ss"):
            reap(syscmd("results/"+pdb_code+"/"+pdbbase+"-dangle_ss","java","-cp","{0}/lib/dangle.jar", "dangle.Dangle", "ss", pdb), pdbbase)
        if not os.path.exists("results/"+pdb_code+"/"+pdbbase+"-dangle_tauomega"):
            reap(syscmd("results/"+pdb_code+"/"+pdbbase+"-dangle_tauomega","java","-cp","{0}/lib/dangle.jar", "dangle.Dangle", "tau", "omega", pdb), pdbbase)
        if not os.path.exists("results/"+pdb_code+"/"+pdbbase+"-dangle_maxb"):
            reap(syscmd("results/"+pdb_code+"/"+pdbbase+"-dangle_maxb","java","-cp","{0}/lib/dangle.jar", "dangle.Dangle", "maxb maxB /..../", pdb), pdbbase)
        if not os.path.exists("results/"+pdb_code+"/"+pdbbase+"-prekin_pperp"):
            reap(syscmd("results/"+pdb_code+"/"+pdbbase+"-prekin_pperp","{0}/bin/linux/prekin", "-pperptoline", "-pperpdump", pdb), pdbbase)
        if not os.path.exists("results/"+pdb_code+"/"+pdbbase+"-cbdev"):
            reap(syscmd("results/"+pdb_code+"/"+pdbbase+"-cbdev", "{0}/bin/linux/prekin", "-cbdevdump", pdb), pdbbase)
        
        if not os.path.exists("results/"+pdb_code+"/"+pdbbase+"-suitename"):
            cmd1 = syscmd(subprocess.PIPE, "java","-Xmx512m", "-cp","{0}/lib/dangle.jar", "dangle.Dangle", "rnabb", pdb)
            cmd1_out = cmd1.stdout.read()
            cmd1.wait()
            cmd2 = syscmd("results/"+pdb_code+"/"+pdbbase+"-suitename", "{0}/bin/linux/suitename", "-report")
            cmd2.stdin.write(cmd1_out)
            cmd2.stdin.flush()
            cmd2.stdin.close()
            reap(cmd1, pdbbase)
            reap(cmd2, pdbbase)
        
        full_path_results = os.path.join(base_con_dir, "results/")
        
        cmd9 = syscmd(subprocess.PIPE, "{0}/cmdline/{script}", "-q", pdb, model_num,
                      "results/"+pdb_code+"/"+pdbbase+"-clashlist", 
                      "results/"+pdb_code+"/"+pdbbase+"-cbdev", 
                      "results/"+pdb_code+"/"+pdbbase+"-rotalyze", 
                      "results/"+pdb_code+"/"+pdbbase+"-ramalyze", 
                      "results/"+pdb_code+"/"+pdbbase+"-dangle_protein", 
                      "results/"+pdb_code+"/"+pdbbase+"-dangle_rna", 
                      "results/"+pdb_code+"/"+pdbbase+"-dangle_dna", 
                      "results/"+pdb_code+"/"+pdbbase+"-prekin_pperp", 
                      "results/"+pdb_code+"/"+pdbbase+"-suitename",
                      "results/"+pdb_code+"/"+pdbbase+"-dangle_maxb",
                      "results/"+pdb_code+"/"+pdbbase+"-dangle_tauomega",
                      "results/"+pdb_code+"/"+pdbbase+"-dangle_ss",
                      full_path_results+pdb_code,
                      "{bondtype}",
                      build_type)
        print long_reap(cmd9, pdbbase).strip()
        #print cmd9.stdout.read().strip()
        e_time = time.time()
        sys.stderr.write(repr(e_time - s_time) + " seconds(?) for {script} of "+pdbbase+"\\n")
"""
#}}}

#{{{ write_analyze_sub
analyze_sub = """universe = vanilla

Notify_user  = vbchen@bmrb.wisc.edu
notification = Error

#requirements = ((TARGET.FileSystemDomain == "bmrb.wisc.edu") || (TARGET.FileSystemDomain == ".bmrb.wisc.edu"))

Executable  = {script}.py
Arguments   = $(BUILD) $(DIR)

should_transfer_files = YES
when_to_transfer_output = ON_EXIT
#transfer_input_files = $(PDBSINPUT),{0}/bin/linux/probe,{0}/bin/linux/cluster,{0}/bin/clashlist
transfer_input_files = {0}/bin/linux/probe,{0}/bin/linux/cluster,{0}/bin/clashlist
transfer_output_files = results
#transfer_output_remaps = "$(PDBREMAPS)"

maxRunTime = 7200
periodic_remove = JobStatus == 2 && \\
 (((CurrentTime - EnteredCurrentStatus) + \\
   (RemoteWallClockTime - CumulativeSuspensionTime)) > $(maxRunTime) )

log         = logs/{script}$(NUMBER).log
output      = logs/{script}$(NUMBER).out
error       = logs/{script}$(NUMBER).err
copy_to_spool   = False
priority    = 0

queue
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

#{{{ replace_condor_files
def replace_condor_files():
  print "this may not be as easy as originally thought"
#}}}

#{{{ make_files
def make_files(indir, file_size_limit, bond_type, update_scripts=False):
  molprobity_home = os.path.dirname(os.path.dirname(os.path.realpath(__file__)))
  #print "mp home: " + molprobity_home
  #print "indir: " + indir

  if os.path.exists(indir):
    indir_base = os.path.basename(os.path.realpath(indir))
    outdir = os.path.join(indir, "condor_sub_files_"+indir_base)
    if not os.path.exists(outdir):
      os.makedirs(outdir)
      os.makedirs(os.path.join(outdir,"logs"))
      os.makedirs(os.path.join(outdir,"results"))
      os.makedirs(os.path.join(outdir,"pdbs"))
    else:
      sys.stderr.write("\"condor_sub_files\" directory detected in \""+indir+"\", please delete it before running this script\n")
      sys.exit()
    #split_pdbs_to_models(indir, os.path.join(outdir, "pdbs"))
    #print opts.total_file_size_limit
    
    list_of_lists = divide_pdbs(indir, file_size_limit)

    split_pdbs_to_dirs(outdir, list_of_lists)

    #pdb = "{pdbbase}"
    #build = "nobuild"
    #if build_type=="build":
    #  pdb = "{pdbbase}F"
    #  build = "build"
    write_mpanalysis_dag(outdir, list_of_lists, bond_type)
    write_file(outdir, "reduce.sh", reduce_sh.format(molprobity_home, buildtype="{buildtype}", bondtype="{bondtype}", outdir="{outdir}", pdbbase="{pdbbase}"), 0755)
    write_file(outdir, "reduce.sub", reduce_sub.format(molprobity_home))
    write_file(outdir, "oneline.py", analysis_py.format(molprobity_home, bondtype=bond_type, script="molparser.py"), 0755)
    write_file(outdir, "residuer.py", analysis_py.format(molprobity_home, bondtype=bond_type, script="py-residue-analysis.py"), 0755)
    write_file(outdir, "oneline.sub", analyze_sub.format(molprobity_home, script="oneline"))
    write_file(outdir, "residuer.sub", analyze_sub.format(molprobity_home, script="residuer"))
    write_file(outdir, "post_process.sh", post_sh, 0755)
    #write_file(outdir, "local_run.sh", local_run.format(molprobity_home, pdbbase="{pdbbase}"), 0755)
    #write_file(outdir, "local_run.py", local_run_py.format(molprobity_home), 0755)
    write_file(outdir, "post_process.sub", post_sub)
    #write_file(outdir, "clashlist.sh", clash_sh.format(bondtype=opts.bond_type, pdb="{pdb}", pdbbase="{pdbbase}"), 0755)
    #write_file(outdir, "clashlist.sub", clash_sub.format(molprobity_home))
    
  else:
    sys.stderr.write(indir + " does not seem to exist!\n")
#}}}

if __name__ == "__main__":

  opts, indir = parse_cmdline()
  make_files(indir, opts.total_file_size_limit, opts.bond_type, opts.update_scripts)
