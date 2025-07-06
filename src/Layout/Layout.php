<?php

namespace SparkLocalize\Layout;

use ScssPhp\ScssPhp\Compiler;
use SparkLocalize\Enums\FormType;

abstract class Layout {
	public function __construct(
		public array $styles = [
			'assets/style.css'
		]
	) {}
	protected function buildBootstrap(string $customCss, array $components): string {
		$scssCompiler = new Compiler();
		$scssCompiler->setImportPaths($_ENV['COMPOSER_VENDOR_DIR'] ?? __DIR__ . '/../../../../twbs/bootstrap/scss');
		return $scssCompiler->compileString(
			$customCss .
			'@import "mixins/banner";
			@include bsBanner("");' .
			implode(
				PHP_EOL,
				array_map(
					fn(string $component) => '@import "' . $component . '";',
					$components
				)
			)
		)->getCss();
	}
	abstract public function renderHeader(
		string $title,
		array  $targetLanguages,
		string $heading = '',
		string $description = '',
		string $extra = ''
	): string;
	protected function renderData(array $targetLanguages): string {
		return '<script>const targetLanguages = ' . json_encode($targetLanguages) . ';</script>';
	}
	/**
	 * Renders the body of the page.
	 * 
	 * @param array<string, string> $input
	 * An associative array of strings you want translated.
	 * 
	 * The key should be your own unique identifier for the string,
	 * and the value should be the string you want translated.
	 * 
	 * e.g., `['greeting' => 'Hello, world!']`
	 * 
	 * @param string $destination
	 * The URL to submit the form to.
	 * (For Netlify, the page to go to after submission.)
	 * 
	 * @param FormType $formType
	 * The type of form to render.
	 * Valid values are:
	 * - Html: A standard HTML form. (default)
	 * - Netlify: A Netlify form.
	 * 
	 * @param string $sourceLanguage
	 * The ISO 639 language code of the language you are
	 * translating from.
	 * 
	 * @return string 
	 */
	abstract public function renderBody(
		array    $input,
		string   $destination,
		FormType $formType = FormType::Html,
		string   $sourceLanguage = 'en'
	): string;
	abstract protected function renderItem(
		string $key,
		string $text,
		string $sourceLanguage = 'en'
	): string;
	protected function renderItems(
		array  $input,
		string $sourceLanguage = 'en'
	): string {
		return implode(PHP_EOL, array_map(
			fn(string $key, string $text) => $this->renderItem($key, htmlspecialchars($text), $sourceLanguage),
			array_keys($input),
			$input
		));
	}
	abstract public function renderFooter(): string;
	public function getStyle(): string {
		return $this->buildBootstrap(
			'$color-mode-type: media-query;',
			[
				'functions',
				'variables',
				'variables-dark',
				'maps',
				'mixins',
				'utilities',

				'root',
				'reboot',
				'type',
				'containers',
				'tables',
				'buttons',
				'card',

				'helpers',
				'utilities/api'
			]
		);
	}
	abstract public function getScript(): string;
}

?>