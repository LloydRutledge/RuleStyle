<?php
error_reporting(E_ALL ^ E_NOTICE); // turns off notice displays: FIX turn on to check

/* all constants */

define("EndpointURLserver", "http://localhost:3030/Fresnel/");
define("FreselServerURLprefix", "http://localhost/RuleStyle/FresnelRules.php?resource=");
define("Resource", $_GET["resource"]);

define("EndpointURLdecl", EndpointURLserver . "query?output=json&query=" . urlencode("prefix transfr: <http://is.cs.ou.nl/transfr#>" . "prefix ex: <http://example.org/#>" . "prefix reas: <http://www.w3.org/2000/10/swap/reason#> " . "prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> " . "prefix fresnel: <http://www.w3.org/2004/09/fresnel#> " . "prefix rei: <http://www.w3.org/2000/10/swap/reify#> "));

define("EndpointURLstart", EndpointURLdecl . urlencode("SELECT * WHERE { "));

/* all functions */
function bindings($array) // SPARQL query bindings
{
    return $array['results']['bindings'];
}

function emptyRtn($array) // is the array empty?
{
    return empty(bindings($array));
}

function fragment($URL) // fragment ID from a URI
{
    if (strpos($URL, "#") > 0)
        return substr($URL, strpos($URL, "#") + 1);
    else
        return $URL;
}

function qryRtnCell($array, $key, $varName) // returns value assigned to variable in SPARQL return
{
    return bindings($array)[$key][$varName]['value'];
}

function getSPARQLrtn($WHEREcontent) // sends SPARQL query and returns results
{
    $qryRtn = file_get_contents(EndpointURLstart . urlencode($WHEREcontent . " } "));
    return json_decode($qryRtn, true);
}

function internalLink($URL, $anchor) // an HTML link to the Fresnel browser display for another resource
{
    print_r("<a href='" . FreselServerURLprefix . urlencode($URL) . "'>" . $anchor . "</a>\n");
}

function getLens()
{
    /* find lens for the resource as lens with query that returns resource */
    $lens = qryRtnCell(getSPARQLrtn(" ?lens fresnel:classLensDomain ?type . <" . Resource . "> rdf:type ?type . "), 0, 'lens'); // lens from class domain
    $lensQueries = getSPARQLrtn(" ?lens fresnel:instanceLensDomain ?query . "); // all lenses with an instance query and their queries
    foreach (array_keys(bindings($lensQueries)) as $key) { // for each lens and its query
        $thisLens = qryRtnCell($lensQueries, $key, 'lens'); // URL for this lens
        $QResult = json_decode(file_get_contents(EndpointURLdecl . urlencode(qryRtnCell($lensQueries, $key, 'query'))), true);
        $triggerURI = qryRtnCell($QResult, 0, array_keys(bindings($QResult)[$key])[0]); // URL lens query returns as trigger value of 1st bound variable
        if ($triggerURI == Resource) // this lens's query returns resource: FIX tentatative because only checks first returned
            $lens = $thisLens; // this is the lens to apply for this resource
    }
    return $lens;
}

function getobjectStyle($thisPredicate, $thisObject, $objFmtQs) // get the value style CSS code from any format for the current value box
{
    $objectStyle = ""; // initialize variable to value checked for later as meaning no style
    foreach (array_keys(bindings($objFmtQs)) as $key) { // for each format and its query
        $thisFmt = qryRtnCell($objFmtQs, $key, 'fmt'); // URL for this format
        $QResult = json_decode(file_get_contents(EndpointURLdecl . urlencode(qryRtnCell($objFmtQs, $key, 'query'))), true);
        $thatSubject   = qryRtnCell($QResult, 0, array_keys(bindings($QResult)[$key])[0]); // URL lens query returns as trigger value of 1st bound variable
        $thatPredicate = qryRtnCell($QResult, 0, array_keys(bindings($QResult)[$key])[1]); // URL lens query returns as trigger value of 1st bound variable
        $thatObject    = qryRtnCell($QResult, 0, array_keys(bindings($QResult)[$key])[2]); // URL lens query returns as trigger value of 1st bound variable
        if ($thatSubject == Resource && $thatPredicate == $thisPredicate)
            $objectStyle = " style='" . qryRtnCell(getSPARQLrtn("<" . $thisFmt . "> transfr:objectStyle ?style . "), 0, 'style') . "' "; // style from format
    }
    return $objectStyle;
}

/* start Box model box functions */

function propertyBox($objFmtQs, $reifFmtQs, $thisPredicate)
{
    print_r("<tr class='propertyBox'>\n"); // Fresnel box model property box
    labelBox($thisPredicate);
    objectBox($objFmtQs, $reifFmtQs, $thisPredicate);
    print_r("</td></tr>");
}

