<?php
$access_token = 'LINE developersで取得できるチャンネルアクセストークン';
$url = 'https://api.line.me/v2/bot/message/reply';

// 自分が打った言葉
$raw = file_get_contents('php://input');
$receive = json_decode($raw, true);

// リプライトークンなるものが取得できる（有効期限が短いので注意）
$event = $receive['events'][0];
$reply_token  = $event['replyToken'];
$message_text = $event['message']['text'];

$headers = array('Content-Type: application/json',
                 'Authorization: Bearer ' . $access_token);

// simple_html_domというパーサーを使ってYahoo!知恵袋の恋愛カテゴリのランキングからURLと見出しを抜き出す
require_once 'simple_html_dom.php';
$html = file_get_html('https://chiebukuro.yahoo.co.jp/ranking/category_ranking.php?cate_id=2078675272');

foreach ($html->find('td.cell3 a') as $key => $value) {
    $chiebukuros_url[] = $value->href;
    // なぜか1位〜20位の見出しが全部繋がって取れてしまうので...で区切ってあとでもう一回付ける
    $chiebukuros_datas = $value->innertext;
    $chiebukuros_data = explode(".", $chiebukuros_datas);
    $chiebukuros_text[] = str_replace('<wbr>', '', $chiebukuros_data[0]) . '...';
}

// 1位〜20位のうち、ランダムに知恵袋を選んで返すメッセージを形成
$chiebukuro = array_rand($chiebukuros_url);
$chiebukuro_message = $chiebukuros_text[$chiebukuro] . '　' . $chiebukuros_url[$chiebukuro];
// パーサーでメモリが爆発するらしいから解放
$html->clear();

// build request body
$message = array('type' => 'text',
                 'text' => $chiebukuro_message);
$body = json_encode(array('replyToken' => $reply_token,
                          'messages'   => array($message)));

// post json with curl
$options = array(CURLOPT_URL            => $url,
                 CURLOPT_CUSTOMREQUEST  => 'POST',
                 CURLOPT_RETURNTRANSFER => true,
                 CURLOPT_HTTPHEADER     => $headers,
                 CURLOPT_POSTFIELDS     => $body);
$curl = curl_init();
curl_setopt_array($curl, $options);
curl_exec($curl);
curl_close($curl);
