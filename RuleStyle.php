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
    return substr($URL, strpos($URL, "#") + 1);
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

function getValueStyle($fmtQueries) // get the value style CSS code from any format for the current value box
{
    $valueStyle = ""; // initialize variable to value checked for later as meaning no style
    foreach (array_keys(bindings($fmtQueries)) as $key) { // for each format and its query
        $thisFmt = qryRtnCell($fmtQueries, $key, 'fmt'); // URL for this format
        $QResult = json_decode(file_get_contents(EndpointURLdecl . urlencode(qryRtnCell($fmtQueries, $key, 'query'))), true);
        $triggerURI = qryRtnCell($QResult, 0, array_keys(bindings($QResult)[$key])[0]); // URL lens query returns as trigger value of 1st bound variable
                                                                                        // this format's query returns resource: FIX: check rest of triple, tentatative because only checks first returned
        if ($triggerURI == Resource)
            $valueStyle = " style='" . qryRtnCell(getSPARQLrtn("<" . $thisFmt . "> fresnel:valueStyle ?style . "), 0, 'style') . "' "; // style from format
    }
    return $valueStyle;
}

function propertyBox($fmtQueries, $predicate1)
{
    print_r("<tr class='propertyBox'>\n"); // Fresnel box model property box
    print_r("<td class='labelBox'>" . fragment($predicate1) . "</td>\n"); // Fresnel box model label box with property label
    print_r("<td class='objectBox'>\n"); // Fresnel box model object box
    $valueStyle = valueBox($fmtQueries, $predicate1);
    reifyBox($valueStyle);
    print_r("</td></tr>");
}

function valueBox($fmtQueries, $predicate1)
{
    print_r("<span class='valueBox' "); // Fresnel box model value box
    $valueStyle = getValueStyle($fmtQueries);
    print_r($valueStyle); // with style if any
    print_r(" >\n "); // end value box start tag
    $object = qryRtnCell(getSPARQLrtn(" <" . Resource . "> <" . $predicate1 . "> ?object "), 0, 'object'); // get object
    internalLink($object, fragment($object)); // set Fresnel browser link to object
    print_r("</span> \n");
    return $valueStyle;
}

function reifyBox($valueStyle)
{
    if ($valueStyle != "") {
        print_r("<span class='reifyBox'>"); // Fresnel box model reify box
        internalLink("http://example.org/#inf", "(?)"); // Icon link to explanation: FIX: icon from style, query for link
        print_r("</span>");
    }
}

?>
<html>
<head>
<title>RuleStyle <?php print_r ( fragment ( Resource ) ) ;  // browser tab label has tool and resource fragment ID ?> </title>
</head>
<body>
  <?php
  $lens=getLens();
$fmtQueries = getSPARQLrtn(" ?fmt transfr:valueFormatDomain ?query . "); // all formats with valueFormatDomains and their queries
if ($lens == "http://example.org/#explBox") {
    print_r("<p>Explanation for: ");
    $qryRtnGiv = getSPARQLrtn(" ?Inferred a reas:Inference ; reas:gives/rdf:rest*/rdf:first ?statement . "); // the inferred triple
    foreach (array_keys(bindings($qryRtnGiv)) as $key) // print the triple
        print_r(fragment(qryRtnCell($qryRtnGiv, $key, 'statement')) . " ");
    print_r("</p>\n<p>"); // between triple and explanation
    $qryRtnExp = getSPARQLrtn(" ?Inferred a reas:Inference ; reas:evidence/rdf:first/rdf:rest*/rdf:first ?statement . "); // an inference's explanation
    foreach (array_keys(bindings($qryRtnExp)) as $key) // dipslay each statement
        print_r(fragment(qryRtnCell($qryRtnExp, $key, 'statement')) . " ");
    print_r("</p>\n"); // end of explanation
} else {
    ?>
	<p> <?php print_r ( fragment ( Resource ) ) ;  // show resource fragment ?> </p>
	<div class='containerBox'>
	<table class='resourceBox'>
        <?php
    /* if resource has triple with explanation than assign its triples' values a yellow background */
    /* walk through the show properties list to show the triples */
    $showPropList = bindings(getSPARQLrtn(" <" . $lens . "> fresnel:showProperties/rdf:rest*/rdf:first ?prop ")); // showProperties's order list of properties
    foreach (array_keys($showPropList) as $key) { // for each property
        $predicate1 = $showPropList[$key]['prop']['value']; // get the property URI
        propertyBox($fmtQueries, $predicate1);
    }
    print_r("</table></div>");
}
?>
</body>
</html>
