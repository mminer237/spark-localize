<?php

namespace SparkLocalize;

abstract class Layout {
	abstract public function renderHeader(
		string $title,
		string $description = '',
		string $style = '/assets/default.css',
		string $extra = ''
	): string;
	abstract public function renderBody(
		array  $input,
		array  $targetLanguages,
		string $sourceLanguage = 'en'
	): string;
	abstract public function renderItem(
		string $key,
		string $text,
		string $sourceLanguage = 'en'
	): string;
	abstract public function renderFooter(): string;
}

?>