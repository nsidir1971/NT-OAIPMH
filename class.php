<?php
global $dbh;
global $semanticTypes;
global $repo_url, $handlerURL;
global $poplerpath;
global $physicalCollectionPath;
global $semanticTypes;


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

function validateVerb($verb){
    $res = false;
    $accepableVerbs = [
        'Identify',
        'ListMetadataFormats',
        'GetRecord',
        'ListRecords',
        'ListSets',
        'ListIdentifiers',
    ];
    if(in_array($verb, $accepableVerbs)){
        $res = true;
    }
    return $res;

}

function displayErrorResponse($errorType, $requestDate, $verb, $metadataPrefix, $setGroup, $from, $until, $identifier, $resumptionToken){
    global $oaiURL;
    
    $settext = '';
    if($setGroup != ''){
        $settext = ' set="' . $setGroup . '" ';
    }
    $fromtext = '';
    if($from != ''){
        $fromtext = ' from="' . $from . '"';
    }
    $untiltext = '';
    if($until != ''){
        $untiltext= ' until="' . $until . '"';
    }
    
    $restxt ='';
    if($resumptionToken != ''){
        $restext= ' resumptionToken="' . $resumptionToken . '"';
    }

    switch($errorType){
        case 'badArgument':
            $displayErrorText = 'Missing or illegal argument in the request.';
            $XMLrequest = '<request>' . $oaiURL . '</request>';
            break;
        case 'badResumptionToken':
            $displayErrorText = 'The resumptionToken is invalid or expired.';
            $XMLrequest = ' <request verb="' . $verb . '" ' . $restext. '>' . $oaiURL . '</request>';
            break;
        case 'badVerb':
            $displayErrorText = 'The verb argument is illegal or missing.';
            $XMLrequest = '<request>' . $oaiURL . '</request>';
            break;
        case 'cannotDisseminateFormat':
            $displayErrorText = 'The metadata format is not supported by the repository.';
            $XMLrequest = '<request verb="' . $verb . '" identifier="' . $identifier . '" metadataPrefix="' . $metadataPrefix . '">' . $oaiURL . '</request>';
            break;
        case 'idDoesNotExist':
            $displayErrorText = 'The specified identifier does not exist.';
            $XMLrequest = '<request verb="' . $verb . '" identifier="' . $identifier . '" metadataPrefix="' . $metadataPrefix . '">' . $oaiURL . '</request>';
            break;
        case 'noRecordsMatch':
            $displayErrorText = 'No records match the provided criteria.';
            $XMLrequest = ' <request verb="' . $verb . '" metadataPrefix="' . $metadataPrefix . '" ' . $settext . $fromtext . $untiltext . $restext . '>' . $oaiURL . '</request>';
            break;
        case 'noMetadataFormats':
            $displayErrorText = 'No metadata formats are available for this identifier.';
            $XMLrequest = '<request verb="' . $verb . '" metadataPrefix="' . $metadataPrefix . '" identifier="' . $identifier . '">' . $oaiURL . '</request>';
            break;
        case 'noSetHierarchy':
            $displayErrorText = 'This repository does not support sets.';
            $XMLrequest = '<request verb="' . $verb . '">' . $oaiURL . '</request>';
            break;    

    }
    $XMLresponse = '<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/">
                        <responseDate>' . $requestDate . '</responseDate>' .
                        $XMLrequest . 
                        '<error code="' . $errorType . '">' . $displayErrorText . '</error>
                    </OAI-PMH>';
    return $XMLresponse;                
}

