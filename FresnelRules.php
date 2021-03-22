<?php  define ( "EndpointURLserver"     , "http://localhost:3030/Fresnel/"              ) ;
  define ( "FreselServerURLprefix" , "http://localhost/TransFresnel.php?resource=" ) ;
  define ( "Resource"              , $_GET [ "resource" ]                          ) ; 

  define (
    "EndpointURLstart" ,
    EndpointURLserver .
    "query?output=json&query=" .
    urlencode (
      "prefix ex: <http://example.org/#>" .
      "prefix reas: <http://www.w3.org/2000/10/swap/reason#> " .
      "prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> " .
      "prefix fresnel: <http://www.w3.org/2004/09/fresnel#> " .
      "SELECT * WHERE { "
     )
   ) ;
  
  $qryRtnAry = getSPARQLrtn (
    " <" . Resource . "> ?predicate ?object "
  ) ;
							     
  $qryRtnTyp = getSPARQLrtn (
    " ?resource a reas:Inference . FILTER ( ?resource = <" . Resource . "> ) "
  ) ;
							     
  $qryRtnGiv1 = getSPARQLrtn (
    " ?explanation reas:gives/rdf:first <" . Resource . "> "
  ) ;

  $qryRtnGiv = getSPARQLrtn (
    " ?Inferred a reas:Inference ; reas:gives/rdf:rest*/rdf:first ?statement . "
  ) ;

  $qryRtnExp = getSPARQLrtn (
    " ?Inferred a reas:Inference ; reas:evidence/rdf:first/rdf:rest*/rdf:first ?statement . "
  ) ;

  $lens = qryRtnCell (
    getSPARQLrtn (
      " ?lens fresnel:classLensDomain ?type . <" . Resource . "> rdf:type ?type . " 
    ) ,
    0 ,
    'lens'
  ) ;

  $propList = bindings ( getSPARQLrtn (
      " <" . $lens . "> fresnel:showProperties/rdf:rest*/rdf:first ?prop " 
  ) ) ;

  function bindings ( $array ) { return $array [ 'results' ] [ 'bindings' ] ; }
  function emptyRtn ( $array ) { return empty ( bindings ( $array ) )       ; } 

  function fragment ( $URL ) {
    return substr ( $URL, strpos ( $URL, "#" ) + 1 );
  }

  function qryRtnCell ( $array , $key , $varName ) {
    return bindings ( $array ) [ $key ] [ $varName ] [ 'value' ] ;
  }

  function getSPARQLrtn ( $WHEREcontent ) {
    $qryRtn = file_get_contents (
      EndpointURLstart .
      urlencode ( 
        $WHEREcontent .
        " } "
      ) 
    ) ;
    return json_decode ( $qryRtn, true ) ;
  }

  function internalLink ( $URL , $anchor ) {
    print_r (
      "<a href='" .
      FreselServerURLprefix .
      urlencode ( $URL ) .
      "'>" .		  
      $anchor .
      "</a>"
    ) ;
  }

?>

<html>
 <head>
  <title>TransFresnel</title>
 </head>
 <body>
  <?php
    if ( $lens == "http://example.org/#explsLens" ) {
      print_r ( "<p>Explanation for inference: " ) ;
      foreach ( array_keys ( bindings ( $qryRtnGiv ) ) as $key ) 
        print_r (
          fragment ( qryRtnCell ( $qryRtnGiv, $key, 'statement' ) ) .
          " "
        ) ;
      print_r ( "</p><p>" ) ;
      foreach ( array_keys ( bindings ( $qryRtnExp ) ) as $key )
        print_r (
          fragment ( qryRtnCell ( $qryRtnExp, $key, 'statement' ) ) .
          " "
        ) ;
      print_r ( "</p>" ) ;
    } else {
      ?>
        <p> <?php print_r ( fragment ( Resource ) ) ; ?> </p>
      <table class=resourceBox>
        <?php
           foreach ( array_keys ( $propList ) as $key ) {
             $predicate1 = $propList [ $key ] [ 'prop' ] ['value'];
             print_r (
	       "<tr class=propertyBox><td class=labelBox>" .
               fragment ( $predicate1 ) .
               "</td><td class=valueBox><p "
	     ) ;
             if ( ! emptyRtn ( $qryRtnGiv1 ) )
               print_r ( " style='background-color:yellow' " ) ;
             print_r ( ">" ) ;
	     $object = qryRtnCell ( 
               getSPARQLrtn ( " <" . Resource . "> <" . $predicate1 . "> ?object " ) ,
	       0 ,
	       'object'
             ) ;
             internalLink ( $object, fragment ( $object ) ) ;
	     if ( ! emptyRtn ( $qryRtnGiv1 ) ) {
	       print_r ( " " ) ;
	       internalLink ( "http://example.org/#inf", "(?)" ) ;
	     }
	     print_r ( "</p></td></tr>" ) ;
           } ;
      print_r ( "</table>" ) ;
    }
   ?>
 </body>
</html>
