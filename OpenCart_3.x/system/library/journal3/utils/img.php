<?php

namespace Journal3\Utils;

use WebPConvert\WebPConvert;

class Img {

	private static $status = null;

	/** @var \ModelJournal3Image */
	private static $image;

	public static function canOptimise() {
		if (static::$status === null) {
			$cwebp = static::exec("cwebp -version 2>&1");

			static::$status = array(
				'optipng'   => static::exec("optipng --version 2>&1"),
				'jpegoptim' => static::exec("jpegoptim --version 2>&1"),
				'cwebp'     => $cwebp ? $cwebp : (function_exists('imagewebp') ? ['GD'] : false),
			);
		}

		return static::$status;
	}

	public static function optimise($image) {
		$type = strtolower(pathinfo($image, PATHINFO_EXTENSION));

		switch ($type) {
			case 'png':
				static::optipng($image);
				break;

			case 'jpg':
			case 'jpeg':
				static::jpegoptim($image);
				break;
		}
	}

	public static function optipng($file) {
		if (static::canOptimise()['optipng']) {
			static::exec("optipng -preserve -strip all -quiet " . $file);
		}
	}

	public static function jpegoptim($file) {
		if (static::canOptimise()['jpegoptim']) {
			static::exec("jpegoptim -p --strip-all --max=85 " . $file);
		}
	}

	public static function cwebp($file) {
		if (!Request::hasWebpSupport() || !static::canOptimise()['cwebp'] || !class_exists('\WebPConvert\WebPConvert')) {
			return $file;
		}

		$output = $file . '.webp';

		if (!is_file(DIR_IMAGE . $output)) {
			try {
				WebPConvert::convert('image/' . $file, 'image/' . $output, array(
					'encoding'      => 'lossy',
					'near-lossless' => 100,
					'quality'       => 85,
					'method'        => 4,
				));
			} catch (\Exception $e) {
				// trigger_error('WebP error: ' . $e->getMessage());
			}
		}

		return $output;
	}

	private static function exec($cmd) {
		if (!function_exists('exec')) {
			return false;
		}

		if (in_array(strtolower(ini_get('safe_mode')), array('on', '1'), true)) {
			return false;
		}

		$disabled_functions = explode(',', ini_get('disable_functions'));

		if (in_array('exec', $disabled_functions)) {
			return false;
		}

		@exec($cmd, $output, $code);

		if ($code) {
			global $log;

			return false;
		}

		return $output;
	}

	public static function resize($image) {
		if (static::$image === null) {
			\Journal3::getInstance()->getRegistry()->get('load')->model('journal3/image');

			static::$image = \Journal3::getInstance()->getRegistry()->get('model_journal3_image');
		}

		if (is_file(DIR_IMAGE . $image)) {
			return static::$image->resize($image);
		}

		return null;
	}

}
