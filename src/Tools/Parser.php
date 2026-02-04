<?php

namespace SparkLocalize\Tools;

use SparkLocalize\Enums\HtmlTags;
use SparkLocalize\SparkLocalize;

class Parser {
	private array $tags;
	private array $options;

	public function __construct(
		array $input,
		array $options = [
			"htmlTags" => HtmlTags::Simplify
		]
	)
	{
		$this->options = $options;
		if (isset($options['htmlTags']) && $options['htmlTags'] === HtmlTags::Simplify) {
			$tag_count = 0;
			$this->tags = SparkLocalize::simplifyHtmlTags($input);
		}
	}

	public function parseCsv(string $output): array {
		$lines = preg_split('/\r?\n/', trim($output));
		$headers = str_getcsv(array_shift($lines));
		$entries = array_map('str_getcsv', $lines);

		/* Set up data structure and map column indices to point to place in tree */
		$data_structure = [];
		$mapping = [];
		for ($i = 0; $i < count($headers); $i++) {
			$parts = explode('.', $headers[$i]);
			$parent = &$data_structure;
			for ($j = 0; $j < count($parts); $j++) {
				if ($j === count($parts) - 1) {
					if (preg_match('/^\d+$/', $parts[$j])) {
						$mapping[$i] = &$parent;
						$parent = "";
					}
					else {
						$mapping[$i] = &$parent[$parts[$j]];
						$parent[$parts[$j]] = "";
					}
					break;
				}
				elseif (!isset($parent[$parts[$j]])) {
					$parent[$parts[$j]] = [];
				}
				$child = &$parent[$parts[$j]];
				unset($parent);
				$parent = &$child;
				unset($child);
			}
			unset($parent);
		}

		/* Fill data structure with values */
		$data = [];
		foreach ($entries as $row => $entry) {
			foreach ($entry as $i => $value) {
				if (isset($this->options['htmlTags']) && $this->options['htmlTags'] === HtmlTags::Simplify) {
					$value = $this->complicateHtmlTags($value);
				}

				if (isset($mapping[$i])) {
					if ($mapping[$i] !== "")
						$mapping[$i] .= " ";
					$mapping[$i] .= $value;
				}
				else {
					throw new \Exception("Invalid CSV format: missing header for column $i, row $row");
				}
			}
			$data[$row] = unserialize(serialize($data_structure));
			foreach ($mapping as &$value) {
				$value = "";
				unset($value);
			}
		}

		return $data;
	}

	/** Replace number tags back with original HTML */
	private function complicateHtmlTags(string $input): string {
		foreach ($this->tags as $n => $tag) {
			$attributes_string = "";
			foreach ($tag->attributes as $attr => $val) {
				$attributes_string .= " $attr=\"$val\"";
			}
			$input = preg_replace(
				'/<' . $n . '>/',
				'<' . $tag->tagName . $attributes_string . '>',
				$input
			);
			$input = preg_replace(
				'/<\/' . $n . '>/',
				'</' . $tag->tagName . '>',
				$input
			);
		}
		if (preg_match('/<\/?\d+>/', $input, $matches) === 1) {
			throw new \Exception("Unable to complicate HTML tag \"$matches[0]\": There are more HTML tags in the CSV than in the original input");
		}
		return $input;
	}
}
