<?php

namespace App\Gallery;

class Gallery extends GalleryBase {
	public function __construct($container) {
		$settings = [
			'base_dir' => __DIR__,
			'fields' => [
				'picture_type' => 'picture_type',
				'thumb_type' => 'thumb_type',
			],
			'folders' => [
				'picture' => [
					'storage' => 'gallery_pictures',
					'public' => 'gallery_pictures_public',
				],
				'thumb' => [
					'storage' => 'gallery_thumbs',
					'public' => 'gallery_thumbs_public',
				],
			],
		];

		parent::__construct($container, $settings);
	}
}
