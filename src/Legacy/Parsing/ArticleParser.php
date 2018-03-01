<?php

namespace App\Legacy\Parsing;

use Warcry\Contained;
use Warcry\Util\Text;

class ArticleParser extends Contained {
	const SPACE_CHAR = '_';

	protected $decorator;

	public function __construct($container) {
		parent::__construct($container);

		$this->decorator = $this->legacyDecorator; // from container
	}

	/*public function parseXML($input) {
		$text = '';
		$contents = [];

		$xmlParser = new Xml2Array;
		$arrOutput = $xmlParser->parse($input);
		
		// analyze array
		if (is_array($arrOutput)) {
			$rootArray = $arrOutput[0];
			if (is_array($rootArray)) {
				if ($rootArray["name"] == "ARTICLE") {
					// article found
					$contentNodes = $rootArray["children"];
					if (is_array($contentNodes)) {
						$subtitleCount = 0;
						$subtitle2Count = 0;

						// content
						foreach ($contentNodes as $contentNode) {
							if (is_array($contentNode)) {
								$name = $contentNode["name"];
								$attrs = $contentNode["attrs"];
								$children = $contentNode["children"];

								$tagData = $this->parseLinks($contentNode["tagData"]);
								$tagText = $this->stripTags($contentNode["tagData"]);
								
								switch ($name) {
									case "SUBTITLE":
										$label = ++$subtitleCount;
										$subtitle2Count = 0;

										$contentsLink["level"] = 1;
										$contentsLink["label"] = $label;
										$contentsLink["text"] = $tagData;
										
										$contents[] = $contentsLink;

										$text .= $this->decorator->subtitleBlock($tagData, $label);
										break;
										
									case "SUBTITLE2":
										$label = $subtitleCount . "_" . ++$subtitle2Count;

										$contentsLink["level"] = 2;
										$contentsLink["label"] = $label;
										$contentsLink["text"] = $tagData;
										
										$contents[] = $contentsLink;

										$text .= $this->decorator->subtitleBlock($tagData, $label, 2);
										break;
										
									case "BOLD":
										$text .= $this->decorator->boldBlock($tagData);
										break;
										
									case "TEXT":
										$text .= $this->decorator->textBlock($tagData);
										break;
	
									case "IMAGE":
										if (is_array($attrs)) {
											$source = $attrs["SOURCE"];
											$alt = $attrs["ALT"];
											$width = $attrs["WIDTH"];
											$height = $attrs["HEIGHT"];
											$align = $attrs["ALIGN"];
											
											$text .= $this->decorator->imageBlock($source, $alt, $width, $height, $align);
										}
										break;
	
									case "SCREENSHOT":
										if (is_array($attrs)) {
											$id = $attrs["ID"];
											$alt = $attrs["ALT"];
											$width = $attrs["WIDTH"];
											$height = $attrs["HEIGHT"];
											$align = $attrs["ALIGN"];
											
											$text .= $this->decorator->screenshotBlock($id, $alt, $width, $height, $align);
										}
										break;
	
									case "PROPERTY":
										$propname = "property_name";
										if (is_array($attrs)) {
											$propname = $attrs["NAME"];
										}
										
										$text .= $this->decorator->propertyBlock($propname, $tagData);
										break;

									case "HTML":
										$text .= $this->parseAsIs($children);
										break;
								}
							}
						}
					}
				}
			}
		}

		return [ 'text' => $text, 'contents' => $contents ];
	}*/

	// заменяет экранирующие символы $space на пробелы
	public function toSpaces($text, $space = self::SPACE_CHAR) {
		$text = stripslashes($text);
		return preg_replace("/{$space}/", ' ', $text);
	}

	// заменяет пробелы на экранирующие символы $space
	public function fromSpaces($text, $space = self::SPACE_CHAR) {
		return preg_replace('/\s+/u', $space, $text);
	}

