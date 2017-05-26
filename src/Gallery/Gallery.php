<?php

namespace App\Gallery;

use Warcry\Contained;

use Warcry\File\File;
use Warcry\File\Image;

class Gallery extends Contained {
	const IMAGE_TYPES = [
		'jpeg' => 'jpg',
		'png' => 'png',
		'gif' => 'gif',
	];
	
	private $folders;
	private $file;
	
	public function __construct($container) {
		parent::__construct($container);
		
		$this->folders = $this->getSettings('folders');
		$this->file = new File();
	}
	
	private function getFolder($folder) {
		if (!isset($this->folders[$folder])) {
			throw new \InvalidArgumentException('Неизвестная папка с изображениями: ' . $folder);
		}
		
		return $this->folders[$folder];
	}
	
	private function getUrl($folder, $item, $typeField) {
		$path = $this->getFolder($folder);
		$ext = $this->getExtension($item[$typeField]);
		
		return $path . $item['id'] . '.' . $ext;
	}
	
	public function getPictureUrl($item) {
		return $this->getUrl('gallery_pictures_public', $item, 'picture_type');
	}
	
	public function getThumbUrl($item) {
		return $this->getUrl('gallery_thumbs_public', $item, 'thumb_type');
	}
	
	public function getExtension($type) {
		if (!array_key_exists($type, self::IMAGE_TYPES)) {
			throw new \InvalidArgumentException('Неизвестный или не поддерживаемый формат изображения: ' . $type);
		}
		
		return self::IMAGE_TYPES[$type];
	}
	
	private function buildImagePath($folder, $name, $imgType) {
		$path = $this->getFolder($folder);
		
		try {
			$ext = $this->getExtension($imgType);
		}
		catch (\Exception $ex) {
			$ext = $imgType;
		}

		return __DIR__ . $path . $name . '.' . $ext;
	}
	
	public function buildPicturePath($name, $imgType) {
		return $this->buildImagePath('gallery_pictures', $name, $imgType);
	}
	
	public function buildThumbPath($name, $imgType) {
		return $this->buildImagePath('gallery_thumbs', $name, $imgType);
	}
	
	public function buildTypesString() {
		// image/jpeg, image/png, image/gif
		$parts = [];
		
		foreach (array_keys(self::IMAGE_TYPES) as $type) {
			$parts[] = 'image/' . $type;
		}
		
		return implode(', ', $parts);
	}
	
	public function save($item, $data) {
		// если идет повторное сохранение миниатюры, картинка не пересохраняется
		// чтобы не гонять картинку туда-сюда
		// в этом случае поле 'picture' приходит пустое
		if (array_key_exists('picture', $data)) {
			$picture = new Image();
			$picture->parseBase64($data['picture']);
			if ($picture->notEmpty()) {
				$this->savePicture($item, $picture);
			}
		}
		
		// пока не должно быть пустого поля 'thumb', но потом почему бы и нет
		if (array_key_exists('thumb', $data)) {
			$thumb = new Image();
			$thumb->parseBase64($data['thumb']);
			if ($thumb->notEmpty()) {
				$this->saveThumb($item, $thumb);
			}
		}
		
		$item->save();
	}
	
	private function cleanUp($mask, $except = null) {
		foreach (glob($mask) as $toDel) {
			if ($toDel != $except) {
				$this->file->delete($toDel);
			}
		}
	}

	private function savePicture($item, $picture) {
		$fileName = $this->buildPicturePath($item->id, $picture->imgType);
		$picture->save($fileName);
		
		$item->picture_type = $picture->imgType;
		
		// delete previous version if extension changed
		$mask = $this->buildPicturePath($item->id, '*');
		$this->cleanUp($mask, $fileName);
	}
	
	private function saveThumb($item, $thumb) {
		$fileName = $this->buildThumbPath($item->id, $thumb->imgType);
    	$thumb->save($fileName);
		
		$item->thumb_type = $thumb->imgType;
		
		// delete previous version if extension changed
		$mask = $this->buildThumbPath($item->id, '*');
		$this->cleanUp($mask, $fileName);
	}
	
	public function delete($item) {
		$pictureFileName = $this->buildPicturePath($item->id, $item->picture_type);
		$this->file->delete($pictureFileName);

		$thumbFileName = $this->buildThumbPath($item->id, $item->thumb_type);
		$this->file->delete($thumbFileName);
	}
}
