#!/usr/bin/python
import sys, os, re, pprint, subprocess, gzip, platform
    
class cyrange_results:
  def __init__(self, cyrange_output_text):
    self.chain_range_dict = {}
    for line in cyrange_output_text.split("\n"):
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
            if chain in self.chain_range_dict.keys():
              old_range = self.chain_range_dict[chain]
              old_range.extend(range(int(lower), int(upper)+1))
              self.chain_range_dict[chain] = old_range
            else:
              self.chain_range_dict[chain] = range(int(lower), int(upper)+1)
    
  def is_empty(self):
    if len(self.chain_range_dict.keys()) == 0:
      sys.stderr.write("cyrange results are empty\n")
      return True
    return False
    
  def is_core(self, chain, resnum):
    if chain in self.chain_range_dict.keys() and int(resnum) in self.chain_range_dict[chain]:
      return True
    elif "" in self.chain_range_dict.keys() and int(resnum) in self.chain_range_dict[""]:
      return True
    return False

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
  
def determine_os():
  my_os = platform.system()
  if my_os == 'Darwin':
    base_dir = "macosx"
  elif my_os == 'Linux':
    base_dir = 'linux'
  else:
    raise OSError("OS not supported by cyrange")
  return base_dir

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
  cyrange_path = os.path.join(".",determine_os(), "cyrange")
  cy_out = long_reap(syscmd(subprocess.PIPE, cyrange_path,pdb), pdb)
  pdb_cyrange_results = cyrange_results(cy_out)
  return pdb_cyrange_results

def split_pdb(pdb_cyrange_results, pdb_file):
  core_lines_list = []
  notcore_lines_list = []
  with open(pdb_file) as f:
    for line in f:
      if line.startswith("MODEL") or line.startswith("END") or line.startswith("END"):
        core_lines_list.append(line)
        notcore_lines_list.append(line)
      if line.startswith("ATOM"):
        nucacid_residues = ["  G", "  A", "  C", "  U", " DG", " DA", " DC", " DT"]
        resname = line[17:20]
        chain = line[21]
        res = line[22:26]
        #fixes bug where cyrange doesn't indicate nucleic acid residues by chain so they don't get removed
        #since cyrange is currently only protein only
        if not resname in nucacid_residues:
          if pdb_cyrange_results.is_core(chain, res):
            core_lines_list.append(line)
          else:
            notcore_lines_list.append(line)
        else:
          notcore_lines_list.append(line)
  return core_lines_list, notcore_lines_list

def process_file(f):
  from_zip = False
  if f.endswith(".gz"):
    from_zip = True
    f = unzip_to_temp(f)
  pdb_cyrange_results = run_cyrange(f)
  if not pdb_cyrange_results.is_empty():
    core_lines, notcore_lines = split_pdb(pdb_cyrange_results,f)
    
    file_path, name = os.path.split(f)    
    core_path = os.path.join(file_path, os.path.splitext(name)[0]+"-core.pdb")
    write_pdb_file(core_lines, core_path, from_zip)
    notcore_path = os.path.join(file_path, os.path.splitext(name)[0]+"-illdefined.pdb")
    write_pdb_file(notcore_lines, notcore_path, from_zip)
  if from_zip:
    os.unlink(f)
    
def write_pdb_file(pdb_lines_list, path, do_gzip = False):
  if do_gzip:
    pdb_file = gzip.open(path+".gz", "wr")
  else:
    pdb_file = open(path, 'wr')
  for line in pdb_lines_list:
    pdb_file.write(line)
  pdb_file.close()

    
if __name__ == '__main__' :
  for arg in sys.argv[1:]:
    if os.path.isdir(arg):
      for f in os.listdir(arg):
        #print "pre-check "+f
        if not (f.endswith("-cyranged.pdb") or f.endswith("-cyranged.pdb.gz")) and (f.endswith(".pdb") or f.endswith(".ent") or f.endswith(".gz")):
          #print f
          process_file(os.path.join(arg, f))
    elif os.path.isfile(arg):
      if not (arg.endswith("-cyranged.pdb") or arg.endswith("-cyranged.pdb.gz")) and (arg.endswith(".pdb") or arg.endswith(".ent") or arg.endswith(".gz")):
        process_file(os.path.realpath(arg))
      
      

