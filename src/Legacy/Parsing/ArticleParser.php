<?php

namespace App\Legacy\Parsing;

use Warcry\Contained;

class ArticleParser extends Contained {
	const SPACE_CHAR = '_';

	protected $decorator;

	public function __construct($container) {
		parent::__construct($container);

		$this->decorator = $this->legacyDecorator; // from container
	}

	public function parseXML($input) {
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
	}

	// заменяет экранирующие символы $space на пробелы
	public function toSpaces($text, $space = self::SPACE_CHAR) {
		$text = stripslashes($text);
		return preg_replace("/{$space}/", ' ', $text);
	}

	// заменяет пробелы на экранирующие символы $space
	public function fromSpaces($text, $space = self::SPACE_CHAR) {
		return preg_replace('/\s+/u', $space, $text);
	}

	protected function parseTagLink($text) {
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
	}
	
	protected function getWebDbLink($appendix) {
		return $this->getSettings('legacy.webdb_ru_link') . $appendix;
	}

	protected function parseTagItem($text)	{
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
	}

	protected function parseTagNPC($text) {
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
	}

	protected function parseTagSpell($text) {
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
	}

	protected function parseTagQuest($text) {
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
	}

	protected function parseTagCoords($text) {
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
	}

	protected function parseTagUrl($text) {
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
	}

	public function parseLinks($text) {
		$text = $this->parseTagLink($text);
		$text = $this->parseTagItem($text);
		$text = $this->parseTagNPC($text);
		$text = $this->parseTagSpell($text);
		$text = $this->parseTagQuest($text);
		$text = $this->parseTagCoords($text);
		$text = $this->parseTagUrl($text);

		return $text;
	}

	public function parseAsIs($nodeArray) {
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
	}

	// вырезает из текста теги [tag][/tag]
	public function stripTags($text) {
		return preg_replace('/\[(.*)\](.*)\[\/(.*)\]/', '\$2', $text);
	}

	public function parseBB($input) {
		$text = $input;
		
		$text = str_replace([ "\r\n", "\r", "\n" ], '<br/>', $text);

		// other replaces
		$text = $this->replaces($text);

		$text = $this->parseDoubleBrackets($text);
		$text = $this->parseBrackets($text);

		// titles
		$result = $this->parseTitles($text);

		$text = $result['text'];

		$tbs = "<p>";
		$tbe = "</p>";

		$text = preg_replace('#(<br/><br/><br/>)#', '<br/><br/>', $text);
		//$text = preg_replace('#(<br/><br/><p)#', '<p', $text);
		//$text = preg_replace('#(/p><br/><br/>)#', '/p>', $text);
		$text = $tbs . preg_replace('#(<br/><br/>)#', $tbe . $tbs, $text) . $tbe;
		
		$text = preg_replace('#(<p><p)#', '<p', $text);
		$text = preg_replace('#(</p></p>)#', '</p>', $text);
		$text = preg_replace('#(<p><div)#', '<div', $text);
		$text = preg_replace('#(</div></p>)#', '</div>', $text);
		
		$text = str_replace('<p><ul>', '<ul>', $text);
		$text = str_replace('</ul></p>', '</ul>', $text);
		
		$result['text'] = $text;
		
		return $result;
	}

