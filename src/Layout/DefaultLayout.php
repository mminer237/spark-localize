<?php

namespace SparkLocalize\Layout;

class DefaultLayout extends Layout {
	public function renderHeader(string $title, array $targetLanguages, string $description = '', string $extra = ''): string {
		return <<<HTML
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title>$title</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
HTML . ($description ? "
		<meta name=\"description\" content=\"$description\">" : '') . "\n\t\t" .
		implode("\n\t\t", array_map(
			fn($style) => "<link rel=\"stylesheet\" href=\"$style\">",
		$this->styles)) .
		"\n\t\t" .
		$this->renderData($targetLanguages) . '
		<script src="assets/script.js"></script>' .
		($extra ? "
		$extra" : '') . PHP_EOL . <<<HTML
	</head>
	<body class="d-flex flex-column min-vh-100 element">
		<header>
			<h1 class="text-center">$title</h1>
HTML . ($description ? "
			<p>$description</p>" : '') . PHP_EOL . <<<HTML
		</header>
		<main>
HTML;
	}

	public function renderBody(array $input, string $sourceLanguage = 'en'): string {
		return '
			<section class="container p-4">
				<h2>Translate '.\Locale::getDisplayLanguage($sourceLanguage).' to <input type="text" id="targetLanguage" name="targetLanguage"></h2>
				<form action="">
					<div class="table-responsive card mb-2">
						<table class="table mb-0">
							<thead>
								<tr>
									<th scope="col">Source</th>
									<th scope="col">Translation</th>
								</tr>
							</thead>
							<tbody>' . PHP_EOL . $this->renderItems($input, $sourceLanguage) . "
							</tbody>
						</table>
					</div>
					<button type=\"submit\" class=\"btn btn-primary\">Submit</button>
				</form>
			</section>\n";
	}

	protected function renderItem(string $key, string $text, string $sourceLanguage = 'en'): string {
		return <<<HTML
									<tr>
										<td lang="$sourceLanguage">$text</td>
										<td>
											<input type="text" class="form-control" name="$key">
										</td>
									</tr>
		HTML;
	}

	public function renderFooter(): string {
		return <<<HTML
		</main>
		<footer class="mt-auto text-center">
			<p>Powered by <a href="https://github.com/mminer237/spark-localize">Spark Localize</a></p>
		</footer>
	</body>
</html>
HTML;
	}
}
