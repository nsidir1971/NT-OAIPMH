<?php
global $config,$config_multimedia_allowed_mime_types;


/**
 * Checks if user can perform the selected action
 *
 * @param int $cid creator_id
 */
function hasAccess($cid): bool
{
   if($cid == $_SESSION['nt2fuid']){
        return true;
    }
    return false;
}

function isMyProfile($userID){
    if($_SESSION['nt2fuid']==$userID){
        return true;
    }
    return false;
}


/**
 * Returns a GUIDv4 string
 *
 * Uses the best cryptographically secure method
 * for all supported pltforms with fallback to an older,
 * less secure version.
 *
 * @param bool $trim
 * @return string
 */
function GUIDv4 ($trim = true)
{
    // Windows
    if (function_exists('com_create_guid') === true) {
        if ($trim === true)
            return trim(com_create_guid(), '{}');
        else
            return com_create_guid();
    }

    // OSX/Linux
    if (function_exists('openssl_random_pseudo_bytes') === true) {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // Fallback (PHP 4.2+)
    mt_srand((double)microtime() * 10000);
    $charid = strtolower(md5(uniqid(rand(), true)));
    $hyphen = chr(45);                  // "-"
    $lbrace = $trim ? "" : chr(123);    // "{"
    $rbrace = $trim ? "" : chr(125);    // "}"
    $guidv4 = $lbrace.
        substr($charid,  0,  8).$hyphen.
        substr($charid,  8,  4).$hyphen.
        substr($charid, 12,  4).$hyphen.
        substr($charid, 16,  4).$hyphen.
        substr($charid, 20, 12).
        $rbrace;
    return $guidv4;
}

function URL_exists($filename){
    $correct_http=curPageURL();
    $full_url=$correct_http.'/'.$filename;
    $headers=get_headers($full_url);
    return stripos($headers[0],"200 OK")?true:false;
}

function curPageURL() {
    if(isset($_SERVER["HTTPS"]) && !empty($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] != 'on' )) {
        $url = 'http://'.$_SERVER["SERVER_NAME"];//https url
    }  else {
        $url =  'https://'.$_SERVER["SERVER_NAME"];//http url
    }
    return $url;
}

function get_country_name($countrykey){
    global $countryList;
    $country_name="";
    if($countrykey<>''){
        $country_name=$countryList[$countrykey];
    }
    return $country_name;

}

function get_country_list($selectedvalue=''){
    global $countryList;
    if($selectedvalue==''){
        $selectedvalue='GR';
    }
    foreach($countryList as $key=>$value){
        $seltext='';
        if($selectedvalue<>'' && $key==$selectedvalue){
            $seltext=' selected="selected"';
        }
        echo '<option value="'.$key.'" '.$seltext.'>'.$value.'</option>';
    }

}
/**
 * Returns a proper title string
 *
 * Titles are encoded as title#article in the DB
 * for proper sorting with the 1st letter sans the article.
 * This function displays them properly while allowing propper sorting.
 *
 * @param string $title
 * @return string
 */
function fix_title($play_title){
    $find_pos=mb_stripos($play_title,'#');
    if($find_pos){
        $trimmed_title=mb_substr($play_title,0,$find_pos);
        $article=mb_substr($play_title,$find_pos,mb_strlen($play_title));
        $fixed_title=mb_substr($article,1).' '.$trimmed_title;
    }else{
        $fixed_title=$play_title;
    }
    return preg_replace("/\"/", "&quot;", $fixed_title);
}

/**
 * Function that prepares a plat title to be saved depending it contains an article or not
 * @param $title string
 * @param $language string 'el|en'
 * @return string
 */
function preparePlayTitle($title, $language){
    switch($language){
        case 'el':
            $article_array=array("Ο","Η","Το","Οι","Τα","Τους","Στο","Στον","Στην","Στη","Στους", "Στις", "Του", "Της", "Των");
            break;
        case 'en':
            $article_array=array("A", "The");
            break;
    }

    for($i=0;$i<count($article_array);$i++){
        if(strpos($title,$article_array[$i])!==false){ //αν το άρθρο υπάρχει στη λέξη που δίνεται
            $position=intval(strpos($title,$article_array[$i]))+1;
            $article=mb_substr($title,0,$position+1,'utf-8');
            $new_title=mb_substr($title,$position+1,strlen($title),'utf-8').'#'.$article;
            break;
        }else{
            $new_title=trim($title);
        }
    }
    return trim($new_title);
}

