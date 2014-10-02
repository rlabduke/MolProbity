#!/usr/bin/python
# (jEdit options) :folding=explicit:collapseFolds=1:
import sys, os, getopt, re, subprocess, shutil
from optparse import OptionParser
import time
import gzip
import pprint
import make_condor_files2
#make_condor_files = imp.load_source("make_files", "make_condor_files2.py")
#divide_pdbs = imp.load_source("divide_pdbs", "make_condor_files2.py")

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
    help="sans parser location, needed for nmrstar output")
  parser.add_option("-r", "--requirement", action="store", dest="requirement",
    type="string", default="bmrb",
    help="requirements for limiting where condor jobs get submitted (none or bmrb)")
  parser.add_option("-w", "--web", action="store", dest="update_bmrb_website", 
    type="string", default="none",
    help="use this option to auto update results on BMRB website afterwards")
  parser.add_option("-c", "--core", action="store", dest="cyrange_location", 
    type="string", default="none",
    help="cyrange core calculation software location")
  #parser.add_option("-r", "--reduce", action="store", type="boolean",
  #  dest="run_reduce", default="false",
  #  help="run reduce to add hydrogens first (use -t to specify length)")
  #parser.add_option("-b", "--build", action="store", type="string",
  #  dest="build_type", default="default",
  #  help="specify whether to use -build or -nobuild for running reduce")
  opts, args = parser.parse_args()
  if not opts.sans_location is "none" and not os.path.isdir(opts.sans_location):
    sys.stderr.write("\n**ERROR: sans location must be a directory!\n")
    sys.exit(help())
  else:
    opts.sans_location = os.path.realpath(opts.sans_location)
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
      rebuilt_args = rebuild_args(parser, opts, os.path.realpath(indir))
      return opts, os.path.realpath(indir), rebuilt_args
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

#{{{ rebuild_args
def rebuild_args(parser, opts, indir):
  indir = os.path.realpath(indir)
  rebuilt_args = []
  for i, dest in enumerate([x.dest for x in parser._get_all_options()[1:]]):
    opt_value = opts.__dict__[dest]
    #print opt_value
    if not dest is "cyrange_location" and not opt_value == 'none' and not opt_value == False:
      rebuilt_args.append(parser._get_all_options()[1:][i].get_opt_string())
      if not opt_value == True:
        rebuilt_args.append(repr(opts.__dict__[dest]).replace("'", ""))
  return " ".join(rebuilt_args)
#}}}