function labelBox($thisPredicate)
{
    print_r("<td class='labelBox'>"); // Fresnel box model label box with property label
    if ($thisPredicate == "b0") { // if current show property in list is 1st blank node which in this example is for the explBox lens because of sublenses
        print_r("Explanation for: ");
    } elseif ($thisPredicate != "b1") { // if current show property in list is after 1st blank node which in this example is for the explBox lens because of sublenses
        print_r(fragment($thisPredicate));
    }
    print_r("</td>\n");
}

function objectBox($objFmtQs, $reifFmtQs, $thisPredicate)
{
    $objectStyle = getobjectStyle($thisPredicate, "put thisObject here later", $objFmtQs); // FIX: implement object match
    print_r("<td class='objectBox' " . $objectStyle . " >\n"); // Fresnel box model object box
    valueBox($thisPredicate);
    reifyBox($reifFmtQs, $objectStyle);
}

function valueBox($thisPredicate)
{
    print_r("<span class='valueBox' >\n"); // Fresnel box model value box
    if ($thisPredicate == "b0") { // if current show property in list is 1st blank node which in this example is for the explBox lens because of sublenses
        $qryRtnGiv = getSPARQLrtn(" ?Inferred a reas:Inference ; reas:gives/rdf:rest*/rdf:first ?statement . "); // the inferred triple
        foreach (array_keys(bindings($qryRtnGiv)) as $key) // print the triple
            print_r(fragment(qryRtnCell($qryRtnGiv, $key, 'statement')) . " ");
    } elseif ($thisPredicate == "b1") {
        $qryRtnExp = getSPARQLrtn(" ?Inferred a reas:Inference ; reas:evidence/rdf:first/rdf:rest*/rdf:first ?statement . "); // an inference's explanation
        foreach (array_keys(bindings($qryRtnExp)) as $key) // dipslay statement #1
            print_r(fragment(qryRtnCell($qryRtnExp, $key, 'statement')) . " ");
        print_r("<br/>");
        $qryRtnExp = getSPARQLrtn(" ?Inferred a reas:Inference ; reas:evidence/rdf:rest/rdf:first/rdf:rest*/rdf:first ?statement . "); // an inference's explanation
        foreach (array_keys(bindings($qryRtnExp)) as $key) // dipslay statement #2
            print_r(fragment(qryRtnCell($qryRtnExp, $key, 'statement')) . " ");
    } else { // if normal
        $object = qryRtnCell(getSPARQLrtn(" <" . Resource . "> <" . $thisPredicate . "> ?object "), 0, 'object'); // get object
        internalLink($object, fragment($object)); // set Fresnel browser link to object
    }
    print_r("</span> \n");
}

function reifyBox($reifFmtQs, $objectStyle)
{
    if ($objectStyle != "") {
        print_r("<span class='reifyBox'>"); // Fresnel box model reify box
        $reifyLabel = qryRtnCell(getSPARQLrtn(" ?reifyFormatDomain transfr:reifyLabel ?reifyLabel "), 0, 'reifyLabel'); // FIX: now only one format
        internalLink("http://example.org/#inf", $reifyLabel); // Icon link to explanation: FIX: icon from style, query for link
        print_r("</span>");
    }
}

/* end Box model box functions */
/* end all functions */

/* Load variables for whole display */

$objFmtQs  = getSPARQLrtn(" ?fmt transfr:objectFormatDomain ?query . "); // all formats with objectFormatDomains and their queries
$reifFmtQs = getSPARQLrtn(" ?fmt transfr:reifyFormatDomain  ?query . "); // above for reify
$lens = getLens();
$showPropList = bindings(getSPARQLrtn(" <" . $lens . "> fresnel:showProperties/rdf:rest*/rdf:first ?prop ")); // showProperties's list of properties

?>
<html>
<head>
<title>RuleStyle <?php print_r ( fragment ( Resource ) ) ;  // browser tab label has tool and resource fragment ID ?> </title>
</head>
<body>
  <?php
?>
	<!-- Each box from the Fresnel box model is encoded here as an HTML element with a class named for the box so CSS can override the default HTML style  -->
	<div class='containerBox'>
		<!-- Fresnel container box here for completeness for specifications although only contains single resource box  -->
		<table class='resourceBox'>
        <?php
        /* walk through the show properties list to show the triples */
        foreach (array_keys($showPropList) as $key) { // for each property in the show properties list
            $thisPredicate = $showPropList[$key]['prop']['value']; // get the current property URI from the show properties list
            propertyBox($objFmtQs, $reifFmtQs, $thisPredicate); // output the HTML for the property box for all triples with this resource and property if any
        }
        print_r("</table></div>"); // close the resource box then container box
        ?>
</body>
</html>
