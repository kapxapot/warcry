<?php

namespace App\Validation;

use Warcry\Contained;
use Warcry\Validation\Rules\ContainerRule;

use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

class Validator extends Contained {
	public $errors;
	
	private static $dictionaries;

	public static function setDictionaries($dictionaries) {
		self::$dictionaries = $dictionaries;
	}
	
	public static function translateMessage($message) {
		return self::translate('messages', $message);
	}
	
	private function translateField($field) {
		return self::translate('fields', $field);
	}
	
	private static function translate($dictionaryName, $value) {
		$dictionary = self::$dictionaries[$dictionaryName];
		return isset($dictionary[$value]) ? $dictionary[$value] : $value;
	}

	public function validate($request, array $rules) {
		foreach ($rules as $field => $rule) {
			try {
				foreach ($rule->getRules() as $subRule) {
					if ($subRule instanceof ContainerRule) {
	    				$subRule->setContainer($this->container);
					}
				}
				
				$name = $this->translateField($field);

				$rule->setName($name)->assert($request->getParam($field));
			}
			catch (NestedValidationException $e) {
				$e->setParam('translator', __NAMESPACE__ . '\Validator::translateMessage');
				$this->errors[$field] = $e->getMessages();
			}
		}

		return $this;
	}
	
	public function failed() {
		return !empty($this->errors);
	}

	public function getRulesFor($table, $data, $id = null) {
		$rules = [];
		
		$name = function() { return v::notBlank()->alnum(); };
		$alias = function() { return v::noWhitespace()->notEmpty()->alnum(); };
		$text = function() { return v::notBlank(); };
		$url = function() { return v::noWhitespace()->notEmpty(); };
		$posInt = function() { return v::numeric()->positive(); };
		$image = function() { return v::imageNotEmpty()->imageTypeAllowed(); };

		$settings = $this->getSettings();

		$password = v::noWhitespace()->length($settings['password_min']);

		$lat = function($add = '') { return "/^[\w {$add}]+$/"; };
		$cyr = function($add = '') { return "/^[\w\p{Cyrillic} {$add}]+$/u"; };

		switch ($table) {
			case 'articles':
				$rules = [
					'name_ru' => $text(),//->regex($cyr("'\(\):\-\.\|,\?!—«»")),
				];
				
				if (array_key_exists('name_en', $data) && array_key_exists('cat', $data)) {
					$rules['name_en'] = $text()->regex($lat("':\-"))->articleNameCatAvailable($data['cat'], $id);
				}

				break;
		
			case 'gallery_authors':
				$rules = [
					'name' => $text()->galleryAuthorNameAvailable($id),
					'alias' => $alias()->galleryAuthorAliasAvailable($id),
				];
				
				break;

			case 'gallery_pictures':
				$rules = [
					'comment' => $text(),
					'picture' => v::optional($image()),
					'thumb' => $image(),
				];
				
				break;

			case 'games':
				$rules = [
					'icon' => $url(),
					'name' => $text()->gameNameAvailable($id),
					'alias' => $alias()->gameAliasAvailable($id),
					'news_forum_id' => v::optional($posInt()),
					'main_forum_id' => v::optional($posInt()),
					'position' => $posInt(),
				];
				
				break;
		
			case 'menus':
			case 'menu_items':
				$rules = [
					'link' => $url(),
					'text' => $text(),
					'position' => $posInt(),
				];
				
				break;

			case 'streams':
				$rules = [
					'title' => $text()->streamTitleAvailable($id),
					'stream_id' => $alias()->streamIdAvailable($id),
					'comments' => $text(),
				];
				
				break;
		
			case 'users':
				$rules = [
					'login' => $alias()->length($settings['login_min'], $settings['login_max'])->loginAvailable($id),
					'email' => $url()->email()->emailAvailable($id),
					'password' => $id ? v::optional($password) : $password,
				];
				
				break;
				
			case 'password':
				$rules = [
					'password_old' => v::matchesPassword($data['password']),
					'password' => $password,
				];
				
				break;
		}

		return $rules;
	}
}
