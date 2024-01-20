<?php

namespace SparkLocalize\Layout;

abstract class Layout {
	public function __construct(
		public array $styles = [
			'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
			'assets/default.css'
		]
	) {}
	abstract public function renderHeader(
		string $title,
		string $description = '',
		string $extra = ''
	): string;
	abstract public function renderBody(
		array  $input,
		array  $targetLanguages,
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
			fn(string $key, string $text) => $this->renderItem($key, $text, $sourceLanguage),
			array_keys($input),
			$input
		));
	}
	abstract public function renderFooter(): string;
}

?>