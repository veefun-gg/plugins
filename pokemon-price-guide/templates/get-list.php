<?php
$root_path = ! empty($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') : __DIR__;
while (! file_exists($root_path . '/wp-load.php') && dirname($root_path) !== $root_path) {
    $root_path = dirname($root_path);
}

include_once($root_path . '/wp-load.php');
global $wpdb;

$perpage = $_POST['perpage'];
$gridlist = $_POST['gridlist'];
$type = $_POST['type'];
$order = $_POST['order'];
$_SESSION['perpage'] = $perpage;
$_SESSION['gridlist'] = $gridlist;
$_SESSION['type'] = $type;
$_SESSION['order'] = $order;

$oset = $_POST['offset'];
$offset = ($oset - 1) * $perpage;
$start = $offset + 1;
$end = ($oset * $perpage);

$types = explode(",",$type);
if(!empty($type)) {
    $type = '';
    $e = 0;
    foreach($types as $t) {
        if($e != 0) {
            $type .= ',';
        }
        $type .= '"'.$t.'"';
        $e++;
    }
}

$q = "SELECT * FROM ".$wpdb->prefix."ptp_cache_card WHERE card_set_name = '".urldecode($_POST['set'])."'";
$q2 = 'SELECT DISTINCT type FROM '.$wpdb->prefix.'ptp_cache_card_types WHERE card_id IN (SELECT api_id FROM '.$wpdb->prefix.'ptp_cache_card WHERE card_set_name = "'.urldecode($_POST['set']).'") ORDER BY type ASC';
if($type != '') {
    $q = 'SELECT * FROM '.$wpdb->prefix.'ptp_cache_card WHERE card_set_name = "'.urldecode($_POST['set']).'" AND api_id IN (SELECT card_id FROM '.$wpdb->prefix.'ptp_cache_card_types WHERE type IN('.urldecode($type).'))';
}
if(urldecode($_POST['name']) != '') {
    $q = "SELECT * FROM ".$wpdb->prefix."ptp_cache_card WHERE name LIKE '%".urldecode($_POST['name'])."%'";
}

if($order == 'number') {
    $q .= " ORDER BY card_number ASC";
} else if($order == 'alpha') {
    $q .= " ORDER BY name ASC, api_id ASC";
}

if($order == 'price') {
    $q = 'SELECT DISTINCT attachment_id, api_id, image_small, permalink, name, cached_meta FROM '.$wpdb->prefix.'ptp_cache_card INNER JOIN '.$wpdb->prefix.'ptp_pricing ON '.$wpdb->prefix.'ptp_cache_card.api_id = '.$wpdb->prefix.'ptp_pricing.card_id WHERE '.$wpdb->prefix.'ptp_cache_card.card_set_name = "'.urldecode($_POST['set']).'" ORDER BY '.$wpdb->prefix.'ptp_pricing.price_avgmarket DESC';
    
    if($type != '') {
        $q = 'SELECT DISTINCT attachment_id, api_id, image_small, permalink, name, cached_meta FROM '.$wpdb->prefix.'ptp_cache_card INNER JOIN '.$wpdb->prefix.'ptp_pricing ON '.$wpdb->prefix.'ptp_cache_card.api_id = '.$wpdb->prefix.'ptp_pricing.card_id WHERE '.$wpdb->prefix.'ptp_cache_card.card_set_name = "'.urldecode($_POST['set']).'" AND api_id IN (SELECT card_id FROM '.$wpdb->prefix.'ptp_cache_card_types WHERE type IN('.urldecode($type).')) ORDER BY '.$wpdb->prefix.'ptp_pricing.price_avgmarket DESC';
    }
    if(urldecode($_POST['name']) != '') {
        $q = 'SELECT DISTINCT attachment_id, api_id, image_small, permalink, name, cached_meta FROM '.$wpdb->prefix.'ptp_cache_card INNER JOIN '.$wpdb->prefix.'ptp_pricing ON '.$wpdb->prefix.'ptp_cache_card.api_id = '.$wpdb->prefix.'ptp_pricing.card_id WHERE '.$wpdb->prefix.'ptp_cache_card.name LIKE "%'.urldecode($_POST['name']).'%" ORDER BY '.$wpdb->prefix.'ptp_pricing.price_avgmarket DESC';
    }
    
}

$count = $wpdb->get_results($q);
$count = $wpdb->num_rows;

$q .= ' LIMIT '.$perpage.' OFFSET '.$offset;
$cards = $wpdb->get_results($q);
?>
        
<div class="types">

    <?php
    $sets = $wpdb->get_results($q2);
    $c = 0;
    foreach($sets as $s) {
    ?>
    <label id="type_<?php echo $s->type; ?>"><input name="type" type="checkbox" value="<?php echo $s->type; ?>"<?php if(in_array(trim($s->type),$types)) {  ?> checked="checked"<?php } ?> /> <?php echo $s->type; ?></label>
    <?php
    $c++;
    }
    ?>

</div>

<div class="clear"></div>

<?php
$k = 1;
$z = 1;

if($count < $perpage) {
    $perpage = $count;
}

if($end > $count) {
    $end = $count;
}

if($end > $count) {
    $count = $end;
}

if($count == 0) {
    $start = 0;
}

echo '<div class="full results">Viewing '.$start.' to '.$end.' of '.$count.' cards</div>';
foreach($cards as $card) {
    
    $data = unserialize($card->cached_meta);

    if($gridlist == 'grid') {
        $image = $card->image_small;
?>

    <div class="fifth card" data-id="<?php echo $card->api_id; ?>">
        <?php echo '<a href="'.get_bloginfo('wpurl').'/price-guide/'.$card->permalink.'/'.$card->api_id.'/">'; ?>
            <img src="<?php echo $image; ?>" />
            <h3><?php echo $card->name; ?></h3>
            <div class="full meta">
                <?php 
                $price = '';
                $q = "SELECT * FROM ".$wpdb->prefix."ptp_pricing WHERE price_market = 'tcgplayer' AND price_type = 'normal' AND card_id = '".$card->api_id."' ORDER BY logged_date DESC limit 1";
                $pricing = $wpdb->get_row($q);
                if($pricing) {
                    $price = $pricing->price_avgmarket;
                }
                if($price == '') {
                    $q = "SELECT * FROM ".$wpdb->prefix."ptp_pricing WHERE price_market = 'tcgplayer' AND price_type = 'holofoil' AND card_id = '".$card->api_id."' ORDER BY logged_date DESC limit 1";
                    $pricing = $wpdb->get_row($q);
                    if($pricing) {
                        $price = $pricing->price_avgmarket;
                    }
                }
                if($price == '') {
                    $q = "SELECT * FROM ".$wpdb->prefix."ptp_pricing WHERE price_market = 'tcgplayer' AND price_type = 'reverseHolofoil' AND card_id = '".$card->api_id."' ORDER BY logged_date DESC limit 1";
                    $pricing = $wpdb->get_row($q);
                    if($pricing) {
                        $price = $pricing->price_avgmarket;
                    }
                }
                if($price == '') {
                    $price = '--';
                }
                ?>
                <div class="full meta">
                    Rarity: <?php echo $data['rarity']; ?>
                </div>
                <div class="full meta">
                    Number: <?php echo $data['number']; ?>/<?php echo $data['set']['printedTotal']; ?>
                </div>
                <div class="full meta">
                    Avg. Price: <span id="result_<?php echo $card->api_id; ?>">$<?php echo $price; ?></span>
                </div>
            </div>
        </a>
    </div>

    <?php if($z == 5) { echo '<div class="clear"></div>'; $z = 0; } ?>
  
<?php    
    } else {
?>

    <div class="full card<?php if($k == 2) { echo ' odd'; $k = 0; } ?>" data-id="<?php echo $card->api_id; ?>">
        <?php echo '<a href="'.get_bloginfo('wpurl').'/price-guide/'.$card->permalink.'/'.$card->api_id.'/">'; ?>
            <div class="quarter quarters">
                <img src="<?php echo $card->image_small; ?>" />
            </div>
            <div class="threequarters desc">
                <h3>
                    <?php echo $card->name; ?>
                </h3>
                <div class="quarter meta">
                    Rarity: <?php echo $data['rarity']; ?>
                </div>
                <div class="quarter meta">
                    Number: <?php echo $data['number']; ?>/<?php echo $data['set']['printedTotal']; ?>
                </div>
                <div class="quarter meta">
                    <?php 
                $price = '';
                $q = "SELECT * FROM ".$wpdb->prefix."ptp_pricing WHERE price_market = 'tcgplayer' AND price_type = 'normal' AND card_id = '".$card->api_id."' ORDER BY logged_date DESC limit 1";
                $pricing = $wpdb->get_row($q);
                if($pricing) {
                    $price = $pricing->price_avgmarket;
                }
                if($price == '') {
                    $q = "SELECT * FROM ".$wpdb->prefix."ptp_pricing WHERE price_market = 'tcgplayer' AND price_type = 'holofoil' AND card_id = '".$card->api_id."' ORDER BY logged_date DESC limit 1";
                    $pricing = $wpdb->get_row($q);
                    if($pricing) {
                        $price = $pricing->price_avgmarket;
                    }
                }
                if($price == '') {
                    $q = "SELECT * FROM ".$wpdb->prefix."ptp_pricing WHERE price_market = 'tcgplayer' AND price_type = 'reverseHolofoil' AND card_id = '".$card->api_id."' ORDER BY logged_date DESC limit 1";
                    $pricing = $wpdb->get_row($q);
                    if($pricing) {
                        $price = $pricing->price_avgmarket;
                    }
                }
                if($price == '') {
                    $price = '--';
                }
                ?>
                    Avg. Price: <span id="result_<?php echo $card->api_id; ?>">$<?php echo $price; ?></span>
                </div>
                <div class="quarter meta last">
                    <?php echo '<a class="btn" href="'.get_bloginfo('wpurl').'/price-guide/'.$card->permalink.'/'.$card->api_id.'/">'; ?>
                        View
                    </a>
                </div>
                <div class="clear"></div>
            </div>
        </a>
    </div>

    <div class="clear"></div>
  
<?php
    }

$k++;
$z++;
}
?>

<?php if($count != 0) { ?>

<div class="pagination" data-type="" data-scroll="ptable" aria-label="Price guide pages">

    <a class="item prev<?php if($oset == 1) { ?> disabled<?php } ?>" role="button" tabindex="0" data-page="<?php if($oset > 1) { ?><?php echo ($oset - 1); ?><?php } ?>"<?php if($oset == 1) { ?> aria-disabled="true"<?php } ?>>Prev</a>
    <?php
    $pages = ($count / $perpage);
    $pages = ceil($pages);
    $i = 1;
    while($i <= $pages) {
    ?>

        <a class="item<?php if($i == $oset) { ?> current<?php } ?>" role="button" tabindex="0" data-page="<?php echo $i; ?>"<?php if($i == $oset) { ?> aria-current="page"<?php } ?>><?php echo $i; ?></a>

    <?php 
    $i++;
    } 
    ?>
    <a class="item next<?php if($oset == $pages) { ?> disabled<?php } ?>" role="button" tabindex="0" data-page="<?php if($oset < $pages) { ?><?php echo ($oset + 1); ?><?php } ?>"<?php if($oset == $pages) { ?> aria-disabled="true"<?php } ?>>Next</a>

</div>

<?php } ?>
