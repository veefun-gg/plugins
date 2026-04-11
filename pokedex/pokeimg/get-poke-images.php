<?php
define('ABSPATH', dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/');
include_once(ABSPATH.'wp-load.php');
global $wpdb,$current_user;

//if(is_user_logged_in()) {   

   echo 'test';
    
   $args = array(
      'post_type' => 'pokedex',
      'posts_per_page' => 999999,
      'meta_query' => array(
         array(
            'key' => 'pokemon_image',
            'value' => '',
            'compare' => '!='
         )
      )
   );
   $poke = new WP_Query($args);
   if($poke->have_posts()) {

       while($poke->have_posts()) { 
           $poke->the_post();
            
           echo '<p>'.get_post_meta( $poke->post->ID, 'pokemon_image', true ).'</p>';
           
           $url = get_post_meta( $poke->post->ID, 'pokemon_image', true );

            // Use basename() function to return the base name of file
            $file_name = basename($url);

            // Use file_get_contents() function to get the file
            // from url and use file_put_contents() function to
            // save the file by using base name
            if (file_put_contents($file_name, file_get_contents($url)))
            {
                echo "File downloaded successfully";
            }
            else
            {
                echo "File downloading failed.";
            }

       } wp_reset_postdata();

   }
    
//}
?>