	/*protected function parseTagLink($text) {
		$newtext = '';

		$parts = preg_split('/(\[link.*\].*\[\/link\])/U', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach ($parts as $part) {
			if (preg_match('/\[link(.*)\](.*)\[\/link\]/', $part, $matches)) {
				$attrs = trim($matches[1]);
				$content = $matches[2];
				
				$id = $content;
				$cat = '';
				
				$attr_pair_list = preg_split('/\s+/', $attrs);
				
				foreach ($attr_pair_list as $attr_pair)
				{
					$name_value = preg_split('/=/', $attr_pair);
					
					$attr_name = trim($name_value[0], '"');
					$attr_value = trim($name_value[1], '"');
					
					switch ($attr_name)
					{
						case 'id':
							$id = $attr_value;
							break;
							
						case 'cat':
							$cat = $attr_value;
							break;
					}
				}

				$name_en = $this->toSpaces($id);
				$cat_sp = $this->toSpaces($cat);
				
				$article = $this->db->getArticle($name_en, $cat_sp);

				if ($article) {
					$newtext .= $this->decorator->articleUrl($content, $name_en, $id, $cat_sp, $cat, true);
				}
				else {
					$newtext .= $this->decorator->noArticleUrl($content, $name_en, $cat_sp);
				}
			}
			else {
				$newtext .= $part;
			}
		}
		
		return $newtext;
	}*/
	
	protected function getWebDbLink($appendix) {
		return $this->getSettings('legacy.webdb_ru_link') . $appendix;
	}

	/*protected function parseTagItem($text)	{
		$newtext = '';
		
		$parts = preg_split('/(\[item.*\].*\[\/item\])/U', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach ($parts as $part) {
			if (preg_match('/\[item(.*)\](.*)\[\/item\]/', $part, $matches)) {
				$attrs = trim($matches[1]);
				$content = $matches[2];
				
				$id = $content;
				
				if (strlen($attrs) > 0) {
					$attr_pair_list = preg_split('/\s+/', $attrs);
					
					foreach ($attr_pair_list as $attr_pair) {
						$name_value = preg_split('/=/', $attr_pair);
						
						$attr_name = trim($name_value[0], '"');
						$attr_value = trim($name_value[1], '"');
						
						switch ($attr_name) {
							case 'id':
								$id = $attr_value;
								break;
						}
					}
				}

				if (strlen($id) > 0) {
					// заменяем экранирующие символы на пробелы, а их на +
					$id_esc = $this->toSpaces($id);
					$search_str = $this->fromSpaces($id_esc, '+');
					$title = '';

					if (is_numeric($id)) {
						$item_id = $id;
					}
					else {
						$item_id = $this->db->getItemId($id_esc);
					}

					if (isset($item_id)) {
						$item_args = 'item=' . $item_id;
					}
					else {
						$item_args = 'search?q=' . $search_str;
						$title = "Искать {$id_esc} на Wowhead";
					}
				
					$url = $this->getWebDbLink($item_args);
					$newtext .= $this->decorator->url($url, $content, $title);

					if (isset($item_id)) {
						$sources = $this->db->getRecipesByItemId($item_id);
						if (is_array($sources) && count($sources) > 0) {
							$recipe_data = $sources[0];

							$title = 'Рецепт: ' . $recipe_data['name_ru'];
							$rel = 'spell=' . $recipe_data['id'] . '&amp;domain=ru';
							$recipeUrl = $this->decorator->recipePageUrl($recipe_data['id'], $title, $rel);
							
							$newtext .= '&nbsp;' . $recipeUrl;
						}
					}
				}
				else {
					$newtext .= $content;
				}
			}
			else {
				$newtext .= $part;
			}
		}
		
		return $newtext;
	}*/

	/*protected function parseTagNPC($text) {
		$newtext = '';
		
		$parts = preg_split('/(\[npc.*\].*\[\/npc\])/U', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach ($parts as $part) {
			if (preg_match('/\[npc(.*)\](.*)\[\/npc\]/', $part, $matches)) {
				$attrs = trim($matches[1]);
				$content = $matches[2];
				
				$id = $content;
				
				if (strlen($attrs) > 0) {
					$attr_pair_list = preg_split('/\s+/', $attrs);
					
					foreach ($attr_pair_list as $attr_pair) {
						$name_value = preg_split('/=/', $attr_pair);
						
						$attr_name = trim($name_value[0], '"');
						$attr_value = trim($name_value[1], '"');
						
						switch ($attr_name) {
							case 'id':
								$id = $attr_value;
								break;
						}
					}
				}

				if (strlen($id) > 0) {
					// заменяем экранирующие символы на пробелы, а их на +
					$id_esc = $this->toSpaces($id);
					$search_str = $this->fromSpaces($id_esc, '+');
					$title = '';
					$npc_suffix = '';
					
					if (is_numeric($id)) {
						$npc_id = $id;
					}
					else {
						$npc_id = $this->db->getNPCId($id_esc);
					}

					if (isset($npc_id)) {
						$npc_args = 'npc=' . $npc_id;
					}
					else {
						$npc_args = 'search?q=' . $search_str;
						$npc_suffix = '#npcs';
						$title = "Искать {$id_esc} на Wowhead";
					}
				
					$url = $this->getWebDbLink($npc_args . $npc_suffix);
					$newtext .= $this->decorator->url($url, $content, $title);
				}
				else {
					$newtext .= $content;
				}
			}
			else {
				$newtext .= $part;
			}
		}
		
		return $newtext;
	}*/