/***
 * Returns person names in the Firstname + Lastname format
 * @param string $pname
 * @return string
 */
function fix_person_name($stored_name){
    if(is_numeric(mb_stripos($stored_name, ','))){
        $findcommapos=mb_stripos($stored_name, ',');
        $firstname=trim(mb_substr($stored_name, $findcommapos+1, mb_strlen($stored_name)));
        $lastname=trim(mb_substr($stored_name, 0, $findcommapos));
        $displayedname=$firstname.' '.$lastname;
    }else{
        $displayedname=$stored_name;
    }
    return $displayedname;
}


/***
 * Reverse function that identifies if title
 * contains an article and moves it to the end of the string with a # delemeter
 * @param string $title_string
 * @return string
 */
function prepare_artile_in_title($title){
    $article_array=array("Ο ","Η ","Του ","Το ","Οι ","Τα ","Τους ","Στο ","Στον ","Στην ","Στη ","Στους ", "Στις ", "Της ", "Των ");
    for($i=0;$i<count($article_array);$i++){
        if(mb_strpos($title,$article_array[$i])!==false){ //αν το άρθρο υπάρχει στη λέξη που δίνεται

            $position=intval(mb_strpos($title,$article_array[$i]));
            if($position==0) {
                $article = mb_substr($title, 0, mb_strlen($article_array[$i]), 'utf-8');
                $new_title = mb_substr($title, $position + mb_strlen($article_array[$i]), mb_strlen($title), 'utf-8') . '#' . trim($article);
            }else{
                $new_title=trim($title);
            }
            break;
        }else{
            $new_title=trim($title);
        }
    }
    return trim($new_title);
}

function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
    $sort_col = array();
    foreach ($arr as $key=> $row) {
        $sort_col[$key] = $row[$col];
    }
    array_multisort($sort_col, $dir, $arr);
}
function isVideo($file){
    global $config_multimedia_allowed_mime_types;
    $mime = mime_content_type($file);
    if ( !in_array($mime, $config_multimedia_allowed_mime_types) ) { return false; }
    if (strstr($mime, "video/")){
        return true;
    }
}

function rename_win($oldfile,$newfile) {
    if (!rename($oldfile,$newfile)) {
        if (copy ($oldfile,$newfile)) {
            unlink($oldfile);
            return TRUE;
        }
        return FALSE;
    }
    return TRUE;
}

function form_arr($a) {
    echo "<pre>";
    print_r($a);
    echo "</pre>";
}

function checkfileFormat($name,$is_part=false){
    $regex='/(\d\d\d\d+(-)\d+(-))\w+/';
    if($is_part){
        $regex='/(\d\d\d\d+(-)\d)\w+/';
    }
    if (preg_match($regex, $name)==1) { //case when the name is in correct format
        return true;
    } else {
        return false;
    }
}

// Check if the file has a width and height
function isImage($tempFile) {
    global $config;

    $mime_type = mime_content_type($tempFile);
    if ( !in_array($mime_type, $config["media_types"]) ) { return false; }
    // Get the size of the image
    $size = getimagesize($tempFile);

    if (isset($size) && $size[0] && $size[1] && $size[0] *  $size[1] > 0) {
        return true;
    } else {
        return false;
    }

}

function isPdf($tempFile) {
    global $config;

    $mime_type = mime_content_type($tempFile);
    if ( !in_array($mime_type, $config["doc_types"]) ) { return false; }
    // Get the size of the image
    $size = filesize($tempFile);
    if (isset($size) && $size > 0) {
        return true;
    } else {
        return false;
    }

}

