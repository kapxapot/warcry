<?php

namespace App\Gallery;

use Warcry\Contained;
use Warcry\File\File;
use Warcry\File\Image;

class GalleryBase extends Contained {
	const IMAGE_TYPES = [
		'jpeg' => 'jpg',
		'png' => 'png',
		'gif' => 'gif',
	];
	
	protected $baseDir;

	protected $pictureField;
	protected $pictureTypeField;

	protected $thumbTypeField;
	protected $thumbField;

	protected $pictureFolder;
	protected $picturePublicFolder;

	protected $thumbFolder;
	protected $thumbPublicFolder;

	protected $folders;

	public function __construct($container, $settings = []) {
		parent::__construct($container);
		
		$this->baseDir = $settings['base_dir'];

		$fieldSettings = $settings['fields'];

		$this->pictureField = $fieldSettings['picture'] ?? 'picture';
		$this->pictureTypeField = $fieldSettings['picture_type'];

		$this->thumbField = $fieldSettings['thumb'] ?? 'thumb';
		$this->thumbTypeField = $fieldSettings['thumb_type'];

		$folderSettings = $settings['folders'];
		
		$this->pictureFolder = $folderSettings['picture']['storage'];
		$this->picturePublicFolder = $folderSettings['picture']['public'];
		
		$this->thumbFolder = $folderSettings['thumb']['storage'];
		$this->thumbPublicFolder = $folderSettings['thumb']['public'];

		$this->folders = $this->getSettings('folders');
	}
	
	protected function getFolder($folder) {
		if (!isset($this->folders[$folder])) {
			throw new \InvalidArgumentException('Unknown image folder: ' . $folder);
		}
		
		return $this->folders[$folder];
	}
	
	protected function getUrl($folder, $item, $typeField) {
		$path = $this->getFolder($folder);
		$ext = $this->getExtension($item[$typeField]);
		
		return $path . $item['id'] . '.' . $ext;
	}
	
	// get public url
	public function getPictureUrl($item) {
		return $this->getUrl($this->picturePublicFolder, $item, $this->pictureTypeField);
	}
	
	// get public url
	public function getThumbUrl($item) {
		return $this->getUrl($this->thumbPublicFolder, $item, $this->thumbTypeField);
	}
	
	public function getExtension($type) {
		if (!array_key_exists($type, self::IMAGE_TYPES)) {
			throw new \InvalidArgumentException('Unknown or unsupported image type: ' . $type);
		}
		
		return self::IMAGE_TYPES[$type];
	}
	
	protected function buildImagePath($folder, $name, $imgType) {
		$path = $this->getFolder($folder);
		
		try {
			$ext = $this->getExtension($imgType);
		}
		catch (\Exception $ex) {
			$ext = $imgType;
		}

		return $this->baseDir . $path . $name . '.' . $ext;
	}
	
	// get server path
	public function buildPicturePath($name, $imgType) {
		return $this->buildImagePath($this->pictureFolder, $name, $imgType);
	}
	
	// get server path
	public function buildThumbPath($name, $imgType) {
		return $this->buildImagePath($this->thumbFolder, $name, $imgType);
	}
	
	public function buildTypesString() {
		// image/jpeg, image/png, image/gif
		$parts = [];
		
		foreach (array_keys(self::IMAGE_TYPES) as $type) {
			$parts[] = 'image/' . $type;
		}
		
		return implode(', ', $parts);
	}
	
	protected function getPicture($data) {
		$picture = null;
		
		if (array_key_exists($this->pictureField, $data)) {
			$picture = Image::parseBase64($data[$this->pictureField]);
		}
		
		return $picture;
	}
	
	protected function getThumb($data) {
		$thumb = null;
		
		if (array_key_exists($this->thumbField, $data)) {
			$thumb = Image::parseBase64($data[$this->thumbField]);
		}
		
		return $thumb;
	}
	
	public function save($item, $data) {
		// если идет повторное сохранение миниатюры, картинка не пересохраняется
		// чтобы не гонять картинку туда-сюда
		// в этом случае поле 'picture' приходит пустое
		$picture = $this->getPicture($data);
		if ($picture && $picture->notEmpty()) {
			$this->savePicture($item, $picture);
		}
		
		// пока не должно быть пустого поля 'thumb', но потом почему бы и нет
		$thumb = $this->getThumb($data);
		if ($thumb && $thumb->notEmpty()) {
			$this->saveThumb($item, $thumb);
		}
		
		$item = $this->beforeSave($item, $picture, $thumb);
		
		$item->save();
	}
	
	protected function beforeSave($item, $picture, $thumb) {
		if ($picture && $picture->notEmpty()) {
			$item->{$this->pictureTypeField} = $picture->imgType;
		}
		
		if ($this->pictureTypeField != $this->thumbTypeField && $thumb && $thumb->notEmpty()) {
			$item->{$this->thumbTypeField} = $thumb->imgType;
		}
		
		return $item;
	}

	private function savePicture($item, $picture) {
		$fileName = $this->buildPicturePath($item->id, $picture->imgType);
		$picture->save($fileName);

		// delete previous version if extension changed
		$mask = $this->buildPicturePath($item->id, '*');
		File::cleanUp($mask, $fileName);
	}
	
	private function saveThumb($item, $thumb) {
		$fileName = $this->buildThumbPath($item->id, $thumb->imgType);
		$thumb->save($fileName);

		// delete previous version if extension changed
		$mask = $this->buildThumbPath($item->id, '*');
		File::cleanUp($mask, $fileName);
	}
	
	public function delete($item) {
		$pictureFileName = $this->buildPicturePath($item->id, $item->{$this->pictureTypeField});
		File::delete($pictureFileName);

		$thumbFileName = $this->buildThumbPath($item->id, $item->{$this->thumbTypeField});
		File::delete($thumbFileName);
	}
}
