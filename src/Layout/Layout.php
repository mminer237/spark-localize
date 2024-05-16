<?php

namespace SparkLocalize\Layout;

require_once __DIR__ . '/../../vendor/autoload.php';
use ScssPhp\ScssPhp\Compiler;

abstract class Layout {
	public function __construct(
		public array $styles = [
			'assets/bootstrap.css'
		]
	) {
		$this->buildBootstrap(
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
	protected function buildBootstrap(string $customCss, array $components): bool {
		$scssCompiler = new Compiler();
		$scssCompiler->setImportPaths( __DIR__ . '/../../vendor/twbs/bootstrap/scss/');
		file_put_contents(
			'assets/bootstrap.css',
			$scssCompiler->compileString(
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
			)->getCss()
		);
		return true;
	}
	abstract public function renderHeader(
		string $title,
		array  $targetLanguages,
		string $description = '',
		string $extra = ''
	): string;
	protected function renderData(array $targetLanguages): string {
		return '<script>const targetLanguages = ' . json_encode($targetLanguages) . ';</script>';
	}
	abstract public function renderBody(
		array  $input,
		string $sourceLanguage = 'en'
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
	abstract public function getScript(): string;
}

?>