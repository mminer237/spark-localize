<?php

namespace SparkLocalize;

class DefaultLayout extends Layout
{
	public function __construct() {

	}

	public function renderHeader(string $title, string $description = '', string $style = '/assets/default.css', string $extra = ''): string {
		return <<<HTML
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title>$title</title>
		<meta name="description" content="$description">
		<link rel="stylesheet" href="$style">
		$extra
	</head>
	<body>
		<header>
			<h1>$title</h1>
			<p>$description</p>
		</header>
		<main>
HTML;
	}

	public function renderBody(array $input, array $targetLanguages, string $sourceLanguage = 'en'): string {
		return <<<HTML
		<table>

		</table>
		HTML;
	}

	public function renderItem(string $key, string $text, string $sourceLanguage = 'en'): string {
		return <<<HTML

		HTML;
	}

	public function renderFooter(): string {
		return <<<HTML
		</main>
	</body>
	<footer>
		<p>Powered by Spark Localize</p>
	</footer>
</html>
HTML;
	}
}
