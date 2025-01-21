<?php
/***************
 * Configuration
 * *************/
//$repo_url='https://www.nt-archive.gr/rdf';
//$repo_url='http://local.nt2.rdf';
//$handlerURL='https://hdl.handle.net/99999999999999';
$handlerURL='https://www.nt-archive.gr';
$physicalCollectionPath="E:/Sites-IIS/NT_Collections";
$NT_rdf='https://id.loc.gov/authorities/names/n90693693';


$poplerpath="C:\poppler-24.08.0\Library\bin";
$URL_site_collections="https://www.nt-archive.gr/collections";


$semanticTypes=array(
    'programs' => array('labelGR'=>'Πρόγραμμα','labelEN'=>'Program','concept' => 'https://semantics.gr/authorities/ekt-item-types/Theatrical-program', 'exact'=> 'http://vocab.getty.edu/page/aat/300027217', 
                    'set'=>'programs' ,'setName'=>'Προγράμματα','setDescription'=>'Συλλογή προγραμμάτων από θεατρικές παραστάσεις', 'setDescriptionEN'=>'Collection of programs related to theatrical plays'),
    'publications' => array('labelGR'=>'Δημοσίευμα','labelEN'=>'Publication','concept'=>'https://semantics.gr/authorities/EKTrepository-resourceTypes/1017094458', 'exact'=>'http://vocab.getty.edu/page/aat/300111999', 
                    'set'=>'pubications' ,'setName'=>'Δημοσιεύματα','setDescription'=>'Συλλογή δημοσιευμάτων για θεατρικές παραστάσεις', 'setDescriptionEN'=>'Collection of publications related to theatrical plays'),
    'photos' => array('labelGR'=>'Φωτογραφία','labelEN'=>'Photograph','concept'=>'https://semantics.gr/authorities/ekt-unesco/1476367459', 'exact'=> 'https://vocabularies.unesco.org/thesaurus/concept11166',
                    'set'=>'photos' ,'setName'=>'Φωτογραφίες','setDescription'=>'Συλλογή φωτογραφιών από θεατρικές παραστάσεις', 'setDescriptionEN'=>'Collection of photos from theatrical plays'),
    'histphotos' => array('labelGR'=>'Ιστορική φωτογραφία','labelEN'=>'Historical Photograph','concept'=>'https://semantics.gr/authorities/ekt-unesco/1476367459', 'exact'=> 'https://vocabularies.unesco.org/thesaurus/concept11166',
                    'set'=>'histphotos' ,'setName'=>'Ιστορικές Φωτογραφίες','setDescription'=>'Συλλογή φωτογραφιών από στιγμές που σχετίζονται με την ιστορία του Εθνικού Θεάτρου', 'setDescriptionEN'=>'Collection of photos documenting momments from the history of National Theatre of Greece'),
    'sounds' => array('labelGR'=>'Ηχογράφιση','labelEN'=>'Sound Recording','concept'=>'https://semantics.gr/authorities/EKTrepository-resourceTypes/541140624', 'exact'=>'https://vocabularies.unesco.org/thesaurus/concept9812',
                    'set'=>'sounds' ,'setName'=>'Ηχογραφήσεις','setDescription'=>'Συλλογή ηχογραφήσεων από θεατρικές παραστάσεις', 'setDescriptionEN'=>'Collection of sound recordings from theatrical plays'),
    'soundparts' => array('labelGR'=>'Ηχογράφιση','labelEN'=>'Sound Recording','concept'=>'https://semantics.gr/authorities/EKTrepository-resourceTypes/541140624', 'exact'=>'https://vocabularies.unesco.org/thesaurus/concept9812',
                    'set'=>'soundparts' ,'setName'=>'Αποσπάσματα ηχογραφήσεων','setDescription'=>'Συλλογή αποσπασμάτων ηχογραφήσεων από θεατρικές παραστάσεις', 'setDescriptionEN'=>'Collection of parts of sound recordings from theatrical plays'),
    'videos' => array('labelGR'=>'Μαγνητοσκόπηση','labelEN'=>'Video Recording','concept'=>'https://semantics.gr/authorities/SSH-LCSH/sh2008000037', 'exact'=>'https://id.loc.gov/authorities/subjects/sh2008000037',
                    'set'=>'videos' ,'setName'=>'Βίντεο','setDescription'=>'Συλλογή μαγνητοσκοπήσεων θεατρικών παραστάσεων', 'setDescriptionEN'=>'Collection of video recordings from theatrical plays'),
    'videoparts' => array('labelGR'=>'Μαγνητοσκόπηση','labelEN'=>'Video Recording','concept'=>'http://semantics.gr/authorities/SSH-LCSH/sh2008000037', 'exact'=>'https://id.loc.gov/authorities/subjects/sh2008000037',
                    'set'=>'videoparts' ,'setName'=>'Αποσπάσματα μαγνητοσκοπήσεων','setDescription'=>'Συλλογή αποσπασμάτων μαγνητοσκοπήσεων από θεατρικές παραστάσεις', 'setDescriptionEN'=>'Collection of parts of video recordings from theatrical plays'),
    'posters' => array('labelGR'=>'Αφίσα','labelEN'=>'Poster','concept'=>'https://semantics.gr/authorities/ekt-item-types/afisa', 'exact'=>'https://vocabularies.unesco.org/thesaurus/concept1604',
                    'set'=>'posters' ,'setName'=>'Αφίσες','setDescription'=>'Συλλογή αφισών από θεατρικές παραστάσεις', 'setDescriptionEN'=>'Collection of posters from theatrical plays'),
    'costumes' => array('labelGR'=>'Θεατρικό κοστούμι','labelEN'=>'Costume','concept'=>'https://semantics.gr/authorities/craft-item-types/941945856', 'exact'=>'https://vocabularies.unesco.org/thesaurus/concept5474',
                    'set'=>'costumes' ,'setName'=>'Κοστούμια','setDescription'=>'Συλλογή θεατρικών κοστουμιών από παραστάσεις', 'setDescriptionEN'=>'Collection of theatrical costumes from plays'),
    'plays' => array('labelGR'=>'Θεατρική παράσταση','labelEN'=>'Theatrical play','concept'=>'http://semantics.gr/authorities/ekt-item-types/Play', 'exact'=>'http://vocab.getty.edu/aat/300201028',
                    'set'=>'plays' ,'setName'=>'Παραστάσεις','setDescription'=>'Συλλογή θεατρικών παραστάσεεων', 'setDescriptionEN'=>'Collection of theatrical plays')                
);

$config_play_genre_el = array(
    "0" => "Αρχαίο δράμα: Τραγωδία",
    "1" => "Μιούζικαλ",
    "2" => "Πρόζα με τραγούδια",
    "3" => "Live streaming",
);

$config_play_genre_en = array(
    "0" => "Ancient drama: Tragedy",
    "1" => "Musical",
    "2" => "Prose with songs",
    "3" => "Live streaming",
);

$config_play_type_el = array(
    "0" => "Ενιαία παράσταση",
    "1" => "Δραματουργική σύνθεση",
    "2" => "Σύνθεση"
);


$config_play_type_en = array(
    "0" => "Single show",
    "1" => "Dramatic composition",
    "2" => "Composition"
);
?>