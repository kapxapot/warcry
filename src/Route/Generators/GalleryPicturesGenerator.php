<?php

namespace App\Route\Generators;

class GalleryPicturesGenerator extends EntityGenerator {
	public function getRules($data, $id = null) {
		return [
			'comment' => $this->rule('text'),
			'picture' => $this->optional('image'),
			'thumb' => $this->rule('image'),
		];
	}
	
	public function getOptions() {
		//$container = $this->container;
		//$gallery = $container->gallery;
		
		return [
			'uri' => 'gallery_authors/{id:\d+}/gallery_pictures',
			'filter' => 'author_id',
			
			// move to functions too?
			/*'mutator' => function($item) use ($gallery) {
				$item['picture'] = $gallery->getPictureUrl($item);
				$item['thumb'] = $gallery->getThumbUrl($item);
				
				unset($item['picture_type']);
				unset($item['thumb_type']);
				
				if ($item['points']) {
					$item['points'] = explode(',', $item['points']);
				}
		
				return $item;
			},*/

			'admin_uri' => 'gallery/{id:\d+}/gallery_pictures',
			/*'admin_mutator' => function($params, $args) use ($container) {
				$authorId = $args['id'];
				$author = $container->db->getEntityById('gallery_authors', $authorId);
		
				$params['source'] = "gallery_authors/{$authorId}/gallery_pictures";
				$params['breadcrumbs'] = [
					[ 'text' => 'Галерея', 'link' => $container->router->pathFor('admin.gallery_authors') ],
					[ 'text' => $author['name'] ],
					[ 'text' => 'Картинки' ],
				];
				$params['hidden'] = [
					'author_id' => $authorId,
				];
				
				return $params;
			},*/
		];
	}
	
	public function afterLoad($item) {
		$item['picture'] = $this->gallery->getPictureUrl($item);
		$item['thumb'] = $this->gallery->getThumbUrl($item);
		
		unset($item['picture_type']);
		unset($item['thumb_type']);
		
		if ($item['points']) {
			$item['points'] = explode(',', $item['points']);
		}

		return $item;
	}
	
	public function getAdminParams($params, $args) {
		$authorId = $args['id'];
		$author = $this->db->getEntityById('gallery_authors', $authorId);

		$params['source'] = "gallery_authors/{$authorId}/gallery_pictures";
		$params['breadcrumbs'] = [
			[ 'text' => 'Галерея', 'link' => $this->router->pathFor('admin.gallery_authors') ],
			[ 'text' => $author['name'] ],
			[ 'text' => 'Картинки' ],
		];
		
		$params['hidden'] = [
			'author_id' => $authorId,
		];
		
		return $params;
	}
	
	public function beforeSave($data, $id = null) {
		if (isset($data['points'])) {
			$data['points'] = implode(',', $data['points']);
		}

		if (isset($data['picture'])) {
			unset($data['picture']);
		}

		if (isset($data['thumb'])) {
			unset($data['thumb']);
		}
		
		return $data;
	}
	
	public function afterSave($item, $data) {
		$this->gallery->save($item, $data);
	}
	
	public function afterDelete($item) {
		$this->gallery->delete($item);
	}
}