function validatemetaPref($param){
    $res = false;
    switch($param){
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
                select programID AS itemID, (CASE WHEN modified IS NOT NULL THEN modified ELSE created END) AS timestamp, 'programs' AS setgroup
                FROM programs WHERE published=1
                UNION
                select pubID AS itemID, (CASE WHEN modified IS NOT NULL THEN modified ELSE created END) AS timestamp, 'publications' AS setgroup
                FROM publications WHERE published=1
                UNION
                select photoID AS itemID, (CASE WHEN modified IS NOT NULL THEN modified ELSE created END) AS timestamp, 'photos' AS setgroup
                FROM photos WHERE published=1
                UNION
                select histphotoID AS itemID, (CASE WHEN modified IS NOT NULL THEN modified ELSE created END) AS timestamp, 'histphotos' AS setgroup
                FROM historicPhotos WHERE published=1
                UNION
                select soundID AS itemID, (CASE WHEN modified IS NOT NULL THEN modified ELSE created END) AS timestamp, 'sounds' AS setgroup
                FROM sounds WHERE published=1
                UNION
                select soundpartID AS itemID, (CASE WHEN modified IS NOT NULL THEN modified ELSE created END) AS timestamp, 'soundparts' AS setgroup
                FROM soundParts WHERE published=1
                UNION
                select videoID AS itemID, (CASE WHEN modified IS NOT NULL THEN modified ELSE created END) AS timestamp, 'videos' AS setgroup
                FROM videos WHERE published=1
                UNION
                select videopartID AS itemID, (CASE WHEN modified IS NOT NULL THEN modified ELSE created END) AS timestamp, 'videoparts' AS setgroup
                FROM videoParts WHERE published=1
                UNION
                select posterID AS itemID, (CASE WHEN modified IS NOT NULL THEN modified ELSE created END) AS timestamp, 'posters' AS setgroup
                FROM posters WHERE published=1
                UNION
                select costumeID AS itemID, (CASE WHEN modified IS NOT NULL THEN modified ELSE created END) AS timestamp, 'costumes' AS setgroup
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

function getIdentifierTimestamp($set, $identID){
    global $dbh;
    $res=array();
    switch($set){
        case 'programs':
            $fldid='programID';
            $tbl = 'programs';
            break;
        case 'publications':
            $fldid='pubID';
            $tbl = 'publications';
            break;
        case 'photos':
            $fldid='photoID';
            $tbl = 'photos';
            break;    
        case 'histphotos':
            $fldid='histPhotoID';
            $tbl = 'historicPhotos';
            break;
        case 'sounds':
            $fldid='soundID';
            $tbl = 'sounds';
            break;                        
        case 'soundparts':
            $fldid='soundpartID';
            $tbl = 'soundParts';
            break;                            
        case 'videos':
            $fldid='videoID';
            $tbl = 'videos';
            break;                        
        case 'videoparts':
            $fldid='videopartID';
            $tbl = 'videoParts';
            break;
        case 'posters':
            $fldid='posterID';
            $tbl = 'posters';
            break;
        case 'costumes':
            $fldid='costumeID';
            $tbl='costumes';
            break;
    }
    $sql = "SELECT (CASE WHEN modified IS NOT NULL THEN modified ELSE created END) AS timestamp
                FROM $tbl WHERE $fldid=:itid AND published=1";
    try{
        $stmt=$dbh->prepare($sql);
        $stmt->bindParam(":itid", $identID);
        $stmt->execute();
        $res=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!empty($res)){
            return $res['timestamp'];
        }else{
            return $res;
        }
        
    }catch(PDOException $err){
        echo $err->getMessage();
    }

}




function rdf_get_item($item, $itemID){
    global $dbh;
    global $repo_url;
    global $semanticTypes;
    global $handlerURL;
    global $NT_rdf;
    global $semanticTypes;
    global $URL_site_collections;
    $rdf='';
    $XMLprov='';
    $XMLaggre='';
    $XMLweb='';
    $XMLextras='';
    $collectionFolder='';
    $provCHO='';
    $aggre='';
    $webRes='';
    $XML_DC_Subject = '';
    $extra_work_subjects= '';

    if(check_item_availability($item, $itemID)){
        $info=get_item_details($item, $itemID);
        $hasplay=false;
        
        $xmlRelation = '';
        $xmlSkosRelation = '';
        if($info['playID']!='' and is_numeric($info['playID'])){
            $hasplay=true;
            $playYear=getPlayYear_v2($info['playID']);
            $extra_work_subjects = get_xml_work_subjects($info['playID']);
            $xmlRelation = '<dc:relation rdf:resource="' . $handlerURL  . '/play/'. $info['playID'] . '"/>';
            $titlePlayEN='';
            if( $info['playTitle']!=$info['playTitleEN'] and $info['playTitleEN']!=''){
                $titlePlayEN = '<skos:prefLabel xml:lang="en">' . fix_title($info['playTitleEN']) . '</skos:prefLabel>';
            }
            $xmlSkosRelation = '<skos:Concept rdf:about="' . $handlerURL  . '/play/'. $info['playID'] . '">
                                    <skos:prefLabel xml:lang="el">' . fix_title($info['playTitle']) . '</skos:prefLabel>'. 
                                $titlePlayEN . 
                                '</skos:Concept>';
            
        }
        
        switch ($item){
            //TODO: Check in all cases the timing information. Wherever is directly available, otherwise get it from play year
            case 'programs':
                
                $XML_DC_Subject = '<dc:subject xml:lang="el">Θεατρική παράσταση</dc:subject>
                                   <dc:subject xml:lang="el">Πολιτιστική επικοινωνία</dc:subject>' . $extra_work_subjects;
                $title='Πρόγραμμα για την παράσταση &quot;' . fix_title($info['playTitle']). '&quot; ('.$playYear.')';
                $xmlENtitle='';
                if( $info['playTitle']!=$info['playTitleEN'] and $info['playTitleEN']!=''){
                    $titleEN='Program for the play &quot;' . fix_title($info['playTitleEN']). '&quot; ('.$playYear.')';
                    $xmlENtitle='<dc:title xml:lang="en">' . $titleEN . '</dc:title>';
                }

                $descr=getProgramDescription($itemID);
                $xmlENdescr='';
                if($descr['el']!=$descr['en'] AND $descr['en']!=''){
                    $xmlENdescr='<dc:description xml:lang="en" rdf:parseType="Literal">' . $descr['en'] . '</dc:description>';
                }
                
                $XMLprov='<dc:title xml:lang="el">' . $title . '</dc:title>'.
                          $xmlENtitle .
                          '<dc:type rdf:resource="' . $semanticTypes['programs']['concept'] . '" />
                          <dc:identifier>' . $handlerURL . "/program/" . $itemID . '</dc:identifier>
                          <dc:identifier>' . 'program/' . $itemID . '</dc:identifier>'.
                          $XML_DC_Subject.
                          '<dc:description xml:lang="el" rdf:parseType="Literal">' . $descr['el'] . '</dc:description>'.
                          $xmlENdescr. 
                         '<dc:publisher rdf:resource="' . $NT_rdf . '" />' .
                         $xmlRelation .
                         '<dcterms:created>' . $playYear. '</dcterms:created>
                          <dc:date>' . $playYear . '</dc:date>
                          <edm:type>TEXT</edm:type>';

                $prgramLangs=getLangs('program', $itemID);
                $XMLlangs='';
                foreach($prgramLangs as $lang){
                    $XMLlangs.='<dc:language>' . $lang['lexiconURL'] . '</dc:language>';
                }
                $XMLprov.=$XMLlangs;

                
                $itemURLfile = '';
                $thumbURLfile = '';
                $files=get_physical_details('program', $itemID, $info['programFile']);
                
                if(!empty($files)){
                    $itemURLfile = $files['physicalFileURL'];
                    $thumbURLfile = $files['thumbURL'];

                    if($files['size']<1048576){
                        $displaySize=intval($files['size']/1024). ' KB';
                    }elseif($files['size']>1048576 and $files['size']<1073741824){
                        $displaySize=intval($files['size']/(1024*1024)). ' MB';
                    }elseif($files['size']>1073741824){
                        $displaySize=intval($files['size']/(1024*1024*1024)). ' GB';
                    }
                    $semantic=$semanticTypes['programs'];

                    $pgtxt='pages';
                    if($files['pagecount']==1){
                        $pgtxt='page';
                    }

                    //resource must be found for National Theatre
                    $XMLweb='<dc:type rdf:resource="' . $semantic['concept'] . '" />
                            <dc:creator rdf:resource="' . $NT_rdf . '" />
                            <dc:format>' . $files['mime'] . '</dc:format>
                            <dc:extend>' . $displaySize . '</dc:extend>
                            <dcterms:extent>' . $files['pagecount'] . ' ' . $pgtxt . '</dcterms:extent> 
                            <dc:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/" />';  
                                             
                    $XMLextras='<skos:Concept rdf:about="' . $semanticTypes['programs']['concept'] . '">
                                    <skos:prefLabel xml:lang="el">' . $semanticTypes['programs']['labelGR'] . '</skos:prefLabel>
                                    <skos:prefLabel xml:lang="en">' . $semanticTypes['programs']['labelEN'] . '</skos:prefLabel>
                                    <skos:exactMatch rdf:resource="'  . $semanticTypes['programs']['exact'] . '"/>
                                </skos:Concept>
                                <skos:Agent rdf:about="' . $NT_rdf . '">
                                    <skos:prefLabel xml:lang="el">Εθνικό Θέατρο</skos:prefLabel>
                                    <skos:prefLabel xml:lang="en">National Theatre</skos:prefLabel>
                                </skos:Agent>' . $xmlSkosRelation;         
                }    
                $XMLaggre='<edm:aggregatedCHO rdf:resource="#' . $itemID . '"/>
                <edm:dataProvider>Εθνικό Θέατρο</edm:dataProvider>
                <edm:isShownAt rdf:resource="' . $handlerURL . "/program/" . $itemID . '"/>
                <edm:isShownBy rdf:resource="' . $itemURLfile . '"/>
                <edm:object rdf:resource="' . $thumbURLfile . '"/>
                <edm:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/"/>
                <dc:rights>Εθνικό Θέατρο</dc:rights>';      

                break;
            case 'publications':
                $XML_DC_Subject = '<dc:subject xml:lang="el">Τύπος</dc:subject>
                                   <dc:subject xml:lang="en">Press</dc:subject>' . $extra_work_subjects;

                $xmlENpubtitle='';
                if($info['pubTitle']!=$info['pubTitleEN'] AND $info['pubTitleEN']!=''){
                    $xmlENpubtitle='<dc:title xml:lang="en">' . $info['pubTitleEN'] . '</dc:title>';
                }

                $descrEL='Δημοσίευμα για την παράσταση &quot;' . fix_title($info['playTitle']). '&quot; ('.$playYear.')';
                $xmlENdescr='';
                if($info['playTitle']!=$info['playTitleEN'] and $info['playTitleEN']!=''){
                     $descrEN='Article for the play &quot;' . fix_title($info['playTitleEN']). '&quot; ('.$playYear.')';
                     $xmlENdescr='<dc:description xml:lang="en" rdf:parseType="Literal">' . $descrEN . '</dc:description>';
                }

                $XMLprov='<dc:title xml:lang="el">' . $info['pubTitle'] . '</dc:title>'
                          . $xmlENpubtitle .
                          '<dc:type rdf:resource="' . $semanticTypes['publications']['concept'] . '" />
                          <dc:identifier>' . $handlerURL . "/pub/" . $itemID . '</dc:identifier>
                          <dc:identifier>' . 'pub/' . $itemID . '</dc:identifier>'.
                          $XML_DC_Subject.
                          '<dc:description xml:lang="el" rdf:parseType="Literal">' . $descrEL . '</dc:description>'.
                          $xmlENdescr . $xmlRelation .
                          '<dc:publisher>' . $info['pubName'] . '</dc:publisher>
                          <dcterms:issued>' . $info['pubDate'] . '</dcterms:issued> 
                          <dc:date>' . $info['pubDate'] . '</dc:date>
                          <edm:type>TEXT</edm:type>';

                $pubauthorXML=getPubAuthor($itemID);
                if($pubauthorXML!=''){
                    $XMLprov.=$pubauthorXML;
                }

                $pubMedium=getCategory('pubMedium', $info['pubMediumID']);
                if(!empty($pubMedium)){
                    $XMLprov.='<dcterms:medium rdf:resource="' . $pubMedium['lexiconURL'] . '" />';


                    $xmlENpubmediumdescr='';
                    if($pubMedium['descr']!=$pubMedium['descrEN'] AND $pubMedium['descrEN']!=''){
                        $xmlENpubmediumdescr='<skos:prefLabel xml:lang="en">' . $pubMedium['descrEN'] . '</skos:prefLabel>';
                    }
                    $XMLextras.='<skos:Concept rdf:about="' . $pubMedium['lexiconURL'] . '">
                                    <skos:prefLabel xml:lang="el">' . $pubMedium['descr'] . '</skos:prefLabel>'
                                    . $xmlENpubmediumdescr .
                                 '</skos:Concept>';
                }

                $pubType=getCategory('pubType', $info['pubTypeID']);
                if(!empty($pubType)){
                    $XMLprov.='<dc:subject rdf:resource="' . $pubType['lexiconURL'] . '" />';


                    $xmlENpubtype='';
                    if($pubType['descr']!=$pubType['descrEN'] AND $pubType['descrEN']!=''){
                        $xmlENpubtype='<skos:prefLabel xml:lang="en">' . $pubType['descrEN'] . '</skos:prefLabel>';
                    }
                    $XMLextras.='<skos:Concept rdf:about="' . $pubType['lexiconURL'] . '">
                                    <skos:prefLabel xml:lang="el">' . $pubType['descr'] . '</skos:prefLabel>'
                                  . $xmlENpubtype .  
                                 '</skos:Concept>';
                }

                
                $prgramLangs=getLangs('pub', $itemID);
                $XMLlangs='';
                foreach($prgramLangs as $lang){
                    $XMLlangs.='<dc:language>' . $lang['lexiconURL'] . '</dc:language>';
                }
                $XMLprov.=$XMLlangs;

                $itemURLfile = '';
                $thumbURLfile = '';
               
                $files=get_physical_details('pub', $itemID, $info['pubFile']);
                if(!empty($files)){
                    $itemURLfile = $files['physicalFileURL'];
                    $thumbURLfile = $files['thumbURL'];
                    if($files['size']<1048576){
                        $displaySize=intval($files['size']/1024). ' KB';
                    }elseif($files['size']>1048576 and $files['size']<1073741824){
                        $displaySize=intval($files['size']/(1024*1024)). ' MB';
                    }elseif($files['size']>1073741824){
                        $displaySize=intval($files['size']/(1024*1024*1024)). ' GB';
                    }
                    $semantic=$semanticTypes['publications'];

                    $pgtxt='pages';
                    if($files['pagecount']==1){
                        $pgtxt='page';
                    }
                    //resource must be found for National Theatre
                    $XMLweb='<dc:type rdf:resource="' . $semantic['concept'] . '" />
                            <dc:creator rdf:resource="' . $NT_rdf . '" />
                            <dc:format>' . $files['mime'] . '</dc:format>
                            <dc:extend>' . $displaySize . '</dc:extend>
                            <dcterms:extent>' . $files['pagecount'] . ' ' . $pgtxt .'</dcterms:extent> 
                            <dc:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/" />';  
                           
                    $XMLextras.='<skos:Concept rdf:about="' . $semanticTypes['publications']['concept'] . '">
                                    <skos:prefLabel xml:lang="el">' . $semanticTypes['publications']['labelGR'] . '</skos:prefLabel>
                                    <skos:prefLabel xml:lang="en">' . $semanticTypes['publications']['labelEN'] . '</skos:prefLabel>
                                    <skos:exactMatch rdf:resource="'  . $semanticTypes['publications']['exact'] . '"/>
                                </skos:Concept>
                                <skos:Agent rdf:about="' . $NT_rdf . '">
                                    <skos:prefLabel xml:lang="el">Εθνικό Θέατρο</skos:prefLabel>
                                    <skos:prefLabel xml:lang="en">National Theatre</skos:prefLabel>
                                </skos:Agent>' . $xmlSkosRelation;         
                }   
                $XMLaggre='<edm:aggregatedCHO rdf:resource="#' . $itemID . '"/>
                    <edm:dataProvider>Εθνικό Θέατρο</edm:dataProvider>
                    <edm:isShownAt rdf:resource="' . $handlerURL . "/pub/" . $itemID . '"/>
                    <edm:isShownBy rdf:resource="' . $itemURLfile . '"/>
                    <edm:object rdf:resource="' . $thumbURLfile . '"/>
                    <edm:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/"/>
                    <dc:rights>Εθνικό Θέατρο</dc:rights>';  
                break;
            case 'photos':
                $title='Φωτογραφία από την παράσταση &quot;' . fix_title($info['playTitle']). '&quot; ('.$playYear.')';

                $xmlENtitle='';
                if($info['playTitle']!=$info['playTitleEN'] and $info['playTitleEN']!=''){
                    $titleEN='Photo from the play &quot;' . fix_title($info['playTitleEN']). '&quot; ('.$playYear.')';
                    $xmlENtitle='<dc:title xml:lang="en" rdf:parseType="Literal">' . $titleEN . '</dc:title>';
                }
                
                $XML_DC_Subject = '<dc:subject xml:lang="el">Θεατρική παράσταση</dc:subject>
                                   <dc:subject xml:lang="en">Theatrical play</dc:subject>
                                   <dc:subject xml:lang="el">Ηθοποιοί</dc:subject>
                                   <dc:subject xml:lang="en">Actors</dc:subject>' . $extra_work_subjects;

                $xmlENphotodescr='';
                if($info['photoDescription']!=$info['photoDescriptionEN'] AND $info['photoDescriptionEN']!=''){
                    $xmlENphotodescr='<dc:description xml:lang="en" rdf:parseType="Literal">' . $info['photoDescriptionEN'] . '</dc:description>';
                }

                
                $xmlENphotographer='';
                if($info['photographer']!=$info['photographerEN'] AND $info['photographerEN']!=''){
                    $xmlENphotographer='<dc:creator xml:lang="en">' . $info['photographerEN'] . '</dc:creator>';
                }

                $xmlPhotoDate='';
                if($info['photoDate']!=''){
                    $xmlPhotoDate='<dc:date>' . $info['photoDate'] . '</dc:date>';
                }else{
                    $xmlPhotoDate='<dc:date xml:lang="el">Άγνωστη</dc:date><dcterms:created xml:lang="en">Unknown</dcterms:created>';
                }

                $XMLprov='<dc:title xml:lang="el" rdf:parseType="Literal">' . $title . '</dc:title>'.
                          $xmlENtitle .
                          '<dc:type rdf:resource="' . $semanticTypes['photos']['concept'] . '" />
                          <dc:identifier>' . $handlerURL . "/photo/" . $itemID . '</dc:identifier>
                          <dc:identifier>' . 'photo/' . $itemID . '</dc:identifier>'.
                          $XML_DC_Subject. $xmlRelation .
                          '<dc:description xml:lang="el" rdf:parseType="Literal">' . $info['photoDescription'] . '</dc:description>'
                          . $xmlENphotodescr . $xmlPhotoDate .
                          '<dc:date>' . $playYear . '</dc:date>
                          <edm:type>IMAGE</edm:type>
                          <dc:creator xml:lang="el">' . $info['photographer'] . '</dc:creator>'
                          . $xmlENphotographer;
                
                $itemURLfile = '';
                $thumbURLfile = '';
                $files=get_physical_details('photo', $itemID, $info['photoFile']);
                if(!empty($files) and $files['size']!=''){
                    $itemURLfile = $files['physicalFileURL'];
                    $thumbURLfile = $files['thumbURL'];
                    if($files['size']<1048576){
                        $displaySize=intval($files['size']/1024). ' KB';
                    }elseif($files['size']>1048576 and $files['size']<1073741824){
                        $displaySize=intval($files['size']/(1024*1024)). ' MB';
                    }elseif($files['size']>1073741824){
                        $displaySize=intval($files['size']/(1024*1024*1024)). ' GB';
                    }
                    $semantic=$semanticTypes['photos'];
                    //resource must be found for National Theatre
                    $XMLweb='<dc:type rdf:resource="' . $semantic['concept'] . '" />
                             <dc:creator rdf:resource="' . $NT_rdf . '" />
                             <dc:format>' . $files['mime'] . '</dc:format>
                             <dc:extend>' . $displaySize . '</dc:extend>
                             <dc:extend>'. $files['width'] . 'x' . $files['height'] .' px</dc:extend>
                             <dc:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/" />';  
    
                    $XMLextras='<skos:Concept rdf:about="' . $semantic['concept'] . '">
                                    <skos:prefLabel xml:lang="el">' . $semantic['labelGR'] . '</skos:prefLabel>
                                    <skos:prefLabel xml:lang="en">' . $semantic['labelEN'] . '</skos:prefLabel>
                                    <skos:exactMatch rdf:resource="'  . $semantic['exact'] . '"/>
                                </skos:Concept>' . $xmlSkosRelation;         
                }
                $XMLaggre='<edm:aggregatedCHO rdf:resource="#' . $itemID . '"/>
                           <edm:dataProvider>Εθνικό Θέατρο</edm:dataProvider>
                           <edm:isShownAt rdf:resource="' . $handlerURL . "/photo/" . $itemID . '"/>
                           <edm:isShownBy rdf:resource="' . $itemURLfile . '"/>
                           <edm:object rdf:resource="' . $thumbURLfile . '"/>
                           <edm:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/"/>
                           <dc:rights>Εθνικό Θέατρο</dc:rights>';
                break;
            case 'histphotos':
                $xmlENhisttitle='';
                if($info['hisTitle']!=$info['hisTitleEN'] AND $info['hisTitleEN']!=''){
                    $xmlENhisttitle='<dc:title xml:lang="en">' . $info['hisTitleEN'] . '</dc:title>';
                }

                $XML_DC_Subject = '<dc:subject xml:lang="el">Πολιτιστική ζωή</dc:subject>
                                   <dc:subject xml:lang="en">Cultural life</dc:subject>
                                   <dc:subject xml:lang="el">Καλλιτέχνες</dc:subject>
                                   <dc:subject xml:lang="en">Artists</dc:subject>
                                   <dc:subject xml:lang="el">Ιστορικές προσωπικότητες</dc:subject>
                                   <dc:subject xml:lang="en">Historical figures</dc:subject>' . $extra_work_subjects;

                $xmlENhistphotodescr='';
                if($info['hisPhotoDescription']!=$info['hisPhotoDescriptionEN'] AND $info['hisTitleEN']!=''){
                    $xmlENhistphotodescr='<dc:description xml:lang="en"  rdf:parseType="Literal">' . $info['hisPhotoDescriptionEN'] . '</dc:description>';
                }

                $xmlCreated = '';
                if($info['hisPhotoDate']!='' and $info['hisPhotoDate']!='Χ.χ.'){
                    $xmlCreated = '<dc:date>' . $info['hisPhotoDate'] . '</dc:date>';
                }else{
                    $xmlCreated = '<dc:date xml:lang="el">Άγνωστη</dc:date>
                                <dcterms:created xml:lang="en">Unknown</dcterms:created>';
                }

                $edmplace ='';
                $xmledmPlace = '';
                if($info['hisPlace']!=''){
                    $placeGeoURL=getPlaceRDF($info['hisPlace']);
                    $edmplace='<dcterms:spatial rdf:resource="' . $placeGeoURL . '" />';
                    $xmlPlaceEN = '';
                    if($info['hisPlaceEN']!='' and $info['hisPlaceEN']!=$info['hisPlace']){
                        $xmlPlaceEN = '<skos:prefLabel xml:lang="en">' . $info['hisPlaceEN'] . '</skos:prefLabel>';
                    }
                    $xmledmPlace='<edm:Place rdf:about="' . $placeGeoURL . '">
                                    <skos:prefLabel xml:lang="el">' . $info['hisPlace'] . '</skos:prefLabel>' .
                                    $xmlPlaceEN .
                                '</edm:Place>';
                }

                $XMLprov='<dc:title xml:lang="el">' . $info['hisTitle'] . '</dc:title>'
                          . $xmlENhisttitle .
                          '<dc:type rdf:resource="' . $semanticTypes['histphotos']['concept'] . '" />
                          <dc:identifier>' . $handlerURL . "/histphoto/" . $itemID . '</dc:identifier>
                          <dc:identifier>' . 'hisphoto/' . $itemID . '</dc:identifier>'.
                          $XML_DC_Subject. $xmlRelation .
                          '<dc:description xml:lang="el" rdf:parseType="Literal">' . $info['hisPhotoDescription'] . '</dc:description>'
                          . $xmlENhistphotodescr . $xmlCreated . 
                          '<edm:type>IMAGE</edm:type>' . $edmplace;
                $photographer=getCategory('hisPhotosPhotographer', $info['hisPhotoPhotographerID']);
                if(!empty($photographer)){

                    $xmlENhistphotographer='';
                    if($photographer['descr']!=$photographer['descrEN'] AND $photographer['descrEN']!=''){
                        $xmlENhistphotographer='<dc:creator xml:lang="el"> ' . $photographer['descrEN'] . '</dc:creator>';
                    }

                    $XMLphotographer='<dc:creator xml:lang="el">' . $photographer['descr'] . '</dc:creator>'. $xmlENhistphotographer;
                    $XMLprov.=$XMLphotographer;
                }
                $itemURLfile = '';
                $thumbURLfile = '';
                $files=get_physical_details('histphoto', $itemID, $info['hisPhotoFile']);
                if(!empty($files)){
                    $itemURLfile = $files['physicalFileURL'];
                    $thumbURLfile = $files['thumbURL'];
                    if($files['size']<1048576){
                        $displaySize=intval($files['size']/1024). ' KB';
                    }elseif($files['size']>1048576 and $files['size']<1073741824){
                        $displaySize=intval($files['size']/(1024*1024)). ' MB';
                    }elseif($files['size']>1073741824){
                        $displaySize=intval($files['size']/(1024*1024*1024)). ' GB';
                    }
                    $semantic=$semanticTypes['histphotos'];
                    //resource must be found for National Theatre
                    $XMLweb='<dc:type rdf:resource="' . $semantic['concept'] . '" />
                             <dc:creator rdf:resource="' . $NT_rdf . '" />
                             <dc:format>' . $files['mime'] . '</dc:format>
                             <dc:extend>' . $displaySize . '</dc:extend>
                             <dc:extend>'. $files['width'] . 'x' . $files['height'] .' px</dc:extend>
                             <dc:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/" />';  
       
                    $XMLextras='<skos:Concept rdf:about="' . $semantic['concept'] . '">
                                    <skos:prefLabel xml:lang="el">' . $semantic['labelGR'] . '</skos:prefLabel>
                                    <skos:prefLabel xml:lang="en">' . $semantic['labelEN'] . '</skos:prefLabel>
                                    <skos:exactMatch rdf:resource="'  . $semantic['exact'] . '"/>
                                </skos:Concept>' . $xmledmPlace . $xmlSkosRelation;         
                }
                $XMLaggre='<edm:aggregatedCHO rdf:resource="#' . $itemID . '"/>
                <edm:dataProvider>Εθνικό Θέατρο</edm:dataProvider>
                <edm:isShownAt rdf:resource="' . $handlerURL . "/histphoto/" . $itemID . '"/>
                <edm:isShownBy rdf:resource="' . $itemURLfile . '"/>
                <edm:object rdf:resource="' . $thumbURLfile . '"/>
                <edm:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/"/>
                <dc:rights>Εθνικό Θέατρο</dc:rights>';

                break;
            case 'sounds':
                $title='Ηχογράφιση από την παράσταση &quot;' . fix_title($info['playTitle']). '&quot; ('.$playYear.')';
                $xmlENsoundtitle='';
                if($info['playTitle']!=$info['playTitleEN'] and $info['playTitleEN']!=''){
                    $titleEN='Recording from the play &quot;' . fix_title($info['playTitleEN']). '&quot; ('.$playYear.')';
                    $xmlENsoundtitle='<dc:title xml:lang="en">' . $titleEN . '</dc:title>';
                }

                $XML_DC_Subject = '<dc:subject xml:lang="el">Θεατρική παράσταση</dc:subject>
                                   <dc:subject xml:lang="en">Theatrical play</dc:subject>
                                   <dc:subject xml:lang="el">Ηθοποιοί</dc:subject>
                                   <dc:subject xml:lang="en">Actors</dc:subject>' . $extra_work_subjects;

                $xmlENsounddescr='';
                if($info['soundDescription']!=$info['soundDescriptionEN'] AND $info['soundDescriptionEN']!=''){
                    $xmlENsounddescr='<dc:description xml:lang="en" rdf:parseType="Literal">' . $info['soundDescriptionEN'] . '</dc:description>';
                }

                $XMLprov='<dc:title xml:lang="el">' . $title . '</dc:title>'.
                          $xmlENsoundtitle.
                          '<dc:type rdf:resource="' . $semanticTypes['sounds']['concept'] . '" />
                          <dc:identifier>' . $handlerURL . "/sound/" . $itemID . '</dc:identifier>
                          <dc:identifier>' . 'sound/' . $itemID . '</dc:identifier>'.
                          $XML_DC_Subject. $xmlRelation .
                          '<dc:description xml:lang="el" rdf:parseType="Literal">' . str_replace('&', '&amp;', $info['soundDescription']) . '</dc:description>'.
                          $xmlENsounddescr .
                         '<dc:date>' . $playYear . '</dc:date>
                          <edm:type>SOUND</edm:type>';
                $soundType=getCategory('soundType', $info['soundTypeID']);   
                if(!empty($soundType)){
                    $XMLprov.='<dc:subject rdf:resource="' . $soundType['lexiconURL'] . '" />';

                    $xmlENsoundtypedescr='';
                    if($soundType['descr']!=$soundType['descrEN'] and $soundType['descrEN']!=''){
                        $xmlENsoundtypedescr='<skos:prefLabel xml:lang="en">' . $soundType['descrEN'] . '</skos:prefLabel>';
                    }

                    $XMLextras.='<skos:Concept rdf:about="' . $soundType['lexiconURL'] . '">
                                    <skos:prefLabel xml:lang="el">' . $soundType['descr'] . '</skos:prefLabel>'.
                                    $xmlENsoundtypedescr .
                                 '</skos:Concept>';
                }
                      
                $files=get_physical_details('sound', $itemID, $info['soundFile']);
                if(!empty($files)){
                    if($files['size']<1048576){
                        $displaySize=intval($files['size']/1024). ' KB';
                    }elseif($files['size']>1048576 and $files['size']<1073741824){
                        $displaySize=intval($files['size']/(1024*1024)). ' MB';
                    }elseif($files['size']>1073741824){
                        $displaySize=intval($files['size']/(1024*1024*1024)). ' GB';
                    }
                    $semantic=$semanticTypes['sounds'];
                    //resource must be found for National Theatre
                    $XMLweb='<dc:type rdf:resource="' . $semantic['concept'] . '" />
                                <dc:creator rdf:resource="' . $NT_rdf . '" />
                                <dc:format>' . $files['mime'] . '</dc:format>
                                <dc:extend>' . $displaySize . '</dc:extend>
                                <dc:extend>'. $files['duration'] . '</dc:extend>
                                <dc:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/" />'; 

                    $XMLextras='<skos:Concept rdf:about="' . $semantic['concept'] . '">
                                    <skos:prefLabel xml:lang="el">' . $semantic['labelGR'] . '</skos:prefLabel>
                                    <skos:prefLabel xml:lang="en">' . $semantic['labelEN'] . '</skos:prefLabel>
                                    <skos:exactMatch rdf:resource="'  . $semantic['exact'] . '"/>
                                </skos:Concept>' . $xmlSkosRelation;         
                }
                $XMLaggre='<edm:aggregatedCHO rdf:resource="#' . $itemID . '"/>
                            <edm:dataProvider>Εθνικό Θέατρο</edm:dataProvider>
                            <edm:isShownAt rdf:resource="' . $handlerURL . "/sound/" . $itemID . '"/>
                            <edm:isShownBy rdf:resource="' . $files['physicalFileURL'] . '"/>
                            <edm:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/"/>
                            <dc:rights>Εθνικό Θέατρο</dc:rights>'; 
                break;
            case 'soundparts':
                $title='Τμήμα ηχογράφισης από την παράσταση &quot;' . fix_title($info['playTitle']). '&quot; ('.$playYear.')';
                $xmlENsoundtitle='';
                if($info['playTitle']!=$info['playTitleEN'] and $info['playTitleEN']!=''){
                    $titleEN='Part of sound recording from the play &quot;' . fix_title($info['playTitleEN']). '&quot; ('.$playYear.')';
                    $xmlENsoundtitle='<dc:title xml:lang="en">' . $titleEN . '</dc:title>';
                }
                
                $XML_DC_Subject = '<dc:subject xml:lang="el">Θεατρική παράσταση</dc:subject>
                                   <dc:subject xml:lang="en">Theatrical play</dc:subject>
                                   <dc:subject xml:lang="el">Ηθοποιοί</dc:subject>
                                   <dc:subject xml:lang="en">Actors</dc:subject>' . $extra_work_subjects;

                $xmlENsoundparttitle='';
                if($info['soundPartTitle']!=$info['soundPartTitleEN'] and $info['soundPartTitleEN']!=''){
                    $xmlENsoundparttitle=' <dc:description xml:lang="en" rdf:parseType="Literal">' . str_replace('&', '&amp;', $info['soundPartTitleEN']) . '</dc:description>';
                }
                
                
                $XMLprov='<dc:title xml:lang="el">' . $title . '</dc:title>' .
                $xmlENsoundtitle .
                '<dc:type rdf:resource="' . $semanticTypes['soundparts']['concept'] . '" />
                <dc:identifier>' . $handlerURL . "/soundpart/" . $itemID . '</dc:identifier>
                <dc:identifier>' . 'soundpart/' . $itemID . '</dc:identifier>'.
                $XML_DC_Subject. $xmlRelation .
                '<dc:description xml:lang="el" rdf:parseType="Literal">' . str_replace('&', '&amp;', $info['soundPartTitle']) . '</dc:description>'.
                $xmlENsoundparttitle .
                '<dc:date>' . $playYear . '</dc:date>
                <edm:type>SOUND</edm:type>';
                $soundPartExt=$info['soundPart'];
                if($soundPartExt<10){
                    $soundPartExt='0'.$soundPartExt;
                }
                $soundPartFile=$info['soundFile'].'-'.$soundPartExt;
                


                $files=get_physical_details('soundpart', $itemID, $soundPartFile);
                if(!empty($files)){
                    if($files['size']<1048576){
                        $displaySize=intval($files['size']/1024). ' KB';
                    }elseif($files['size']>1048576 and $files['size']<1073741824){
                        $displaySize=intval($files['size']/(1024*1024)). ' MB';
                    }elseif($files['size']>1073741824){
                        $displaySize=intval($files['size']/(1024*1024*1024)). ' GB';
                    }
                    $semantic=$semanticTypes['soundparts'];
                    //resource must be found for National Theatre
                    $XMLweb='<dc:type rdf:resource="' . $semantic['concept'] . '" />
                                <dc:creator rdf:resource="' . $NT_rdf . '" />
                                <dc:format>' . $files['mime'] . '</dc:format>
                                <dc:extend>' . $displaySize . '</dc:extend>
                                <dc:extend>'. $files['duration'] . '</dc:extend>
                                <dc:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/" />';  

                    $XMLextras='<skos:Concept rdf:about="' . $semantic['concept'] . '">
                                    <skos:prefLabel xml:lang="el">' . $semantic['labelGR'] . '</skos:prefLabel>
                                    <skos:prefLabel xml:lang="en">' . $semantic['labelEN'] . '</skos:prefLabel>
                                    <skos:exactMatch rdf:resource="'  . $semantic['exact'] . '"/>
                                </skos:Concept>' . $xmlSkosRelation;         
                }
                $XMLaggre='<edm:aggregatedCHO rdf:resource="#' . $itemID . '"/>
                            <edm:dataProvider>Εθνικό Θέατρο</edm:dataProvider>
                            <edm:isShownAt rdf:resource="' . $handlerURL . "/soundpart/" . $itemID . '"/>
                            <edm:isShownBy rdf:resource="' . $files['physicalFileURL'] . '"/>
                            <edm:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/"/>
                            <dc:rights>Εθνικό Θέατρο</dc:rights>';
                break;    
            case 'videos':
                $title='Μαγνητοσκόπηση της παράστασης &quot;' . fix_title($info['playTitle']). '&quot; ('.$playYear.')';
                $xmlENtitle='';
                if($info['playTitle']!=$info['playTitleEN'] and $info['playTitleEN']!=''){
                    $titleEN='Video recording from the play &quot;' . fix_title($info['playTitleEN']). '&quot; ('.$playYear.')';
                    $xmlENtitle='<dc:title xml:lang="en">' . $titleEN . '</dc:title>';
                }

                $XML_DC_Subject = '<dc:subject xml:lang="el">Θεατρική παράσταση</dc:subject>
                                   <dc:subject xml:lang="en">Theatrical play</dc:subject>
                                   <dc:subject xml:lang="el">Ηθοποιοί</dc:subject>
                                   <dc:subject xml:lang="en">Actors</dc:subject>' . $extra_work_subjects;

                $xmlENdescr='';
                if($info['videoDescription']!=$info['videoDescriptionEN'] and $info['videoDescriptionEN']!=''){
                    $xmlENdescr='<dc:description xml:lang="en" rdf:parseType="Literal">' . str_replace('&', '&amp;', $info['videoDescriptionEN']) . '</dc:description>';
                }

               
                $XMLprov='<dc:title xml:lang="el">' . $title . '</dc:title>'.
                          $xmlENtitle .
                          '<dc:type rdf:resource="' . $semanticTypes['videos']['concept'] . '" />
                          <dc:identifier>' . $handlerURL . "/video/" . $itemID . '</dc:identifier>
                          <dc:identifier>' . 'video/' . $itemID . '</dc:identifier>'.
                          $XML_DC_Subject. $xmlRelation .
                          '<dc:description xml:lang="el" rdf:parseType="Literal">' . str_replace('&', '&amp;', $info['videoDescription']) . '</dc:description>'.
                          $xmlENdescr .
                          '<dc:date>' . $playYear . '</dc:date>
                          <edm:type>VIDEO</edm:type>';
                $edmObject='';
                if($info['IsURL']==0){ //Video is NOT a URL address
                    $files=get_physical_details('video', $itemID, $info['videoFile']);
                    if(!empty($files)){
                        if($files['size']<1048576){
                            $displaySize=intval($files['size']/1024). ' KB';
                        }elseif($files['size']>1048576 and $files['size']<1073741824){
                            $displaySize=intval($files['size']/(1024*1024)). ' MB';
                        }elseif($files['size']>1073741824){
                            $displaySize=intval($files['size']/(1024*1024*1024)). ' GB';
                        }
                        $semantic=$semanticTypes['videos'];
                        //resource must be found for National Theatre
                        $XMLweb='<dc:type rdf:resource="' . $semantic['concept'] . '" />
                                    <dc:creator rdf:resource="' . $NT_rdf . '" />
                                    <dc:format>' . $files['mime'] . '</dc:format>
                                    <dc:extend>' . $displaySize . '</dc:extend>
                                    <dc:extend>'. $files['duration'] . '</dc:extend>
                                    <dc:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/" />';  
                    }
                    if($files['thumbURL']!=''){
                        $edmObject = '<edm:object rdf:resource="' . $files['thumbURL'] . '"/>';
                    }
                }else{    // Video IS a youtube or URL address
                    $XMLweb='<dc:type rdf:resource="http://vocab.getty.edu/page/aat/300310110" />
                    <dc:creator rdf:resource="' . $NT_rdf . '" />
                    <edm:hasTitle>My YouTube Video</edm:hasTitle>
                    <edm:hasURL>' . $info['videoFile'] . '</edm:hasURL>
                    <dc:format>video/mp4</dc:format>
                    <dc:type>Moving Image</dc:type>
                    <dc:language>el</dc:language>
                    <dc:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/" />';  
                }

                $XMLaggre='<edm:aggregatedCHO rdf:resource="#' . $itemID . '"/>
                           <edm:dataProvider>Εθνικό Θέατρο</edm:dataProvider>
                           <edm:isShownAt rdf:resource="' . $handlerURL . "/video/" . $itemID . '"/>
                           <edm:isShownBy rdf:resource="' . $files['physicalFileURL'] . '"/>'.
                            $edmObject .
                           '<edm:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/"/>
                           <dc:rights>Εθνικό Θέατρο</dc:rights>';

                $XMLextras='<skos:Concept rdf:about="' . $semantic['concept'] . '">
                                        <skos:prefLabel xml:lang="el">' . $semantic['labelGR'] . '</skos:prefLabel>
                                        <skos:prefLabel xml:lang="en">' . $semantic['labelEN'] . '</skos:prefLabel>
                                        <skos:exactMatch rdf:resource="'  . $semantic['exact'] . '"/>
                                    </skos:Concept>' . $xmlSkosRelation;                               
                break;
            case 'videoparts':
                $title='Τμήμα μαγνητοσκόπησης από την παράσταση &quot;' . fix_title($info['playTitle']). '&quot; ('.$playYear.')';
                $xmlENtitle='';
                if($info['playTitle']!=$info['playTitleEN'] and $info['playTitleEN']!=''){
                    $titleEN='Part of video recording from the play &quot;' . fix_title($info['playTitleEN']). '&quot; ('.$playYear.')';
                    $xmlENtitle='<dc:title xml:lang="en">' . $titleEN . '</dc:title>';
                }

                $XML_DC_Subject = '<dc:subject xml:lang="el">Θεατρική παράσταση</dc:subject>
                                   <dc:subject xml:lang="en">Theatrical play</dc:subject>
                                   <dc:subject xml:lang="el">Ηθοποιοί</dc:subject>
                                   <dc:subject xml:lang="en">Actors</dc:subject>' . $extra_work_subjects;

                $xmlENdescr='';
                if($info['videoPartTitle']!=$info['videoPartTitleEN'] and $info['videoPartTitleEN']!=''){
                    $xmlENdescr='<dc:description xml:lang="en" rdf:parseType="Literal">' . $info['videoPartTitleEN'] . '</dc:description>';
                }



                
                $XMLprov='<dc:title xml:lang="el">' . $title . '</dc:title>'.
                            $xmlENtitle .
                            '<dc:type rdf:resource="' . $semanticTypes['videoparts']['concept'] . '" />
                            <dc:identifier>' . $handlerURL . "/videopart/" . $itemID . '</dc:identifier>
                            <dc:identifier>' . 'videopart/' . $itemID . '</dc:identifier>'.
                          $XML_DC_Subject. $xmlRelation .
                          '<dc:description xml:lang="el" rdf:parseType="Literal">' . str_replace('&', '&amp;', $info['videoPartTitle']) . '</dc:description>'.
                            $xmlENdescr .
                            '<dc:date>' . $playYear . '</dc:date>
                            <edm:type>VIDEO</edm:type>';
                
                


                $videoPartExt=$info['videoPart'];
                if($videoPartExt<10){
                    $videoPartExt='0'.$videoPartExt;
                }
                $videoPartFile=$info['videoFile'].'-'.$videoPartExt;
                $edmObject='';
                $files=get_physical_details('videopart', $itemID, $videoPartFile);
                if(!empty($files)){
                    if($files['size']<1048576){
                        $displaySize=intval($files['size']/1024). ' KB';
                    }elseif($files['size']>1048576 and $files['size']<1073741824){
                        $displaySize=intval($files['size']/(1024*1024)). ' MB';
                    }elseif($files['size']>1073741824){
                        $displaySize=intval($files['size']/(1024*1024*1024)). ' GB';
                    }
                    $semantic=$semanticTypes['videoparts'];
                    //resource must be found for National Theatre
                    $XMLweb='<dc:type rdf:resource="' . $semantic['concept'] . '" />
                                <dc:creator rdf:resource="' . $NT_rdf . '" />
                                <dc:format>' . $files['mime'] . '</dc:format>
                                <dc:extend>' . $displaySize . '</dc:extend>
                                <dc:extend>'. $files['duration'] . '</dc:extend>
                                <dc:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/" />';  
                       
                    $XMLextras='<skos:Concept rdf:about="' . $semantic['concept'] . '">
                                    <skos:prefLabel xml:lang="el">' . $semantic['labelGR'] . '</skos:prefLabel>
                                    <skos:prefLabel xml:lang="en">' . $semantic['labelEN'] . '</skos:prefLabel>
                                    <skos:exactMatch rdf:resource="'  . $semantic['exact'] . '"/>
                                </skos:Concept>' . $xmlSkosRelation;
                    if($files['thumbURL']!=''){
                        $edmObject = '<edm:object rdf:resource="' . $files['thumbURL'] . '"/>';
                    }

                }
                $XMLaggre='<edm:aggregatedCHO rdf:resource="#' . $itemID . '"/>
                           <edm:dataProvider>Εθνικό Θέατρο</edm:dataProvider>
                           <edm:isShownAt rdf:resource="' . $handlerURL . "/videopart/" . $itemID . '"/>
                           <edm:isShownBy rdf:resource="' . $files['physicalFileURL'] . '"/>'.
                            $edmObject .
                           '<edm:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/"/>
                           <dc:rights>Εθνικό Θέατρο</dc:rights>';
                break;
            case 'posters':
                $creatorsXML='';
                $creators=getPosterCreators($info['posterID']);
                if(!empty($creators) AND $creators[0]['personName']!=''){
                    foreach($creators as $creator){
                        $creatorsXML.= '<dc:creator xml:lang="el">' . fix_person_name($creator['personName']) . '</dc:creator>
                                        <dc:creator xml:lang="en">' . fix_person_name($creator['personNameEN']) . '</dc:creator>';
                    }
                }
                $edmplace='';
                if($info['posterPlace']!=''){
                    $city=$info['posterPlace'];
                    $cityEN=$info['posterPlaceEN'];
                    $placeGeoURL=getPlaceRDF($info['posterPlace']);
                    $edmplace='<dcterms:spatial rdf:resource="' . $placeGeoURL . '" />';
                    
                    $XMLextras='<edm:Place rdf:about="' . $placeGeoURL . '">
                                    <skos:prefLabel xml:lang="el">' . $city . '</skos:prefLabel>
                                    <skos:prefLabel xml:lang="en">' . $cityEN . '</skos:prefLabel>
                                </edm:Place>';
                }
                $dccreated = '';
                if($hasplay){
                    $dccreated='<dc:date>' . $playYear . '</dc:date>';
                }else{
                    $dccreated='<dc:date xml:lang="el">Άγνωστη</dc:date>
                                <dcterms:created xml:lang="en">Unknown</dcterms:created>';
                }
                
                $XML_DC_Subject = '<dc:subject xml:lang="el">Θεατρική παράσταση</dc:subject>
                                   <dc:subject xml:lang="en">Theatrical play</dc:subject>
                                   <dc:subject xml:lang="el">Πολιτιστική επικοινωνία</dc:subject>
                                   <dc:subject xml:lang="en">Cultural communication</dc:subject>' . $extra_work_subjects;

                $additionalDescr=getPosterRepeatDescription($itemID);
                $xmlposterdescr='';
                if($hasplay){
                    $descr='Αφίσα της παράστασης &quot;'.fix_title($info['playTitle']). '&quot; ('.$playYear.') '. $additionalDescr['el'];
                    $xmlposterdescr='<dc:description xml:lang="el" rdf:parseType="Literal">' . $descr . '</dc:description>';
                }else{
                    $xmlposterdescr='<dc:description xml:lang="el" rdf:parseType="Literal">' . $info['posterTitleEN'] . '</dc:description>';
                }
                $xmlENtitle='';
                if( $info['posterTitle']!=$info['posterTitleEN'] and $info['posterTitleEN']!=''){
                    $xmlENtitle='<dc:title xml:lang="en" rdf:parseType="Literal">' . $info['posterTitleEN'] . '</dc:title>';
                }
               

                $XMLprov='<dc:title xml:lang="el" rdf:parseType="Literal">' . $info['posterTitle'] . '</dc:title>'.
                          $xmlENtitle .
                          '<dc:type rdf:resource="' . $semanticTypes['posters']['concept'] . '" />
                          <dc:identifier>' . $handlerURL . "/poster/" . $itemID . '</dc:identifier>
                          <dc:identifier>' . 'poster/' . $itemID . '</dc:identifier>' . $XML_DC_Subject . $xmlRelation . $edmplace . 
                          $xmlposterdescr . $dccreated .
                          '<edm:type>IMAGE</edm:type>'.$creatorsXML;
                

                $files=get_physical_details('poster', $itemID, $info['posterfile']);
                
                $thumbURLfile = '';
                if(!empty($files)){
                    if($files['size']<1048576){
                        $displaySize=intval($files['size']/1024). ' KB';
                    }elseif($files['size']>1048576 and $files['size']<1073741824){
                        $displaySize=intval($files['size']/(1024*1024)). ' MB';
                    }elseif($files['size']>1073741824){
                        $displaySize=intval($files['size']/(1024*1024*1024)). ' GB';
                    }
                    $semantic=$semanticTypes['posters'];
                    //resource must be found for National Theatre
                    $XMLweb='<dc:type rdf:resource="' . $semantic['concept'] . '" />
                             <dc:creator rdf:resource="' . $NT_rdf . '" />
                             <dc:format>' . $files['mime'] . '</dc:format>
                             <dc:extend>' . $displaySize . '</dc:extend>
                             <dc:extend>'. $files['width'] . 'x' . $files['height'] .' px</dc:extend>
                             <dc:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/" />';  
                       
                    $XMLextras='<skos:Concept rdf:about="' . $semantic['concept'] . '">
                                    <skos:prefLabel xml:lang="el">' . $semantic['labelGR'] . '</skos:prefLabel>
                                    <skos:prefLabel xml:lang="en">' . $semantic['labelEN'] . '</skos:prefLabel>
                                    <skos:exactMatch rdf:resource="'  . $semantic['exact'] . '"/>
                                </skos:Concept>'.$XMLextras . $xmlSkosRelation;         
                    $thumbURLfile = $files['thumbURL'];
                }
                $XMLaggre='<edm:aggregatedCHO rdf:resource="#' . $itemID . '"/>
                <edm:dataProvider>Εθνικό Θέατρο</edm:dataProvider>
                <edm:isShownAt rdf:resource="' . $handlerURL . "/poster/" . $itemID . '"/>
                <edm:isShownBy rdf:resource="' . $files['physicalFileURL'] . '"/>
                <edm:object rdf:resource="' . $thumbURLfile . '" />
                <edm:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/"/>
                <dc:rights>Εθνικό Θέατρο</dc:rights>';
                
                break;
            case 'costumes':
                $XML_DC_Subject = '<dc:subject xml:lang="el">Θεατρική παράσταση</dc:subject>
                                   <dc:subject xml:lang="en">Theatrical play</dc:subject>
                                   <dc:subject xml:lang="el">Ενδυματολογία</dc:subject>
                                   <dc:subject xml:lang="en">Costume disign</dc:subject>
                                   <dc:subject xml:lang="el">Μόδα</dc:subject>
                                   <dc:subject xml:lang="en">Fashion</dc:subject>' . $extra_work_subjects;

                $creatorsXML='';
                $creator=getCostumeCreator($info['costumeCreator']);
                if(!empty($creators) AND $creators['personName']!=''){
                    $creatorsXML.= '<dc:creator xml:lang="el">' . fix_person_name($creator['personName']) . '</dc:creator>
                                    <dc:creator xml:lang="en">' . fix_person_name($creator['personNameEN']) . '</dc:creator>';
                }
                
                $costumeTypeGroup=getCategory('costumeTypeGroup', $info['costumeTypeGroupID']);   
                if(!empty($costumeTypeGroup)){
                    if($costumeTypeGroup['lexiconURL']){
                        $XMLprov.='<dc:subject rdf:resource="' . $costumeTypeGroup['lexiconURL'] . '" />';
                        $XMLextras.='<skos:Concept rdf:about="' . $costumeTypeGroup['lexiconURL'] . '">
                                        <skos:prefLabel xml:lang="el">' . mb_ereg_replace('"', '&quot;', $costumeTypeGroup['descr']) . '</skos:prefLabel>
                                        <skos:prefLabel xml:lang="en">' . $costumeTypeGroup['descrEN'] . '</skos:prefLabel>
                                     </skos:Concept>';
                    }elseif($costumeTypeGroup['descr']!=''){
                        $XMLprov.='<dc:subject xml:lang="el">' . mb_ereg_replace('"', '&quot;', $costumeTypeGroup['descr']) . '</dc:subject>';
                            if($costumeTypeGroup['descr']!=$costumeTypeGroup['descrEN']){
                                $XMLprov.='<dc:subject xml:lang="en">' . $costumeTypeGroup['descrEN'] . '</dc:subject>';
                            }
                    }
                    
                }
               
                $costumeTypes=array();
                $costumeTypes=getCostumeMuptiParamXML($info['costumeID'], 'type');
                if(!empty($costumeTypes)){
                    foreach($costumeTypes as $type){
                        if($type['lexiconURL']!=''){
                            $XMLprov.='<dc:subject rdf:resource="' . $type['lexiconURL'] . '" />';
                            $XMLextras.='<skos:Concept rdf:about="' . $type['lexiconURL'] . '">
                                            <skos:prefLabel xml:lang="el">' . mb_ereg_replace('"', '&quot;', $type['descr']) . '</skos:prefLabel>
                                            <skos:prefLabel xml:lang="en">' . $type['descrEN'] . '</skos:prefLabel>
                                        </skos:Concept>';
                        }elseif($type['descr']!=''){
                            $XMLprov.='<dc:subject xml:lang="el">' . mb_ereg_replace('"', '&quot;', $type['descr']) . '</dc:subject>';
                            if($type['descr']!=$type['descrEN']){
                                $XMLprov.='<dc:subject xml:lang="en">' . $type['descrEN'] . '</dc:subject>';
                            }
                        }
                    }
                }  
                 
                $costumeMaterials=array();
                $costumeMaterials=getCostumeMuptiParamXML($info['costumeID'], 'material');
                if(!empty($costumeMaterials)){
                    foreach($costumeMaterials as $material){
                        if($material['lexiconURL']!=''){
                            $XMLprov.='<dc:subject rdf:resource="' . $material['lexiconURL'] . '" />';
                            $XMLextras.='<skos:Concept rdf:about="' . $material['lexiconURL'] . '">
                                            <skos:prefLabel xml:lang="el">' . mb_ereg_replace('"', '&quot;', $material['descr']) . '</skos:prefLabel>
                                            <skos:prefLabel xml:lang="en">' . $material['descrEN'] . '</skos:prefLabel>
                                        </skos:Concept>';
                        }elseif($material['descr']!=''){
                            $XMLprov.='<dc:subject xml:lang="el">' . mb_ereg_replace('"', '&quot;', $material['descr']) . '</dc:subject>';
                            if($material['descr']!=$material['descrEN']){
                                $XMLprov.='<dc:subject xml:lang="en">' . $material['descrEN'] . '</dc:subject>';
                            }
                        }
                    }
                }
                $costumeColors=array();
                $costumeColors=getCostumeMuptiParamXML($info['costumeID'], 'color');
                if(!empty($costumeColors)){
                    foreach($costumeColors as $color){
                        if($color['lexiconURL']!=''){
                            $XMLprov.='<dc:subject rdf:resource="' . $color['lexiconURL'] . '" />';
                            $XMLextras.='<skos:Concept rdf:about="' . $color['lexiconURL'] . '">
                                            <skos:prefLabel xml:lang="en">' . mb_ereg_replace('"', '&quot;', $color['descr']) . '</skos:prefLabel>
                                        </skos:Concept>';
                        }elseif($color['descr']!=''){
                            $XMLprov.='<dc:subject xml:lang="en">' . mb_ereg_replace('"', '&quot;', $color['descr']) . '</dc:subject>';
                        }
                    }
                }
                
                $dccreated = '' ;
                if($hasplay){
                    $dccreated = '<dc:date xml:lang="el">' . $playYear . '</dc:date>';
                }else{
                    $dccreated = '<dc:date xml:lang="el">Άγνωστη</dc:date><dcterms:created xml:lang="en">Unknown</dcterms:created>';
                }

                $XMLprov='<dc:title xml:lang="el" rdf:parseType="Literal">' . $info['title'] . '</dc:title>
                            <dc:title xml:lang="en" rdf:parseType="Literal">' . $info['titleEN'] . '</dc:title>
                            <dc:type rdf:resource="' . $semanticTypes['costumes']['concept'] . '" />
                            <dc:identifier>' . $handlerURL . "/costume/" . $itemID . '</dc:identifier>
                            <dc:identifier>' . 'poster/' . $itemID . '</dc:identifier>
                            <dc:description xml:lang="el" rdf:parseType="Literal">' . $info['description'] . '</dc:description>
                            <dc:description xml:lang="en" rdf:parseType="Literal">' . $info['descriptionEN'] . '</dc:description>'.
                            $dccreated .
                            '<edm:type>IMAGE</edm:type>'.$creatorsXML . $XML_DC_Subject . $xmlRelation . $XMLprov;
                
                $thumbfile = '';
                $edmobject = '';
                if($info['digitizeMethod']==1){
                    $files=get_physical_details('costume3d', $itemID, $info['costumeFile']);
                    if(!empty($files)){
                        if($files['size']<1048576){
                            $displaySize=intval($files['size']/1024). ' KB';
                        }elseif($files['size']>1048576 and $files['size']<1073741824){
                            $displaySize=intval($files['size']/(1024*1024)). ' MB';
                        }elseif($files['size']>1073741824){
                            $displaySize=intval($files['size']/(1024*1024*1024)). ' GB';
                        }
                        
                        $semantic=$semanticTypes['costumes'];
                        //resource must be found for National Theatre
                        $XMLweb='<dc:type rdf:resource="' . $semantic['concept'] . '" />
                                 <dc:creator rdf:resource="' . $NT_rdf . '" />
                                 <dc:format>' . $files['mime'] . '</dc:format>
                                 <dc:extend>' . $displaySize . '</dc:extend>
                                 <dc:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/" />';  
                                 
                        $XMLextras='<skos:Concept rdf:about="' . $semantic['concept'] . '">
                                        <skos:prefLabel xml:lang="el">' . $semantic['labelGR'] . '</skos:prefLabel>
                                        <skos:prefLabel xml:lang="en">' . $semantic['labelEN'] . '</skos:prefLabel>
                                        <skos:exactMatch rdf:resource="'  . $semantic['exact'] . '"/>
                                    </skos:Concept>'.$XMLextras. $xmlSkosRelation;         
                    }

                }else{
                    $files=get_physical_details('costume360', $itemID, $info['costumeFile']);
                    if(!empty($files)){
                        $thumbfile = $files['thumbURL'];
                        if($files['size']!=''){
                            if(intval($files['size'])<1048576){
                                $displaySize=intval($files['size']/1024). ' KB';
                            }elseif(intval($files['size'])>1048576 and intval($files['size'])<1073741824){
                                $displaySize=intval($files['size']/(1024*1024)). ' MB';
                            }elseif(intval($files['size'])>1073741824){
                                $displaySize=intval($files['size']/(1024*1024*1024)). ' GB';
                            }
                        }else{
                            $displaySize='';
                        }
                        $semantic=$semanticTypes['costumes'];
                        //resource must be found for National Theatre
                        $XMLweb='<dc:type rdf:resource="' . $semantic['concept'] . '" />
                                <dc:creator rdf:resource="' . $NT_rdf . '" />
                                    <dc:format>' . $files['mime'] . '</dc:format>
                                    <dc:extend>' . $displaySize . '</dc:extend>
                                    <dc:extend>'. $files['NumberOfFiles']  .' files</dc:extend>
                                     <dc:extend>'. $files['width'] . 'x' . $files['height'] .' px</dc:extend>
                                    <dc:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/" />';  
                                    
                        $XMLextras='<skos:Concept rdf:about="' . $semantic['concept'] . '">
                                        <skos:prefLabel xml:lang="el">' . $semantic['labelGR'] . '</skos:prefLabel>
                                        <skos:prefLabel xml:lang="en">' . $semantic['labelEN'] . '</skos:prefLabel>
                                        <skos:exactMatch rdf:resource="'  . $semantic['exact'] . '"/>
                                    </skos:Concept>'.$XMLextras . $xmlSkosRelation; 
                        $edmobject = '<edm:object rdf:resource="' . $thumbfile . '"/>';
                    }
                }
                
                $XMLaggre='<edm:aggregatedCHO rdf:resource="#' . $itemID . '"/>
                            <edm:dataProvider>Εθνικό Θέατρο</edm:dataProvider>
                            <edm:isShownAt rdf:resource="' . $handlerURL . "/costume/" . $itemID . '"/>
                            <edm:isShownBy rdf:resource="' . $files['physicalFileURL'] . '"/>'.
                            $edmobject .
                            '<edm:rights rdf:resource="https://creativecommons.org/licenses/by-nd/4.0/"/>
                            <dc:rights>Εθνικό Θέατρο</dc:rights>';

                break;
            default:
                die('wrong case -> rdf_get_item');                
        }

        //intro
        $rdf='<rdf:RDF
        xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
        xmlns:dc="http://purl.org/dc/elements/1.1/" 
        xmlns:dcterms="https://purl.org/dc/terms/"
        xmlns:edm="https://www.europeana.eu/schemas/edm/"
        xmlns:ore="https://www.openarchives.org/ore/terms/" 
        xmlns:skos="https://www.w3.org/2004/02/skos/core#">';

        //ProvidedCHO section
        $provCHO='<edm:ProvidedCHO rdf:about="'.$repo_url.'/'.$item.'/'.$itemID.'">'.$XMLprov.'</edm:ProvidedCHO>';

        //Aggregation section
        $aggre='<ore:Aggregation rdf:about="'.$repo_url.'/'.$item.'/'.$itemID.'">'.$XMLaggre.'</ore:Aggregation>';

        //webResource section
       
        if($item!='video'){
            $webRes='<edm:WebResource rdf:about="' . $files['physicalFileURL'] . '">'. $XMLweb .'</edm:WebResource>';
        }else{ 
            if($info['IsURL']==0){
                $webRes='<edm:WebResource rdf:about="'. $info['videoFile'].'">'. $XMLweb .'</edm:WebResource>';
            }
        }
        
        //end of webResource section
        
        //skos section
        //end of skos
        $rdf.= $provCHO . $aggre . $webRes. $XMLextras;

       // $rdf.= $XMLextras;

        $rdf.='</rdf:RDF>';
    }
    return $rdf;
}

function check_item_availability($item, $itemID){
    global $dbh;
    switch ($item){
        case 'programs':
            $tbl="programs";
            $fldid="programID";
            break;
        case 'publications':
            $tbl="publications";
            $fldid="pubID";
            break;
        case 'photos':
            $tbl="photos";
            $fldid="photoID";
            break;
        case 'histphotos':
            $tbl="historicPhotos";
            $fldid="histPhotoID";
            break;
        case 'sounds':
            $tbl="sounds";
            $fldid="soundID";
            break;
        case 'soundparts':
            $tbl="soundParts";
            $fldid="soundPartID";
            break;    
        case 'videos':
            $tbl="videos";
            $fldid="videoID";
            break;
        case 'videoparts':
            $tbl="videoParts";
            $fldid="videoPartID";
            break;
        case 'posters':
            $tbl="posters";
            $fldid="posterID";
            break;
        case 'costumes':
            $tbl="costumes";
            $fldid="costumeID";
            break;
        default:
            die('wrong case ->check_item_availability');                
    }
    try{
        $sql="SELECT COALESCE(published, 0) as published FROM $tbl WHERE $fldid=:fld";
        $stmt=$dbh->prepare($sql);
        $stmt->bindParam(":fld", $itemID, PDO::PARAM_INT);
        $stmt->execute();
        $res=$stmt->fetch(PDO::FETCH_OBJ);
        if($res->published==1){
            return true;
        }else{
            return false;
        }
    }catch(PDOException $err){
        echo $err->getMessage();
    }

}

function get_physical_details($item, $itemID, $itemFile){
    global $dbh;
    global $repo_url, $handlerURL, $physicalCollectionPath;
    $result=array();
    $URLcollectionPathScreen=$handlerURL.'/collections/screen';
    $PHYcollectionPathScreen=$physicalCollectionPath.'/screen';
    $URLcollectionPathThumb=$handlerURL.'/collections/thumbs';
    $PHYcollectionPathThumb=$physicalCollectionPath.'/thumbs';
    $physicalFile='';
    $physicalThumbFile='';
    $fullScreenURL='';
    $fullThumbURL='';
    switch($item){
        case "program":
            $physicalFile=$itemFile.'.pdf';
            $physicalThumbFile=$itemFile.'_tn.jpg';
            $PHYfullScreen=$PHYcollectionPathScreen.'/programs/'.$physicalFile;
            $PHYfullThumb=$PHYcollectionPathThumb.'/programs/'.$physicalThumbFile;
            $URLfullScreen=$URLcollectionPathScreen.'/programs/'.$physicalFile;
            $URLfullThumb=$URLcollectionPathThumb.'/programs/'.$physicalThumbFile;
            $pageCount = countPDFpages($PHYfullScreen);
            //form_arr($pageCount);
            $sizes=get_file_size($PHYfullScreen, $PHYfullThumb);
            $result['size']=$sizes['screenFile'];
            $result['sizeThumb']=$sizes['thumbFile'];
            $result['mime']='application/pdf';
            $result['width']=''; //width
            $result['height']='';
            $result['pagecount']=$pageCount;
            $result['physicalFileURL']=$URLfullScreen;
            $result['thumbURL']=$URLfullThumb;
            break;
        case 'pub':
            $physicalFile=$itemFile.'.pdf';
            $physicalThumbFile=$itemFile.'_tn.jpg';
            $PHYfullScreen=$PHYcollectionPathScreen.'/publications/'.$physicalFile;
            $PHYfullThumb=$PHYcollectionPathThumb.'/publications/'.$physicalThumbFile;
            $URLfullScreen=$URLcollectionPathScreen.'/publications/'.$physicalFile;
            $URLfullThumb=$URLcollectionPathThumb.'/publications/'.$physicalThumbFile;
            $pageCount = countPDFpages($PHYfullScreen);
            //form_arr($pageCount);
            $sizes=get_file_size($PHYfullScreen, $PHYfullThumb);
            $result['size']=$sizes['screenFile'];
            $result['sizeThumb']=$sizes['thumbFile'];
            $result['mime']='application/pdf';
            $result['width']=''; //width
            $result['height']='';
            $result['pagecount']=$pageCount;
            $result['physicalFileURL']=$URLfullScreen;
            $result['thumbURL']=$URLfullThumb;
            break;
        case 'photo':
            $physicalFile=''.$itemFile.'_sc.jpg';
            $physicalThumbFile=$itemFile.'_tn.jpg';
            $PHYfullScreen=$PHYcollectionPathScreen.'/photos/'.$physicalFile;
            $PHYfullThumb=$PHYcollectionPathThumb.'/photos/'.$physicalThumbFile;
            $URLfullScreen=$URLcollectionPathScreen.'/photos/'.$physicalFile;
            $URLfullThumb=$URLcollectionPathThumb.'/photos/'.$physicalThumbFile;
            if(file_exists($PHYfullScreen)){
                $sizes=get_file_size($PHYfullScreen, $PHYfullThumb);
                $details= getimagesize($PHYfullScreen);
                $result['width']=$details[0]; //width
                $result['height']=$details[1];  //height;
                $result['mime']=$details['mime'];  ///$type;
                $result['size']=$sizes['screenFile'];
                $result['sizeThumb']=$sizes['thumbFile'];
            }else{
                $result['width']=0; //width
                $result['height']=0;  //height;
                $result['mime']='';  ///$type;
                $result['size']='';
                $result['sizeThumb']='';
            }
            $result['physicalFileURL']=$URLfullScreen;
            $result['thumbURL']=$URLfullThumb;
            break;
        case 'histphoto':
            $physicalFile=$itemFile.'.jpg';
            $physicalThumbFile=$itemFile.'.jpg';
            $PHYfullScreen=$PHYcollectionPathScreen.'/historicPhotos/'.$physicalFile;
            $PHYfullThumb=$PHYcollectionPathThumb.'/historicPhotos/'.$physicalThumbFile;
            $URLfullScreen=$URLcollectionPathScreen.'/histphotos/'.$physicalFile;
            $URLfullThumb=$URLcollectionPathThumb.'/histphotos/'.$physicalThumbFile;
            $sizes=get_file_size($PHYfullScreen, $PHYfullThumb);
            if(file_exists($PHYfullScreen)){
            $details= getimagesize($PHYfullScreen);
                $result['width']=$details[0]; //width
                $result['height']=$details[1];  //height;
                $result['mime']=$details['mime'];  ///$type;
                $result['size']=$sizes['screenFile'];
                $result['sizeThumb']=$sizes['thumbFile'];
            }else{
                $result['width']=0; //width
                $result['height']=0;  //height;
                $result['mime']='';  ///$type;
                $result['size']='';
                $result['sizeThumb']='';
            }
            $result['physicalFileURL']=$URLfullScreen;
            $result['thumbURL']=$URLfullThumb;
            break;
        case 'sound':
            $physicalFile=$itemFile.'-01.mp3';
            $physicalThumbFile='';
            $PHYfullScreen=$PHYcollectionPathScreen.'/sounds/'.$physicalFile;
            $PHYfullThumb='';
            $URLfullScreen=$URLcollectionPathScreen.'/sounds/'.$physicalFile;
            $URLfullThumb='';
            $sizes=get_file_size($PHYfullScreen, $PHYfullThumb);
            $result['width']=''; //width
            $result['height']='';  //height;
            $result['mime']='audio/mp3';  ///$type;
            $result['size']=$sizes['screenFile'];
            $result['sizeThumb']=$sizes['thumbFile'];
            if(file_exists($PHYfullScreen)){
                $audio = new getID3;
                $fileMp3Data=$audio->analyze($PHYfullScreen);
                $result['duration']=$fileMp3Data['playtime_string'];
            }else{
                $result['duration']='';
            }
            $result['physicalFileURL']=$URLfullScreen;
            $result['thumbURL']=$URLfullThumb;
            break; 
        case 'soundpart':
            $physicalFile=$itemFile.'.mp3';
            $physicalThumbFile='';
            $PHYfullScreen=$PHYcollectionPathScreen.'/sounds/'.$physicalFile;
            $PHYfullThumb='';
            $URLfullScreen=$URLcollectionPathScreen.'/sounds/'.$physicalFile;
            $URLfullThumb='';
            $sizes=get_file_size($PHYfullScreen, $PHYfullThumb);
            $result['width']=''; //width
            $result['height']='';  //height;
            $result['mime']='audio/mp3';  ///$type;
            $result['size']=$sizes['screenFile'];
            $result['sizeThumb']=$sizes['thumbFile'];
            if(file_exists($PHYfullScreen)){
                $audio = new getID3;
                $fileMp3Data=$audio->analyze($PHYfullScreen);
                $result['duration']=$fileMp3Data['playtime_string'];
            }else{
                $result['duration']='';
            }
            $result['physicalFileURL']=$URLfullScreen;
            $result['thumbURL']=$URLfullThumb;
            break;
        case 'video':
            $physicalFile=$itemFile.'.mp4';
            $physicalThumbFile=$itemFile.'.jpg';
            $PHYfullScreen=$PHYcollectionPathScreen.'/videos/'.$physicalFile;
            $PHYfullThumb=$PHYcollectionPathThumb.'/thumbs/videos/'. $physicalThumbFile;
            $URLfullScreen=$URLcollectionPathScreen.'/videos/'.$physicalFile;
            $URLfullThumb=$URLcollectionPathThumb . '/videos/' . $physicalThumbFile;
            $sizes=get_file_size($PHYfullScreen, $PHYfullThumb);
            $result['width']=''; //width
            $result['height']='';  //height;
            $result['mime']='video/mp4';  ///$type;
            $result['size']=$sizes['screenFile'];
            $result['sizeThumb']=$sizes['thumbFile'];
            if(file_exists($PHYfullScreen)){
                $video = new getID3;
                $fileMp3Data=$video->analyze($PHYfullScreen);
                $result['duration']=$fileMp3Data['playtime_string'];
            }else{
                $result['duration']='';
            }
            $result['physicalFileURL']=$URLfullScreen;
            $result['thumbURL']=$URLfullThumb;
            break;
        case 'videopart':
            $physicalFile=$itemFile.'.mp4';
            $physicalThumbFile=$itemFile.'.jpg';
            $PHYfullScreen=$PHYcollectionPathScreen.'/videos/'.$physicalFile;
            $PHYfullThumb=$PHYcollectionPathThumb.'/thumbs/videos/'. $physicalThumbFile;
            $URLfullScreen=$URLcollectionPathScreen.'/videos/'.$physicalFile;
            $URLfullThumb=$URLcollectionPathThumb . '/videos/' . $physicalThumbFile;
            $sizes=get_file_size($PHYfullScreen, $PHYfullThumb);
            $result['width']=''; //width
            $result['height']='';  //height;
            $result['mime']='video/mp4';  ///$type;
            $result['size']=$sizes['screenFile'];
            $result['sizeThumb']=$sizes['thumbFile'];
            //$audio = new Mp3Info($PHYfullScreen);
            if(file_exists($PHYfullScreen)){
                $video = new getID3;
                $fileMp3Data=$video->analyze($PHYfullScreen);
                $result['duration']=$fileMp3Data['playtime_string'];
            }else{
                $result['duration']='';
            }
            $result['physicalFileURL']=$URLfullScreen;
            $result['thumbURL']=$URLfullThumb;
            break;
        case 'poster':
            $physicalFile=$itemFile.'.jpg';
            $physicalThumbFile=$itemFile.'.jpg';
            $PHYfullScreen=$PHYcollectionPathScreen.'/posters/'.$physicalFile;
            $PHYfullThumb=$PHYcollectionPathThumb.'/posters/'.$physicalThumbFile;
            $URLfullScreen=$URLcollectionPathScreen.'/posters/'.$physicalFile;
            $URLfullThumb=$URLcollectionPathThumb.'/posters/'.$physicalThumbFile;
            $sizes=get_file_size($PHYfullScreen, $PHYfullThumb);
            $details= getimagesize($PHYfullScreen);
            $result['width']=$details[0]; //width
            $result['height']=$details[1];  //height;
            $result['mime']=$details['mime'];  ///$type;
            $result['size']=$sizes['screenFile'];
            $result['sizeThumb']=$sizes['thumbFile'];   
            $result['physicalFileURL']=$URLfullScreen;
            $result['thumbURL']=$URLfullThumb;
            break;
        case 'costume360':
            $physicalFile=$itemFile.'_1.png';
            $physicalThumbFile=$itemFile.'_1.png';
            $physicalFile30=$itemFile.'_30.png';
            $physicalThumbFile30=$itemFile.'_30.png';

            $imgfileexist=false;
            if(file_exists($PHYcollectionPathScreen.'/costumes/'.$itemFile.'/img/'.$physicalFile)){  //If _1 image is absent we try _30
                $PHYfullScreen=$PHYcollectionPathScreen.'/costumes/'.$itemFile.'/img/'.$physicalFile;
                $PHYfullThumb=$PHYcollectionPathThumb.'/costumes/'. $itemFile.'/img/'.$physicalThumbFile;
                $URLfullScreen=$URLcollectionPathScreen.'/costumes/'. $itemFile.'/img/'.$physicalFile;
                $URLfullThumb=$URLcollectionPathThumb.'/costumes/'. $itemFile.'/img/'.$physicalThumbFile;
                $imgfileexist = true;
            }elseif(file_exists($PHYcollectionPathScreen.'/costumes/'.$itemFile.'/img/'.$physicalFile30)){
                $PHYfullScreen=$PHYcollectionPathScreen.'/costumes/'.$itemFile.'/img/'.$physicalFile30;
                $PHYfullThumb=$PHYcollectionPathThumb.'/costumes/'. $itemFile.'/img/'.$physicalThumbFile30;
                $URLfullScreen=$URLcollectionPathScreen.'/costumes/'. $itemFile.'/img/'.$physicalFile30;
                $URLfullThumb=$URLcollectionPathThumb.'/costumes/'. $itemFile.'/img/'.$physicalThumbFile30;
                $imgfileexist = true;
            }
            
            if($imgfileexist){
            $sizes=get_file_size($PHYfullScreen, $PHYfullThumb);
            $details= getimagesize($PHYfullScreen);
                $result['width']=$details[0]; //width
                $result['height']=$details[1];  //height;
                $result['mime']=$details['mime'];  ///$type;
                $result['size']=$sizes['screenFile'];
                $result['sizeThumb']=$sizes['thumbFile'];   
                $fi = new FilesystemIterator($PHYcollectionPathScreen.'/costumes/'.$itemFile, FilesystemIterator::SKIP_DOTS);
                $result['NumberOfFiles']=iterator_count($fi);
                $result['physicalFileURL']=$URLfullScreen;
                $result['thumbURL']=$URLfullThumb;
            }else{
                $result['width']=''; //width
                $result['height']='';  //height;
                $result['mime']='';  ///$type;
                $result['size']='';
                $result['sizeThumb']='';
                $result['NumberOfFiles']='';
                $result['physicalFileURL']='';
                $result['thumbURL']='';
            }
            break;
        case 'costume3d':
            $physicalFile=$itemFile.'.gltf';
            $PHYfullScreen=$PHYcollectionPathScreen.'/costumes/'.$physicalFile;
            $URLfullScreen=$URLcollectionPathScreen.'/costumes/'.$physicalFile;
            $sizes=get_file_size($PHYfullScreen, '');

            $result['width']=''; //width
            $result['height']='';  //height;
            $result['mime']='model/gltf+json';  ///$type;
            $result['size']=$sizes['screenFile'];
            $result['sizeThumb']='';  
            $result['physicalFileURL']=$URLfullScreen;
            $result['thumbURL']=$URLfullScreen;
            break;
        default:
            die('wrong case ->get_physical_details');
    }
   
    return $result;
    
}

function get_file_size($screenFile, $thumbFile){
    $result=array(
        'screenFile' => '', 
        'thumbFile' => '',
    );
    if(file_exists($screenFile)){
        $result['screenFile']=filesize($screenFile);
    }else{
        $result['screenFile']=0;
    }

    if(file_exists($thumbFile) && $thumbFile!=''){
        $result['thumbFile']=filesize($thumbFile);
    }else{
        $result['thumbFile']=0;
    }

    return $result;
}

function get_item_details($item, $itemID){
    global $dbh;
    $detail=array();
    switch ($item){
        case 'programs':
            $sql="SELECT programs.*,  plays.playTitle, plays.playID, 
            (CASE WHEN (trans.tr_text is null OR trans.tr_text='') THEN plays.playTitle ELSE trans.tr_text END) as playTitleEN
            FROM programs
            INNER JOIN playPrograms ON playPrograms.programID=programs.programID
            INNER JOIN plays ON plays.playID=playPrograms.playID 
            LEFT JOIN trans ON trans.field_id=plays.playID AND trans.tbl_name='plays' and trans.field_name='playTitle'
            WHERE programs.programID=:itid";
            
            break;
        case 'publications':
            $sql="SELECT publications.*,  plays.playTitle, plays.playID, 
            (CASE WHEN (trans.tr_text is null OR trans.tr_text='') THEN plays.playTitle ELSE trans.tr_text END) as playTitleEN,
            (CASE WHEN (t1.tr_text is null OR t1.tr_text='') THEN publications.pubTitle ELSE t1.tr_text END) as pubTitleEN
            FROM publications
            INNER JOIN plays ON plays.playID=publications.playID 
            LEFT JOIN trans ON trans.field_id=plays.playID AND trans.tbl_name='plays' and trans.field_name='playTitle'
            LEFT JOIN trans t1 ON t1.field_id=publications.pubID AND t1.tbl_name='publications' and t1.field_name='pubTitle'
            WHERE publications.pubID=:itid";
            
            break;
        case 'photos':
            $sql="SELECT photos.*, plays.playTitle, plays.playID, 
                    (CASE WHEN (trans.tr_text is null OR trans.tr_text='') THEN plays.playTitle ELSE trans.tr_text END) as playTitleEN,  
                    (CASE WHEN (trans1.tr_text is null OR trans1.tr_text='') THEN photos.photoDescription ELSE trans1.tr_text END) as photoDescriptionEN ,
                    (CASE WHEN (trans2.tr_text is null OR trans2.tr_text='') THEN photos.photographer ELSE trans2.tr_text END) as photographerEN 
                    FROM photos 
                    INNER JOIN plays ON plays.playID=photos.playID 
                    LEFT JOIN trans ON trans.field_id=plays.playID AND trans.tbl_name='plays' and trans.field_name='playTitle'
                    LEFT JOIN trans trans1 ON trans1.field_id=photos.photoID AND trans1.tbl_name='photos' and trans1.field_name='photoDescription'
                    LEFT JOIN trans trans2 ON trans2.field_id=photos.photoID AND trans2.tbl_name='photos' and trans2.field_name='photographer'
                    WHERE photos.photoID=:itid ";
            
            break;
        case 'histphotos':
            $sql="SELECT historicPhotos.*, plays.playTitle, plays.playID,
                    (CASE WHEN (trans.tr_text is null OR trans.tr_text='') THEN plays.playTitle ELSE trans.tr_text END) as playTitleEN,
                    (CASE WHEN (trans1.tr_text is null OR trans1.tr_text='') THEN historicPhotos.hisPhotoDescription ELSE trans1.tr_text END) as hisPhotoDescriptionEN ,
                    (CASE WHEN (trans2.tr_text is null OR trans2.tr_text='') THEN historicPhotos.hisTitle ELSE trans2.tr_text END) as hisTitleEN,
                    (CASE WHEN (trans3.tr_text is null OR trans3.tr_text='') THEN historicPhotos.hisPlace ELSE trans3.tr_text END) as hisPlaceEN
                    FROM historicPhotos
                    LEFT JOIN plays ON plays.playID=historicPhotos.playID 
                    LEFT JOIN trans ON trans.field_id=plays.playID AND trans.tbl_name='plays' and trans.field_name='playTitle'
                    LEFT JOIN trans trans1 ON trans1.field_id=historicPhotos.histPhotoID AND trans1.tbl_name='historicPhotos' and trans1.field_name='hisPhotoDescription'
                    LEFT JOIN trans trans2 ON trans2.field_id=historicPhotos.histPhotoID AND trans2.tbl_name='historicPhotos' and trans2.field_name='hisTitle'
                    LEFT JOIN trans trans3 ON trans3.field_id=historicPhotos.histPhotoID AND trans3.tbl_name='historicPhotos' and trans3.field_name='hisPlace'
                    WHERE historicPhotos.histPhotoID=:itid";
            
            break;
        case 'sounds':
            $sql="SELECT sounds.*, plays.playTitle, plays.playID,
                (CASE WHEN (trans.tr_text is null OR trans.tr_text='') THEN plays.playTitle ELSE trans.tr_text END) as playTitleEN,  
                (CASE WHEN (trans1.tr_text is null OR trans1.tr_text='') THEN sounds.soundDescription ELSE trans1.tr_text END) as soundDescriptionEN
                FROM sounds
                INNER JOIN plays ON plays.playID=sounds.playID 
                LEFT JOIN trans ON trans.field_id=plays.playID AND trans.tbl_name='plays' and trans.field_name='playTitle'
                LEFT JOIN trans trans1 ON trans1.field_id=sounds.soundID AND trans1.tbl_name='sounds' and trans1.field_name='soundDescription'
                WHERE sounds.soundID=:itid
                ";
            
            break;
        case 'soundparts':
            $sql="SELECT sp.*, s.soundFile, plays.playTitle, plays.playID,
              (CASE WHEN (trans.tr_text is null OR trans.tr_text='') THEN plays.playTitle ELSE trans.tr_text END) as playTitleEN,  
              (CASE WHEN (trans1.tr_text is null OR trans1.tr_text='') THEN sp.soundPartTitle ELSE trans1.tr_text END) as soundPartTitleEN
              FROM soundParts sp
              INNER JOIN sounds s ON s.soundID=sp.soundID
              INNER JOIN plays ON plays.playID=s.playID 
              LEFT JOIN trans ON trans.field_id=plays.playID AND trans.tbl_name='plays' and trans.field_name='playTitle'
              LEFT JOIN trans trans1 ON trans1.field_id=sp.soundPartID AND trans1.tbl_name='soundParts' and trans1.field_name='soundPartTitle'
              WHERE sp.soundPartID=:itid
              ";
               
            break;    
        case 'videos':
            $sql="SELECT v.*, plays.playTitle, plays.playID,
            (CASE WHEN (trans.tr_text is null OR trans.tr_text='') THEN plays.playTitle ELSE trans.tr_text END) as playTitleEN,  
            (CASE WHEN (trans1.tr_text is null OR trans1.tr_text='') THEN v.videoDescription ELSE trans1.tr_text END) as videoDescriptionEN
            FROM videos v
            INNER JOIN plays ON plays.playID=v.playID 
            LEFT JOIN trans ON trans.field_id=plays.playID AND trans.tbl_name='plays' and trans.field_name='playTitle'
            LEFT JOIN trans trans1 ON trans1.field_id=v.videoID AND trans1.tbl_name='videos' and trans1.field_name='videoDescription'
            WHERE v.videoID=:itid
            ";
            break;
        case 'videoparts':
            $sql="SELECT vp.*, v.videoFile, plays.playTitle, plays.playID,
              (CASE WHEN (trans.tr_text is null OR trans.tr_text='') THEN plays.playTitle ELSE trans.tr_text END) as playTitleEN,  
              (CASE WHEN (trans1.tr_text is null OR trans1.tr_text='') THEN vp.videoPartTitle ELSE trans1.tr_text END) as videoPartTitleEN
              FROM videoParts vp
              INNER JOIN videos v ON v.videoID=vp.videoID
              INNER JOIN plays ON plays.playID=v.playID 
              LEFT JOIN trans ON trans.field_id=plays.playID AND trans.tbl_name='plays' and trans.field_name='playTitle'
              LEFT JOIN trans trans1 ON trans1.field_id=vp.videoPartID AND trans1.tbl_name='videoParts' and trans1.field_name='videoPartTitle'
              WHERE vp.videoPartID=:itid
              ";
              break;
        case 'posters':
            $sql="SELECT p.*, plays.playTitle, plays.playID,
                 (CASE WHEN (trans.tr_text is null OR trans.tr_text='') THEN plays.playTitle ELSE trans.tr_text END) as playTitleEN,  
                 (CASE WHEN (trans1.tr_text is null OR trans1.tr_text='') THEN p.posterTitle ELSE trans1.tr_text END) as posterTitleEN,
                 (CASE WHEN (trans2.tr_text is null OR trans2.tr_text='') THEN p.posterPlace ELSE trans2.tr_text END) as posterPlaceEN
                 FROM posters p
                 LEFT JOIN postersPlays pp ON pp.posterID=p.posterID
                 LEFT JOIN plays ON plays.playID=pp.playID
                 LEFT JOIN trans ON trans.field_id=plays.playID AND trans.tbl_name='plays' and trans.field_name='playTitle'
                 LEFT JOIN trans trans1 ON trans1.field_id=p.posterID AND trans1.tbl_name='posters' and trans1.field_name='posterTitle'
                 LEFT JOIN trans trans2 ON trans2.field_id=p.posterID AND trans2.tbl_name='posters' and trans2.field_name='posterPlace'
                 WHERE p.posterID=:itid
                ";
           
            break;
        case 'costumes':
            $sql="SELECT c.*, plays.playTitle, plays.playID,
                (CASE WHEN (trans.tr_text is null OR trans.tr_text='') THEN plays.playTitle ELSE trans.tr_text END) as playTitleEN,  
                (CASE WHEN (t1.tr_text is null OR t1.tr_text='') THEN c.title ELSE t1.tr_text END) as titleEN,
                (CASE WHEN (t2.tr_text is null OR t2.tr_text='') THEN c.description ELSE t2.tr_text END) as descriptionEN,
                (CASE WHEN (t3.tr_text is null OR t3.tr_text='') THEN c.creationPlace ELSE t3.tr_text END) as creationPlaceEN
                FROM costumes c
                INNER JOIN costumesPlays cp ON cp.costumeID=c.costumeID
                INNER JOIN plays ON plays.playID=cp.playID
                LEFT JOIN trans ON trans.field_id=plays.playID AND trans.tbl_name='plays' and trans.field_name='playTitle'
                LEFT JOIN trans t1 ON t1.field_id=c.costumeID AND t1.tbl_name='costumes' and t1.field_name='title' AND t1.lang='en'
                LEFT JOIN trans t2 ON t2.field_id=c.costumeID AND t2.tbl_name='costumes' and t2.field_name='description' AND t2.lang='en'
                LEFT JOIN trans t3 ON t3.field_id=c.costumeID AND t3.tbl_name='costumes' and t3.field_name='creationPlace' AND t3.lang='en'
                WHERE c.costumeID=:itid
            ";            
            break;
        default:
            die('wrong case -> get_item_details');                
    }
    try{
        $stmt=$dbh->prepare($sql);
        $stmt->bindParam(":itid", $itemID, PDO::PARAM_INT);
        $stmt->execute();
        $res=$stmt->fetch(PDO::FETCH_ASSOC);
        return $res;
    }catch(PDOException $err){
        return $err->getMessage();
    }
    
    
}




function getPlayYear_v2($playID){
    global $dbh;
    $sql="SELECT (CASE WHEN MIN(IIF(r1.repeatDateStart<>'', YEAR(r1.repeatDateStart), ''))>0 then MIN(IIF(r1.repeatDateStart<>'', YEAR(r1.repeatDateStart), '')) ELSE MIN(r1.repeatPeriod1) END) AS playYear 
    FROM repeats r1 WHERE r1.playID=:pid";
    $stmt=$dbh->prepare($sql);
    $stmt->bindParam(":pid", $playID, PDO::PARAM_INT);
    $stmt->execute();
    $obj=$stmt->fetch(PDO::FETCH_OBJ);
    $y1=$obj->playYear;
    return $y1;
}

function get_xml_work_subjects($playID){
    global $dbh;
    $outputXML = '';
    $sql = "select wgo.descr as subjectText, (CASE WHEN (trans.tr_text is null OR trans.tr_text='') THEN wgo.descr ELSE trans.tr_text END) as subjectTextEN
        FROM worksOrigins wo
        INNER JOIN worksGenreOrigin wgo on wgo.workGenreOriginID = wo.workOriginID
        LEFT JOIN trans ON trans.field_id=wgo.workGenreOriginID AND trans.tbl_name='worksGenreOrigin' and trans.field_name='descr'
        WHERE wo.workID in (SELECT workID FROM playWorks WHERE playID = :pid1)
        UNION
        select wgo.descr as subjectText, (CASE WHEN (trans.tr_text is null OR trans.tr_text='') THEN wgo.descr ELSE trans.tr_text END) as subjectTextEN
        FROM worksPeriods wo
        INNER JOIN worksGenrePeriod wgo on wgo.workGenrePeriodID = wo.workPeriodID
        LEFT JOIN trans ON trans.field_id=wgo.workGenrePeriodID AND trans.tbl_name='worksGenrePeriod' and trans.field_name='descr'
        WHERE wo.workID in (SELECT workID FROM playWorks WHERE playID = :pid2)
        UNION
        select wgo.descr as subjectText, (CASE WHEN (trans.tr_text is null OR trans.tr_text='') THEN wgo.descr ELSE trans.tr_text END) as subjectTextEN
        FROM worksTypes wo
        INNER JOIN worksGenreType wgo on wgo.workGenreTypeID = wo.workTypeID
        LEFT JOIN trans ON trans.field_id=wgo.workGenreTypeID AND trans.tbl_name='worksGenreType' and trans.field_name='descr'
        WHERE wo.workID in (SELECT workID FROM playWorks WHERE playID = :pid3)";
    try{
        $stmt=$dbh->prepare($sql);
        $stmt->bindParam(":pid1", $playID, PDO::PARAM_INT);
        $stmt->bindParam(":pid2", $playID, PDO::PARAM_INT);
        $stmt->bindParam(":pid3", $playID, PDO::PARAM_INT);
        $stmt->execute();
        $subjects=$stmt->fetchAll(PDO::FETCH_ASSOC);

        if(!empty($subjects)){
            foreach($subjects as $single){
                $xmldescren = '';
                if(trim($single['subjectTextEN'])!='' AND trim($single['subjectTextEN'])!= trim($single['subjectText'])){
                    $xmldescren = '<dc:subject xml:lang="en">' . trim($single['subjectTextEN']) . '</dc:subject>';
                }
                $outputXML .= '<dc:subject xml:lang="el">' . trim($single['subjectText']) . '</dc:subject>'. $xmldescren;
            }
        }
        return $outputXML;
    }catch(PDOException $err){

    }
}


function getLangs($item, $itemID){
    global $dbh;
    switch($item){
        case 'program':
            $sql="SELECT le.lexiconURL FROM languages le 
                    INNER JOIN programsLangs pl ON pl.languageID=le.languageID
                    WHERE pl.programID=:pid";
            break;
        case 'pub':
            $sql="SELECT le.lexiconURL FROM languages le 
                    INNER JOIN pubsLangs pl ON pl.languageID=le.languageID
                    WHERE pl.pubID=:pid";
            break;    
        default:
            die('wrong case -> getLangs');    
    }
    try{
        $stmt=$dbh->prepare($sql);
        $stmt->bindParam(":pid", $itemID, PDO::PARAM_INT);
        $stmt->execute();
        $res=$stmt->fetchAll(PDO::FETCH_ASSOC);
        return $res;
    }catch(PDOException $err){
        echo $err->getMessage();
    }
}

function getProgramDescription($itemID){
    global $dbh;
    $output=array();
    $sql="SELECT * FROM programs WHERE programID=:pid";
    $outputEL='Το πρόγραμμα αυτό περιλαμβάνει: ';
    $outputEN='Program includes: ';
    try{
        $stmt=$dbh->prepare($sql);
        $stmt->bindParam(":pid", $itemID, PDO::PARAM_INT);
        $stmt->execute();
        $res=$stmt->fetch(PDO::FETCH_ASSOC);
        if($res['hasContributors']==1){
            $outputEL.='τους συντελεστές, ';
            $outputEN.='the contributors, ';
        }
        if($res['hasActors']==1){
            $outputEL.='την διανομή, ';
            $outputEN.='the actors, ';
        }
        if($res['hasText']==1){
            $outputEL.='το κείμενο παράστασης, ';
            $outputEN.='the play texts, ';
        }
        if($res['hasNotes']==1){
            $outputEL.='σημειώματα συντελεστών, ';
            $outputEN.='contributor notes, ';
        }
        if($res['hasBios']==1){
            $outputEL.='βιογραφικά σημειώματα, ';
            $outputEN.='bios, ';
        }
        if($res['hasWorks']==1){
            $outputEL.='εργογραφιες - χρονολόγια, ';
            $outputEN.='works - Chronicles, ';
        }
        if($res['hasPlays']==1){
            $outputEL.='παραστασιογραφία, ';
            $outputEN.='other plays, ';
        }
        if($res['hasFotos']==1){
            $outputEL.='φωτογραφίες συντελεστών, ';
            $outputEN.='photos, ';
        }
        if($res['hasFotosTrials']==1){
            $outputEL.='φωτογραφίες από πρόβες, ';
            $outputEN.='rehearsal photos, ';
        }
        if($res['hasFotosOther']==1){
            $outputEL.='φωτογραφίες από άλλες παραστάσεις, ';
            $outputEN.='photos of other plays, ';
        }
        if($res['hasModel']==1){
            $outputEL.='μακέτες σκηνικών - κουστουμιών, ';
            $outputEN.='models - costumes, ';
        }
        if($res['hasScore']==1){
            $outputEL.='την μουσική, ';
            $outputEN.='music, ';
        }
        $outputEL=mb_substr($outputEL, 0, -2);
        $outputEN=mb_substr($outputEN, 0, -2);;
        $output['el']=$outputEL;
        $output['en']=$outputEN;
        return $output;
    }catch(PDOException $err){
        echo $err->getMessage();
    }
}

// function countPDFpages($path){
//     $count=exec('"c:\Program Files\ImageMagick-7.1.1-Q16-HDRI\magick.exe" convert "' . $path . '" -set option:totpages %[n] -delete 1--1 -format "%[totpages]\n" info:');
//     return $count;
// }

function countPDFpages($path){
    global $poplerpath;
    $command = $poplerpath . '\pdfinfo.exe ' . escapeshellarg($path) . ' | findstr /C:"Pages:"';
    $output = shell_exec($command);
    preg_match('/Pages:\s+(\d+)/', $output, $matches);
    return $matches[1] ?? 0;
}

function getCategory($category, $catID){
    global $dbh;
    switch($category){
        case 'pubType':
            $catTbl="pubTypes";
            $fldid="pubTypeID";
            break;
        case 'pubMedium':
            $catTbl="pubMediums";
            $fldid="pubMediumID";
            break;
        case 'hisPhotosPhotographer';
            $catTbl='hisPhotosPhotographers';
            $fldid='hisPhotoPhotographerID';
            break;    
        case 'soundType';
            $catTbl='soundTypes';
            $fldid='soundTypeID';
            break;
        case 'costumePeriod';
            $catTbl='costumePeriods';
            $fldid='costumePeriodID';    
            break;
        case 'costumeTypeGroup':
            $catTbl='costumesGenreTypesGroups';
            $fldid='costumesGenreTypesGroupID';
            break;
        default:
            die('wrong case -> getCategory');    
    }
    try{
        $sql="SELECT cattbl.descr, cattbl.lexiconURL, 
                (CASE WHEN (trans.tr_text is null OR trans.tr_text='') THEN cattbl.descr ELSE trans.tr_text END) as descrEN 
                FROM $catTbl cattbl
                LEFT JOIN trans ON trans.tbl_name='$catTbl' and trans.field_name='descr' and trans.field_id=:cid1
                WHERE cattbl.$fldid=:cid2";
        $stmt=$dbh->prepare($sql);
        $stmt->bindParam(":cid1", $catID, PDO::PARAM_INT);
        $stmt->bindParam(":cid2", $catID, PDO::PARAM_INT);
        $stmt->execute();
        $res=$stmt->fetch(PDO::FETCH_ASSOC);
        return $res;
    }catch(PDOException $err){
        echo $err->getMessage();
    }
}

function getCostumeMuptiParamXML($costumeID, $param){
    global $dbh;
    switch($param){
        case 'type':
            $tblmain='costumesGenreTypes';
            $tblinner='costumesTypes';
            $fldid='costumeTypeID';
            break;
        case 'material':
            $tblmain='costumesGenreMaterials';
            $tblinner='costumesMaterials';
            $fldid='costumeMaterialID';
            break;
        case 'color':
            $tblmain='costumesGenreColors';
            $tblinner='costumesColors';
            $fldid='costumeColorID';
            break;
    }
    $sql="SELECT m.descr, m.lexiconURL, 
            (CASE WHEN (trans.tr_text is null OR trans.tr_text='') THEN m.descr ELSE trans.tr_text END) as descrEN 
            FROM $tblmain m 
            INNER JOIN $tblinner inn ON inn.$fldid=m.$fldid
            LEFT JOIN trans ON trans.tbl_name='$tblmain' and trans.field_name='descr' and trans.field_id=:cid1
            WHERE inn.costumeID=:cid";
    try{ 
        $stmt=$dbh->prepare($sql);
        $stmt->bindParam(":cid", $costumeID, PDO::PARAM_INT);
        $stmt->bindParam(":cid1", $costumeID, PDO::PARAM_INT);
        $stmt->execute();
        $res=$stmt->fetchAll(PDO::FETCH_ASSOC);
        return $res;
    }catch(PDOException $err){
        echo $err->getMessage();
    }
}

function getPubAuthor($pubID){
    global $dbh;
    try{
        $outXML='';
        $sql="SELECT TOP 1 p.personName, pa.pubAuthorRank, (CASE WHEN (trans.tr_text is null OR trans.tr_text='') THEN p.personName ELSE trans.tr_text END) as personNameEN  FROM people p 
              INNER JOIN pubAuthors pa ON pa.personID=p.personID 
              LEFT JOIN trans ON trans.tbl_name='people' and trans.field_name='personName' and trans.field_id=pa.personID
              WHERE pa.pubID=:pid
              ORDER BY pa.pubAuthorRank";
        $stmt=$dbh->prepare($sql);
        $stmt->bindParam(":pid", $pubID, PDO::PARAM_INT);
        $stmt->execute();
        $res=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!empty($res)){
            $authname=fix_person_name($res['personName']);
            $authnameEN=fix_person_name(($res['personNameEN']));
            if($authname!=$authnameEN and $authnameEN!=''){
                $outXML='<dc:creator xml:lang="el">' . $authname . '</dc:creator>
                <dc:creator xml:lang="en">' . $authnameEN . '</dc:creator>';
            }else{
                $outXML='<dc:creator xml:lang="el">' . $authname . '</dc:creator>';
            }
            
        }
        return $outXML;         
    }catch(PDOException $err){
        echo $err->getMessage();
    }
}

