<?php
global $dbh;
global $oaiURL;
global $semanticTypes;
ini_set('memory_limit', '-1');

include_once 'config.php';
include_once 'class.php';
include_once 'util.php';
require_once dirname(__FILE__) .  '/vendor/getid3/getid3.php';

$oaiURL = 'https://www.nt-archive.gr/oai-pmh';
$error = '';
$hasrequestError = false;
$xmlirecs = '';
//DB connection
$dbh = db_connect();

//Current requset date
$currRequestDate=date('Y-m-d h:i:s');
$currRequestDate=str_replace(" ", "T", $currRequestDate);
$currRequestDate.='Z';

//verb parameter
$verb='';
if(isset($_GET['verb']) and $_GET['verb'] != ''){
    $verb = $_GET['verb'];
    if(!validateVerb($verb)){
        $error = "badVerb";
        $hasrequestError = true; 
    }
}else{
    $error = "badVerb";
    $hasrequestError = true; 
}

//Identifier param
$identifier='';
if(isset($_GET['identifier']) and $_GET['identifier'] != ''){
    $identifier = $_GET['identifier'];
}

//ListIdentifiers
$metadataPrefix='';
if(isset($_GET['metadataPrefix']) and $_GET['metadataPrefix'] != ''){
    $metadataPrefix = $_GET['metadataPrefix'];
    if(!validatemetaPref($metadataPrefix)){
        $error = 'cannotDisseminateFormat';
        $hasrequestError = true;
    }
}

//from param
$from='';
$fromtext = '';
if(isset($_GET['from']) and $_GET['from'] != ''){
    if(validateDate($_GET['from'])){
        $from = $_GET['from'];
        $fromtext = ' from="' . $from . '"';
    }else{
        $error = 'badArgument';
        $hasrequestError = true;
    }
}
// until param
$until = '';
$untiltext='';
if(isset($_GET['until']) and $_GET['until'] != ''){
    if(validateDate($_GET['until'])){
        $until = $_GET['until'];
        $untiltext= ' until="' . $until . '"';
    }else{
        $error = 'badArgument';
        $hasrequestError = true;
    }
}

//set param
$set = '';
$settext='';
if(isset($_GET['set']) and $_GET['set']!=''){
    $set = $_GET['set'];
    if(!validateSet($set)){
        $error = 'badArgument';
        $hasrequestError = true;
    }
    $settext = ' set="' . $set . '"';
}

//resumptionToken param
$resumptionToken ='';
$restext='';
if(isset($_GET['resumptionToken']) and $_GET['resumptionToken']!='' ){
    $resumptionToken = $_GET['resumptionToken'];
    $restext = ' resumptionToken="' . $resumptionToken . '"';

}