function generateThumb($filename, $lun, $item, $quality = 80) {
    global $config_histPhoto_thumb_Folder, $config_poster_thumb_Folder, $config_photo_thumb_Folder;
    try {
        $pathinfo = pathinfo($filename);
        echo $filename;
        switch($item){
            case 'photo':
                $thumb=$config_photo_thumb_Folder.DIRECTORY_SEPARATOR.$pathinfo['filename'].'_tn.jpg';
                break;
            case 'historicPhoto':
                $thumb=$config_histPhoto_thumb_Folder.DIRECTORY_SEPARATOR.$pathinfo['basename'];
                break;
            case 'poster':
                $thumb=$config_poster_thumb_Folder.DIRECTORY_SEPARATOR.$pathinfo['basename'];
                break;
        }
        
            //echo $thumb_webp;
        list ($x, $y, $type) = getimagesize ($filename);

        switch ($type) {
            case 1:   //   gif -> jpg
                $img = imagecreatefromgif($filename);
                break;
            case 2:   //   jpeg -> jpg
                $img = imagecreatefromjpeg($filename);
                break;
            case 3:  //   png -> jpg
                $img = imagecreatefrompng($filename);
                break;
        }
        if ($x > $y) {
            $tx = $lun;
            $ty = round($lun / $x * $y);
        } else {
            $tx = round($lun / $y * $x);
            $ty = $lun;
        }
        $thb = imagecreatetruecolor($tx, $ty);

        // Enable interlancing
        if(imageistruecolor($thb)) imageinterlace($thb, true);

        imagecopyresampled ($thb, $img, 0,0, 0,0, $tx,$ty, $x,$y);
        switch ($type) {
            case 1:   //   gif
                imagegif($thb, $thumb, $quality);
                break;
            case 2:   //   jpeg -> jpg
                imagejpeg($thb, $thumb, $quality);
                break;
            case 3:  //   png -> jpg
                imagepng($thb, $thumb, 9);
                break;
        }
        imagedestroy ($thb);
        imagedestroy ($img);
    } catch ( Exception $e) {
        http_response_code(500);
        var_dump($e);
    }
}

function generateScreen($filename, $lun, $item, $quality = 100) {
    global $config_histPhoto_screen_Folder, $config_photo_screen_Folder, $config_poster_screen_Folder;
    try {
        $pathinfo = pathinfo($filename);

        switch($item){
            case 'photo':
                $screen=$config_photo_screen_Folder.DIRECTORY_SEPARATOR.$pathinfo['filename'].'_sc.jpg';
                break;
            case 'historicPhoto':
                $screen=$config_histPhoto_screen_Folder.DIRECTORY_SEPARATOR.$pathinfo['basename'];
                break;
            case 'poster':
                $screen=$config_poster_screen_Folder.DIRECTORY_SEPARATOR.$pathinfo['basename'];
                break;
        }
        list ($x, $y, $type) = getimagesize ($filename);

        switch ($type) {
            case 1:   //   gif -> jpg
                $img = imagecreatefromgif($filename);
                break;
            case 2:   //   jpeg -> jpg
                $img = imagecreatefromjpeg($filename);
                break;
            case 3:  //   png -> jpg
                $img = imagecreatefrompng($filename);
                break;
        }
        if ($x > $y) {
            $tx = $lun;
            $ty = round($lun / $x * $y);
        } else {
            $tx = round($lun / $y * $x);
            $ty = $lun;
        }
        $thb = imagecreatetruecolor($tx, $ty);

        // Enable interlancing
        if(imageistruecolor($thb)) imageinterlace($thb, true);

        imagecopyresampled ($thb, $img, 0,0, 0,0, $tx,$ty, $x,$y);
        switch ($type) {
            case 1:   //   gif
                imagegif($thb, $screen, $quality);
                break;
            case 2:   //   jpeg -> jpg
                imagejpeg($thb, $screen, $quality);
                break;
            case 3:  //   png -> jpg
                imagepng($thb, $screen, 9);
                break;
        }
        imagedestroy ($thb);
        imagedestroy ($img);
    } catch ( Exception $e) {
        http_response_code(500);
        var_dump($e);
    }
}


/**
 * Recursively deletes non-empty directories
 * @param $src
 * @return void
 */
function recursive_rmdir($src) {
    $dir = opendir($src);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            $full = $src . '/' . $file;
            if ( is_dir($full) ) {
                recursive_rmdir($full);
            }
            else {
                unlink($full);
            }
        }
    }
    closedir($dir);
    rmdir($src);
}


/**
 * Returns a value greater than zero if the value is found in the array
 * @param $arr
 * @param $value
 * @param $col
 * @return int
 */
function ep_array_search($arr, $value, $col) {
    $c = 0;
    foreach ($arr as $k=>$v) {
        if ($value == $v[$col]) {
            $c++;
        }
    }
    return $c;
}



/**
 * Check if the requested page is associated with a playID
 * If not then set the S_SESSION['nt2b_selPlayID'] to 0
 * @param $string: the requested page
 * @param $string: the requested url
 * @return int
 */
function check_if_associated_with_play($page, $req_url) {
    $page_whitelist = array('org', 'work', 'person', 'program', 'pub', 'photo', 'sound', 'soundpart','poster', 'histphoto', 'costume', 'video', 'videopart', 'music', 'musicscore');

    if (in_array($page, array('play', 'repeat', 'manage', 'managecol'))) return 1;

    if (in_array($page, $page_whitelist) and stripos($req_url, 'param')) {
        return 1;
    }

    return 0;
}



