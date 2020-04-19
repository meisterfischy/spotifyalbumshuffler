<?php

session_start();

if (!isset($_SESSION)) {
    $_SESSION['access_token'] = '';
    $_SESSION['refresh_token'] = '';
    $_SESSION['exp_date'] = 0;
}

print_r(getTotalSongs('2JN9ZjnLh182Dak5rIazyP'));

# decides whether the token is still valid or has to be renewed, returns the access token
function token() {
       
    if ((time() - 300) > $_SESSION['exp_date'])
        getToken($_SESSION['access_token'], $_SESSION['refresh_token'], $_SESSION['exp_date']);    
    return $_SESSION['access_token'];

}

# gets first token or renews it 
function getToken(&$acs_tok, &$ref_tok, &$date) {

    $url = 'https://accounts.spotify.com/api/token';
    $credentials = '';
    $spot_api_redirect = 'https://music.xn--langerlmmel-zhb.de/';
    $headers = array(
        'Authorization: Basic '.base64_encode($credentials)
    );

    if ($_SESSION['access_token'] == '')
        $data = 'grant_type=authorization_code&code='.$_GET['code'].'&redirect_uri='.urlencode($spot_api_redirect);
    else 
        $data = 'grant_type=refresh_token&refresh_token='.$ref_tok;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $data
    ]);

    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $acs_tok = $response['access_token'];
    $ref_tok = $response['refresh_token'];
    $date = time() + $response['expires_in'];

}

function getAlbums($play_id) {   

    $ids = new \Ds\Set();

    for($offset = 0; $offset <= getTotalSongs($play_id); $offset += 100) {
        
        $url = 'https://api.spotify.com/v1/playlists/'.$play_id.'/tracks?fields=items(track(album(id)))&offset='.$offset;
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer '.token()
          );

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_URL => $url
        ]);

        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        foreach($response['items'] as $id) 
            $ids -> add($id['track']['album']['id']);

    }

}

function getTotalSongs($play_id) {

    $url = 'https://api.spotify.com/v1/playlists/'.$play_id.'/tracks?fields=total';
    $headers = array(
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer '.token()
    );

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_URL => $url
    ]);

    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    return $response['total'];

}

?>
