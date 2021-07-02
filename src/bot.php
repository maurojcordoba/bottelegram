<?php
set_error_handler("LogDeErrores");

date_default_timezone_set("America/Argentina/Buenos_Aires");

include_once 'config.php';

$input = file_get_contents('php://input');
$update = json_decode($input, TRUE);

if  ((isset($update['message']['text'])) && (substr($update['message']['text'],0,1)=='/')) { 

    $chatId = $update['message']['chat']['id'];
    $message = $update['message']['text'];
    
    $message = preg_split("/[\s@]+/", $update['message']['text']);
    $message = strtolower($message[0]);

    switch($message) {
        case '/start':
            $response = 'Iniciado';
            sendMessage($chatId, $response);
            break;
        case '/help':
            $response = getHelp();
            sendMessage($chatId, $response);
            break;
        case '/info':
            $response = 'Hola! Soy @botana1_bot';
            sendMessage($chatId, $response);
            break;
        case '/sortea':
            $response = getEquipos();
            //$response = 'paso';
            sendMessage($chatId, $response);
            break;
        case '/wz':
            $image = 'wz1.jpg';
            sendPhoto($chatId, $urldom.$image);
            break;
        case '/batiwz':
            $image = 'wz2.jpg';
            sendPhoto($chatId, $urldom.$image);
            break;
        case '/dado':
            sendDice($chatId);
            break;
        case '/partidos-hoy':
        case '/partidos-ayer':
        case '/partidos-man':
            $response = "Usar /hoy, /aye o /man";
            sendMessage($chatId, $response);
            break;
        case '/hoy':
            $response = getPartidos('hoy');
            sendMessage($chatId, $response);
            break;
        case '/aye':
            $response = getPartidos('ayer');
            sendMessage($chatId, $response);
            break;
        case '/man':
            $response = getPartidos('man');
            sendMessage($chatId, $response);
            break;
        case '/planeros':
            $image = 'planeros.gif';
            sendAnimation($chatId, $urldom.$image);
            break;
        default:
            $response = getFrase();
            sendMessage($chatId, $response);
            break;
    }
}


if(isset($update['inline_query'])){

    $results = array();

    $inline_query_id = $update['inline_query']['id'];
    $query = $update['inline_query']['query'];
    if(!empty($query)){
        $urlApiMovieDB = "$urlMovieDB/3/search/movie?api_key=$apiKeyMovieDB&language=$languageMovieDB&query=$query&page=1";
    
        $resultSearch = file_get_contents($urlApiMovieDB);
        $arrSearch = json_decode($resultSearch, TRUE);
    
        $movies = $arrSearch['results'];
        
    
        foreach ($movies as $key => $movie) {
            $id = $movie['id']; // unique identifier of the content
            $title = $movie['title']; // inline title
            $description = $movie['overview']; // inline description
            
            $thumb_url = 'https://image.tmdb.org/t/p/w200'.$movie['backdrop_path'];
            $thumb_width = 100;
            $thumb_height = 100;
    
            $poster_path = 'https://image.tmdb.org/t/p/w200'. $movie['poster_path'];
    
            $original_title = $movie['original_title'];
            $vote_average = $movie['vote_average'];
            $vote_count = $movie['vote_count'];
            $urlMovie = "https://www.themoviedb.org/movie/$id?language=es/";
            
            
            array_push($results, 
                Array(
                    'type' => 'article', 
                    'id' => "$id", 
                    'title' => $title, 
                    'description' => $description, 
                    'thumb_url' => $thumb_url,
                    'thumb_width' => $thumb_width,
                    'thumb_height' => $thumb_height,
                    'input_message_content' => Array(
                    'message_text' => "<b>$title</b>\nOriginal Title: $original_title\nVote Average: $vote_average\nVote Count: $vote_count\n\n$description\n\n<a href='$urlMovie'>URL</a><img src='$thumb_url' alt='$original_title'>",
                        'parse_mode' => 'HTML',
                    )
                )
            );         
        }

        $results = json_encode($results);
        
        answerInlineQuery($inline_query_id, $results);
    }
}

function answerInlineQuery($inline_query_id, $results){
    $url = $GLOBALS['website'].'/answerInlineQuery?inline_query_id='.$inline_query_id.'&results='.urlencode($results);
    $data = file_get_contents($url);
}

function sendMessage($chatId, $response) {
    $url = $GLOBALS['website'].'/sendMessage?chat_id='.$chatId.'&parse_mode=HTML&text='.urlencode($response);
    file_get_contents($url);
}