function create_thumb_from_pdf($pdf_path, $jpg_path) {
    global $config;
    $pdf_file = escapeshellarg($pdf_path);
    $jpg_file = escapeshellarg($jpg_path);

    $result = null;
    $output = null;
    //exec('"C:\Program Files\ImageMagick-7.1.1-Q16-HDRI\magick.exe" "'.$pdf_file.'" -colorspace RGB -density 300 "'.$jpg_file.'" 2>&1', $output, $result);
    exec("\"{$config["image_magick_path"]}\" \"$pdf_file\" -colorspace RGB -density 300 \"$jpg_file\" 2>&1", $output, $result);
}


/**
 * @param $array
 * @param $key
 * @param $value
 *
 * @return array
 */
function searchkeymulti($array, $key, $value)
{
    $results = array();

    if (is_array($array)) {
        //echo  $value;
        if (isset($array[$key]) && $array[$key] == $value) {
            $results[] = $array;
        }
        //form_arr($results);
        foreach ($array as $subarray) {

            $results = array_merge(
                $results, searchkeymulti($subarray, $key, $value)
            );
        }
    }
    return $results;
}


function detect_str_lang($str) {
    if (!preg_match('/[^A-Za-z0-9]/', $str)) {
        // string contains only english letters & digits
        echo 'en';
    } else {
        echo 'el';
    }
}



// CLEARS THE GIVEN PAGE ASSOCIATED SESSION VARIABLES WHEN CHANGING LANGUAGE
// ARGUMENTS: THE RELATED PAGE AND THE PREVIOUS LANGUAGE
function clear_sessions($page, $prev_lang) {
    switch ($page) {
        case 'works':
            if ($prev_lang != $_SESSION['ntlang']) {
                $_SESSION['filterWorkLetter'] = '';
                $_SESSION['filterWorkText'] = '';
                $_SESSION['searchByString'] = '';
                $_SESSION['filterTypeTerm'] = '';
                $_SESSION['filterTypeCategory'] = '';
                $_SESSION['selectedWorkTypeDescr'] = '';
                $_SESSION['orderByField'] = '';
                $_SESSION['orderBy'] = '';
            }
            break;
        case 'people':
            if ($prev_lang != $_SESSION['ntlang']) {
                $_SESSION['filterPeopleLetter'] = '';
                $_SESSION['filterPeopleText'] = '';
                $_SESSION['orderByPeopleField'] = '';
                $_SESSION['orderByPeople'] = '';
            }
            break;
        case 'places':
            if ($prev_lang != $_SESSION['ntlang']) {
                $_SESSION['filterPlacesLetter'] = '';
                $_SESSION['filterPlacesText'] = '';
                $_SESSION['whereBtn'] = '';
                $_SESSION['orderByPlacesField'] = '';
                $_SESSION['orderByPlaces'] = '';
            }
            break;
        case 'roles':
            if ($prev_lang != $_SESSION['ntlang']) {
                $_SESSION['filterRoleLetter'] = 'all';
                $_SESSION['filterRoleText'] = '';
                $_SESSION['pageRoleNum'] = 0;
                $_SESSION['numOfRolePages'] = 10;
                $_SESSION['totalRolePages'] = '';
            }
            break;
        case 'stages':
            if ($prev_lang != $_SESSION['ntlang']) {
                $_SESSION['filterCom'] = 'no';
                $_SESSION['pagePlayNum'] = 0;
                $_SESSION['numOfCompPages'] = 9;
                $_SESSION['totalSearchCompPages'] = '';
            }
            break;
        case 'costumes':
            if ($prev_lang != $_SESSION['ntlang']) {
                $_SESSION['totalCostumesSearchPages'] = 0;
                $_SESSION['pageLength'] = 10;
                $_SESSION['costumeOrderBy'] = '';
                $_SESSION['costumeOrderByField'] = '';
            }
            break;
        case 'plays':
            if ($prev_lang != $_SESSION['ntlang']) {
                $_SESSION['orderByPlay'] = 1;
                $_SESSION['pagePlayNum'] = 0;
                $_SESSION['filterPlayYear'] = 0;
                $_SESSION['filterPlayLetter'] ='all';
                $_SESSION['filterPlayText'] ='';
                $_SESSION['numPlayOfPages']=9;
            }
            break;
    }
}

function fix_footer($page){

}

