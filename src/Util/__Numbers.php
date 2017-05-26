<?php

namespace App\Util;

class Numbers {
	private $digits = [
		'm' => [ 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять' ],
		'f' => [ 'одна', 'две' ],
		'n' => [ 'одно' ]
	];
	
	private $tens = [
		'десять',
		'один$',
		'две$',
		'три$',
		'четыр$',
		'пят$',
		'шест$',
		'сем$',
		'восем$',
		'девят$',
	];
	
	private $decades = [
		'двадцать',
		'тридцать',
		'сорок',
		'пятьдесят',
		'шестьдесят',
		'семьдесят',
		'восемьдесят',
		'девяносто',
	];
	
	private $hundreds = [
		'сто',
		'двести',
		'триста',
		'четыреста',
		'пятьсот',
		'шестьсот',
		'семьсот',
		'восемьсот',
		'девятьсот',
	];
	
	private $lions = [
		'мил$',
		'миллиард',
		'трил$',
		'квадрил$',
		'квинтил$',
		'секстил$',
		'септил$',
		'октил$',
		'нонил$',
		'децил$',
	];

	// отрицательное число приводится к модулю
	// дробная часть числа отбрасывается
	private function normalize($num) {
		if (!is_numeric($num)) {
			throw new \InvalidArgumentException("Параметр не является числом: {$num}");
		}

		return floor(abs($num));
	}
	
	// преобразует массив в число
	// [ 1, 2, 3, 4 ] => 1234
	public function fromArray($a, $reverse = false) {
		if ($reverse) {
			$a = array_reverse($a);
		}
		
		$n = 0;
		
		foreach ($a as $d) {
			$n = $n * 10 + $d;
		}
		
		return $n;
	}

	// преобразует число в массив
	// 1234 => [ 4, 3, 2, 1 ]
	private function toArray($num, $reverse = false) {
		$num = $this->normalize($num);

		$a = [];
		
		while ($num > 0) {
			$a[] = $num % 10;
			$num = floor($num / 10);
		}

		return $reverse ? $a : array_reverse($a);
	}

	public function toString($num) {
		$num = $this->normalize($num);
		
		$result = '';		
		$offset = 0;

		while ($num > 0) {
			if ($offset > 33) {
				throw new \OutOfRangeException('Oops, так далеко мы не умеем считать!');
			}
			
			$parts = [];
			
			$d321 = $num % 1000;
			$d21 = $d321 % 100;
			$d1 = $d21 % 10;

			$d2 = floor($d21 / 10);
			$d3 = floor($d321 / 100);

			if ($d3 > 0) {
				$parts[] = $this->hundreds[$d3 - 1];
			}
			
			if ($d2 == 1) {
				$parts[] = str_replace('$', 'надцать', $this->tens[$d21 - 10]);
			}
			else {
				if ($d2 >= 2) {
					$parts[] = $this->decades[$d2 - 2];
				}
	
				if ($d1 > 0) {
					$genderDigits = $this->digits[($offset == 3) ? 'f' : 'm'];
					$parts[] = isset($genderDigits[$d1 - 1])
						? $genderDigits[$d1 - 1]
						: $this->digits['m'][$d1 - 1];
				}
			}

			if ($offset == 3) {
				$appendix = 'тысяч';
				if ($d2 != 1) {
					if ($d1 == 1) {
						$appendix .= 'а';
					}
					elseif ($d1 >= 2 && $d1 <= 4) {
						$appendix .= 'и';
					}
				}
			}
			elseif ($offset > 3) {
				$end = 'ов';
				if ($d2 != 1) {
					if ($d1 == 1) {
						$end = '';
					}
					elseif ($d1 >= 2 && $d1 <= 4) {
						$end = 'а';
					}
				}

				$appendix = str_replace('$', 'лион', $this->lions[($offset / 3) - 2]) . $end;
			}

			if ($appendix) {
				$parts[] = $appendix;
			}

			$result = implode(' ', $parts) . ((strlen($result) > 0) ? ' ' : '') . $result;

			$num = floor($num / 1000);
			$offset += 3;
		}

		return $result;
	}
}