#{{{ cyranger_py
cyranger_py = """#!/usr/bin/python
import sys, os, re, pprint, subprocess, gzip

# Run a command without blocking
def syscmd(outfile, *commands):
  if outfile != subprocess.PIPE:
    outfile = open(outfile, "w")
  #print(list(commands))
  # apparently when using shell=true with subprocess, you need to pass a string instead of a list of arguments
  return subprocess.Popen(" ".join(list(commands)),stdout=outfile,stderr=subprocess.PIPE, stdin=subprocess.PIPE, shell=True)

#apparently if the output gets too long the system can hang due to the pipes overflowing
def long_reap(the_cmd, pdb):
  results = the_cmd.communicate(pdb)
  err = results[1]
  if err != "":
    sys.stderr.write(pdb+" had the following error\\n"+err)
  return results[0]


def unzip_to_temp(pdb):
  path = os.path.dirname(os.path.realpath(pdb))
  pdb_name, ext = os.path.splitext(os.path.basename(pdb))
  if pdb_name.startswith("pdb") and pdb_name.endswith(".ent"):
    pdb_name = pdb_name[3:-4]
  pdb_in = gzip.open(pdb)
  full_pdb_out = os.path.join(path, pdb_name+".pdb")
  with open(full_pdb_out,"w+") as pdb_out:
    pdb_out.writelines(pdb_in)
  pdb_in.close()
  return full_pdb_out

def run_cyrange(pdb):
  cy_out = long_reap(syscmd(subprocess.PIPE, "./cyrange",pdb), pdb)
  chain_range_dict = {}
  for line in cy_out.split("\\n"):
    #print line
    if "Optimal" in line:
      for element in line.split(" "):
        if ".." in element:
          lower,upper = element.strip(",:").split("..")
          chain = ""
          low_match = re.match("[A-Za-z]", lower)
          upp_match = re.match("[A-Za-z]", upper)
          if low_match and upp_match:
            #use chain ID
            chain = lower[0:1]
            lower = lower[1:]
            upper = upper[1:]
          #print chain, lower, upper
          if chain in chain_range_dict.keys():
            old_range = chain_range_dict[chain]
            old_range.extend(range(int(lower), int(upper)+1))
            chain_range_dict[chain] = old_range
          else:
            chain_range_dict[chain] = range(int(lower), int(upper)+1)
  #print chain_range_dict.keys()
  if len(chain_range_dict.keys()) == 0:
    sys.stderr.write(pdb+" failed to generate cyrange results\\n")
  return chain_range_dict

def trim_pdb(range_dict, pdb_file, do_gzip = False):
  file_path, name = os.path.split(pdb_file)
  out_path = os.path.join(file_path, os.path.splitext(name)[0]+"-cyranged.pdb")
  with open(pdb_file) as f:
    if do_gzip:
      outfile = gzip.open(out_path+".gz", "wr")
    else:
      outfile = open(out_path, 'wr')
    for line in f:
      if line.startswith("MODEL") or line.startswith("END") or line.startswith("END"):
        outfile.write(line)
      if line.startswith("ATOM"):
        #print ":"+line[21]+":"
        #print ":"+line[22:26]+":"
        chain = line[21]
        res = line[22:26]
        if chain in range_dict.keys() and int(res) in range_dict[chain]:
          outfile.write(line)
        elif "" in range_dict.keys() and int(res) in range_dict[""]:
          outfile.write(line)
    outfile.close()

            
def process_file(f):
  from_zip = False
  if f.endswith(".gz"):
    from_zip = True
    f = unzip_to_temp(f)
  range_dict = run_cyrange(f)
  if len(range_dict.keys()) > 0:
    if from_zip:
      trim_pdb(range_dict,f,True)
    else:
      trim_pdb(range_dict,f)
  if from_zip:
    os.unlink(f)

for arg in sys.argv[1:]:
  if os.path.isdir(arg):
    for f in os.listdir(arg):
      if not (f.endswith("-cyranged.pdb") or f.endswith("-cyranged.pdb.gz")) and (f.endswith(".pdb") or f.endswith(".ent") or f.endswith(".gz")):
        process_file(os.path.join(arg, f))
  elif os.path.isfile(arg):
    process_file(os.path.realpath(arg))
"""
#}}}

#{{{ cyranger_sub
cyranger_sub = """universe = vanilla

Notify_user  = vbchen@bmrb.wisc.edu
notification = Error

{req}

Executable     = cyranger.py
should_transfer_files = YES
transfer_input_files = {cy_loc}

copy_to_spool   = False
priority        = 0

Arguments       = $(ARGS)
log         = cylogs/cyranger$(NUMBER).log
error      = cylogs/cyranger$(NUMBER).err
queue
"""
#}}}

#{{{ mpprep_sub
mpprep_sub = """universe = vanilla

Notify_user  = vbchen@bmrb.wisc.edu
notification = Error

{req}

Executable     = {mp_loc}/cmdline/make_condor_files2.py
#should_transfer_files = YES
#transfer_input_files = 

copy_to_spool   = False
priority        = 0

Arguments       = $(ARGS)
log         = mppreplogs/mpprep.log
error      = mppreplogs/mpprep.err
queue
"""
#}}}

