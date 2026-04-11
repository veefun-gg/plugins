<?php
require_once '_guard.php';
	
    //$api = getPokeCardsAll($_POST['page'],$_POST['per']);

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL,"https://api.pokemontcg.io/v2/cards/?page=".$_POST['page']."&pageSize=".$_POST['per']);
    //curl_setopt($ch, CURLOPT_GET, 1);
    //curl_setopt($ch, CURLOPT_POSTFIELDS,"email=".$email."");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Api-Key: b044a33f-df37-46b7-952a-5af4888d89c4'));

    // Receive server response ...
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $server_output = curl_exec($ch);
    $server_output = json_decode($server_output, ARRAY_A);

    curl_close ($ch);

    //$api = $api->toArray();
    //echo '<pre>'.print_r($server_output, true).'</pre>';

    if(!empty($server_output)) {
            
        $text = '<p style="font-weight:bold;font-size:18px;text-align:center;">Collected '.($_POST['page'] * $_POST['per']).' Cards</p>|';

        foreach ($server_output['data'] as $card) {

            //$crd = $card->toArray();

            //echo '<p><strong>'.$crd['name'].'</strong></p>';

            $text .= '<div style="width:70px;display:inline;padding:2px;"><img style="width:70px;height:auto;" src="'.$card['images']['small'].'" /></div>';

            populateCacheCard($card);

            //echo '<pre>'.print_r($card->toArray(), true).'</pre>';

            //usleep(15);

        //    print_r($model->toJson());
        }
        
        echo $text;
        
    } else {
        
        echo 'EOL';
        
    }
?>