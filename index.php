<?php
global $dbh;
global $oaiURL;
global $semanticTypes;
include_once 'class.php';
$oaiURL = 'https://www.nt-archive.gr/oai-pmh';
$error = '';
$hasrequestError = false;

//DB connection
$dbh = db_connect();


//verb parameter
$verb='';
if(isset($_GET['verb']) and $_GET['verb'] != ''){
    $verb = $_GET['verb'];
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

//Current requset date
$currdate=date('Y-m-d h:i:s');
$currdate=str_replace(" ", "T", $currdate);
$currdate.='Z';


//XML static

$xmlHeader = '<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/
                http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">
                <responseDate>' . $currdate . '</responseDate>';

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
        break;
    case "ListRecords":
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
        $currdate = date('YYYYMMDD');
        if(count($allIdentifiers) > 100){
            if($resumptionToken != ''){
                $decodedresumption = base64_decode($resumptionToken);
                $resumArray=explode('_', $decodedresumption);
                if($resumArray[0] == 'listIdent'){
                    $page=intval($resumArray[1]);
                    $start=$page * 100;
                    if(($start + 100) < count($allIdentifiers)){
                        $end = $start + 99;
                        $newpage=$page+1;
                        $cursor = $end + 1;
                        $resumptionToken = base64_encode('listIdent_' . $newpage . '_token_' . $currdate);        
                    }else{
                        $end = count($allIdentifiers)-1;
                        $cursor = $end + 1;
                        $resumptionToken = '';
                    }
                }
            }else{
                $start = 0;
                $end = 99;
                $cursor = $end + 1;
                $page = 1;
                $resumptionToken = base64_encode('listIdent_' . $page . '_token_' . $currdate);
            }
        }else{
            $start = 0;
            $end = count($allIdentifiers)-1;
            $resumptionToken = '';
        }
        $xmlidents = "<ListIdentifiers>";
        $k=0;
        for($i = $start; $i <= $end; $i++){
            $identname = 'oai:nt-archive.gr:'.$metadataPrefix . ':' . $allIdentifiers[$i]['itemID'];
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
        
        break;
    default:
        $error = "badVerb";
        $hasrequestError = true;
}

if(!$hasrequestError){
    header("Content-type: text/xml; charset=utf-8");
    $XMLresponse  = $xmlHeader . $xmlRequest . $xmlBody . $xmlFooter;
    echo $XMLresponse;
}



?>