<?php

require 'vendor/autoload.php';

$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->load(__DIR__ . '/.env');

$accessToken = getAccessToken(getenv('API_KEY'), getenv('API_SECRET'));

$db = getDb();

$curl = new \Curl\Curl();
$curl->setHeader('Authorization', 'Bearer ' . $accessToken);
$curl->setDefaultJsonDecoder(true);
$curl->get('https://api.twitter.com/1.1/statuses/user_timeline.json', [
    'screen_name' => 'lapitv',
    'since_id' => $db['maxId'] ?? 0,
    'count' => 20,
]);

$maxId = 0;

foreach ($curl->response as $tweet) {
    $id = $tweet['id'];
    $text = $tweet['text'];
    $usernameDisplay = $tweet['user']['name'];
    $screenName = $tweet['user']['screen_name'];
    $avatar = $tweet['user']['profile_image_url_https'];
    $url = 'https://twitter.com/' . $screenName . '/status/' . $id;

    $media = '';
    if(!empty($tweet['entities']['media'][0]['media_url_https'])) {
        $media = $tweet['entities']['media'][0]['media_url_https'];
    }

    $time = new \DateTime($tweet['created_at']);

    sendWebhook([
        'username' => $usernameDisplay,
        'avatar_url' => $avatar,
        'embeds' => [
            [
                'title' => 'Nouveau tweet !',
                'url' => $url,
                'description' => $text,
                'color' => 16743434,
                'image' => ['url' => $media],
                'footer' => ['text' => 'Envoyé le ' . $time->format('d-m-Y à H:i:s')],
            ],
        ],
    ]);

    if($id > $maxId) {
        $maxId = $id;
    }
}

if(!empty($maxId)) {
    setDb('maxId', $maxId);
}

function sendWebhook($data)
{
    $curl = new \Curl\Curl();
    $curl->setHeader('Content-Type', 'application/json');
    $curl->post(getenv('WEBHOOK'), $data);

    var_dump($curl->response);
}


function getBearerTokenBase64($consumerKey, $consumerSecret): string
{
    $encodedKey = rawurlencode($consumerKey);
    $encodedSecret = rawurlencode($consumerSecret);

    return base64_encode($encodedKey . ':' . $encodedSecret);
}

function getAccessToken($consumerKey, $consumerSecret)
{
    $bearToken = getBearerTokenBase64($consumerKey, $consumerSecret);

    $curl = new \Curl\Curl();
    $curl->setHeaders([
        'Authorization' => 'Basic ' . $bearToken,
        'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
    ]);
    $curl->post('https://api.twitter.com/oauth2/token', ['grant_type' => 'client_credentials']);

    if (!empty($curl->error)) {
        throw new \Exception($curl->error);
    }

    return $curl->response->access_token;
}

function getDb()
{
    $file = file_get_contents(__DIR__ . '/db.json');

    if (empty($file)) {
        return [];
    }

    $db = json_decode($file, true);
    if (empty($file)) {
        return [];
    }

    return $db;
}

function setDb($key, $value)
{
    $db = getDb();
    $db[$key] = $value;

    file_put_contents(__DIR__ . '/db.json', json_encode($db));
}