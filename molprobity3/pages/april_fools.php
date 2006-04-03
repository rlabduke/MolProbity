<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    Fake entry page as an April Fool's Day joke.
    Be careful to not *actually* keep people from using the site!
*****************************************************************************/
// We use a uniquely named wrapper class to avoid re-defining display(), etc.
class april_fools_delegate extends BasicDelegate {
    
#{{{ display - creates the UI for this page
############################################################################
/**
* Context is not used.
*/
function display($context)
{
    // Completely Automated Public Turing test to tell Computers and Humans Apart
    echo mpPageHeader("Welcome!");
    echo makeEventForm("onSubmitAnswer");
?>
<script language="JavaScript">
<!--
function hintTimer()
{
    setTimeout('showHint()', 60*1000)
    setTimeout('showHint2()', 90*1000)
    document.getElementById("submit_btn").form.onsubmit = showMsg
}
function showMsg()
{
    window.alert("Thanks for playing -- happy April 1!")
    return true // ok to submit form
}
function showHint()
{
    document.getElementById("hint").style.display = '' // == default state
}
function showHint2()
{
    document.getElementById("hint2").style.display = '' // == default state
}
// This nifty function means we won't override other ONLOAD handlers
function windowOnLoad(f)
{
    var prev = window.onload;
    window.onload = function() { if(prev) prev(); f(); }
}
windowOnLoad(hintTimer)
// -->
</script>
The MolProbity server has recently been overrun by webcrawlers, bots, and spiders.
To conserve computer power for real users, we are now employing a simple
<a href='http://en.wikipedia.org/wiki/Captcha' target='_blank' title='Completely Automated Public Turing test to tell Computers and Humans Apart'>CAPTCHA</a>
founded on basic knowledge of biochemistry and structural biology.

<p>Just answer this simple question to gain access to MolProbity:
<div class='indent'>
<?php
    // Display a question randomly
    $qFunc = "question".mt_rand(1,4); // min,max (inclusive)
    $this->$qFunc(); // call the function whose name is in $qFunc
    
    echo "</div>\n";
    echo "<p>You have <b>1</b> guesses remaining.\n";
    echo "<span id='hint' style='display:none; color:#990000'>Hint: today is April 1st.</span>\n";
    echo "<span id='hint2' style='display:none; color:#990000'>It's a joke. Funny ha ha. Just press the button to continue.</span>\n";
    echo "<br><input id='submit_btn' type='submit' name='cmd' value='Submit answer &gt;'>\n";
    echo "</form>\n";
    echo mpPageFooter();
}
#}}}########################################################################

#{{{ onSubmitAnswer
############################################################################
function onSubmitAnswer($arg, $req)
{
    // Start session regardless of the answer
    pageGoto("welcome.php");
}
#}}}########################################################################

#{{{ question1 - unscramble this protein sequence
############################################################################
function question1()
{
    $symbols = array('G', 'A', 'V', 'L', 'I', 'P', 'F', 'Y', 'W', 'S', 'T', 'C', 'M', 'K', 'H', 'R', 'D', 'N', 'Q', 'E');
    $len = mt_rand(70,120);
    $seq = "";
    for($i = 0; $i < $len; $i++) $seq .= " ".$symbols[mt_rand(0, count($symbols)-1)];
?>
    The sequence below can be unscrambled to form the sequence of a small human protein.
    <?php echo "<p><tt>$seq</tt>\n"; ?>
    <p>What protein is it? (Answer is not case sensitive.)
    <br><input type='text' size='20' name='dummy'>
<?php
}
#}}}########################################################################

#{{{ question2 - identify active site from electron density
############################################################################
function question2()
{
?>
    <center><img src='img/mystery_map.jpg'></center>
    <p>Pictured above is the electron density for the active site of which protein? (Answer is not case sensitive.)
    <br><input type='text' size='20' name='dummy'>
<?php
}
#}}}########################################################################

#{{{ question3 - how many restriction sites?
############################################################################
function question3()
{
    $symbols = array('A', 'C', 'G', 'T');
    $len = mt_rand(60,90);
    $seq = "";
    for($i = 0; $i < $len; $i++) $seq .= $symbols[mt_rand(0, count($symbols)-1)];
?>
    <?php echo "<p><tt>5'-$seq-3'</tt>\n"; ?>
    <p>According to the NEB catalog, how many restriction sites are there in the sequence above?
    <input type='text' size='5' name='dummy'>
<?php
}
#}}}########################################################################

#{{{ question4 - meaning of life
############################################################################
function question4()
{
?>
    <p>What is the answer to life, the Universe, and everything?
    <input type='text' size='2' maxlength='2' name='dummy'>
<?php
}
#}}}########################################################################

#{{{ a_function_definition - sumary_statement_goes_here
############################################################################
/**
* Documentation for this function.
*/
//function someFunctionName() {}
#}}}########################################################################

}//end of class definition
?>
