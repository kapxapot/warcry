<?php

namespace App\Legacy;

use Warcry\Contained;

class Decorator extends Contained {
	private function p($text, $class = null, $label = null) {
		return $this->pStart($class, $label) . $text . $this->pEnd();
	}

	private function pStart($class = null, $label = null) {
		if ($class) {
			$class = " class=\"{$class}\"";
		}

		if ($label) {
			$label = " id=\"{$label}\"";
		}

		return "<p{$class}{$label}>";
	}

	private function pEnd() {
		return '</p>';
	}

	public function textBlock($text) {
		return $this->p($text);
	}

	public function boldBlock($text) {
		return $this->p($text, "nd_bold");
	}

	public function subtitleBlock($text, $label = null, $level = null) {
		return $this->p($text, "nd_subtitle" . $level, $label);
	}

	public function propertyBlock($name, $text) {
		return $this->textBlock("<b>{$name}</b>: {$text}");
	}
	
	public function text($text, $class = null) {
		if ($class) {
			$class = " class=\"{$class}\"";
		}
		
		return "<span{$class}>{$text}</span>";
	}

	function url($url, $text, $title = null, $style = null, $rel = null, $data = null) {
		if ($title) {
			$title = " title=\"{$title}\"";
		}

		if ($style) {
			$style = " class=\"{$style}\"";
		}

		if ($rel) {
			$rel = " rel=\"{$rel}\"";
		}
		
		if (is_array($data)) {
			$data = implode(array_map(function($k, $v) {
				return " data-{$k}=\"{$v}\"";
			}, array_keys($data), $data));
		}

		return "<a href=\"{$url}\"{$title}{$style}{$rel}{$data}>{$text}</a>";
	}

	private function articleBase($template) {
		return $template
			? "%article%"
			: $this->getSettings('legacy.articles.page');
	}

	public function articleUrlBare($name, $cat, $template = false) {
		if ($cat) {
			$cat = '/' . $cat;
		}

		$url = $this->articleBase($template);

		return $url . '/' . $name . $cat;
	}

	public function articleUrl($nameRu, $nameEn, $nameEsc, $cat, $catEsc, $template = false, $style = "nd_article") {
		if ($cat) {
			$cat = " ({$cat})";
		}

		$url = $this->articleUrlBare($nameEsc, $catEsc, $template);

		return $this->url($url, $nameRu, $nameEn . $cat, $style);
	}

	public function noArticleUrl($nameRu, $nameEn, $cat = null) {
		if ($cat) {
			$cat = " ({$cat})";
		}

		return "<font class=\"nd_noarticle\" title=\"{$nameEn}{$cat}\">{$nameRu}</font>";
	}
	
	public function entityUrl($url, $text, $title = null) {
		return $this->url($url, $text, $title, 'nd_article');
	}

	public function recipePageUrl($id, $title, $rel = null, $content = '[~]') {
		$url = $this->legacyRouter->recipe($id);
		
		if ($rel) {
			$rel = " rel=\"{$rel}\"";
		}

		return "<a href=\"{$url}\" title=\"{$title}\"{$rel}>{$content}</a>";
	}

	public function coordsBlock($x, $y) {
		return '[' . round($x) . ',&nbsp;' . round($y) . ']';
	}

	public function colorBlock($color, $content) {
		return "<span style=\"color: {$color}\">{$content}</span>";
	}

	public function padLeft($text, $pad) {
		if ($pad > 0) {
			$class = " class=\"pad{$pad}\"";
		}
		
		return "<div{$class}>{$text}</div>";
	}

	private function arrayToClassString($classes) {
		$result = '';
		if (count($classes) > 0) {
			$c = implode(' ', $classes);
			$result = " class=\"{$c}\"";
		}
		
		return $result;
	}

