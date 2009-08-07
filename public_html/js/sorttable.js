/*
Original code from http://kryogenix.org/code/browser/sorttable/ [MIT license]
Modifications by IWD, March 2006:
  - If table cells have a 'sv' (Sort Value) property,
    that is used for sorting in preference to all else.
    e.g. <td sv='42'>text goes here</td>
*/

//IWD: This speeds up sorting in Mozilla (supposedly);
// see http://webfx.eae.net/dhtml/sortabletable/implementation.html
var REMOVE_BEFORE_SORT = true;

addEvent(window, "load", sortables_init);

var SORT_COLUMN_INDEX;

function sortables_init() {
    // Find all tables with class sortable and make them sortable
    if (!document.getElementsByTagName) return;
    tbls = document.getElementsByTagName("table");
    for (ti=0;ti<tbls.length;ti++) {
        thisTbl = tbls[ti];
        if (((' '+thisTbl.className+' ').indexOf("sortable") != -1) && (thisTbl.id)) {
            ts_makeSortable(thisTbl);
        }
    }
}

function ts_makeSortable(table) {
    if (table.rows && table.rows.length > 0) {
        var firstRow = table.rows[0];
    }
    if (!firstRow) return;
    
    // We have a first row: assume it's the header, and make its contents clickable links
    for (var i=0;i<firstRow.cells.length;i++) {
        var cell = firstRow.cells[i];
        var txt = ts_getInnerText(cell);
        cell.innerHTML = '<a href="#" class="sortheader" onclick="ts_resortTable(this, '+i+');return false;">'+txt+'<span class="sortarrow">&nbsp;&nbsp;&nbsp;</span></a>';
    }
    
    //IWD:
    // For other rows, look for sv='...' and convert to fsv
    // (Sort Value [text, attribute] and Float Sort Value [number, property])
    for(var r = 1; r < table.rows.length; r++)
    {
        var row = table.rows[r];
        for(var c = 0; c < row.cells.length; c++)
        {
            var cell = row.cells[c];
            // This works in Safari *and* Firefox (and hopefully IE)
            if(hasAttr(cell, 'sv'))
                cell.fsv = parseFloat(cell.getAttribute('sv'))
        }
    }
}

function ts_getInnerText(el) {
	if (typeof el == "string") return el;
	if (typeof el == "undefined") { return el };
	if (el.innerText) return el.innerText;	//Not needed but it is faster
	var str = "";
	
	var cs = el.childNodes;
	var l = cs.length;
	for (var i = 0; i < l; i++) {
		switch (cs[i].nodeType) {
			case 1: //ELEMENT_NODE
				str += ts_getInnerText(cs[i]);
				break;
			case 3:	//TEXT_NODE
				str += cs[i].nodeValue;
				break;
		}
	}
	return str;
}

