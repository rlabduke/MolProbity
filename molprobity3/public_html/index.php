<?php # (jEdit options) :folding=explicit:collapseFolds=1:
/*****************************************************************************
    This page is the command center for MolProbity.
    It is the only page to appear in the browser URL bar, with few exceptions.
    
    This page provides a clean model-view-controller architecture
    in cooperation with lib/event-page.php.
    
    The pages that do the actual work reside in pages/ and are referred to
    as "delegates", because the work and UI is delegated to them from here.
    (They are also called "pages", obviously.)
*****************************************************************************/
// EVERY *top-level* page must start this way:
// 1. Define it's relationship to the root of the MolProbity installation.
// Pages in subdirectories of lib/ or public_html/ will need more "/.." 's.
    if(!defined('MP_BASE_DIR')) define('MP_BASE_DIR', realpath(dirname(__FILE__).'/..'));
// 2. Include core functionality - defines constants, etc.
    require_once(MP_BASE_DIR.'/lib/core.php');
// 3. Restore session data. If you don't want to access the session
// data for some reason, you must call mpInitEnvirons() instead.
    $isNewSess = mpStartSession(true);
// New sessions must define where they start!
// Default is welcome page unless URL is like index.php?start=____
    if($isNewSess)
    {
        switch($_REQUEST['start'])
        {
            case "sitemap":     pageGoto("sitemap.php"); break;
            case "xray":        pageGoto("helper_xray.php"); break;
            default:            pageGoto("welcome.php"); break;
        }
    }

// Process submitted event /////////////////////////////////////////////////////
$page = end($_SESSION['pages']);                    // not a ref; read only
if(! $page['delegate']) die("No page delegate defined for events"); // else cryptic error from require_once()
// FUNKY: Must use require_once() b/c UI delegate (below) may be the same,
// or may be different, and we can't redefine classes or functions.
require_once(MP_BASE_DIR."/pages/$page[delegate]"); // defines $delegate
// Can't have an event submitted if we're having to start a new session.
if($isNewSess)
    mpLog("new-session:New interactive user session started on the web");
elseif(isset($_REQUEST['eventID']))
{
    $eid = $_REQUEST['eventID'] + 0;
    if(isset($page['handlers'][$eid]))
    {
        $funcName   = $page['handlers'][$eid]['funcName'];
        $funcArg    = $page['handlers'][$eid]['funcArg'];
        // We use a variable function name here to call the handler.
        $delegate->$funcName($funcArg, $_REQUEST);
        
        // In case we changed $_SESSION but display() calls mpSessReadOnly()
        // This won't stop the session from being automatically saved again
        // after display() and the end of the page.
        // (Though display() shouldn't write to $_SESSION anyway, except events!)
        mpSaveSession(); 
    }
    else
        mpLog("bad-event:Event ID '$eid' is unknown for page $page[delegate]. No action taken.");
}

// Clean up from event processing //////////////////////////////////////////////
clearEventHandlers();   // events defined by previous display() are not valid
// FUNKY: can't unset $delegate here b/c it may or may not be redefined
// when the UI delegate is require_once()'d. It will be overwritten if needed,
// though, so we really don't need to worry about it.

// Handle a return from a called page //////////////////////////////////////////
//  Allowing return values and pseudo-handlers for pageReturn() gets us in a
//  nasty situation, where the handler itself could pageReturn(), ad infinitum.
//  This requires us to put a while() loop here with a require_once() for every
//  pass. The danger is that we will go from delegate page A, to B, and back to
//  A -- but then A won't be included again and so $delegate won't be defined
//  correctly! I don't see a reasonable way around this, so we won't support it.

// Display user interface //////////////////////////////////////////////////////
$page = end($_SESSION['pages']);                    // not a ref; read only
if(! $page['delegate']) die("No page delegate defined for display"); // else cryptic error from require_once()
// Can't call mpSessReadOnly() or we won't be able to create events.
// Other than events, display() shouldn't write to the session though.
// FUNKY: Must use require_once() b/c event delegate (above) may be the same,
// or may be different, and we can't redefine classes or functions.
require_once(MP_BASE_DIR."/pages/$page[delegate]"); // defines $delegate
// Not a variable function name; UI function is always 'display()'
$delegate->display($page['context']);               // creates a HTML UI

?>
