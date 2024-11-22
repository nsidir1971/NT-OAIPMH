<?php
global $dbh;
global $oaiURL;
include_once 'class.php';
$oaiURL = 'https://www.nt-archive.gr/oai-pmh';
$error = '';
$hasrequestError = false;

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
if(isset($_GET['from']) and $_GET['from'] != ''){
    if(validateDate($_GET['from'])){
        $from = $_GET['from'];
    }else{
        $error = 'badArgument';
        $hasrequestError = true;
    }
}
// until param
$until = '';
if(isset($_GET['until']) and $_GET['until'] != ''){
    if(validateDate($_GET['until'])){
        $until = $_GET['until'];
    }else{
        $error = 'badArgument';
        $hasrequestError = true;
    }
}

//set param
$set = '';
if(isset($_GET['set']) and $_GET['set']!=''){
    $set = $_GET['set'];
}

//resumptionToken param
$resumptionToken ='';
if(isset($_GET['resumptionToken']) and $_GET['resumptionToken']!='' ){
    $resumptionToken = $_GET['resumptionToken'];

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
        break;
    case "GetRecord":
        break;
    case "ListRecords":
        break;
    case "ListSets":
        break;
    case "ListIdentifiers":
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