function displayFulldDate($repeatDate, $lang){
    if($lang=='el'){
        if(mb_stripos($repeatDate, '/')===false){  //For dates not in date format at all
            $newDate=$repeatDate;
        }else{
            $dateArray=explode('/', $repeatDate);
            if(count($dateArray)==2){  //Not full date e.g. 2023/10
                $newDate=$dateArray[1].'/'.$dateArray[0];
            }elseif(count($dateArray)==3){ //Full date 2023/10/5
                $newDate = date("d/m/Y", strtotime($repeatDate));
            }
        }
    }else{
        $newDate=$repeatDate;
    }
    return $newDate;
}

/*
αν ειναι 4ψήφιο σκετο το βγάζει όπως είναι.
αν είναι 1991-1993 το βγάζει όπως είναι.
αν είναι -0χχχ το βγάζει ΕΛ=> χχχ π.Χ.    (π.χ. -0388  ->  388 π.Χ.) ΕΝ=>xxx B.C.
αν είναι: 1600 ή 1635 το βγάζει όπως είναι.
αν ειναι -0xxx ante  -> το βγάζει: EL=> πριν το xxx π.Χ.  EN=> xxx B.C. ante
αν ειναι xxxx ante  -> το βγάζει πριν το xxxx.
αν ειναι 1939, 1945, 1957  να βγάζει το ίδιο.
αν είναι 11ος-12ος αι.  E:=> να βγαζει το ίδιο.  EN=> 11th-12th cent.
αν ειναι -0460 / -0450  -> να βγαζει EL=> 460 π.Χ. / 450 π.Χ.  EN=>460 B.C. / 450 B.C.
αν είναι xxxx c. να βγάζει EL=> το ίδιο (το c. είναι από το circa και σημαίνει περίπου)
αν ειναι -0xxx c. να βγάζει EL=> c. xxx π.Χ. EN=> c. xxx B.C.
αν ειναι -0468 post να βγάζει EL-> μετά το 468  π.Χ  EN=> 468 B.C. post
αν είναι 3ος αι. π.Χ. να βγάζει ΕΛ=> το ίδιο ΕΝ=> 3rd c. B.C.
 */

// FORMAT DATE
function fix_date($d, $lang) {
    $formatted_d = '';
    $cent1 = 'th';
    $cent2 = 'th';
    $delim = '';
    if (preg_match('/^(\d+){4,4}(?![\s.,-])/', $d, $matches)) {
        $formatted_d = $matches[0];    
    } else if (preg_match('/^((\d+){4,4})\s(ante|post)/', $d, $matches)) {
        $formatted_d = ($lang == 'el' ? 'πριν το ' . $matches[1] : $matches[1] . ' ante');
    } else if (preg_match('/^(\d+){4,4}(\-|\s\p{Greek}+\s|,\s)(\d+){4,4}(?!,)/u', $d, $matches)) {
        $formatted_d = $matches[0];
    } else if (preg_match('/^(\d+){4,4},\s(\d+){4,4},\s(\d+){4,4}/u', $d, $matches)) {
        $formatted_d = $matches[0];
    } else if ((preg_match('/^-0((\d+){3,3})(\s(\/|ή)\s)-0((\d+){3,3})/', $d, $matches))) {
        if ($lang == 'en') $delim = ' or ';
        $formatted_d = ($lang == 'el' ? $matches[1] . ' π.Χ.' . $matches[3] . $matches[5] . ' π.Χ.' : $matches[1] . ' B.C.' . $delim . $matches[5] . ' B.C.');
    } else if ((preg_match('/^-0((\d+){3,3})(\s?!-ante)/', $d, $matches))) {
        $formatted_d = ($lang == 'el' ? $matches[1] . ' π.Χ.' : $matches[1] . ' B.C.');
    } else if ((preg_match('/^-0((\d+){3,3})\s(ante)/', $d, $matches))) {
        $formatted_d = ($lang == 'el' ? 'πριν το ' . $matches[1] . ' π.Χ.' : $matches[1] . ' B.C. ante');
    } else if ((preg_match('/^-0((\d+){3,3})/', $d, $matches))) {
        $formatted_d = ($lang == 'el' ? $matches[1] . ' π.Χ.' : $matches[1] . ' B.C.');
    } else if ((preg_match('/^(\d+){1,2}ος\s?-\s?(\d+){1,2}ος\sαι\./', $d, $matches))) {
        if ($matches[1] == 1) {
            $cent1 = 'st';
        } else if ($matches[1] == 2) {
            $cent1 = 'nd';
        } else if ($matches[1] == 3) {
            $cent1 == 'rd';
        }
        if ($matches[2] == 1) {
            $cent2 = 'st';
        } else if ($matches[2] == 2) {
            $cent2 = 'nd';
        } else if ($matches[2] == 3) {
            $cent2 == 'rd';
        } 
        $formatted_d = ($lang == 'el' ? $matches[0] . ' π.Χ.' : $matches[1] . $cent1 . ' - ' . $matches[2] . $cent2 . ' cent.');
    } else if ((preg_match('/^(\d+){1,2}ος\sαι\.\sπ\.Χ\./', $d, $matches))) {
        if ($matches[1] == 1) {
            $cent1 = 'st';
        } else if ($matches[1] == 2) {
            $cent1 = 'nd';
        } else if ($matches[1] == 3) {
            $cent1 == 'rd';
        }
        $formatted_d = ($lang == 'el' ? $matches[0] : $matches[1] . $cent1 . ' c. B.C.');
    } else if (preg_match('/^((\d+){4,4})\s(c\.)/', $d, $matches)) {
        $formatted_d = ($lang == 'el' ? $matches[1] : $matches[1] . ' c.');
    } else if ((preg_match('/^-0((\d+){3,3})(\sc\.)/', $d, $matches))) {
        //echo 222;
        $formatted_d = ($lang == 'el' ? 'c. '. $matches[1] . ' π.Χ.' : 'c. ' . $matches[1] . ' B.C. ');
    } else if ((preg_match('/^-0((\d+){3,3})(\spost)/', $d, $matches))) {
        $formatted_d = ($lang == 'el' ? 'μετά το ' . $matches[1] . ' π.Χ.' : $matches[1] . ' B.C. ' . $matches[3]);
    }
    //form_arr($matches);
    return $formatted_d;
}