	/*protected function parseTagSpell($text) {
		$newtext = '';
		
		$parts = preg_split('/(\[spell.*\].*\[\/spell\])/U', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach ($parts as $part) {
			if (preg_match('/\[spell(.*)\](.*)\[\/spell\]/', $part, $matches)) {
				$attrs = trim($matches[1]);
				$content = $matches[2];
				
				$id = $content;
				$skill = '';
				
				if (strlen($attrs) > 0) {
					$attr_pair_list = preg_split('/\s+/', $attrs);
					
					foreach ($attr_pair_list as $attr_pair) {
						$name_value = preg_split('/=/', $attr_pair);
						
						$attr_name = trim($name_value[0], '"');
						$attr_value = trim($name_value[1], '"');
						
						switch ($attr_name) {
							case 'id':
								$id = $attr_value;
								break;

							case 'skill':
								$skill = $attr_value;
						}
					}
				}

				if (strlen($id) > 0) {
					// заменяем экранирующие символы на пробелы, а их на +
					$id_esc = $this->toSpaces($id);
					$search_str = $this->fromSpaces($id_esc, '+');
					$skill = $this->toSpaces($skill);
					$title = '';
					$spell_suffix = '';

					$recipe = $this->db->getRecipeByName($id_esc);
					if ($recipe) {
						$title = 'Рецепт: ' . $id_esc;
						$rel = 'spell=' . $recipe['id'] . '&amp;domain=ru';
						$recipeUrl = $this->decorator->recipePageUrl($recipe['id'], $title, $rel, $id_esc);
						
						$newtext .= $recipeUrl;
					}
					else {
						if (is_numeric($id)) {
							$spell_id = $id;
						}
						else {
							$spell_id = $this->db->getSpellId($id_esc, $skill);
						}

						if (isset($spell_id)) {
							$spell_args = 'spell=' . $spell_id;
						}
						else {
							$spell_args = 'search?q=' . $search_str;
							$spell_suffix = '#abilities';
							$title = "Искать {$id_esc} на Wowhead";
						}

						$url = $this->getWebDbLink($spell_args . $spell_suffix);
						$newtext .= $this->decorator->url($url, $content, $title);
					}
				}
				else {
					$newtext .= $content;
				}
			}
			else {
				$newtext .= $part;
			}
		}
		
		return $newtext;
	}*/

	/*protected function parseTagQuest($text) {
		$newtext = '';
		
		$parts = preg_split('/(\[quest.*\].*\[\/quest\])/U', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach ($parts as $part) {
			if (preg_match('/\[quest(.*)\](.*)\[\/quest\]/', $part, $matches)) {
				$attrs = trim($matches[1]);
				$content = $matches[2];
				
				$id = $content;
				
				if (strlen($attrs) > 0) {
					$attr_pair_list = preg_split('/\s+/', $attrs);
					
					foreach ($attr_pair_list as $attr_pair) {
						$name_value = preg_split('/=/', $attr_pair);
						
						$attr_name = trim($name_value[0], '"');
						$attr_value = trim($name_value[1], '"');
						
						switch ($attr_name) {
							case 'id':
								$id = $attr_value;
								break;
						}
					}
				}

				if (strlen($id) > 0) {
					// заменяем экранирующие символы на пробелы, а их на +
					$id_esc = $this->toSpaces($id);
					$search_str = $this->fromSpaces($id_esc, '+');
					$title = '';
					$quest_suffix = '';

					if (is_numeric($id)) {
						$quest_id = $id;
					}
					else {
						$quest_id = $this->db->getQuestId($id_esc);
					}

					if (isset($quest_id)) {
						$quest_args = 'quest=' . $quest_id;
					}
					else {
						$quest_args = 'search?q=' . $search_str;
						$quest_suffix = '#quests';
						$title = "Искать {$id_esc} на Wowhead";
					}
				
					$url = $this->getWebDbLink($quest_args . $quest_suffix);
					$newtext .= $this->decorator->url($url, $content, $title);
				}
				else {
					$newtext .= $content;
				}
			}
			else {
				$newtext .= $part;
			}
		}
		
		return $newtext;
	}*/

