#!/usr/bin/python
# (jEdit options) :folding=explicit:collapseFolds=1:
import sys, os, getopt, re, subprocess

#number_to_run = 50

#{{{ parse_cmdline
#parse the command line--------------------------------------------------------------------------
def parse_cmdline():
  try:
    #opts, args = getopt.getopt(sys.argv[1:], 'hn:',['help', 'number='])
    opts, args = getopt.getopt(sys.argv[1:], 'h',['help'])
  except getopt.GetoptError as err:
    print str(err)
    help()
    sys.exit()
  for o, a in opts:
    if o in ("-h", "--help"):
      help()
      sys.exit()
    #elif o in ("-n", "--number"):
    #  number_to_run = a
  return args
  if len(args) < 2:
    sys.stderr.write("\n**ERROR: User must specify output directory and input directory\n")
    sys.exit(help())
  else:
    outdir = args[0]
    if (os.path.isdir(outdir)):
      return outdir, args[1:]
    else:
      sys.stderr.write("\n**ERROR: First argument must be a directory!\n")
      sys.exit(help())
#------------------------------------------------------------------------------------------------
#}}}

#{{{ help
def help():
  print """USAGE:   python preprocess_pdb.py [output_directory] [pdb_file or directory]
  
  [output_directory]      Directory for all output files to go
  [pdb_file or directory] Takes as input either pdb_files or a directory of files.
                          Note that this script will convert ALL files in the directory.
                          
  This script adds REMARK tags to any lines that aren't ATOM, TER, or END.
  It remediates files, since some files come with hydrogens.
  It also checks atom names to make sure they are justified correctly.
  
FLAGS:
  -h     Print this help message
"""
#}}}

def split_pdbs(in_dir, size_limit):
  if (os.path.isdir(in_dir)):
    files = os.listdir(in_dir)
    files.sort()
    #print arg
    list_of_lists = []
    pdb_list = []
    list_size = 0
    list_of_lists.append(pdb_list)
    #print files
    for f in files:
      #arg_file = os.path.join(arg, f)
      if (not os.path.isdir(os.path.realpath(f))):
        root, ext = os.path.splitext(f)
        if (ext == ".pdb"):
          #print f
          if (list_size <= size_limit):
            pdb_list.append(f)
            #print pdb_list
            list_size = list_size + os.path.getsize(f)
          else:
            pdb_list = []
            pdb_list.append(f)
            list_of_lists.append(pdb_list)
            list_size = os.path.getsize(f)
    return list_of_lists
    #print list_of_lists

#{{{ write_super_dag
def write_super_dag(outdir, list_of_pdblists):
  out_name = "supermol.dag"
  outfile = os.path.join(os.path.realpath(outdir), out_name)
  out=open(outfile, 'wr')
  for num, pdbs in enumerate(list_of_pdblists):
    out.write("SUBDAG EXTERNAL "+repr(num)+" moldag"+repr(num)+".dag\n")
    write_mol_dag(outdir, num, pdbs)
  out.close()
#}}}

#{{{ write_mol_dag
def write_mol_dag(outdir, num, pdbs):
  out_name = "moldag"+repr(num)+".dag"
  outfile = os.path.join(os.path.realpath(outdir), out_name)
  out=open(outfile, 'wr')
  
  out.write("Jobstate_log logs/mol.jobstate.log\n")
  out.write("NODE_STATUS_FILE mol.status 3600\n")
  out.write("\n")
  
  parent_childs = "" # create parent_childs part of dag file now so only have to loop once thru pdbs
  for pdb_file in pdbs:
    pdb, ext = os.path.splitext(pdb_file)
    out.write("Job clash"+pdb+" clashlist.sub\n")
    out.write("VARS clash"+pdb+" PDB=\""+pdb_file+"\"\n")
    out.write("\n")
    parent_childs = parent_childs+"PARENT clash"+pdb+" CHILD local"+repr(num)+"\n"
  
  out.write("Job local"+repr(num)+" local.sub\n")
  out.write("VARS local"+repr(num)+" PDB=\""+" ".join(pdbs)+"\"\n")
  out.write("VARS local"+repr(num)+" NUMBER=\""+repr(num)+"\"\n")
  out.write(parent_childs)
  
  out.close()
#}}}

#{{{ run_clashlist
def run_clashlist(outdir, arg):
  in_file = os.path.basename(arg)
  in_file, ext = os.path.splitext(in_file)
  out_name = in_file+"-clashlist.data"
  out_name_err = in_file+"-clashlist.err"
  outfile = os.path.join(os.path.realpath(outdir), out_name)
  outfileerr = os.path.join(os.path.realpath(outdir), out_name_err)
  out=open(outfile, 'wr')
  err=open(outfileerr, 'wr')
  test = subprocess.call(["clashlist", arg, "40", "10"], stdout=out, stderr=err)
  out.close()
  err.close()
#}}}

if __name__ == "__main__":
  outdir, arg = parse_cmdline()
  print arg
  if os.path.exists(arg):
    list_of_lists = split_pdbs(arg, 5000000)
    write_super_dag(outdir, list_of_lists)
  else:
    print arg + " does not seem to exist!"