// CHECK IF URL EXISTS
function check_url_status($url) {
    $status = '';
    $headers = @get_headers($url);

    if($headers && strpos( $headers[0], '200')) { 
        $status = 'true'; 
    } else { 
        $status = 'false'; 
    }
    
    return $status;
}

function convertStrToDate($input)
{
    $time_input = strtotime($input);
    $date_input = getDate($time_input);
    return $date_input['mday'].'/'.$date_input['mon'].'/'.$date_input['year'];


}



// CHECK IF ANY OF THE ARRAY KEYS EXISTS AND HAS VALUE
function any($coll) {
    $any = false;
    foreach($coll as $k=>$v) {
        if (isset($k) and ($v != '' or $v != null)) {
            $any = true;
            break;
        }
    }
    return $any;
}


// CHECK IF ANY OF THE ARRAY VALUES HAS VALUE
function any_val($coll) {
    $any = false;
    foreach($coll as $k=>$v) {
        if ($v != '' or $v != null) {
            $any = true;
            break;
        }
    }
    return $any;
}


// CHECK IF ALL OF THE ARRAY KEYS EXIST AND HAVE VALUE
function all($coll) {
    $coll_len = count($coll);
    $cnt = 0;

    foreach($coll as $k=>$v) {
        if (isset($k) and ($v != '' or $v != null)) {
            $cnt++;
        }
    }

    if ($coll_len == $cnt) {
        return true;
    } else {
        return false;
    }
}


// CLEAR SESSION KEYS BY GIVEN PREFIX
function clear_sessions_by_prefix($prefix) {
    foreach (array_keys($_SESSION) as $key) {
        if (strpos($key, $prefix) === 0) {
            unset($_SESSION[$key]);
        }
    }
}


// CREATE A SET FROM THE GIVEN 2D ARRAY.
// THAT WILL CREATE AN ARRAY WITH UNIQUE VALUES ON NESTED COLUMN NAMED $col
function to_set($two_d_arr, $col) {
    $prev_val = '';
    $ret = array();
    foreach($two_d_arr as $arr) {
        if ($arr[$col] == $prev_val) continue;
        $prev_val = $arr[$col];
        array_push($ret, $arr);
    }
    return $ret;
}

function strip_tags_content($text) {
    return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
}

function randomizeImage(){
    global $config;
    $imagesDir= realpath(dirname(__FILE__)) .'/../media/banners/';
    $images = array_slice(scandir($imagesDir), 2);
    return $config['app_path'] . '/media/banners/' . $images[array_rand($images)];
}

function is_true($var)
{
    return $var == 'TRUE';
}