if(!$hasrequestError){
    //XML static

    $xmlHeader = '<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                    xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/
                    http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">
                    <responseDate>' . $currRequestDate . '</responseDate>';

    $xmlFooter = '</OAI-PMH>';  


    switch($verb){
        case "Identify":
            $xmlRequest = '<request verb="Identify">' . $oaiURL . '</request>';
            $xmlBody = '<Identify>
                            <repositoryName>Collections of Digital Archive of the National Theatre of Greece</repositoryName>
                            <baseURL>' . $oaiURL . '</baseURL>
                            <protocolVersion>2.0</protocolVersion>
                            <adminEmail>info@nt-archive.gr</adminEmail>
                            <earliestDatestamp>1932-01-01T00:00:00Z</earliestDatestamp>
                            <deletedRecord>no</deletedRecord>
                            <granularity>YYYY-MM-DD</granularity>
                            <description>
                                <policy xmlns="https://www.nt-archive.gr/policy">
                                    <termsOfUse>https://www.nt-archive.gr/terms</termsOfUse>
                                </policy>
                            </description>
                        </Identify>';
            break;
        case "ListMetadataFormats":
            $xmlRequest = '<request verb="ListMetadataFormats">' . $oaiURL . '</request>';
            $xmlBody = '<ListMetadataFormats>
                            <metadataFormat>
                                <metadataPrefix>edm</metadataPrefix>
                                <schema>http://www.europeana.eu/schemas/edm/EDM.xsd</schema>
                                <metadataNamespace>http://www.europeana.eu/schemas/edm/</metadataNamespace>
                            </metadataFormat>
                        </ListMetadataFormats>'; 
            break;
        case "GetRecord":
            $xmlRequest = '<request verb="ListRecords" metadataPrefix="' . $metadataPrefix . '" identifier="'.$identifier.'"></request>';
            //$currdate = date('YYYYMMDD');
            $identInfo=explode(':', $identifier);
            if(count($identInfo)==5 ){
                if(is_numeric($identInfo[4])){
                    if(validateSet($identInfo[3])){
                        $set = $identInfo[3];
                        $itemID = $identInfo[4];
                        $identimestamp = getIdentifierTimestamp($set, $itemID);
                        if($identimestamp!='' and !is_null($identimestamp)){
                            $xmlirecs .= '<GetRecord>
                                            <record>';
                            $xmlirecs .= '<header>
                                                <identifier>' . $identifier . '</identifier>
                                                <datestamp>' . $identimestamp . '</datestamp>
                                                <setSpec>' . $set . '</setSpec>
                                        </header>';
                            $singleMetadata = rdf_get_item($set, $itemID);
                            $xmlirecs .= '<metadata>' . $singleMetadata . '</metadata>';
                            $xmlirecs .= '</record>
                                        </GetRecord>';
                            $xmlBody = $xmlirecs;
                        }else{
                            $error = 'idDoesNotExist';
                            $hasrequestError = true;    
                        }
                    }else{
                        $error='badArgument';
                        $hasrequestError = true;    
                    }
                }else{
                    $error='badArgument';
                    $hasrequestError = true;
                }
            }else{
                $error = 'idDoesNotExist';
                $hasrequestError = true;
            }

            break;
        case "ListRecords":
            $cursor = 0;
            $xmlRequest = '<request verb="ListRecords" metadataPrefix="' . $metadataPrefix . '" ' . $settext . $fromtext . $untiltext . $restext . '>' . $oaiURL . '</request>';
            
            $allRecords = getIdentifiers($from, $until, $set);

            $currdatelist = date('YYYYMMDD');
            if(count($allRecords)>50){
                if($resumptionToken != ''){
                    $decodedresumption = base64_decode($resumptionToken);
                    $resumArray=explode('_', $decodedresumption);
                    echo '------>'.count($resumArray);
                    if(count($resumArray)==4){
                        if($resumArray[0] == 'listRec'){
                            $page=intval($resumArray[1]);
                            $start=$page * 50;
                            if(($start + 50) < count($allRecords)){
                                $end = $start + 49;
                                $newpage=$page+1;
                                $cursor = $end + 1;
                                $resumptionToken = base64_encode('listRec_' . $newpage . '_token_' . $currdatelist);        
                            }else{
                                $end = count($allRecords)-1;
                                $cursor = $end + 1;
                                $resumptionToken = '';
                            }
                        }
                    }else{
                        $error = 'badResumptionToken';
                        $hasrequestError = true;
                    }
                    
                }else{
                    $start = 0;
                    $end = 49;
                    $cursor = $end + 1;
                    $page = 1;
                    $resumptionToken = base64_encode('listRec_' . $page . '_token_' . $currdatelist);
                }
            }else{
                $start = 0;
                $end = count($allRecords)-1;
                $resumptionToken = '';
            }

            if(!$hasrequestError){
                $xmlirecs = "<ListRecords>";
                $k=0;
                for($i = $start; $i <= $end; $i++){
                    $identname = 'oai:nt-archive.gr:'.$metadataPrefix . ':' . $set . ':' . $allRecords[$i]['itemID'];
                    
                    $itemID = $allRecords[$i]['itemID'];
                    $setgroup = $allRecords[$i]['setgroup'];
                    $identdate = str_replace(' ', 'T', $allRecords[$i]['timestamp']).'Z';
                    $xmlirecs .= '<record>';
                    $xmlirecs .= '<header>
                                        <identifier>' . $identname . '</identifier>
                                        <datestamp>' . $identdate . '</datestamp>
                                        <setSpec>' . $allRecords[$i]['setgroup'] . '</setSpec>
                                </header>';
                    $singleMetadata = rdf_get_item($setgroup, $itemID);
                
                    $xmlirecs .= '<metadata>' . $singleMetadata . '</metadata>';
                    $xmlirecs .= '</record>';
                    $k=$i;
                    
                }

                if($resumptionToken!=''){
                    $xmlirecs .= '<resumptionToken cursor="' . $cursor . '" completeListSize="' . count($allRecords) . '" >' . $resumptionToken . '</resumptionToken>';
                }
                $xmlirecs .= "</ListRecords>";
                $xmlBody = $xmlirecs;
            }



            break;
        case "ListSets":
            $xmlRequest = '<request verb="ListSets">' . $oaiURL . '</request>';
            $xmlBody = '<listSets>';
            $xmlset = '';
            foreach($semanticTypes as $key => $set){

                $xmlset .= '<set>
                                <setSpec>' . $set['set'] . '</setSpec>
                                <setName>' . $set['setName'] . '</setName>
                                <setDescription >
                                    <dc:description xmlns:dc="http://purl.org/dc/elements/1.1/" xml:lang="en">'.
                                    $set['setDescription'] .
                                    '</dc:description>
                                    <dc:description xmlns:dc="http://purl.org/dc/elements/1.1/" xml:lang="el">'.
                                    $set['setDescriptionEN'] .
                                    '</dc:description>
                                </setDescription>
                        </set>';

            }
            $xmlBody .= $xmlset . '</listSets>';
            break;
        case "ListIdentifiers":
            $cursor = '';
            $xmlRequest = '<request verb="ListIdentifiers" metadataPrefix="' . $metadataPrefix . '" ' . $settext . $fromtext . $untiltext . $restext . '>' . $oaiURL . '</request>';
            $allIdentifiers = getIdentifiers($from, $until, $set);
            $currdatelist = date('YYYYMMDD');
            if(!empty($allIdentifiers)){
                if(count($allIdentifiers) > 100){
                    if($resumptionToken != ''){
                        $decodedresumption = base64_decode($resumptionToken);
                        $resumArray=explode('_', $decodedresumption);
                        if(count($resumArray)==4){
                            if($resumArray[0] == 'listIdent'){
                                $page=intval($resumArray[1]);
                                $start=$page * 100;
                                if(($start + 100) < count($allIdentifiers)){
                                    $end = $start + 99;
                                    $newpage=$page+1;
                                    $cursor = $end + 1;
                                    $resumptionToken = base64_encode('listIdent_' . $newpage . '_token_' . $currdatelist);        
                                }else{
                                    $end = count($allIdentifiers)-1;
                                    $cursor = $end + 1;
                                    $resumptionToken = '';
                                }
                            }
                        }else{
                            $error='badResumptionToken';
                            $hasrequestError=true;
                        }
                    }else{
                        $start = 0;
                        $end = 99;
                        $cursor = $end + 1;
                        $page = 1;
                        $resumptionToken = base64_encode('listIdent_' . $page . '_token_' . $currdatelist);
                    }
                }else{
                    $start = 0;
                    $end = count($allIdentifiers)-1;
                    $resumptionToken = '';
                }
                if(!$hasrequestError){
                    $xmlidents = "<ListIdentifiers>";
                    $k=0;
                    for($i = $start; $i <= $end; $i++){
                        $identname = 'oai:nt-archive.gr:'.$metadataPrefix . ':'. $allIdentifiers[$i]['setgroup'] . ':' . $allIdentifiers[$i]['itemID'];
                        $identdate = str_replace(' ', 'T', $allIdentifiers[$i]['timestamp']).'Z';
    
                        $xmlidents .= '<header>
                                            <identifier>' . $identname . '</identifier>
                                            <datestamp>' . $identdate . '</datestamp>
                                            <setSpec>' . $allIdentifiers[$i]['setgroup'] . '</setSpec>
                                    </header>';
                        $k=$i;               
                    }
    
                    if($resumptionToken!=''){
                        $xmlidents .= '<resumptionToken cursor="' . $cursor . '" completeListSize="' . count($allIdentifiers) . '" >' . $resumptionToken . '</resumptionToken>';
                    }
                    $xmlidents .= "</ListIdentifiers>";
                    $xmlBody = $xmlidents;
                }
                
            }else{
                $error = "noRecordsMatch";
                $hasrequestError = true;     
            }
            break;
        default:
           die();
    }

    if($hasrequestError){
        $XMLresponse = displayErrorResponse($error, $currRequestDate, $verb, $metadataPrefix, $set, $from, $until, $identifier, $resumptionToken);
    }else{
        $XMLresponse  = $xmlHeader . $xmlRequest . $xmlBody . $xmlFooter;
    }

    header("Content-type: text/xml; charset=utf-8");
    
    echo $XMLresponse;
}else{
    header("Content-type: text/xml; charset=utf-8");
    $XMLresponse = displayErrorResponse($error, $currRequestDate, $verb, $metadataPrefix, $set, $from, $until, $identifier, $resumptionToken);
    echo $XMLresponse;                    

}



?>