	public function image($tag, $source, $alt = null, $width = 0, $height = 0, $thumb = null) {
		$imgText = null;

		$divClasses = [ 'img' ];
		$imgClasses = [];
		
		$mainTag = 'figure';
		$captionTag = 'figcaption';
		
		switch ($tag) {
			case 'rightimg':
				$divClasses[] = 'img-right';
				break;
				
			case 'leftimg':
				$divClasses[] = 'img-left';
				break;

			case 'img':
				//$divClasses[] = 'img-center';
				//$imgClasses[] = 'center';
				$mainTag = 'div';
				$captionTag = 'div';
				break;
		}

		if ($source) {
			if ($alt) {
				$alt = htmlspecialchars($alt, ENT_QUOTES);
				$imgAttrText .= " title=\"{$alt}\"";
				$subText = "<{$captionTag} class=\"img-caption\">{$alt}</{$captionTag}>";
			}
			
			$imgSrc = $thumb ?? $source;

			$imgClasses[] = 'img-responsive';

			if ($width > 0) {
				$imgAttrText .= " width=\"{$width}\"";
				$thumb = $imgSrc;
			}

			if ($height > 0) {
				$imgAttrText .= " height=\"{$height}\"";
			}
			
			$imgClassText = $this->arrayToClassString($imgClasses);
			$divClassText = $this->arrayToClassString($divClasses);

			$imgText = "<img src=\"{$imgSrc}\"{$imgClassText}{$imgAttrText} />";

			if ($thumb) {
				$imgText = "<a href=\"{$source}\" class=\"colorbox\">{$imgText}</a>";
			}

			$imgText = "<{$mainTag}{$divClassText}>{$imgText}{$subText}</{$mainTag}>";
		}

		return $imgText;
	}

	public function youtubeBlock($code, $width = 0, $height = 0) {
		if ($width > 0) {
			$widthText = " width=\"{$width}\"";
		}
		
		if ($height > 0) {
			$heightText = " height=\"{$height}\"";
		}
		
		if ($width == 0 && $height == 0) {
			$divClass = ' class="embed-responsive embed-responsive-16by9"';
			$iFrameClass = ' class="embed-responsive-item"';
		}
		else {
			$divClass = ' class="center"';
		}
		
		return "<div{$divClass}><iframe{$iFrameClass} src=\"https://www.youtube.com/embed/{$code}\"{$widthText}{$heightText} frameborder=\"0\" allowfullscreen></iframe></div>";
	}

	public function quoteBlock($quotename, $text, $author, $url = null, $date = null) {
		$result = null;

		switch ($quotename) {
			case "quote":
				$header = null;

				if ($date) {
					$date = "[{$date}]";
				}

				if ($author || $date) {
					if ($author) {
						if ($url) {
							$author = $this->url($url, $author);
						}

						$author = "<span class=\"quote-author\">{$author}</span>";
						
						if ($date) {
							$date = ' ' . $date;
						}
					}

					$header = "<div class=\"quote-header\">{$author}{$date}:</div>";
				}

				$result = "<div class=\"quote\">{$header}<div class=\"quote-body\">{$text}</div></div>";
				break;

			case "bluepost":
				$author = $author ?? 'Blizzard';

				if ($url) {
					$author = $this->url($url, $author, null, 'blue');
				}
				
				if ($date) {
					$date = " [{$date}]";
				}

				$result = "<div class=\"bluepost\"><div class=\"bluepost-header\"><span class=\"bluepost-author\">{$author}</span>{$date}:</div><div class=\"bluepost-body\">{$text}</div></div>";
				break;
		}

		return $result ?? $text;
	}

	protected function divBlock($id, $title, $body, $visible = false) {
		$shortid = "short" . $id;
		$fullid = "full" . $id;

		$shortstyle = $visible ? "none" : "block";
		$fullstyle = $visible ? "block" : "none";

		$short = "<div id=\"{$shortid}\" style=\"display:{$shortstyle};\">
				<span class=\"spoiler-header\" onclick=\"{$fullid}.style.display='block'; {$shortid}.style.display='none';\">{$title} <span class=\"glyphicon glyphicon-chevron-right\" aria-hidden=\"true\"></span></span>
				</div>";

		$full = "<div id=\"{$fullid}\" style=\"display:{$fullstyle};\">
				<span class=\"spoiler-header\" onclick=\"{$fullid}.style.display='none';{$shortid}.style.display='block';\">{$title} <span class=\"glyphicon glyphicon-chevron-down\" aria-hidden=\"true\"></span></span>
				<div class=\"spoiler-body\">{$body}</div>
			</div>";

		return $short . $full;
	}

	public function spoilerBlock($content, $label = null) {
		$label = $label ?? 'Спойлер';

		$id = mt_rand();

		$div = $this->divBlock($id, $label, $content);

		return "<div class=\"spoiler\">{$div}</div>";
	}
	
	public function next() {
		return '<span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>';
	}
	
	public function prev() {
		return '<span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>';
	}
	
	public function list($items, $ordered = false) {
		$tag = $ordered ? 'ol' : 'ul';

		$items = array_map(function($item) {
			return '<li>' . $item . '</li>';
		}, $items);
					
		return  '<' . $tag . '>' . implode($items) . '</' . $tag . '>';
	}
}
