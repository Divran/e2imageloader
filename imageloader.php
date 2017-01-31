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

		if (isset($_GET["maxres"])) {
			$res_str = $_GET["maxres"];
			$res_expl = split("x",$res_str);
			$max_width = is_numeric($res_expl[0]) ? min((int)$res_expl[0],512) : 512;
			$max_height = is_numeric($res_expl[1]) ? min((int)$res_expl[1],512) : 512;

			if ($max_width < 16) {$max_width = 16;}
			if ($max_height < 16) {$max_height = 16;}
		}

		$keep_aspect = false;
		if (isset($_GET["keepaspect"])) {
			$keep_aspect = ($_GET["keepaspect"] == "1");
		}

		if ($image !== false) {
			$rescaled = false;

			$original_width = $width;
			$original_height = $height;
			$scaled = false;

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
				
				$squaresize = max($max_width,$max_height);
				if ($keep_aspect && ($width != $squaresize || $height != $squaresize)) {
					// Create new (square) image
					$temp = imagecreatetruecolor($squaresize,$squaresize);

					// Fill background
					$black = imagecolorallocate( $temp, 0,0,0 );
					imagefill($temp,0,0,$black);

					// Center image
					$x_offset = $max_width/2-$width/2;
					$y_offset = $max_height/2-$height/2;
				} else {
					// Create new image
					$temp = imagecreatetruecolor($width,$height);
				}

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
				if ($keep_aspect && ($width != $squaresize || $height != $squaresize)) {
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