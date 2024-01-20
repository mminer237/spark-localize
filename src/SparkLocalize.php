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
	 * @param string[] $targetLanguages
	 * A list of language codes you want to give the option to
	 * translate the strings into.
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
		array $options = [
			"splitSentences" => true,
			"htmlTags" => HtmlTags::Simplify
		]
	): string {
		/* Split sentences */
		if ($options["splitSentences"]) {
			array_walk_recursive($input, function(&$value) {
				$split_value = preg_split(
					'/([.?!]"?)\s+/',
					$value,
					flags: PREG_SPLIT_DELIM_CAPTURE
				);
				for ($i = 0; $i < count($split_value) - 1; $i += 2) {
					$split_value[$i] .= $split_value[$i + 1];
					unset($split_value[$i + 1]);
				}
				if (count($split_value) > 1) {
					$value = array_values($split_value);
				}
			});
		}
		print_r($input);

		$input = self::flattenInput($input);
		print_r($input);

		return
			$this->layout->renderHeader($this->title) .
			$this->layout->renderBody($input, $targetLanguages) .
			$this->layout->renderFooter();
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
}