<?php
//NMR Model Splitting Script

//Make New Directory
mkdir("splitmodels/");


//Pattern for the coordinates
$coord = '/^(ATOM|HETATM|TER)/';

//Pattern for end of model
$endmdl = '/^(ENDMDL)/';


//PATTERN for model 
$mdl = '/^(MODEL)/';

//Iterate through the directory
$ending = ".pdb";
$dirHandle = opendir(".");
while(($file = readdir($dirHandle)) !== false)
{

	if(is_file($file) && $ending == substr($file, -strlen($ending)))
	{
	//Open PDB file
	$pdbopen = fopen($file,"rb");

	
	
while(! feof($pdbopen))
{
	$line = fgets($pdbopen);
		
		if(preg_match($mdl, $line))
		{
		$mdline =  $line;
		$mdlnum = substr($mdline, 5, 20);
		$mdlnum = trim($mdlnum);
		}
		elseif(preg_match($coord, $line))
		{
		$model = $model . $line;
		}
		elseif(preg_match($endmdl, $line))
		{
		$endmodel =  $line;
		$pdbnew = fopen("splitmodels/" . basename($file, ".pdb") . "-" . $mdlnum . ".pdb", "wb");
		fwrite($pdbnew, $header);
		fwrite($pdbnew, 'REMARK   '.$mdline);
		fwrite($pdbnew, $model);
		fwrite($pdbnew, 'REMARK   '.$endmodel);
		fclose($pdbnew);
		$model = "";
		}
		else
		{
		$header = $header . $line;
		}

}
fclose($pdbopen);

// End if file statement
}
//end reading through directory
}
?>
