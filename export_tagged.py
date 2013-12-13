import os

mp4_svn = 'https://quiddity.biochem.duke.edu/svn/molprobity3/branches/moltbx'

def run():
  #get revision number
  cmd = 'svn info %s' % mp4_svn
  svn_info = os.popen(cmd).read()
  for line in svn_info.splitlines():
    if line.startswith("Revision:"):
      temp = line.strip().split(' ')
      revision = int(temp[1])
      break

  #export moltbx w/ revision(tag) number in name
  cmd = 'svn export https://quiddity.biochem.duke.edu/svn/molprobity3/branches/moltbx moltbx-%d' % revision
  os.system(cmd)

  #apply tag to version #
  file_name = 'moltbx-%d/lib/core.php' % revision
  lines = open(file_name, 'rb').readlines()
  for i, line in enumerate(lines):
    ctr = i
    if line.startswith('define("MP_VERSION"'):
      temp = line.strip().split('");')
      lines[ctr] = lines[ctr].replace(temp[0],temp[0]+'-%d'%revision)
      temp2 = temp[0].split(',')
      version = temp2[1].strip().strip('"')
      break
  out = open(file_name, 'w')
  out.writelines(lines)
  out.close()

  #rename directory to contain version and revision
  cmd = 'mv moltbx-%d moltbx-%s-%d' % (revision,
                                       version,
                                       revision)
  os.system(cmd)

  #create .tgz file for ease of distribution
  cmd = 'tar czf moltbx-%s-%d.tgz moltbx-%s-%d' % (version,
                                                   revision,
                                                   version,
                                                   revision)
  os.system(cmd)

if __name__ == "__main__":
  run()
