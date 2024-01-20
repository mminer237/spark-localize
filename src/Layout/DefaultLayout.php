<?php

namespace SparkLocalize\Layout;

class DefaultLayout extends Layout {
	public function renderHeader(string $title, string $description = '', string $extra = ''): string {
		return <<<HTML
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title>$title</title>
HTML . ($description ? "
		<meta name=\"description\" content=\"$description\">" : '') . "\n\t\t" .
		implode("\n\t\t", array_map(
			fn($style) => "<link rel=\"stylesheet\" href=\"$style\">",
		$this->styles)) .
		($extra ? "
		$extra" : '') . PHP_EOL . <<<HTML
	</head>
	<body>
		<header>
			<h1>$title</h1>
HTML . ($description ? "
			<p>$description</p>" : '') . PHP_EOL . <<<HTML
		</header>
		<main>
HTML;
	}

	public function renderBody(array $input, array $targetLanguages, string $sourceLanguage = 'en'): string {
		return '
			<section>
				<select>
					' .
				implode("\n\t\t\t\t\t", array_map(
					fn(string $lang) => "<option value=\"$lang\">$lang</option>",
				$targetLanguages)) . '
				</select>
				<table>
					<thead>
						<tr>
							<th>Source</th>
							<th>Translation</th>
						</tr>
					</thead>
					<tbody>' . PHP_EOL . $this->renderItems($input, $sourceLanguage) . "
					</tbody>
				</table>
			</section>\n";
	}

	protected function renderItem(string $key, string $text, string $sourceLanguage = 'en'): string {
		return <<<HTML
								<tr>
									<td>$text</td>
									<td>
										<input type="text" name="$key">
									</td>
								</tr>
		HTML;
	}

	public function renderFooter(): string {
		return <<<HTML
		</main>
		<footer>
			<p>Powered by Spark Localize</p>
		</footer>
	</body>
</html>
HTML;
	}
}
