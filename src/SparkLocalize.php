<?php

namespace SparkLocalize;

use SparkLocalize\Layout\Layout;
use SparkLocalize\Layout\DefaultLayout;
use SparkLocalize\Layout\FormType;

enum HtmlTags {
	case Keep;
	case Remove;
	case Simplify;
}

class SparkLocalize {
	public function __construct(
		private string $title = 'Spark Localize',
		private string $heading = 'Spark Localize',
		private string $description = '',
		private string $extra = '',
		private Layout $layout = new DefaultLayout()
	) {}

	/**
	 * Converts the text data to a full web page.
	 * 
	 * @param array<string, string|array> $input
	 * An associative array of strings you want translated.
	 * 
	 * The key should be your own unique identifier for the string,
	 * and the value should be the string you want translated.
	 * 
	 * e.g., `['greeting' => 'Hello, world!']`
	 * 
	 * @param string[]|array<string, string|array> $targetLanguages
	 * A list of language codes you want to give the option to
	 * translate the strings into or an associative array of
	 * language codes as the keys and the partially-translated
	 * strings (in the same structure as $input) as the value.
	 * 
	 * @param string $destination
	 * The URL to submit the form to.
	 * (For Netlify, the page to go to after submission.)
	 * 
	 * @param array{
	 * 	"splitSentences": bool,
	 * 	"htmlTags": HtmlTags,
	 * 	"formType": FormType
	 * } $options
	 * 
	 * @return string The Spark Localize web page HTML.
	 */
	public function render(
		array $input,
		array $targetLanguages,
		string $destination,
		array $options = [
			"splitSentences" => true,
			"htmlTags" => HtmlTags::Simplify,
			"formType" => FormType::Html
		]
	): string {
		/* Simplify HTML tags */
		if (!isset($options["htmlTags"])) {
			$options["htmlTags"] = HtmlTags::Simplify;
		}
		switch ($options["htmlTags"]) {
			case HtmlTags::Keep:
				break;
			case HtmlTags::Remove:
				array_walk_recursive($input, function(&$value) {
					$value = strip_tags($value);
				});
				foreach ($targetLanguages as $language => $_) {
					if (!is_array($targetLanguages[$language])) {
						continue;
					}
					array_walk_recursive($targetLanguages[$language], function(&$value) {
						$value = strip_tags($value);
					});
				}
				break;
			case HtmlTags::Simplify:
				$tag_count = 0;
				self::simplifyHtmlTags($input, $tag_count);
				foreach ($targetLanguages as $language => $_) {
					if (!is_array($targetLanguages[$language])) {
						continue;
					}
					$tag_count = 0;
					self::simplifyHtmlTags($targetLanguages[$language], $tag_count);
				}
				break;
		}
		
		/* Split sentences */
		if (!isset($options["splitSentences"])) {
			$options["splitSentences"] = true;
		}
		if ($options["splitSentences"]) {
			$input = self::splitSentences($input, $options);
		}

		/* Flatten input */
		$input = self::flattenInput($input);

		return
			$this->layout->renderHeader(
				$this->title,
				$targetLanguages,
				$this->heading,
				$this->description,
				$this->extra
			) .
			$this->layout->renderBody(
				$input,
				$destination,
				$options['formType'] ?? FormType::Html
			) .
			$this->layout->renderFooter();
	}

	private static function simplifyHtmlTags(array &$input, int &$tag_count): void {
		array_walk_recursive($input, function(&$value) use (&$tag_count) {
			$value = preg_replace('/^<[\w-]+>(.*)<\/[\w-]+>$/', '$1', $value);
			$tag = null;
			$closing = false;
			$unclosed = [];
			for ($i = 0; $i < strlen($value); $i++) {
				if ($value[$i] === '<') {
					$tag = '';
				}
				elseif ($tag === '' && $value[$i] === '/') {
					$closing = true;
				}
				elseif ($value[$i] === '>') {
					if (!$tag) {
						$tag = null;
						continue;
					}
					if (!$closing) {
						$unclosed[] = $n = ++$tag_count;
					}
					else {
						$n = array_pop($unclosed);
					}
					$value = substr_replace(
						$value,
						$n,
						$i - strlen($tag),
						strlen($tag)
					);
					$i -= strlen($tag) - strlen($n);
					$tag = null;
					$closing = false;
				}
				elseif ($tag !== null)  {
					$tag .= $value[$i];
				}
			}
		});
	}

	private static function splitSentences(array $input, array &$options = []): array {
		array_walk_recursive($input, function(&$value) {
			$split_value = preg_split(
				'/([.?!]"?)\s+/',
				$value,
				flags: PREG_SPLIT_DELIM_CAPTURE
			);
			$first_split_length = count($split_value);
			for ($i = 0; $i < $first_split_length - 1; $i += 2) {
				$split_value[$i] .= $split_value[$i + 1];
				unset($split_value[$i + 1]);
			}
			if (count($split_value) > 1) {
				$value = array_values($split_value);
			}
		});

		if (
			isset($options["htmlTags"]) &&
			$options["htmlTags"] === HtmlTags::Simplify
		) {
			array_walk_recursive($input, function(&$value) {
				$value = preg_replace('/^<[\w-]+>(.*)<\/[\w-]+>$/', '$1', $value);
			});
		}
		return $input;
	}

	private static function flattenInput(array $input): array {
		foreach ($input as $key => $value) {
			if (is_array($value)) {
				$value = self::flattenInput($value);
				$key_indices = array_flip(array_keys($input));
				$input = array_slice(
					$input,
					0,
					$key_indices[$key],
					true
				) +
				array_combine(
					array_map(fn($subKey) => "$key.$subKey", array_keys($value)),
					array_values($value)
				) +
				($key_indices[$key] + 1 < count($key_indices) ?
					array_slice(
						$input,
						$key_indices[$key] + 1,
						preserve_keys: true
					) :
					[]);
			}
		}
		return $input;
	}

	public function getStyle(): string {
		return $this->layout->getStyle();
	}

	public function getScript(): string {
		return $this->layout->getScript();
	}
}