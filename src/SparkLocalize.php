<?php

namespace SparkLocalize;

use SparkLocalize\Layout\Layout;
use SparkLocalize\Layout\DefaultLayout;

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
	 * @param array<string, string> $input
	 * An associative array of strings you want translated.
	 * 
	 * The key should be your own unique identifier for the string,
	 * and the value should be the string you want translated.
	 * 
	 * e.g., `['greeting' => 'Hello, world!']`
	 * 
	 * @param string[]|array<string, string> $targetLanguages
	 * A list of language codes you want to give the option to
	 * translate the strings into or an associative array of
	 * language codes as the keys and the partially-translated
	 * strings (in the same structure as $input) as the value.
	 * 
	 * @param array{
	 * 	"splitSentences"?: bool,
	 * 	"htmlTags"?: HtmlTags
	 * }
	 * 
	 * @return string The Spark Localize web page HTML.
	 */
	public function render(
		array $input,
		array $targetLanguages,
		string $destination,
		array $options = [
			"splitSentences" => true,
			"htmlTags" => HtmlTags::Simplify
		]
	): string {
		/* Simplify HTML tags */
		switch ($options["htmlTags"]) {
			case HtmlTags::Keep:
				break;
			case HtmlTags::Remove:
				array_walk_recursive($input, function(&$value) {
					$value = strip_tags($value);
				});
				break;
			case HtmlTags::Simplify:
				$tag_count = 0;
				array_walk_recursive($input, function(&$value) use (&$tag_count) {
					$value = preg_replace('/^<[\w-]+>(.*)<\/[\w-]+>$/', '$1', $value);
					$tag = null;
					$closing = false;
					$closing_n = null;
					for ($i = 0; $i < strlen($value); $i++) {
						if ($value[$i] === '<') {
							$tag = '';
						}
						elseif ($tag === '' && $value[$i] === '/') {
							$closing = true;
							if ($closing_n === null) {
								$closing_n = $tag_count;
							}
						}
						elseif ($value[$i] === '>') {
							if (!$tag) {
								$tag = null;
								continue;
							}
							if (!$closing) {
								$tag_count++;
								$n = $tag_count;
								if ($closing_n !== null)
									$closing_n++;
							}
							else {
								$n = $closing_n--;
								if ($closing_n === 0) {
									$closing_n = null;
								}
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
				break;
		}
		
		/* Split sentences */
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
				$destination
			) .
			$this->layout->renderFooter();
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