#{{{ write_dag
def write_dag(indir, outdir, list_of_pdblists, rebuilt_args):
  config_name = "supermpdag.config"
  config_file = os.path.join(os.path.realpath(outdir), config_name)
  config = open(config_file, 'wr')
  #config.write("DAGMAN_MAX_JOBS_SUBMITTED = 5\n")
  config.write("DAGMAN_SUBMIT_DELAY = 5")
  config.close()
  out_name = "supermpdag.dag"
  outfile = os.path.join(os.path.realpath(outdir), out_name)
  with open(outfile, 'wr') as out:
    parent_childs = []
    out.write("CONFIG "+ config_file+"\n\n")
    out.write("NODE_STATUS_FILE supermpdag.status 3600\n")
    out.write("\n")
    
    for indx, pdbs in enumerate(list_of_pdblists):
      num = '{0:0>4}'.format(indx)
      out.write("Job cyranger"+num+" cyranger.sub\n")
      out.write("VARS cyranger"+num+" ARGS=\""+" ".join(pdbs)+"\"\n")
      out.write("VARS cyranger"+num+" NUMBER=\""+num+"\"\n\n")
      parent_childs.append("cyranger"+num)
    
    out.write("Job mpprep mpprep.sub\n")
    out.write("VARS mpprep ARGS=\""+indir+" "+rebuilt_args+"\"\n\n")
    out.write("PARENT "+" ".join(parent_childs)+" CHILD mpprep\n")
#}}}

#{{{ prep_dirs
def prep_dirs(indir):
  indir_base = os.path.basename(os.path.realpath(indir))
  outdir = os.path.join(indir, "cyrange_condor_"+indir_base)
  try:
    os.makedirs(outdir)
    os.makedirs(os.path.join(outdir, "cylogs"))
    os.makedirs(os.path.join(outdir, "mppreplogs"))
  except OSError:
    sys.stderr.write("\"cylogs\" directory detected in \""+indir+"\"\n")
  return outdir
#}}}

#{{{ make_files
def make_files(indir, rebuilt_args, file_size_limit, cyrange_loc, do_requirement):
  if not os.path.exists(indir):
    sys.stderr.write(indir + " doesn't seem to exist\n")
  else:
    molprobity_home = os.path.dirname(os.path.dirname(os.path.realpath(__file__)))
    indir_base = os.path.basename(os.path.realpath(indir))
    outdir = prep_dirs(indir)
    #make_condor_files2.prep_dirs(indir, update_scripts)
    if do_requirement == "bmrb":
      condor_req = "requirements = ((TARGET.FileSystemDomain == \"bmrb.wisc.edu\") || (TARGET.FileSystemDomain == \".bmrb.wisc.edu\"))"
    else:
      condor_req = ""
    list_of_lists = make_condor_files2.divide_pdbs(indir, outdir, file_size_limit)
    if not cyrange_loc == "none":
      # write cyrange files
      cyrange_loc = os.path.realpath(cyrange_loc)
      make_condor_files2.write_file(outdir, "cyranger.sub", cyranger_sub.format(req=condor_req, cy_loc=cyrange_loc))
      make_condor_files2.write_file(outdir, "cyranger.py", cyranger_py, 0755)
    make_condor_files2.write_file(outdir, "mpprep.sub", mpprep_sub.format(req=condor_req, mp_loc=molprobity_home))
    write_dag(indir, outdir, list_of_lists, rebuilt_args)
#}}}

if __name__ == "__main__":
  opts, indir, rebuilt_args = parse_cmdline()
  make_files(indir, rebuilt_args, opts.total_file_size_limit, opts.cyrange_location, opts.requirement)
  #make_condor_files2.make_files(indir, 
  #                             opts.total_file_size_limit,
  #                             opts.bond_type,
  #                             opts.sans_location, 
  #                             opts.cyrange_location,
  #                             opts.requirement,
  #                             opts.update_scripts,
  #                             opts.update_bmrb_website)

