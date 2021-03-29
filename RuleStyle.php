<?php
error_reporting(E_ALL ^ E_NOTICE); // turns off notice displays

define("EndpointURLserver", "http://localhost:3030/Fresnel/");
define("FreselServerURLprefix", "http://localhost/RuleStyle/FresnelRules.php?resource=");
define("Resource", $_GET["resource"]);

define("EndpointURLdecl", EndpointURLserver . "query?output=json&query=" . urlencode("prefix ex: <http://example.org/#>" . "prefix reas: <http://www.w3.org/2000/10/swap/reason#> " . "prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> " . "prefix fresnel: <http://www.w3.org/2004/09/fresnel#> " . "prefix rei: <http://www.w3.org/2000/10/swap/reify#> "));

define("EndpointURLstart", EndpointURLdecl . urlencode("SELECT * WHERE { "));

function bindings($array)
{
    return $array['results']['bindings'];
}

function emptyRtn($array)
{
    return empty(bindings($array));
}

function fragment($URL)
{
    return substr($URL, strpos($URL, "#") + 1);
}

function qryRtnCell($array, $key, $varName)
{
    return bindings($array)[$key][$varName]['value'];
}

function getSPARQLrtn($WHEREcontent)
{
    $qryRtn = file_get_contents(EndpointURLstart . urlencode($WHEREcontent . " } "));
    return json_decode($qryRtn, true);
}

function internalLink($URL, $anchor)
{
    print_r("<a href='" . FreselServerURLprefix . urlencode($URL) . "'>" . $anchor . "</a>\n");
}

$qryRtnAry = getSPARQLrtn(" <" . Resource . "> ?predicate ?object ");

$qryRtnTyp = getSPARQLrtn(" ?resource a reas:Inference . FILTER ( ?resource = <" . Resource . "> ) ");

$qryRtnGiv1 = getSPARQLrtn(" ?explanation reas:gives/rdf:first <" . Resource . "> ");

$qryRtnGiv = getSPARQLrtn(" ?Inferred a reas:Inference ; reas:gives/rdf:rest*/rdf:first ?statement . ");

$qryRtnExp = getSPARQLrtn(" ?Inferred a reas:Inference ; reas:evidence/rdf:first/rdf:rest*/rdf:first ?statement . ");

$lensQueries = getSPARQLrtn(" ?lens fresnel:instanceLensDomain ?query . ");

$lens = qryRtnCell(getSPARQLrtn(" ?lens fresnel:classLensDomain ?type . <" . Resource . "> rdf:type ?type . "), 0, 'lens');

$propList = bindings(getSPARQLrtn(" <" . $lens . "> fresnel:showProperties/rdf:rest*/rdf:first ?prop "));

?>
<html>
<head>
<title>RuleStyle</title>
</head>
<body>
  <?php
foreach (array_keys(bindings($lensQueries)) as $key) {
    $thisLens = qryRtnCell($lensQueries, $key, 'lens');
    $QResult = json_decode(file_get_contents(EndpointURLdecl . urlencode(qryRtnCell($lensQueries, $key, 'query'))), true);
    $triggerURI = qryRtnCell($QResult, 0, 'inference');
    if ($triggerURI == Resource)
        $lens = $thisLens;
}
if ($lens == "http://example.org/#explBox") {
    print_r("<p>Explanation for inference: ");
    foreach (array_keys(bindings($qryRtnGiv)) as $key)
        print_r(fragment(qryRtnCell($qryRtnGiv, $key, 'statement')) . " ");
    print_r("</p>\n<p>");
    foreach (array_keys(bindings($qryRtnExp)) as $key)
        print_r(fragment(qryRtnCell($qryRtnExp, $key, 'statement')) . " ");
    print_r("</p>\n");
} else {
    ?>
	<p> <?php print_r ( fragment ( Resource ) ) ; ?> </p>
	<table class='resourceBox'>
        <?php
    foreach (array_keys($propList) as $key) {
        $predicate1 = $propList[$key]['prop']['value'];
        print_r("<tr class=propertyBox>\n<td class='labelBox'>" . fragment($predicate1) . "</td>\n<td class='objectBox'>\n<span class='valueBox' ");
        if (! emptyRtn($qryRtnGiv1))
            print_r(" style='background-color:yellow' ");
        print_r(">\n");
        $object = qryRtnCell(getSPARQLrtn(" <" . Resource . "> <" . $predicate1 . "> ?object "), 0, 'object');
        internalLink($object, fragment($object));
        if (! emptyRtn($qryRtnGiv1)) {
            print_r("</span> \n<span class='reifyBox'>");
            internalLink("http://example.org/#inf", "(?)");
        }
        print_r("</span></td></tr>");
    }
    ;
    print_r("</table>");
}
?>
</body>
</html>