function ts_resortTable(lnk, cellIdx) {
    // get the span
    var span;
    for (var ci=0;ci<lnk.childNodes.length;ci++) {
        if (lnk.childNodes[ci].tagName && lnk.childNodes[ci].tagName.toLowerCase() == 'span') span = lnk.childNodes[ci];
    }
    var spantext = ts_getInnerText(span);
    var td = lnk.parentNode;
    var table = getParent(td,'TABLE');
    var tableLen = table.rows.length; //IWD: supposedly this also helps with speed
    var column = cellIdx || td.cellIndex; //IWD: cellIndex is always 0 is Safari
    SORT_COLUMN_INDEX = column;
    
    //IWD: we always sort by fsv
    var firstRow = new Array();
    var newRows = new Array();
    for (i=0;i<table.rows[0].length;i++) { firstRow[i] = table.rows[0][i]; }
    for (j=1;j<tableLen;j++) { newRows[j-1] = table.rows[j]; }

    //IWD: we use different sort functions b/c missing values always sort to the end
    var sortfn = ts_sort_fsv_up;
    // Sort determined by column
    var sortdir = 1;
    if(hasAttr(td, 'sortdir')) sortdir = parseInt(td.getAttribute('sortdir'));
    if(sortdir == -1) {
        ARROW = '&nbsp;&nbsp;&uarr;';
        sortfn = ts_sort_fsv_down; //IWD
    } else {
        ARROW = '&nbsp;&nbsp;&darr;';
        sortfn = ts_sort_fsv_up; //IWD
    }
    /* Alternating sort
    if (span.getAttribute("sortdir") == 'down') {
        ARROW = '&nbsp;&nbsp;&uarr;';
        span.setAttribute('sortdir','up');
        sortfn = ts_sort_fsv_down; //IWD
    } else {
        ARROW = '&nbsp;&nbsp;&darr;';
        span.setAttribute('sortdir','down');
        sortfn = ts_sort_fsv_up; //IWD
    }*/
    
    newRows.sort(sortfn);
    
    //IWD: This speeds up Mozilla; see top.
    if(REMOVE_BEFORE_SORT)
    {
        var tableNextSib = table.nextSibling;
        var tableParent = table.parentNode;
        tableParent.removeChild(table);
    }

    // We appendChild rows that already exist to the tbody, so it moves them rather than creating new ones
    //IWD: I prefer sorttop over sortbottom
    // do sorttop rows only
    for (i=0;i<newRows.length;i++) { if (newRows[i].className && (newRows[i].className.indexOf('sorttop') != -1)) table.tBodies[0].appendChild(newRows[i]);}
    // don't do sorttop rows
    for (i=0;i<newRows.length;i++) { if (!newRows[i].className || (newRows[i].className && (newRows[i].className.indexOf('sorttop') == -1))) table.tBodies[0].appendChild(newRows[i]);}
    // don't do sortbottom rows
    //for (i=0;i<newRows.length;i++) { if (!newRows[i].className || (newRows[i].className && (newRows[i].className.indexOf('sortbottom') == -1))) table.tBodies[0].appendChild(newRows[i]);}
    // do sortbottom rows only
    //for (i=0;i<newRows.length;i++) { if (newRows[i].className && (newRows[i].className.indexOf('sortbottom') != -1)) table.tBodies[0].appendChild(newRows[i]);}
    
    if(REMOVE_BEFORE_SORT)
    {
        tableParent.insertBefore(table, tableNextSib)
    }
    
    // Delete any other arrows there may be showing
    var allspans = document.getElementsByTagName("span");
    for (var ci=0;ci<allspans.length;ci++) {
        if (allspans[ci].className == 'sortarrow') {
            if (getParent(allspans[ci],"table") == getParent(lnk,"table")) { // in the same table as us?
                allspans[ci].innerHTML = '&nbsp;&nbsp;&nbsp;';
            }
        }
    }
        
    span.innerHTML = ARROW;
}

function getParent(el, pTagName) {
	if (el == null) return null;
	else if (el.nodeType == 1 && el.tagName.toLowerCase() == pTagName.toLowerCase())	// Gecko bug, supposed to be uppercase
		return el;
	else
		return getParent(el.parentNode, pTagName);
}

function ts_sort_fsv_up(a,b) { //IWD
    aa = a.cells[SORT_COLUMN_INDEX].fsv;
    bb = b.cells[SORT_COLUMN_INDEX].fsv;
    if(isNaN(aa))
    {
        if(isNaN(bb)) return ts_sort_default(a,b);
        else return 1;
    }
    else if(isNaN(bb)) return -1;
    else return aa-bb;
}

function ts_sort_fsv_down(a,b) { //IWD
    aa = a.cells[SORT_COLUMN_INDEX].fsv;
    bb = b.cells[SORT_COLUMN_INDEX].fsv;
    if(isNaN(aa))
    {
        if(isNaN(bb)) return ts_sort_default(a,b);
        else return 1;
    }
    else if(isNaN(bb)) return -1;
    else return bb-aa;
}

function ts_sort_default(a,b) {
    aa = ts_getInnerText(a.cells[SORT_COLUMN_INDEX]);
    bb = ts_getInnerText(b.cells[SORT_COLUMN_INDEX]);
    if (aa==bb) return 0;
    if (aa<bb) return -1;
    return 1;
}


function addEvent(elm, evType, fn, useCapture)
// addEvent and removeEvent
// cross-browser event handling for IE5+,  NS6 and Mozilla
// By Scott Andrew
{
  if (elm.addEventListener){
    elm.addEventListener(evType, fn, useCapture);
    return true;
  } else if (elm.attachEvent){
    var r = elm.attachEvent("on"+evType, fn);
    return r;
  } else {
    alert("Handler could not be removed");
  }
}

//IWD: IE6 doesn't support the hasAttribute() method
function hasAttr(obj, attr)
{
    return !!(obj[attr] || attr in obj || (obj.hasAttribute && obj.hasAttribute(attr)));
}