function sendDice($chatId) {
    $url = $GLOBALS['website'].'/sendDice?chat_id='.$chatId;
    file_get_contents($url);
}

function sendPhoto($chatId, $urlimage) {
    $url = $GLOBALS['website'].'/sendPhoto?chat_id='.$chatId.'&photo='.$urlimage;
    file_get_contents($url);
}
function sendAnimation($chatId, $urlimage) {
    $url = $GLOBALS['website'].'/?chat_id='.$chatId.'&animation='.$urlimage;
    file_get_contents($url);
}
// Devuevle 2 equipos con orden random
function getEquipos(){
    
    $file = file('jugadores.txt');

    foreach ($file as $key => $line) {
        $line = str_replace(PHP_EOL, '', $line);
        $j[] = $line;
    }

    shuffle($j);

    $response = "<b>Equipo 1:</b>\n$j[0]\n$j[1]\n$j[2]\n\n<b>Equipo 2:</b>\n$j[3]\n$j[4]\n$j[5]";

    return $response;
}

function getFrase(){    
    $file = file('frasesb.txt');

    foreach ($file as $key => $line) {
        $j[] = $line;
    }
    
    $i = rand(0, count($j)-1);


    return $j[$i];
}

function getPartidos($str='hoy'){
    require 'lib/simplehtmldom/simple_html_dom.php';
    
    $ligas_exc = [];
    $ligas_exc = json_decode(file_get_contents('ligas_exc.json'), true );
    
    
    $url = 'https://www.promiedos.com.ar/';
    
    switch($str) {
        case 'hoy':
            $url = $url;
            break;
        case 'ayer':
            $url = $url.'ayer';
            break;
        case 'man':
            $url = $url.'man';
            break;
    }
    
    $html = file_get_html($url);
    
    $response = '';
    foreach($html->find('#partidos div') as $article) {
        
        if($article->id == 'titulo2'){
            if ($article->plaintext == "PROXIMOS\r\nPARTIDOS"){
                break;
            } 
        }

        if($article->id == 'fixturein'){
                        
                $titulo  = trim($article->find('.tituloin', 0)->plaintext);

                // Exceptua ligas
                if(in_array($titulo,$ligas_exc)) continue;

                $response .= "<b><u>$titulo</u></b>\n";

                foreach($article->find('tr[name=nvp]') as $game) {        
                    
                    if( substr($game->id,0,4) == 'gole' ){
                        $goleseq1    = trim($game->find('td',0)->plaintext);
                        $goleseq2    = trim($game->find('td',1)->plaintext); 
                    
                        if(!empty($goleseq1) && !empty($goleseq2)){
                            $salida = sprintf("<i>%s \n %s</i>",$goleseq1,$goleseq2);
                            //$salida = htmlspecialchars($salida);
                            $response .= str_replace(PHP_EOL, '', $salida);
                            $response .= "\n";
                        }  

                    }else{               
                        $tiempo     = trim($game->find('td',0)->plaintext);
                        $equipo1    = trim($game->find('td',1)->plaintext);                        
                        $res1       = trim($game->find('td',2)->plaintext);
                        $res2       = trim($game->find('td',3)->plaintext);
                        $equipo2    = trim($game->find('td',4)->plaintext);
                    

                        if(!empty($equipo1)){
                            $salida = sprintf('%s %s vs %s %s (%s)',$equipo1,$res1,$equipo2,$res2,trim($tiempo));
                            $response .= str_replace(PHP_EOL, '', $salida);
                            $response .= "\n";
                        }        
                    }
                }
                $response .= "\n";
        }

    }

    if($response == '') $response = 'No hay partidos.';

    return $response;
}

function getHelp(){
    return "/help - Lista de comandos
/sortea - Sortea los equipos
/wz - Envia el warzone llamado
/batiwz - Envia la warzone señal
/aye - Partidos de ayer
/hoy - Partidos de hoy
/man - Partidos de mañana";
}

function bitacora($msg){
    file_put_contents('m.txt',sprintf( "[%s - %s]: %s \r\n", date('Y-m-d'),date('H:i:s'),  print_r($msg,true)), FILE_APPEND);
}

function LogDeErrores($numeroDeError, $descripcion, $fichero, $linea, $contexto){
	error_log("Error: [".$numeroDeError."] ".$descripcion." ".$fichero." ".$linea." ".json_encode($contexto)." \n\r", 3, "log.txt");
}
?>