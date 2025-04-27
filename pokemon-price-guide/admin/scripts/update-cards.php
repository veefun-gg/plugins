<?php
define('ABSPATH', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/');
include_once(ABSPATH.'wp-load.php');
	
    $api = getPokeCardsAll($_POST['page'],$_POST['per']);

    //$api = $api->toArray();
    //echo '<pre>'.print_r($api, true).'</pre>';

    if(!empty($api)) {
            
        $text = '<p style="font-weight:bold;font-size:18px;text-align:center;">Collected '.($_POST['page'] * $_POST['per']).' Cards</p>|';

        foreach ($api as $card) {

            $crd = $card->toArray();

            //echo '<p><strong>'.$crd['name'].'</strong></p>';

            $text .= '<div style="width:70px;display:inline;padding:2px;"><img style="width:70px;height:auto;" src="'.$crd['images']['small'].'" /></div>';

            populateCacheCard($crd);

            //echo '<pre>'.print_r($card->toArray(), true).'</pre>';

            //usleep(15);

        //    print_r($model->toJson());
        }
        
        echo $text;
        
    } else {
        
        echo 'EOL';
        
    }
?>