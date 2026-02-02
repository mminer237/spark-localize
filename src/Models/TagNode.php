<?php

namespace SparkLocalize\Models;

class TagNode {
	/**
	 * @var string $tagName The name of the HTML tag.
	 * @var array<string, string> $attributes
	 * An associative array of HTML attributes
	 * e.g., ['class' => 'my-class']).
	 */
	public function __construct(
		public string $tagName,
		public array  $attributes = []
	) {}
}

?>