	/*protected function parseTagCoords($text) {
		$newtext = '';
		
		$parts = preg_split('/(\[coords.*\/\])/U', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach ($parts as $part) {
			if (preg_match('/\[coords(.*)\/\]/', $part, $matches)) {
				$attrs = trim($matches[1]);

				$loc = '';
				$x = 0;
				$y = 0;
				
				if (strlen($attrs) > 0) {
					$attr_pair_list = preg_split('/\s+/', $attrs);
					
					foreach ($attr_pair_list as $attr_pair) {
						$name_value = preg_split('/=/', $attr_pair);
						
						$attr_name = trim($name_value[0], '"');
						$attr_value = trim($name_value[1], '"');
						
						switch ($attr_name) {
							case 'loc':
								$loc = $attr_value;
								break;

							case 'x':
								$x = $attr_value;
								break;

							case 'y':
								$y = $attr_value;
								break;
						}
					}
				}

				$coords_link = null;
				$coords_text = $this->decorator->coordsBlock($x, $y);

				if (strlen($loc) > 0) {
					$loc = $this->toSpaces($loc);

					if (is_numeric($loc)) {
						$loc_id = $loc;
					}
					else {
						$loc_id = $this->db->getLocationId($loc);
					}

					if (isset($loc_id)) {
						$coords = '';
						if ($x > 0 && $y > 0) {
							$coords = ':' . ($x * 10) . ($y * 10);
						}
						
						$url = $this->getWebDbLink('maps?data=' . $loc_id . $coords);
						$coords_link = $this->decorator->url($url, $coords_text);
					}
				}

				$newtext .= $coords_link ?? $coords_text;
			}
			else {
				$newtext .= $part;
			}
		}
		
		return $newtext;
	}*/

	/*protected function parseTagUrl($text) {
		$newtext = '';
		
		$parts = preg_split('/(\[url.*\].*\[\/url\])/U', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach ($parts as $part) {
			if (preg_match('/\[url(.*)\](.*)\[\/url\]/', $part, $matches)) {
				$attrs = trim($matches[1]);
				$content = $matches[2];
				
				$id = $content;

				if (preg_match('/id="(.*)"/', $attrs, $matches)) {
					$id = $matches[1];
				}

				if (strlen($id) > 0) {
					$newtext .= $this->decorator->url($id, $content);
				}
				else {
					$newtext .= $content;
				}
			}
			else {
				$newtext .= $part;
			}
		}
		
		return $newtext;
	}*/

	/*public function parseLinks($text) {
		$text = $this->parseTagLink($text);
		$text = $this->parseTagItem($text);
		$text = $this->parseTagNPC($text);
		$text = $this->parseTagSpell($text);
		$text = $this->parseTagQuest($text);
		$text = $this->parseTagCoords($text);
		$text = $this->parseTagUrl($text);

		return $text;
	}*/

	/*public function parseAsIs($nodeArray) {
		$resultStr = '';
		foreach ($nodeArray as $node) {
			$name = $node['name'];
			$attrs = $node['attrs'];
			$children = $node['children'];
			$tagData = $node['tagData'];

			$attrList = '';

			foreach ($attrs as $attrkey => $attrvalue) {
				$attrList .= " {$attrkey}=\"{$attrvalue}\"";
			}

			$childrenList = $this->parseAsIs($children);

			$resultStr .= "<{$name}{$attrList}>{$childrenList}" . $this->parseLinks($tagData) . "</{$name}>";
		}

		return $resultStr;
	}*/

	// вырезает из текста теги [tag][/tag]
	public function stripTags($text) {
		return preg_replace('/\[(.*)\](.*)\[\/(.*)\]/U', '\$2', $text);
	}

