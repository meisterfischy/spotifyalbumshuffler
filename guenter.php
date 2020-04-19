<?php

session_start();

if (!isset($_SESSION['access_token']) && !isset($_SESSION['refresh_token']) && !isset($_SESSION['exp_date'])) {
    $_SESSION['access_token'] = '';
    $_SESSION['refresh_token'] = '';
    $_SESSION['exp_date'] = 0;
}

print_r(getAlbums('2JN9ZjnLh182Dak5rIazyP'));

function token() {

    if((time() - 300) > $_SESSION['exp_date']) { 
    
        $url = 'https://accounts.spotify.com/api/token';
        $credentials = '';
        $spot_api_redirect = 'https://music.xn--langerlmmel-zhb.de/guenter.php/';
        $headers = array(
            'Authorization: Basic '.base64_encode($credentials)
         );
    
        if ($_SESSION['access_token'] == '')
            $data = 'grant_type=authorization_code&code='.$_GET['code'].'&redirect_uri='.urlencode($spot_api_redirect);
        else 
            $data = 'grant_type=refresh_token&refresh_token='.$_SESSION['refresh_token'];

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

        $_SESSION['access_token'] = $response['access_token'];
        $_SESSION['refresh_token'] = $response['refresh_token'];
        $_SESSION['exp_date'] = time() + $response['expires_in'];
    
    } return $_SESSION['access_token'];

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

    return $ids;

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
