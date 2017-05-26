<?php

namespace App\Auth;

use Warcry\Contained;
use Warcry\Util\Util;
use Warcry\Util\Numbers;

class Captcha extends Contained {
	private $numbers;
	
	private $fuckUpReplacements = [
		'один' => [ 'адын' ],
		// ..
	];
	
	public function __construct() {
		$this->numbers = new Numbers;
	}

	private function fuckUp($str) {
		foreach ($this->fuckUpReplacements as $key => $reps) {
			$rep = $reps[mt_rand(0, count($reps) - 1)];
			$str = str_replace($key, $rep, $str);
		}

		return $str;
	}

	public function generate($length, $save = false) {
		$num = $this->numbers->generate($length);
		$string = $this->numbers->toString($num);
		
		$fuckedUpString = implode('', array_map(function($value) {
		    return $this->fuckUp($value);
		}, explode(' ', $string)));

		$result = [
			'number' => $num,
			'string' => $string,
			'captcha' => $fuckedUpString
		];
		
		if ($save) {
			$this->save($result);
		}
		
		return $result;
	}
	
	private function save($captcha) {
		$captcha['expires_at'] = Util::generateExpirationTime(10);
		
		$_SESSION['captcha'] = $captcha;
	}
	
	// после прочтения сжечь
	private function load() {
		$captcha = $_SESSION['captcha'];
		unset($_SESSION['captcha']);
		
		return $captcha;
	}
	
	public function validate($number) {
		$captcha = $this->load();

		return $captcha
			&& is_numeric($number)
			&& $captcha['number'] == $number
			&& strtotime($captcha['expires_at']) >= time();
	}
}
