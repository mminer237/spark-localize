<?php

namespace SparkLocalize;

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
	 * @return string The Spark Localize web page HTML.
	 */
	public function render(array $input, array $targetLanguages): string {
		return
			$this->layout->renderHeader($this->title) .
			$this->layout->renderBody($input, $targetLanguages) .
			$this->layout->renderFooter();
	}
}