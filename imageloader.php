<?php
	function createImage($image,$type) {
		if ($type == 1) {return imagecreatefromgif($image);}
		if ($type == 2) {return imagecreatefromjpeg($image);}
		if ($type == 3) {return imagecreatefrompng($image);}
		return false;
	}

	if (isset($_GET["image"])) {
		$imagepath = urldecode($_GET["image"]);
		$info = getimagesize($imagepath);

		$width = $info[0];
		$height = $info[1];
		$mimetype = $info[2];

		$image = createImage($imagepath,$mimetype);

		$max_width = 512;
		$max_height = 512;

		$screen_ratio = 1;

		function clamp($current, $min, $max) {
			return max($min, min($max, $current));
		}

		$bg_r = 0; $bg_g = 0; $bg_b = 0;
		if (isset($_GET["bgcolor"])) {
			$color_str = $_GET["bgcolor"];
			$color_expl = explode(",",$color_str);

			$bg_r = is_numeric($color_expl[0]) ? clamp((int)$color_expl[0],0,255) : 0;
			$bg_g = is_numeric($color_expl[1]) ? clamp((int)$color_expl[1],0,255) : 0;
			$bg_b = is_numeric($color_expl[2]) ? clamp((int)$color_expl[2],0,255) : 0;
		}

		if (isset($_GET["maxres"])) {
			$res_str = $_GET["maxres"];
			$res_expl = explode("x",$res_str);
			$max_width = is_numeric($res_expl[0]) ? min((int)$res_expl[0],512) : 512;
			$max_height = is_numeric($res_expl[1]) ? min((int)$res_expl[1],512) : 512;

			if ($max_width < 16) {$max_width = 16;}
			if ($max_height < 16) {$max_height = 16;}
		}
		

		if (isset($_GET["screenratio"])) {
			$screen_ratio = is_numeric($_GET["screenratio"]) ? (float)$_GET["screenratio"] : 1;
			$screen_ratio = round(clamp($screen_ratio,0.001,1.999),3);
		}

		$squaresize = max($max_width,$max_height);
		$squaresize = min($squaresize,max($width,$height));
		
		$keep_aspect = false;
		if (isset($_GET["keepaspect"])) {
			$keep_aspect = ($_GET["keepaspect"] == "1");

			$keep_aspect = $keep_aspect && ($width != $squaresize || $height != $squaresize);
		}

		if ($image !== false) {
			$rescaled = false;

			$original_width = $width;
			$original_height = $height;
			$scaled = false;

			if ($keep_aspect) {
				//$image_ratio = $width/$height;
				if ($screen_ratio > 1) {
					// This means it's wider than it is large
					$height *= $screen_ratio;
				} elseif ($screen_ratio < 1) {
					// This means it's taller than it is wide
					$width *= $screen_ratio;
				}
			}

			// Downscale image if necessary
			if ($width > $max_width) {
				$ratio = $width / $max_width;

				$width = floor($width / $ratio);
				$height = floor($height / $ratio);

				$scaled = true;
			}

			if ($height > $max_height) {
				$ratio = $height / $max_height;

				$width = floor($width / $ratio);
				$height = floor($height / $ratio);

				$scaled = true;
			}

			if ($scaled || $keep_aspect) {
				$x_offset = 0;
				$y_offset = 0;
				
				if ($keep_aspect) {
					// Create new (square) image
					$temp = imagecreatetruecolor($squaresize,$squaresize);

					// Center image
					$x_offset = $max_width/2-$width/2;
					$y_offset = $max_height/2-$height/2;
				} else {
					// Create new image
					$temp = imagecreatetruecolor($width,$height);
				}

				// Fill background
				$bg = imagecolorallocate( $temp, $bg_r,$bg_g,$bg_b );
				imagefill($temp,0,0,$bg);

				// Copy old image onto new image
				imagecopyresampled(
					$temp,
					$image,
					$x_offset,$y_offset,0,0,
					$width,$height,
					$original_width,
					$original_height
				);

				// If aspect ratio needs to be kept, set size variables now
				if ($keep_aspect) {
					$width = $squaresize;
					$height = $squaresize;
				}

				$image = $temp; // Overwrite original image
			}

			// Step through image
			$str = $width . "x" . $height . ";";
			for($y=0;$y<$height;$y++) {
				for($x=0;$x<$width;$x++) {
					$rgb = imagecolorat($image,$x,$y);
					$r = ($rgb >> 16) & 0xFF;
					$g = ($rgb >> 8) & 0xFF;
					$b = $rgb & 0xFF;

					// Write colors in digital screen's format of "RRRGGGBBB"
					$str .= sprintf("%03d%03d%03d",$r,$g,$b);
				}
			}
			echo $str;
		}
	}
?>