	public function parse($text) {
		// db replaces
		$text = $this->replaces($text);

		// wiki + wowhead [[tags]]
		$text = $this->parseDoubleBrackets($text);

		// all brackets are parsed at this point
		// titles
		// !! before linebreaks replacement !!
		$result = $this->parseTitles($text);
		$text = $result['text'];

		// markdown
		// !! before linebreaks replacement !!
		$text = $this->parseMarkdown($text);

		// \n -> br -> p
		$text = str_replace([ "\r\n", "\r", "\n" ], '<br/>', $text);

		// bb [tags]
		$text = $this->parseBrackets($text);
		

		// all text parsed
		$tbs = '<p>';
		$tbe = '</p>';

		$text = preg_replace('#(<br\s*/>){3,}#', '<br/><br/>', $text);
		//$text = preg_replace('#(<br/><br/><p)#', '<p', $text);
		//$text = preg_replace('#(/p><br/><br/>)#', '/p>', $text);
		$text = $tbs . preg_replace('#(<br/><br/>)#', $tbe . $tbs, $text) . $tbe;

		$replaces = [
			'<p><p' => '<p',
			'</p></p>' => '</p>',
			'<p><div' => '<div',
			'</div></p>' => '</div>',
			'<p><ul>' => '<ul>',
			'</ul></p>' => '</ul>',
		];

		foreach ($replaces as $key => $value) {
			$text = preg_replace('#(' . $key . ')#', $value, $text);
		}

		$result['text'] = $text;
		
		return $result;
	}

