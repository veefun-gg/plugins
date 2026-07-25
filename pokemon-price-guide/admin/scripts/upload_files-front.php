<?php
require_once '_guard.php';

define('ABSPATH2', dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/uploads/');
global $current_user;
get_currentuserinfo();

error_reporting(E_ALL);

	$upload_dir = wp_upload_dir();
	
	$key = 0;
	foreach ($_FILES["file"]['name'] as $filename) {
		
		//$folder_dir = "/videos/";
		
		if(!file_exists($upload_dir['basedir'].'/gallery/')){		
		   mkdir(ABSPATH2.'/gallery/', 0755); //make folder and directory
		}
		
		if(!file_exists($upload_dir['basedir'].'/gallery/images/')){		
		   mkdir(ABSPATH2.'/gallery/images/', 0755); //make folder and directory
		}
		
		if(!file_exists($upload_dir['basedir'].'/gallery/images/'.$_GET['gallery'].'/')){		
		   mkdir(ABSPATH2.'/gallery/images/'.$_GET['gallery'].'/', 0755); //make folder and directory
		}
		
		if(!file_exists($upload_dir['basedir'].'/gallery/images/'.$_GET['gallery'].'/thumbnails/')){		
		   mkdir(ABSPATH2.'/gallery/images/'.$_GET['gallery'].'/thumbnails/', 0755); //make folder and directory
		}
		
		$tempFile = $_FILES['file']['tmp_name'][$key];
		$targetPath = ABSPATH2.'/gallery/images/'.$_GET['gallery'].'/';
        $targetPathThumb = ABSPATH2 . '/gallery/images/'.$_GET['gallery'].'/thumbnails/';
		$filename = preg_replace('/[^a-z0-9\.]+/', '_', strtolower($_FILES['file']['name'][$key]));
		$targetFile =  str_replace('//','/',$targetPath) . $filename;
	   
		//Avoid files Overwrite
		while(file_exists($targetFile)) {
			$user = date("YmdHis");
			$filename = $user."-". preg_replace('/[^a-z0-9\.]+/', '_', strtolower($_FILES['file']['name'][$key]));
			$targetFile =  str_replace('//','/',$targetPath) . $filename;
		} 
				
		move_uploaded_file($tempFile,$targetFile);
		//echo $_FILES['file']['name'];
		echo $filename;
		
		$key++;
        
        if(function_exists('gd_info')) {

        // Create Cropped Image Code

            list($width, $height) = getimagesize($targetFile);
            switch(strtolower(substr($targetFile, -3)))
            {
            case "jpg":
            $imageNew = imagecreatefromjpeg($targetFile);
            break;
            case "png":
            $imageNew = imagecreatefrompng($targetFile);
            break;
            case "gif":
            $imageNew = imagecreatefromgif($targetFile);
            break;
            default:
            exit;
            break;
            }

            $src_w = $width;
            $src_h = $height;	

            $widthNew = 800; //New width of image
            $heightNew = $height/$width*$widthNew; //This maintains proportions	

            $pictureNew = imagecreatetruecolor($widthNew, $heightNew);
            imagealphablending($pictureNew, false);
            imagesavealpha($pictureNew, true);

            $new = imagecopyresampled($pictureNew, $imageNew, 0, 0, 0, 0, $widthNew, $heightNew, $src_w, $src_h);

            if($new)
            {
            switch(strtolower(substr($targetFile, -3)))
            {
            case "jpg":
            //header("Content-Type: image/jpeg");
            $bool2 = imagejpeg($pictureNew,$targetPath.$filename,80);
            break;
            case "png":
            //header("Content-Type: image/png");
            imagepng($pictureNew,$targetPath.$filename);
            break;
            case "gif":
            //header("Content-Type: image/gif");
            imagegif($pictureNew,$targetPath.$filename);
            break;
            }
            }

            imagedestroy($pictureNew);
            imagedestroy($imageNew);


            // Create Cropped Image
            $imgsize = getimagesize($targetFile);
            //list($width, $height) = getimagesize($targetFile);
            switch(strtolower(substr($targetFile, -3)))
            {
            case "jpg":
            $image = imagecreatefromjpeg($targetFile);
            break;
            case "png":
            $image = imagecreatefrompng($targetFile);
            break;
            case "gif":
            $image = imagecreatefromgif($targetFile);
            break;
            default:
            exit;
            break;
            }

            /*
            $height = 325; //New width of image
            $width  = $imgsize[0]/$imgsize[1]*$height; //This maintains proportions

            $src_w = $imgsize[0];
            $src_h = $imgsize[1];

            $picture = imagecreatetruecolor($width, $height);
            imagealphablending($picture, false);
            imagesavealpha($picture, true);
            $bool = imagecopyresampled($picture, $image, 0, 0, 0, 0, $width, $height, $src_w, $src_h);
            */

            /*$picture = imagecreatetruecolor(800, 600);
            imagealphablending($picture, false);
            imagesavealpha($picture, true);

            $cropWidth   = 800;
            $cropHeight  = 600;

            //getting the top left coordinate
            $c1 = array("x"=>($widthNew-$cropWidth)/2, "y"=>($heightNew-$cropHeight)/2);

            $bool = imagecopyresampled($picture, $image, 0, 0, $c1['x'], $c1['y'], $cropWidth, $cropHeight, $cropWidth, $cropHeight );	

            if($bool)
            {
            switch(strtolower(substr($targetFile, -3)))
            {
            case "jpg":
            //header("Content-Type: image/jpeg");
            $bool2 = imagejpeg($picture,$targetPath.$filename,80);
            break;
            case "png":
            //header("Content-Type: image/png");
            imagepng($picture,$targetPath.$filename);
            break;
            case "gif":
            //header("Content-Type: image/gif");
            imagegif($picture,$targetPath.$filename);
            break;
            }
            }

            imagedestroy($picture);
            imagedestroy($image);*/

        // End Cropped Image Code

        // Create Cropped Image Code

            list($width, $height) = getimagesize($targetFile);
            switch(strtolower(substr($targetFile, -3)))
            {
            case "jpg":
            $imageNew = imagecreatefromjpeg($targetFile);
            break;
            case "png":
            $imageNew = imagecreatefrompng($targetFile);
            break;
            case "gif":
            $imageNew = imagecreatefromgif($targetFile);
            break;
            default:
            exit;
            break;
            }

            $src_w = $width;
            $src_h = $height;	

            $widthNew = 500;
            $heightNew = $height/$width*$widthNew;

            if($heightNew < 300) {
                $heightNew = 300;
                $widthNew = $width/$height*$heightNew;
            }

            $pictureNew = imagecreatetruecolor($widthNew, $heightNew);
            imagealphablending($pictureNew, false);
            imagesavealpha($pictureNew, true);

            $new = imagecopyresampled($pictureNew, $imageNew, 0, 0, 0, 0, $widthNew, $heightNew, $src_w, $src_h);

            if($new)
            {
            switch(strtolower(substr($targetFile, -3)))
            {
            case "jpg":
            //header("Content-Type: image/jpeg");
            $bool2 = imagejpeg($pictureNew,$targetPathThumb.$filename,80);
            break;
            case "png":
            //header("Content-Type: image/png");
            imagepng($pictureNew,$targetPathThumb.$filename);
            break;
            case "gif":
            //header("Content-Type: image/gif");
            imagegif($pictureNew,$targetPathThumb.$filename);
            break;
            }
            }

            imagedestroy($pictureNew);
            imagedestroy($imageNew);


            // Create Cropped Image
            list($width, $height) = getimagesize($targetPathThumb.$filename);
            switch(strtolower(substr($targetPathThumb.$filename, -3)))
            {
            case "jpg":
            $image = imagecreatefromjpeg($targetPathThumb.$filename);
            break;
            case "png":
            $image = imagecreatefrompng($targetPathThumb.$filename);
            break;
            case "gif":
            $image = imagecreatefromgif($targetPathThumb.$filename);
            break;
            default:
            exit;
            break;
            }	

            $picture = imagecreatetruecolor(300, 300);
            imagealphablending($picture, false);
            imagesavealpha($picture, true);

            $cropWidth   = 300;
            $cropHeight  = 300;

            //getting the top left coordinate
            $c1 = array("x"=>($width-$cropWidth)/2, "y"=>($height-$cropHeight)/2);

            $bool = imagecopyresampled($picture, $image, 0, 0, $c1['x'], $c1['y'], $cropWidth, $cropHeight, $cropWidth, $cropHeight );	

            if($bool)
            {
            switch(strtolower(substr($targetPathThumb.$filename, -3)))
            {
            case "jpg":
            //header("Content-Type: image/jpeg");
            $bool2 = imagejpeg($picture,$targetPathThumb.$filename,80);
            break;
            case "png":
            //header("Content-Type: image/png");
            imagepng($picture,$targetPathThumb.$filename);
            break;
            case "gif":
            //header("Content-Type: image/gif");
            imagegif($picture,$targetPathThumb.$filename);
            break;
            }
            }

            imagedestroy($picture);
            imagedestroy($image);

        // End Cropped Image Code

    }
		
	}

}

?>