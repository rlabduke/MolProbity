inputfile=''

for i in $@
do
  inputfile=$i
done

#grep for lines starting with ATOM or HETATM
atomchars=$(grep "^[[:blank:]]*ATOM" $inputfile)
hetatomchars=$(grep "^[[:blank:]]*HETATM" $inputfile)

#check if length of either grep output is > 0
if ([[ ${#atomchars} > 0 ]] || [[ ${#hetatomchars} > 0 ]])
then
  hasatoms=true
else
  hasatoms=false
fi
  
shbangs=$(grep "#!" $inputfile)
if [[ ${#shbangs} > 0 ]]
then
  noshbangs=false
else
  noshbangs=true
fi

scripttags=$(grep -i "<script" $inputfile)
if [[ ${#scripttags} > 0 ]]
then
  noscriptflags=false
else
  noscriptflags=true
fi

phptags=$(grep -i "<?php" $inputfile)
if [[ ${#phptags} > 0 ]]
then
  nophpflags=false
else
  nophpflags=true
fi

if ( $hasatoms && $noshbangs && $noscriptflags && $nophpflags)
then
  echo 1
else
  echo 0
fi