function getPlaceRDF($placeStr){
    global $dbh;
    $out='';
    $city=urlencode($placeStr);
    try{
        $geoApiURL='http://api.geonames.org/searchJSON?q=' . $city . '&maxRows=1&username=nsidir';
        $response=json_decode(CallAPI('GET', $geoApiURL));
        $geonames=$response->geonames;
        $geoID=$geonames[0]->geonameId;

        $out='http://sws.geonames.org/'.$geoID.'/';
        return $out;
    }catch(PDOException $err){
        echo $err->getMessage();
    }
}

function getPosterRepeatDescription($posterID){
    global $dbh;
    $result=array();
    try{

        $sql="select p.*, r.*, ro.orgID, o.orgCity, o.orgName,
            (CASE WHEN (trans.tr_text is null OR trans.tr_text='') THEN o.orgName ELSE trans.tr_text END) as orgNameEN,  
            (CASE WHEN (t1.tr_text is null OR t1.tr_text='') THEN o.orgCity ELSE t1.tr_text END) as orgCityEN  
            from postersRepeats pr
            INNER JOIN posters p ON p.posterID=pr.posterID
            INNER JOIN repeatsOrgs ro ON ro.repeatID=pr.repeatID
            INNER JOIN repeats r ON r.repeatID=ro.repeatID
            INNER JOIN organizations o ON o.orgID=ro.orgID
            INNER JOIN trans ON trans.tbl_name='organizations' AND trans.field_id=ro.orgID AND trans.field_name='orgName' AND trans.lang='en'
            INNER JOIN trans  t1 ON t1.tbl_name='organizations' AND t1.field_id=ro.orgID AND t1.field_name='orgCity' AND trans.lang='en'
            where pr.posterID=:pid";

        $stmt=$dbh->prepare($sql);
        $stmt->bindParam(":pid", $posterID, PDO::PARAM_INT);
        $stmt->execute();
        $res=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!empty($res)){
            $out=' που ανέβηκε την περίδο από ';
            $repeatDateStar=displayFulldDate($res['repeatDateStart'], 'el');
            
            $repeatDateEnd='';
            if($res['repeatDateEnd']!=''){
                $repeatDateEnd=' έως '. displayFulldDate($res['repeatDateEnd'], 'el');
            }
            $orgName='';
            if($res['orgName']!=''){
                $orgName=' στο ' . $res['orgName'];
            }
            $orgcity='';
            if($res['orgCity']!=''){
                $orgcity=' (' . $res['orgCity'] . ') ';
            }
            $out.=$repeatDateStar.$repeatDateEnd.$orgName.$orgcity;
            $result['el']=$out;
        }else{
            $result['el']='';
        }
        return $result;
    }catch(PDOException $err){
        echo $err->getMessage();
    }
}

