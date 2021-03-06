<?php 

define('MAX_UPLOAD_SIZE', '2000000');
define('MAX_GALERY_SIZE', '50000000');
define('IMG', 'img/');
define('ACCEPTED_MIME', array('image/jpeg', 'image/jpg', 'image/png'));
define('IMG_GALERY', array_diff(scandir('img'), array('..', '.')));

define('ARR_USERS' , array(
    'admin' => '$2y$10$3gdUfYKl0zoAe1vCWxT2/OsJj5u65.TL9fLg2En5OxssIQn4n7Ioe',
    'guest' => '$2y$10$wj4BR.SutwzkBBy3JpOhmezAHyTGr..LK4FSwveqaVvbFxE6PPTqW'
));

if (isset($_POST['deconnection'])) {
    session_destroy();
}

$regexLogin = '/^[a-z0-9_-]{3,15}$/';
$errorMessage = '';

if (isset($_POST['login']) && !preg_match($regexLogin, $_POST['login']) || isset($_POST['password']) && !preg_match($regexLogin, $_POST['password'])) {
    $errorMessage = 'Login ou mot de passe invalide';
}

if (isset($_POST['login']) && preg_match($regexLogin, $_POST['login']) && isset($_POST['password']) && preg_match($regexLogin, $_POST['password'])) {
    verifPwd ($_POST['password'], $_POST['login'], $src = ARR_USERS);
}

function verifPwd ($pwd, $user, $src = ARR_USERS) { //$pwd : pwd en clair à vérifier $user: nom d'utilisateur $src: source de données user/mdp (default= $ArrUsers)
    global $errorMessage;
    if (isset($src[$user]) && !empty($src[$user])) {
        $hashedPwd = $src[$user];
        //faire ici contrôles suplémentaires
        $boolConnected = password_verify($pwd, $hashedPwd);
        if ($boolConnected == 1) {
            session_regenerate_id(true);
            $_SESSION['name'] = $user;
            if ($_SESSION['name'] == 'admin') {
                header("Status: 301 Moved Permanently", false, 301);
                header('Location: dashboard.php');
                exit();
            }
            if ($_SESSION['name'] == 'guest') {
                header("Status: 301 Moved Permanently", false, 301);
                header('Location: galery.php');
                exit();
            }
        }
    } else {
        $errorMessage = 'Login ou mot de passe invalide';
    }
    if ($_COOKIE["PHPSESSID"] === session_id()) {
        return TRUE;
    } else { 
        return FALSE;
    }
//    return $boolConnected;
}


if (isset($_POST['login']) && !empty($_POST['password'])) {
    verifPwd($_POST['password'], $_POST['login']);
}



function rearrange($arr){
    foreach( $arr as $key => $all ){
        foreach( $all as $i => $val ){
            $new[$i][$key] = $val;   
        }   
    }
    return $new;
}

function testUpload($fileArr) {
    if (empty($fileArr['tmp_name'])  
    && $_SERVER['REQUEST_URI'] == $_SERVER['SCRIPT_NAME'] 
    && $_SERVER['REQUEST_METHOD'] == 'POST')
    {
        $test = FALSE;
    } else {
        $test = TRUE;
    }
    return $test;
}


function testFileSize($fileArr) {
    if (MAX_UPLOAD_SIZE == $_POST['MAX_FILE_SIZE']) {
        if (MAX_UPLOAD_SIZE > $fileArr['size'] && $fileArr['size'] > 0) {
            $msg = [TRUE, 'Le fichier est dans la limite de ' . MAX_UPLOAD_SIZE / 1000000 . ' Mo.'];
        } elseif ($fileArr['size'] == 0){
            $msg = [FALSE, 'Erreur: la taille du fichier est null'];
        } else {
            $msg = [FALSE, 'Erreur: Votre image dépasse les ' . MAX_UPLOAD_SIZE / 1000000 . ' Mo.'];
        }
    } else {
        $msg = [FALSE, 'Erreur: La taille maximum du fichier n\'est pas correctement défini'];
    }
    return $msg; // [BOOL, MSG]
}

function testMime($fileArr) {
    $fileMime = mime_content_type($fileArr['tmp_name']);
    if (in_array($fileMime, ACCEPTED_MIME)){
        $msg = [TRUE, 'Le fichier ' . $fileArr['name'] . ' est une image valide', $fileMime];
    } else {
        $msg = [FALSE, 'Le fichier ' . $fileArr['name'] . ' n\'a pas un format valide', $fileMime];
    }
    return $msg; // [BOOL, MSG, Mime/Type]
}

function showMsgs ($filesArr) {
    foreach ($filesArr as $key => $value) {
        if ($value['testFileSize'] && $value['testMime']){
            echo '<p class="light-green-text text-darken-1">';
            echo $value['name'] . ' : ' . $value['msgFileSize'];
            echo '<br>';
            echo $value['msgMime'] . ' et a bien été téléchargé.';
            echo '</p>';
        } else {
            echo '<p class="red-text text-accent-4">';
            echo $value['name'] . ' : ' . $value['msgFileSize'];
            echo '<br>';
            echo $value['msgMime'] . ' Télechargement abandonné';
            echo '</p>';
        }      
    }
}
$imgGalery = array_diff(scandir('img'), array('..', '.'));


function sizeGalery($imgGalery) {
    $totalImgSize = 0;
    foreach($imgGalery as $img) {
        $imgSize = filesize('img/'.$img);
        $totalImgSize += $imgSize;
    }
    return $totalImgSize;
}

if (isset($_FILES['myImg']) 
&& count($_FILES['myImg']['tmp_name']) > 0 
&& $_SERVER['REQUEST_URI'] == $_SERVER['SCRIPT_NAME'] 
&& $_SERVER['REQUEST_METHOD'] == 'POST' 
&& sizeGalery($imgGalery) < MAX_GALERY_SIZE)
{
    $filesArr = rearrange($_FILES['myImg']);
    foreach ($filesArr as $key => $fileArr) {
        if (testUpload($fileArr)) {
            $filesArr[$key] += ['testFileSize' => testFileSize($fileArr)[0],
                        'msgFileSize' => testFileSize($fileArr)[1],
                        'testMime' => testMime($fileArr)[0],
                        'msgMime' => testMime($fileArr)[1],
                        'mime' => testMime($fileArr)[2]];
            if ($filesArr[$key]['testFileSize'] && $filesArr[$key]['testMime']){
                $mimeExt = preg_split('[/]', $filesArr[$key]['mime']);               
                do {
                    $newID = uniqid('img_');
                    $newName = $newID . '.' . $mimeExt[1];
                } while (file_exists(IMG . $newName));
                move_uploaded_file($filesArr[$key]['tmp_name'], IMG . $newName);
            }
        } else {
            $filesArr[$key] += ['testFileSize' => 0,
                        'msgFileSize' => 'Fichier supérieur à ' . MAX_UPLOAD_SIZE /1000000 . ' Mo.',
                        'testMime' => FALSE,
                        'msgMime' => 'Erreur fichier non conforme.',
                        'mime' => 'none/none'];
        }
    }
} 


