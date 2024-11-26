<?php
global $dbh;
global $semanticTypes;
global $repo_url, $handlerURL;

$semanticTypes=array(
    'program' => array('labelGR'=>'Πρόγραμμα','labelEN'=>'Program','concept' => 'https://semantics.gr/authorities/ekt-item-types/Theatrical-program', 'exact'=> 'http://vocab.getty.edu/page/aat/300027217', 
                    'set'=>'programs' ,'setName'=>'Προγράμματα','setDescription'=>'Συλλογή προγραμμάτων από θεατρικές παραστάσεις', 'setDescriptionEN'=>'Collection of programs related to theatrical plays'),
    'pub' => array('labelGR'=>'Δημοσίευμα','labelEN'=>'Publication','concept'=>'https://semantics.gr/authorities/EKTrepository-resourceTypes/1017094458', 'exact'=>'http://vocab.getty.edu/page/aat/300111999', 
                    'set'=>'pubs' ,'setName'=>'Δημοσιεύματα','setDescription'=>'Συλλογή δημοσιευμάτων για θεατρικές παραστάσεις', 'setDescriptionEN'=>'Collection of publications related to theatrical plays'),
    'photo' => array('labelGR'=>'Φωτογραφία','labelEN'=>'Photograph','concept'=>'https://semantics.gr/authorities/ekt-unesco/1476367459', 'exact'=> 'https://vocabularies.unesco.org/thesaurus/concept11166',
                    'set'=>'photos' ,'setName'=>'Φωτογραφίες','setDescription'=>'Συλλογή φωτογραφιών από θεατρικές παραστάσεις', 'setDescriptionEN'=>'Collection of photos from theatrical plays'),
    'histphoto' => array('labelGR'=>'Ιστορική φωτογραφία','labelEN'=>'Historical Photograph','concept'=>'https://semantics.gr/authorities/ekt-unesco/1476367459', 'exact'=> 'https://vocabularies.unesco.org/thesaurus/concept11166',
                    'set'=>'histphotos' ,'setName'=>'Ιστορικές Φωτογραφίες','setDescription'=>'Συλλογή φωτογραφιών από στιγμές που σχετίζονται με την ιστορία του Εθνικού Θεάτρου', 'setDescriptionEN'=>'Collection of photos documenting momments from the history of National Theatre of Greece'),
    'sound' => array('labelGR'=>'Ηχογράφιση','labelEN'=>'Sound Recording','concept'=>'https://semantics.gr/authorities/EKTrepository-resourceTypes/541140624', 'exact'=>'https://vocabularies.unesco.org/thesaurus/concept9812',
                    'set'=>'sounds' ,'setName'=>'Ηχογραφήσεις','setDescription'=>'Συλλογή ηχογραφήσεων από θεατρικές παραστάσεις', 'setDescriptionEN'=>'Collection of sound recordings from theatrical plays'),
    'soundpart' => array('labelGR'=>'Ηχογράφιση','labelEN'=>'Sound Recording','concept'=>'https://semantics.gr/authorities/EKTrepository-resourceTypes/541140624', 'exact'=>'https://vocabularies.unesco.org/thesaurus/concept9812',
                    'set'=>'soundparts' ,'setName'=>'Αποσπάσματα ηχογραφήσεων','setDescription'=>'Συλλογή αποσπασμάτων ηχογραφήσεων από θεατρικές παραστάσεις', 'setDescriptionEN'=>'Collection of parts of sound recordings from theatrical plays'),
    'video' => array('labelGR'=>'Μαγνητοσκόπηση','labelEN'=>'Video Recording','concept'=>'https://semantics.gr/authorities/SSH-LCSH/sh2008000037', 'exact'=>'https://id.loc.gov/authorities/subjects/sh2008000037',
                    'set'=>'videos' ,'setName'=>'Βίντεο','setDescription'=>'Συλλογή μαγνητοσκοπήσεων θεατρικών παραστάσεων', 'setDescriptionEN'=>'Collection of video recordings from theatrical plays'),
    'videopart' => array('labelGR'=>'Μαγνητοσκόπηση','labelEN'=>'Video Recording','concept'=>'http://semantics.gr/authorities/SSH-LCSH/sh2008000037', 'exact'=>'https://id.loc.gov/authorities/subjects/sh2008000037',
                    'set'=>'videoparts' ,'setName'=>'Αποσπάσματα μαγνητοσκοπήσεων','setDescription'=>'Συλλογή αποσπασμάτων μαγνητοσκοπήσεων από θεατρικές παραστάσεις', 'setDescriptionEN'=>'Collection of parts of video recordings from theatrical plays'),
    'poster' => array('labelGR'=>'Αφίσα','labelEN'=>'Poster','concept'=>'https://semantics.gr/authorities/ekt-item-types/afisa', 'exact'=>'https://vocabularies.unesco.org/thesaurus/concept1604',
                    'set'=>'posters' ,'setName'=>'Αφίσες','setDescription'=>'Συλλογή αφισών από θεατρικές παραστάσεις', 'setDescriptionEN'=>'Collection of posters from theatrical plays'),
    'costume' => array('labelGR'=>'Θεατρικό κοστούμι','labelEN'=>'Costume','concept'=>'https://semantics.gr/authorities/craft-item-types/941945856', 'exact'=>'https://vocabularies.unesco.org/thesaurus/concept5474',
                    'set'=>'costumes' ,'setName'=>'Κοστούμια','setDescription'=>'Συλλογή θεατρικών κοστουμιών από παραστάσεις', 'setDescriptionEN'=>'Collection of theatrical costumes from plays')
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

function validateSet($set){
    global $semanticTypes;
    $res = false;

    if(array_key_exists($set, $semanticTypes)){
        $res = true;
    }
    return $res;
}

function getIdentifiers($from, $until, $set){
    global $dbh;
    $res = array();
    $sqlwhere = '';
    if($from!=''){
        $sqlwhere = "WHERE timestamp>='$from' ";
    }

    if($until != ''){
        if($from != ''){
            $sqlwhere .= " AND timestamp<='$until'";
        }else{
            $sqlwhere = "WHERE timestamp<='$until' ";
        }

    }

    if($set != ''){ //selected collection
        if($sqlwhere != ''){
            $sqlwhere .= " AND setgroup='$set' ";
        }else{
            $sqlwhere = "WHERE setgroup='$set' ";
        }
        
    }
    $sql = "WITH ds AS (
                select programID AS itemID, (CASE WHEN modified IS NOT NULL THEN modified ELSE created END) AS timestamp, 'program' AS setgroup
                FROM programs WHERE published=1
                UNION
                select pubID AS itemID, (CASE WHEN modified IS NOT NULL THEN modified ELSE created END) AS timestamp, 'publication' AS setgroup
                FROM publications WHERE published=1
                UNION
                select photoID AS itemID, (CASE WHEN modified IS NOT NULL THEN modified ELSE created END) AS timestamp, 'photo' AS setgroup
                FROM photos WHERE published=1
                UNION
                select histphotoID AS itemID, (CASE WHEN modified IS NOT NULL THEN modified ELSE created END) AS timestamp, 'histphoto' AS setgroup
                FROM historicPhotos WHERE published=1
                UNION
                select soundID AS itemID, (CASE WHEN modified IS NOT NULL THEN modified ELSE created END) AS timestamp, 'sound' AS setgroup
                FROM sounds WHERE published=1
                UNION
                select soundpartID AS itemID, (CASE WHEN modified IS NOT NULL THEN modified ELSE created END) AS timestamp, 'soundspart' AS setgroup
                FROM soundParts WHERE published=1
                UNION
                select videoID AS itemID, (CASE WHEN modified IS NOT NULL THEN modified ELSE created END) AS timestamp, 'video' AS setgroup
                FROM videos WHERE published=1
                UNION
                select videopartID AS itemID, (CASE WHEN modified IS NOT NULL THEN modified ELSE created END) AS timestamp, 'videopart' AS setgroup
                FROM videoParts WHERE published=1
                UNION
                select posterID AS itemID, (CASE WHEN modified IS NOT NULL THEN modified ELSE created END) AS timestamp, 'poster' AS setgroup
                FROM posters WHERE published=1
                UNION
                select costumeID AS itemID, (CASE WHEN modified IS NOT NULL THEN modified ELSE created END) AS timestamp, 'costume' AS setgroup
                FROM costumes WHERE published=1
                )
            select * from ds $sqlwhere
            ORDER BY  timestamp, setgroup";
    try{
        $stmt=$dbh->query($sql) or die();
        $res=$stmt->fetchAll(PDO::FETCH_ASSOC);

    }catch(PDOException $err){
        echo $err->getMessage();
    }
    return $res;
}

?>