function getPosterCreators($posterID){
    global $dbh;
    $output=array();
    try{
        $sql="SELECT pc.creatorRank, pe.personName, 
            (CASE WHEN (trans.tr_text is null OR trans.tr_text='') THEN pe.personName ELSE trans.tr_text END) as personNameEN  
            FROM postersCreators pc
            INNER JOIN people pe ON pe.personID=pc.personID
            INNER JOIN trans ON trans.field_id=pe.personID AND trans.tbl_name='people' AND trans.field_name='personName' AND trans.lang='en'
            WHERE pc.posterID=:pid";
        $stmt=$dbh->prepare($sql);
        $stmt->bindParam(":pid", $posterID, PDO::PARAM_INT);
        $stmt->execute();
        $res=$stmt->fetchAll(PDO::FETCH_ASSOC);
        return $res;
    }catch(PDOException $err){
        echo $err->getMessage();
    }
}

function getCostumeCreator($personID){
    global $dbh;
    $output=array();
    try{
        $sql="SELECT pe.personName, 
            (CASE WHEN (trans.tr_text is null OR trans.tr_text='') THEN pe.personName ELSE trans.tr_text END) as personNameEN  
            FROM people pe
            INNER JOIN trans ON trans.field_id=pe.personID AND trans.tbl_name='people' AND trans.field_name='personName' AND trans.lang='en'
            WHERE pe.personID=:pid";
        $stmt=$dbh->prepare($sql);
        $stmt->bindParam(":pid", $personID, PDO::PARAM_INT);
        $stmt->execute();
        $res=$stmt->fetchAll(PDO::FETCH_ASSOC);
        return $res;
    }catch(PDOException $err){
        echo $err->getMessage();
    }
}


/**
 * Calling an RESTFull API function
 */

 function CallAPI($method, $url, $data = array()){
    $curl = curl_init();
    switch ($method){
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    // Optional Authentication:
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, "username:password");

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
}
?>