	protected function parseDoubleBrackets($text) {
		$newtext = '';
		
		$parts = preg_split('/(\[\[.*\]\])/U', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach ($parts as $part) {
			if (preg_match('/\[\[(.*)\]\]/', $part, $matches)) {
				$content = $matches[1];
				$content_parts = preg_split('/\|/', $content);

				// анализируем первый элемент на наличие двоеточия ":"
				$tag = $content_parts[0];
				$tag_parts = preg_split('/:/', $tag);
				$tag_prefix = $tag_parts[0];
				if (count($tag_parts) == 1 || !in_array($tag_prefix, [ 'npc', 'item', 'spell', 'quest', 'coords', 'zone', 'ach', 'card', 'news' ])) {
					// статья
					$id = $content_parts[0];
					$cat = '';
					$name = $content_parts[count($content_parts) - 1];

					if (count($content_parts) == 3) {
						$cat = $content_parts[1];
					}

					$id_esc = $this->fromSpaces($id);
					$cat_esc = $this->fromSpaces($cat);
					$article = $this->db->getArticle($id, $cat);

					if ($article) {
						$newtext .= $this->decorator->articleUrl($name, $id, $id_esc, $cat, $cat_esc, true);
					}
					else {
						$newtext .= $this->decorator->noArticleUrl($name, $id, $cat);
					}
				}
				else {
					// другая сущность
					//
					// например:
					//
					// [[npc:27412|Слинкин Демогном]]
					// [[tag_prefix:tag_parts_1|content_parts_1]]
					
					$id = $tag_parts[1];
					$content = $id;
					if (count($content_parts) > 1) {
						$content = $content_parts[1];
					}

					if (strlen($id) > 0) {
						$args = 'search?q=' . $this->fromSpaces($id, '+');
						$wowheadTitle = "Искать {$id} на Wowhead";

						switch ($tag_prefix) {
							case 'npc':
								if (!is_numeric($id)) {
									$id = $this->db->getNPCId($id);
								}

								if ($id > 0) {
									$args = 'npc=' . $id;
								}
								else {
									$suffix = '#npcs';
									$title = $wowheadTitle;
								}
							
								$url = $this->getWebDbLink($args . $suffix);
								$newtext .= $this->decorator->url($url, $content, $title);

								break;

							case 'item':
								if (!is_numeric($id)) {
									$id = $this->db->getItemId($id);
								}

								if ($id > 0) {
									$args = 'item=' . $id;
								}
								else {
									$title = $wowheadTitle;
								}
							
								$url = $this->getWebDbLink($args);
								$newtext .= $this->decorator->url($url, $content, $title);

								if ($id > 0) {
									$sources = $this->db->getRecipesByItemId($id);
									if (is_array($sources) && count($sources) > 0) {
										$recipe_data = $sources[0];
										$title = 'Рецепт: ' . $recipe_data['name_ru'];
										$rel = 'spell=' . $recipe_data['id'] . '&amp;domain=ru';
										$recipeUrl = $this->decorator->recipePageUrl($recipe_data['id'], $title, $rel);
							
										$newtext .= '&nbsp;' . $recipeUrl;
									}
								}

								break;

							case 'spell':
								$recipe = $this->db->getRecipe($id);
								
								if ($recipe) {
									$title = 'Рецепт: ' . $content; // $id
									$rel = 'spell=' . $id . '&amp;domain=ru';
									$recipeUrl = $this->decorator->recipePageUrl($id, $title, $rel, $content);
							
									$newtext .= $recipeUrl;
								}
								else {
									if (!is_numeric($id)) {
										$id = $this->db->getSpellId($id);
									}

									if ($id > 0) {
										$args = 'spell=' . $id;
									}
									else {
										$suffix = '#abilities';
										$title = $wowheadTitle;
									}
									
									$url = $this->getWebDbLink($args . $suffix);
									$newtext .= $this->decorator->url($url, $content, $title);
								}

								break;

							case 'quest':
								if (!is_numeric($id)) {
									$id = $this->db->getQuestId($id);
								}

								if ($id > 0) {
									$args = 'quest=' . $id;
								}
								else {
									$suffix = '#quests';
									$title = $wowheadTitle;
								}
								
								$url = $this->getWebDbLink($args . $suffix);
								$newtext .= $this->decorator->url($url, $content, $title);

								break;

							case 'coords':
								$x = $content_parts[1];
								$y = $content_parts[2];
								
								$coords_link = null;
								$coords_text = $this->decorator->coordsBlock($x, $y);

								if (!is_numeric($id)) {
									$id = $this->db->getLocationId($id);
								}

								if ($id > 0) {
									$coords = '';
									if ($x > 0 && $y > 0) {
										$coords = ':' . ($x * 10) . ($y * 10);
									}
									
									$url = $this->getWebDbLink('maps?data=' . $id . $coords);
									$coords_link = $this->decorator->url($url, $coords_text);
								}

								$newtext .= $coords_link ?? $coords_text;

								break;

							case 'zone':
								if (is_numeric($id)) {
									$args = 'zone=' . $id;
								}
								else {
									$suffix = '#zones';
									$title = $wowheadTitle;
								}
								
								$url = $this->getWebDbLink($args . $suffix);
								$newtext .= $this->decorator->url($url, $content, $title);

								break;

							case 'ach':
								if (is_numeric($id)) {
									$args = 'achievement=' . $id;
								}
								else {
									$suffix = '#achievements';
									$title = $wowheadTitle;
								}
								
								$url = $this->getWebDbLink($args . $suffix);
								$newtext .= $this->decorator->url($url, $content, $title);

								break;

							case 'card':
								if (is_numeric($id)) {
									$args = 'card=' . $id;
								}
								else {
									$title = "Искать {$id} на Hearthhead";
								}
								
								$url = $this->getSettings('legacy.hsdb_ru_link') . $args;
								$newtext .= $this->decorator->url($url, $content, $title);

								break;

							case 'news':
								$url = $this->legacyRouter->news($id);
								$url = $this->legacyRouter->abs($url);
								
								$newtext .= $this->decorator->url($url, $content);

								break;
						}
					}
					else {
						$newtext .= $part;
					}
				}
			}
			else {
				$newtext .= $part;
			}
		}
		
		return $newtext;
	}

	protected function parseTitles($text) {
		$contents = [];
		
		$subtitle_count = 0;
		$subtitle2_count = 0;
	
		$newtext = '';
		
		$parts = preg_split('/(\|{2,3}.*\|{2})/U', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach ($parts as $part) {
			if (preg_match('/(\|{2,3})(.*)\|{2}/', $part, $matches)) {
				$sticks = $matches[1];
				$content = $matches[2];

				if (strlen($sticks) == 2) {
					// subtitle
					$label = ++$subtitle_count;
					$subtitle2_count = 0;

					$contents_link['level'] = 1;
					$contents_link['label'] = $label;
					$contents_link['text'] = strip_tags($content);
					
					$contents[] = $contents_link;

					$newtext .= $this->decorator->subtitleBlock($content, $label);
				}
				else if (strlen($sticks) == 3) {
					// subtitle2
					$label = $subtitle_count . '_' . ++$subtitle2_count;

					$contents_link['level'] = 2;
					$contents_link['label'] = $label;
					$contents_link['text'] = strip_tags($content);
					
					$contents[] = $contents_link;

					$newtext .= $this->decorator->subtitleBlock($content, $label, 2);
				}
			}
			else
			{
				$newtext .= $part;
			}
		}
		
		return [ 'text' => $newtext, 'contents' => $contents ];
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

	protected function parseQuoteBB($text, $quotename) {
		$newtext = '';
		
		$parts = preg_split("/(\[{$quotename}[^\[]*\].*\[\/{$quotename}\])/U", $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		
		foreach ($parts as $part) {
			if (preg_match("/\[{$quotename}([^\[]*)\](.*)\[\/{$quotename}\]/", $part, $matches)) {
				$attrs = preg_split('/\|/', $matches[1]);
				$text = $matches[2];

				if (strlen($text) > 0) {
					$author = '';
					$url = '';
					
					if (count($attrs) > 1) {
						$author = $attrs[1];
					}
					
					if (count($attrs) > 2) {
						$url = $attrs[2];
					}
					
					if (count($attrs) > 3) {
						$date = $attrs[3];
					}

					$newtext .= $this->decorator->quoteBlock($quotename, $text, $author, $url, $date);
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
				$content = $matches[2];
				
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

	protected function parseBrackets($text) {
		$text = $this->parseYoutubeBB($text);
		$text = $this->parseColorBB($text);
		$text = $this->parseImgBB($text, 'img');
		$text = $this->parseImgBB($text, 'leftimg');
		$text = $this->parseImgBB($text, 'rightimg');
		$text = $this->parseUrlBB($text);
		$text = $this->parseQuoteBB($text, 'quote');
		$text = $this->parseQuoteBB($text, 'bluepost');
		$text = $this->parseSpoilerBB($text);

		return $text;
	}
	
	public function renderArticleLinks($text) {
		return str_replace('%article%/', $this->legacyRouter->article(), $text);
	}
}
