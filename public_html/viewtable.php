<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Launches a page to view tables encoded as PHP arrays.

INPUTS (via Get or Post):
    file            absolute path of the file to load

*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    mpStartSession();
// 4. For pages that want to see the session but not change it, such as
// pages that are refreshing periodically to monitor a background job.
    mpSessReadOnly();

# MAIN - the beginning of execution for this page
############################################################################
// Security check on filename
$file = realpath($_REQUEST['file']);
if(!$file || !startsWith($file, $_SESSION['dataDir']))
{
    mpLog("security:Attempt to access '$file' as '$_REQUEST[file]'");
    die("Security failure: illegal file request '$_REQUEST[file]'");
}
$name = basename($file);

// Start the page: produces <HTML>, <HEAD>, <BODY> tags
echo mpPageHeader("Viewing $name");
?>
<form>
<table border='0' width='100%'><tr>
<td align='left'><small>
    When finished, you should 
    <input type="button" value="close this window"
    language="JavaScript" onclick="self.close();">.
</small></td><td align='right'><small><i>
    Hint: Use File | Save As... to save a copy of this page.
</i></small></td>
</tr></table>
</form>
<hr>
<?php
$in = fopen($file, 'rb');
clearstatcache();
$data = fread($in, filesize($file));
$table = unserialize($data);
fclose($in);

    // Sort table rows according to user request
    if(isset($_REQUEST['sort_dir']))    $direction = $_REQUEST['sort_dir']+0;
    else                                $direction  = 1; // ascending
    if(isset($_REQUEST['sort_col']))    $col = $_REQUEST['sort_col']+0;
    else                                $col = -1; // sort by '@@NATIVE@@'
    // Stupid song and dance b/c PHP doesn't have a stable sort function like mergesort.
    $rows = &$table['rows'];
    $i = 1;
    foreach($rows as $key => $row)
        $rows[$key]['@@NATIVE@@'] = $i++;
    // Custom "lambda" sort function -- essentially, curried on the name of the sort field and direction
    $mySortFunc = create_function('$a,$b', "
        if(!isset(\$a[$col]['sort_val']))
        {
            if(!isset(\$b[$col]['sort_val']))   return $direction*(\$a['@@NATIVE@@'] - \$b['@@NATIVE@@']);
            else                                return 1;
        }
        elseif(!isset(\$b[$col]['sort_val']))   return -1;
        elseif(\$a[$col]['sort_val'] < \$b[$col]['sort_val']) return -($direction);
        elseif(\$a[$col]['sort_val'] > \$b[$col]['sort_val']) return $direction;
        else                                    return $direction*(\$a['@@NATIVE@@'] - \$b['@@NATIVE@@']);
    ");
    // This check isn't necessary (sort will be done correctly) but makes me feel better...
    if($col != -1)
        uasort($rows, $mySortFunc);
    
    
    // Debug version:
    //echo "<pre>";
    //print_r($table);
    //echo "</pre>\n";
    
    
    echo $table['prequel'];
    echo "\n";
    echo "<table width='100%' cellspacing='1' border='0'>\n";
    foreach($table['headers'] as $header)
    {
        echo "<tr align='center' bgcolor='".MP_TABLE_HIGHLIGHT."'>";
        $i = 0;
        foreach($header as $cell)
        {
            echo "<td>";
            $sort_dir = ($i == $col ? -$direction : $cell['sort']);
            if($cell['sort']) echo "<a href='viewtable.php?$_SESSION[sessTag]&file=$file&sort_col=$i&sort_dir=$sort_dir'>";
            echo $cell['html'];
            if($cell['sort']) echo "</a>";
            echo "</td>";
            $i++;
        }
        echo "</tr>\n";
    }

    
    $color = MP_TABLE_ALT1;
    foreach($table['rows'] as $row)
    {
        echo "<tr align='center' bgcolor='$color'>";
        foreach($row as $key => $cell)
        {
            // For some odd reason, 0 == '@@NATIVE' in PHP 5.0.4, so we use ===
            // Ah, because the string is being coerced to a number, which becomes zero...
            if($key === '@@NATIVE') continue;
            echo "<td";
            if($cell['color']) echo " bgcolor='$cell[color]'";
            echo ">$cell[html]</td>";
        }
        echo "</tr>\n";
        $color == MP_TABLE_ALT1 ? $color = MP_TABLE_ALT2 : $color = MP_TABLE_ALT1;
    }
    
    
    foreach($table['footers'] as $footer)
    {
        echo "<tr align='center' bgcolor='".MP_TABLE_HIGHLIGHT."'>";
        foreach($footer as $cell)
        {
            echo "<td>$cell[html]</td>";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
    echo $table['sequel'];
    echo "\n";

echo mpPageFooter();
?>