	protected function parseDoubleBrackets($text) {
		$newText = '';
		
		$parts = preg_split('/(\[\[.*\]\])/U', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach ($parts as $part) {
			$parsed = null;
			
			if (preg_match('/\[\[(.*)\]\]/', $part, $matches)) {
				$match = $matches[1];
				
				if (strlen($match) > 0) {
					$parsed = $this->parseDoubleBracketsMatch($match);
				}
			}
			
			$newText .= $parsed ?? $part;
		}
		
		return $newText;
	}
	
	protected function parseDoubleBracketsMatch($match) {
		$text = null;
		
		$mappings = [ 'ach' => 'achievement' ];
		
		$chunks = preg_split('/\|/', $match);
		$chunksCount = count($chunks);

		// анализируем первый элемент на наличие двоеточия ":"
		$tagChunk = $chunks[0];
		$tagParts = preg_split('/:/', $tagChunk);
		$tag = $tagParts[0];
		
		if (count($tagParts) == 1) {
			// статья
			$id = $tagChunk;
			$cat = '';
			$name = $chunks[$chunksCount - 1];

			if ($chunksCount > 2) {
				$cat = $chunks[1];
			}

			$idEsc = $this->fromSpaces($id);
			$catEsc = $this->fromSpaces($cat);
			$article = $this->db->getArticle($id, $cat);

			$text = $article
				? $this->decorator->articleUrl($name, $id, $idEsc, $cat, $catEsc, true)
				: $this->decorator->noArticleUrl($name, $id, $cat);
		}
		else {
			// тег с id
			// [[npc:27412|Слинкин Демогном]]
			// [[tag:id|content]]
			
			$id = $tagParts[1];

			if (strlen($id) > 0) {
				$content = ($chunksCount > 1) ? $chunks[1] : $id;

				// default text for tags
				// in most cases it's exactly what's needed
				$dbTag = $mappings[$tag] ?? $tag;
				$urlChunk = $dbTag . '=' . $id;
				$url = $this->getWebDbLink($urlChunk);
				$text = $this->decorator->url($url, $content, null, null, null, [ 'wowhead' => $urlChunk ]);

				// special treatment
				switch ($tag) {
					case 'item':
						if ($id > 0) {
							$sources = $this->db->getRecipesByItemId($id);
							if (is_array($sources) && count($sources) > 0) {
								$recipeData = $sources[0];
								$title = 'Рецепт: ' . $recipeData['name_ru'];
								$rel = 'spell=' . $recipeData['id'] . '&amp;domain=ru';
								
								$recipeUrl = $this->decorator->recipePageUrl($recipeData['id'], $title, $rel);
					
								// adding
								$text .= '&nbsp;' . $recipeUrl;
							}
						}

						break;

					case 'spell':
						// is spell is a recipe, link it to our recipe page
						$recipe = $this->db->getRecipe($id);
						
						if ($recipe) {
							$title = 'Рецепт: ' . $content; // $id
							$rel = 'spell=' . $id . '&amp;domain=ru';
							$recipeUrl = $this->decorator->recipePageUrl($id, $title, $rel, $content);
					
							// rewriting default
							$text = $recipeUrl;
						}

						break;

					case 'coords':
						if ($chunksCount > 2) {
							$x = $chunks[1];
							$y = $chunks[2];
							
							$coordsLink = null;
							$coordsText = $this->decorator->coordsBlock($x, $y);
	
							if (!is_numeric($id)) {
								$id = $this->db->getLocationId($id);
							}
	
							if ($id > 0) {
								$coords = '';
								if ($x > 0 && $y > 0) {
									$coords = ':' . ($x * 10) . ($y * 10);
								}
								
								$url = $this->getWebDbLink('maps?data=' . $id . $coords);
								$text = $this->decorator->url($url, $coordsText);
							}
						}

						break;

					case 'card':
						$url = $this->getSettings('legacy.hsdb_ru_link') . 'cards/' . $id;
						$text = $this->decorator->url($url, $content, null, 'hh-ttp');

						break;

					case 'news':
					case 'event':
					case 'stream':
						$text = $this->decorator->entityUrl("%{$tag}%/{$id}", $content);

						break;
						
					case 'tag':
						$id = $this->fromSpaces($id, '+');
						$text = $this->decorator->entityUrl("%{$tag}%/{$id}", $content);

						break;
				}
			}
		}
		
		return (strlen($text) > 0) ? $text : null;
	}

	protected function parseTitles($text) {
		$contents = [];

		$text = Text::processLines($text, function($lines) use (&$contents) {
			$results = [];
			
			$subtitleCount = 0;
			$subtitle2Count = 0;
			
			foreach ($lines as $line) {
				$line = trim($line);
				
				if (strlen($line) > 0) {
					$line = preg_replace_callback(
						'/^((\||#){2,})(.*)$/',
						function($matches) use (&$contents, &$subtitleCount, &$subtitle2Count) {
							$sticks = $matches[1];
							$content = trim($matches[3], ' |');
							
							$withContents = true;
							$label = null;
							
							if (substr($content, -1) == '#') {
								$withContents = false;
								$content = rtrim($content, '#');
							}
			
							if (strlen($sticks) == 2) {
								// subtitle
								if ($withContents === true) {
									$label = ++$subtitleCount;
									$subtitle2Count = 0;
				
									$contents[] = [
										'level' => 1,
										'label' => $label,
										'text' => strip_tags($content),
									];
								}
		 
								$line = $this->decorator->subtitleBlock($content, $label);
							}
							else if (strlen($sticks) == 3) {
								// subtitle2
								if ($withContents === true) {
									$label = $subtitleCount . '_' . ++$subtitle2Count;
				
									$contents[] = [
										'level' => 2,
										'label' => $label,
										'text' => strip_tags($content),
									];
								}
		
								$line = $this->decorator->subtitleBlock($content, $label, 2);
							}
							
							return $line;
						},
						$line
					);
				}
	
				$results[] = $line;
			}
			
			return $results;
		});

		return [ 'text' => $text, 'contents' => $contents ];
	}

	protected function replaces($text) {
		$replaces = $this->db->getReplaces();

		foreach ($replaces as $replace) {
			$text = str_replace($replace['first'], $replace['second'], $text);
		}

		return $text;
	}

	protected function parseUrlBB($text) {
		$newtext = '';
		
		$parts = preg_split('/(\[url.*\].*\[\/url\])/U', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach ($parts as $part) {
			if (preg_match('/\[url(.*)\](.*)\[\/url\]/', $part, $matches)) {
				$attrs = trim($matches[1]);
				$content = $matches[2];
				
				$id = $content;

				if (preg_match('/=(.*)/', $attrs, $matches)) {
					$id = $matches[1];
				}

				if (strlen($id) > 0) {
					$newtext .= $this->decorator->url($id, $content);
				}
				else {
					$newtext .= $content;
				}
			}
			else {
				$newtext .= $part;
			}
		}
		
		return $newtext;
	}

	protected function parseImgBB($text, $tag) {
		$newtext = '';
		
		$parts = preg_split("/(\[{$tag}.*\].*\[\/{$tag}\])/U", $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach ($parts as $part) {
			if (preg_match("/\[{$tag}(.*)\](.*)\[\/{$tag}\]/", $part, $matches)) {
				$attrs = preg_split("/\|/", $matches[1]);
				$source = $matches[2];

				if (strlen($source) > 0) {
					$width = 0;
					$height = 0;
					$alt = null;
					$thumb = null;
					
					foreach ($attrs as $attr) {
						if (is_numeric($attr)) {
							if ($width == 0) {
								$width = $attr;
							}
							else {
								$height = $attr;
							}
						}
						elseif (strpos($attr, 'http') === 0) {
							$thumb = $attr;
						}
						else {
							$alt = $attr;
						}
					}

					$newtext .= $this->decorator->image($tag, $source, $alt, $width, $height, $thumb);
				}
			}
			else {
				$newtext .= $part;
			}
		}
		
		return $newtext;
	}

	protected function parseColorBB($text) {
		$newtext = '';
		
		$parts = preg_split('/(\[color=.*\].*\[\/color\])/U', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach ($parts as $part) {
			if (preg_match('/\[color=(.*)\](.*)\[\/color\]/', $part, $matches)) {
				$color = trim($matches[1]);
				$content = $matches[2];
				
				if (strlen($color) > 0) {
					$newtext .= $this->decorator->colorBlock($color, $content);
				}
				else {
					$newtext .= $content;
				}
			}
			else {
				$newtext .= $part;
			}
		}
		
		return $newtext;
	}

	protected function parseQuoteBB($text, $quotename, $default = null) {
		$newtext = '';
		
		$parts = preg_split("/(\[{$quotename}[^\[]*\].*\[\/{$quotename}\])/U", $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach ($parts as $part) {
			if (preg_match("/\[{$quotename}([^\[]*)\](.*)\[\/{$quotename}\]/", $part, $matches)) {
				$attrs = preg_split('/\|/', $matches[1], -1, PREG_SPLIT_NO_EMPTY);
				$text = Text::trimBrs($matches[2]);

				if (strlen($text) > 0) {
					$author = null;
					$url = null;

					foreach ($attrs as $attr) {
						if (strpos($attr, 'http') === 0) {
							$url = $attr;
						}
						elseif (strlen($author) == 0) {
							$author = $attr;
						}
						else {
							$date = $attr;
						}
					}

					$newtext .= $this->decorator->quoteBlock($quotename, $text, $author ?? $default, $url, $date);
				}
			}
			else {
				$newtext .= $part;
			}
		}
		
		return $newtext;
	}

	protected function parseYoutubeBB($text) {
		$newtext = '';
		
		$parts = preg_split('/(\[youtube.*\].*\[\/youtube\])/U', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach ($parts as $part) {
			if (preg_match('/\[youtube(.*)\](.*)\[\/youtube\]/', $part, $matches)) {
				$attrs = preg_split('/\|/', $matches[1]);
				$code = $matches[2];

				if (strlen($code) > 0) {
					$width = 0;
					$height = 0;
					
					if (count($attrs) > 2) {
						$width = $attrs[1];
						$height = $attrs[2];
					}

					$newtext .= $this->decorator->youtubeBlock($code, $width, $height);
				}
			}
			else {
				$newtext .= $part;
			}
		}
		
		return $newtext;
	}

	protected function parseSpoilerBB($text) {
		$newtext = '';
		
		$parts = preg_split('/(\[spoiler.*\].*\[\/spoiler\])/U', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach ($parts as $part) {
			if (preg_match('/\[spoiler(.*)\](.*)\[\/spoiler\]/', $part, $matches)) {
				$attrs = trim($matches[1]);
				$content = Text::trimBrs($matches[2]);

				$label = null;
				if (preg_match('/=(.*)/', $attrs, $matches)) {
					$label = $matches[1];
				}

				$newtext .= $this->decorator->spoilerBlock($content, $label);
			}
			else {
				$newtext .= $part;
			}
		}
		
		return $newtext;
	}

	protected function parseListBB($text) {
		return preg_replace_callback(
			'/\[list(=1)?\](.*)\[\/list\]/Us',
			function($matches) {
				$ordered = strlen($matches[1]) > 0;
				$content = strstr($matches[2], '[*]');
				
				if ($content !== false) {
					$items = preg_split('/\[\*\]/', $content, -1, PREG_SPLIT_NO_EMPTY);
					$result = $this->decorator->list($items, $ordered);
				}

				return $result ?? 'Неверный формат списка!';
			},
			$text
		);
	}

	protected function parseBrackets($text) {
		$text = $this->parseYoutubeBB($text);
		$text = $this->parseColorBB($text);
		$text = $this->parseImgBB($text, 'img');
		$text = $this->parseImgBB($text, 'leftimg');
		$text = $this->parseImgBB($text, 'rightimg');
		$text = $this->parseUrlBB($text);
		$text = $this->parseQuoteBB($text, 'quote');
		$text = $this->parseQuoteBB($text, 'bluepost', 'Blizzard');
		$text = $this->parseSpoilerBB($text);
		$text = $this->parseListBB($text);

		return $text;
	}
	
	protected function parseListMD($text) {
		return Text::processLines($text, function($lines) {
			$results = [];
			$list = [];
			$ordered = null;

			$flush = function() use (&$list, &$ordered, &$results) {
				if (count($list) > 0) {
					$results[] = $this->decorator->list($list, $ordered);
					$list = [];
					$ordered = null;
				}
			};
			
			foreach ($lines as $line) {
				if (preg_match('/^(\*|-|\+|(\d+)\.)\s+(.*)$/', trim($line), $matches)) {
					$itemOrdered = strlen($matches[2]) > 0;

					if (count($list) > 0 && $ordered !== $itemOrdered) {
						$flush();
					}
					
					$list[] = $matches[3];
					$ordered = $itemOrdered;
				}
				else {
					$flush();
					$results[] = $line;
				}
			}
			
			$flush();

			return $results;
		});
	}
	
	protected function parseMarkdown($text) {
		$text = $this->parseListMD($text);

		return $text;
	}
	
	public function renderLinks($text) {
		$text = str_replace('%article%/', $this->legacyRouter->article(), $text);
		$text = str_replace('%news%/', $this->legacyRouter->news(), $text);
		$text = str_replace('%stream%/', $this->legacyRouter->stream(), $text);
		$text = str_replace('%event%/', $this->legacyRouter->event(), $text);
		$text = str_replace('%tag%/', $this->legacyRouter->tag(), $text);
		
		return $text;
	}

	/*public function convertArticleFromXml($text) {
		return Text::processLines($text, function($lines) {
			$results = [];
			
			foreach ($lines as $line) {
				$line = trim($line);
				
				if (strlen($line) > 0) {
					$removes = [ '<article', '</article', '<section', '<title' ];
					
					foreach ($removes as $remove) {
						if (strpos($line, $remove) === 0) {
							$line = null;
							break;
						}
					}
					
					if ($line) {
						$replaces = [
							'<property name="' => '[b]',
							'</property>' => '',
							'">' => ':[/b] ',
							'<text>' => '',
							'</text>' => '',
							'<subtitle>' => '||',
							'</subtitle>' => '||',
							'<subtitle2>' => '|||',
							'</subtitle2>' => '||',
							'[link id="' => '[[',
							'[link id=' => '[[',
							'[/link]' => ']]',
							'[spell]' => '[[spell:|',
							'[spell id="' => '[[spell:',
							'[/spell]' => ']]',
							'[item]' => '[[item:|',
							'[item id="' => '[[item:',
							'[/item]' => ']]',
							'[npc]' => '[[npc:|',
							'[npc id="' => '[[npc:',
							'[/npc]' => ']]',
							'[quest]' => '[[quest:|',
							'[quest id="' => '[[quest:',
							'[/quest]' => ']]',
							'"]' => '|',
							'" cat="' => '|',
							'[url id="' => '[url=',
						];
						
						foreach ($replaces as $from => $to) {
							$line = str_replace($from, $to, $line);
						}
						
						$line = preg_replace_callback('/\[\[(.*)\]\]/U', function($m) {
							$str = $this->toSpaces($m[1]);
							$str = str_replace(']', '|', $str);
							
							$result = '[[' . $str . ']]';
	
							return $result;
						}, $line);
					}
					
					$line = trim($line);
					
					if (strlen($line) > 0) {
						$results[] = $line;
					}
				}
				else {
					$results[] = $line;
				}
			}
			
			return $results;
		});
	}*/
}
