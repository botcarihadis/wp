<?php
require __DIR__ . '/token.php';
require __DIR__ . '/simple_html_dom.php';
require __DIR__ . '/Bot.php';

$bot = new Bot($token, $username);

$bot->start("Tulis kalimat yang mau Anda cari");

$bot->text(function ($katakunci) {

    $f = time().Bot::$from_id;
    $url = 'https://kitabhadis.wordpress.com/?s='.urlencode($katakunci);
    
    $no = 0;
    proses($url, $f, $no);

});

function proses($url, $f, $no){
    system("curl https://www.w3.org/services/html2txt?url=$url > $f");

    $a = file_get_contents($f);
    
    preg_match_all('/\[\d+\]\w+([\s\w]+)\d+|\[\d+\] https\:\/\/kitabhadis\.wordpress\.com\/\d+\/\d+\/\d+\/\d+([^\n]+)/', $a, $b);
    
    $b0 = $b[0];
    
    $daftar_lanjutkan_membaca = preg_grep('/Lanjutkan membaca/', $b0);
    
    foreach ($daftar_lanjutkan_membaca as $key => $value) {
        unset($b0[$key]);
        unset($b0[$key + 1]);
    }
    
    $new_array = array_values($b0);
    
    foreach ($new_array as $key => $value) {
        $new_array[$key] = preg_replace('/\[\d+\](\s+)?/', '', $value);
    }
    
    $hasil = '';
    foreach ($new_array as $key => $value) {
        if((count($new_array) - 1) == $key) continue;
        $href = $new_array[$key + 1];
        if(strpos($href,'http') === false) continue;
        $no++;
        $hasil .= "$no. <a href='$href'>$value</a>\n";
    }
    
    // kirim hasil
    Bot::sendMessage($hasil, ['parse_mode'=>'html', 'disable_web_page_preview'=>true]);
    
    // proses next page
    if(strpos($a,'Pos-pos Lebih Lama') !== false){
        preg_match_all('/https\:\/\/kitabhadis\.wordpress\.com\/page\/(\d+)\/\?s\=([^\n]+)/', $a, $array);
        if(!empty($array[0][0])){
            if(count($array[0])>1){
                $url_next_page = $array[0][1];
            }else{
                if($array[1][0])
                $url_next_page = $array[0][0];
            }
            proses($url_next_page, $f, $no);
        }
    }
    unlink($f);
}

$bot->run();