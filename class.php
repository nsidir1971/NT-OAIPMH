<?php
global $dbh;
global $semanticTypes;
global $repo_url, $handlerURL;

$semanticTypes=array(
    'program' => array('labelGR'=>'Πρόγραμμα','labelEN'=>'Program','concept' => 'https://semantics.gr/authorities/ekt-item-types/Theatrical-program', 'exact'=> 'http://vocab.getty.edu/page/aat/300027217'),
    'pub' => array('labelGR'=>'Δημοσίευμα','labelEN'=>'Publication','concept'=>'https://semantics.gr/authorities/EKTrepository-resourceTypes/1017094458', 'exact'=>'http://vocab.getty.edu/page/aat/300111999'),
    'photo' => array('labelGR'=>'Φωτογραφία','labelEN'=>'Photograph','concept'=>'https://semantics.gr/authorities/ekt-unesco/1476367459', 'exact'=> 'https://vocabularies.unesco.org/thesaurus/concept11166'),
    'histphoto' => array('labelGR'=>'Ιστορική φωτογραφία','labelEN'=>'Historical Photograph','concept'=>'https://semantics.gr/authorities/ekt-unesco/1476367459', 'exact'=> 'https://vocabularies.unesco.org/thesaurus/concept11166'),
    'sound' => array('labelGR'=>'Ηχογράφιση','labelEN'=>'Sound Recording','concept'=>'https://semantics.gr/authorities/EKTrepository-resourceTypes/541140624', 'exact'=>'https://vocabularies.unesco.org/thesaurus/concept9812'),
    'soundpart' => array('labelGR'=>'Ηχογράφιση','labelEN'=>'Sound Recording','concept'=>'https://semantics.gr/authorities/EKTrepository-resourceTypes/541140624', 'exact'=>'https://vocabularies.unesco.org/thesaurus/concept9812'),
    'video' => array('labelGR'=>'Μαγνητοσκόπηση','labelEN'=>'Video Recording','concept'=>'https://semantics.gr/authorities/SSH-LCSH/sh2008000037', 'exact'=>'https://id.loc.gov/authorities/subjects/sh2008000037'),
    'videopart' => array('labelGR'=>'Μαγνητοσκόπηση','labelEN'=>'Video Recording','concept'=>'http://semantics.gr/authorities/SSH-LCSH/sh2008000037', 'exact'=>'https://id.loc.gov/authorities/subjects/sh2008000037'),
    'poster' => array('labelGR'=>'Αφίσα','labelEN'=>'Poster','concept'=>'https://semantics.gr/authorities/ekt-item-types/afisa', 'exact'=>'https://vocabularies.unesco.org/thesaurus/concept1604'),
    'costume' => array('labelGR'=>'Θεατρικό κοστούμι','labelEN'=>'Costume','concept'=>'https://semantics.gr/authorities/craft-item-types/941945856', 'exact'=>'https://vocabularies.unesco.org/thesaurus/concept5474'),
    'musicScore' => array('labelGR'=>'Παρτιτούρα','labelEN'=>'Music Score','concept'=>'https://semantics.gr/authorities/openarchives-item-types/Score', 'exact'=>'https://vocab.getty.edu/page/aat/300111999')
);

ini_set('memory_limit', '-1');


function db_connect(): PDO
{
    global $dbh;
    try {
        $dbh = new PDO(
            "sqlsrv:Server=194.177.217.92;Database=NT_DB", "ethnikoTheatroUSR", "ethilsp!23"); //MS SQL Server connection string
        /*** set the error reporting attribute ***/
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $dbh;
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
}

function validateDate($date){
    $timestamp = strtotime($date); 
	return $timestamp ? true : false; 
}

function validatemetaPref($param){
    $res = false;
    switch($param){
        case 'oai_dc':
        case 'edm':
            $res = true;
            break;
    }
    return $res;
}

?>