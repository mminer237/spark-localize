<?php

use PhpTui\Term\Actions;
use PhpTui\Term\Event\CharKeyEvent;
use PhpTui\Term\Event\CodedKeyEvent;
use PhpTui\Term\KeyCode;
use PhpTui\Term\Terminal;
use PhpTui\Tui\Bridge\PhpTerm\PhpTermBackend;
use PhpTui\Tui\DisplayBuilder;
use PhpTui\Tui\Extension\Core\Widget\BlockWidget;
use PhpTui\Tui\Extension\Core\Widget\GridWidget;
use PhpTui\Tui\Extension\Core\Widget\List\ListItem;
use PhpTui\Tui\Extension\Core\Widget\List\ListState;
use PhpTui\Tui\Extension\Core\Widget\ListWidget;
use PhpTui\Tui\Extension\Core\Widget\ParagraphWidget;
use PhpTui\Tui\Layout\Constraint;
use PhpTui\Tui\Text\Text;
use PhpTui\Tui\Text\Title;
use PhpTui\Tui\Widget\Borders;
use PhpTui\Tui\Widget\Direction;

require_once __DIR__ . '/../vendor/autoload.php';

// If no arguments are passed
if ($argc < 2) {
	echo "Usage: php merge.php <translations.json|translations/> [-l language] [-k language_key] [-o output.json] [-m master.json|master/]" . PHP_EOL;
	exit(1);
}

/* Read translations path */
$input_path = $argv[1];

/* Read language option */
$language = null;
if ($argc >= 3) {
	for ($i = 2; $i < $argc; $i++) {
		if ($argv[$i] === '-l' && isset($argv[$i + 1])) {
			$language = $argv[$i + 1];
			break;
		}
	}
}

/* Read language key option */
$language_key = null;
if ($argc >= 3) {
	for ($i = 2; $i < $argc; $i++) {
		if ($argv[$i] === '-k' && isset($argv[$i + 1])) {
			$language_key = $argv[$i + 1];
			break;
		}
	}
}

/* Read output path option */
$output_path = null; // (If null, will output to stdout)
if ($argc >= 3) {
	for ($i = 2; $i < $argc; $i++) {
		if ($argv[$i] === '-o' && isset($argv[$i + 1])) {
			$output_path = $argv[$i + 1];
			break;
		}
	}
}

/* Read master translation path option */
$master_path = null;
if ($argc >= 3) {
	for ($i = 2; $i < $argc; $i++) {
		if ($argv[$i] === '-m' && isset($argv[$i + 1])) {
			$master_path = $argv[$i + 1];
			break;
		}
	}
}

/* Load translations */
$translations = [];
if ($master_path !== null) {
	if (!file_exists($master_path)) {
		echo "Master file does not exist: $master_path" . PHP_EOL;
		exit(1);
	}
	$translations[0] = json_decode(file_get_contents($master_path), true);
	$number_of_master_files = 1;
}
else {
	$number_of_master_files = 0;
}
if (is_dir($input_path)) {
	$files = scandir($input_path);
	if (count($files) === 0) {
		echo "No files found in directory: $input_path" . PHP_EOL;
		exit(1);
	}
	foreach ($files as $file) {
		if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
			$translations[] = json_decode(file_get_contents("$input_path/$file"), true);
		}
	}
	if (count($translations) === $number_of_master_files) {
		if ($language == null) {
			echo "No translation  or folders found in directory: $input_path" . PHP_EOL;
			echo "(Language not specified)" . PHP_EOL;
			exit(1);
		}
		foreach ($files as $folder) {
			if (is_dir("$input_path/$folder")) {
				if ($folder === $language) {
					$files = scandir("$input_path/$folder");
					foreach ($files as $file) {
						if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
							$translations[] = json_decode(file_get_contents("$input_path/$folder/$file"), true);
						}
					}
					break;
				}
			}
		}
	}
	if (count($translations) === $number_of_master_files) {
		echo "No translation files found in directory: $input_path" . PHP_EOL;
		exit(1);
	}
}
else {
	if (!file_exists($input_path)) {
		echo "Input file does not exist: $input_path" . PHP_EOL;
		exit(1);
	}
	$input_data = json_decode(file_get_contents($input_path), true);
	if (!is_array($input_data) || count($input_data) === 0) {
		echo "No JSON data found in file: $input_path" . PHP_EOL;
		exit(1);
	}
	if (is_array($input_data) && count($input_data) > 0) {
		if (!isset($input_data[0])) {
			// It is an object, not an array, so presumably
			// it is just a single translation file
			$translations[] = $input_data;
		}
		else {
			if (isset($language_key)) {
				if (!isset($language)) {
					echo "Language key specified without language." . PHP_EOL;
					exit(1);
				}
				// Add the translations with the same language
				foreach ($input_data as $translation) {
					if (isset($translation[$language_key]) && $translation[$language_key] === $language) {
						$translations[] = $translation;
					}
				}
				if (count($translations) === $number_of_master_files) {
					echo "No items in file with language key \"$language_key\" set to \"$language\"." . PHP_EOL;
					exit(1);
				}
			}
			else {
				foreach ($input_data as $item) {
					// No language key specified, so just add all items
					$translations[] = $item;
				}
			}
		}
	}
}

if (count($translations) <= 1) {
	echo "Did not find multiple translations to merge." . PHP_EOL;
	exit(1);
}
else {
	echo "Found " . count($translations) . " translations to merge." . PHP_EOL;
}

$unmerged = [];
$results = [];
function compare_transations($translations, $path) {
	global $unmerged, $results;
	for ($i = 0; $i < count($translations); $i++) {
		foreach ($translations[$i] as $key => $value) {
			$compiled_path = $path === null ? $key : "$path.$key";
			if (is_array($value)) {
				$array_values = [];
				$type_error = false;
				for ($j = $i; $j < count($translations); $j++) {
					if (isset($translations[$j][$key])) {
						if (!is_array($translations[$j][$key])) {
							// Array and string conflict
							$type_error = true;
						}
						$array_values[] = $translations[$j][$key];
					}
				}
				if ($type_error) {
					$unmerged[$compiled_path] = $array_values;
				}
				else {
					compare_transations($array_values, $compiled_path);
				}
			}
			else {
				$values = [];
				for ($j = $i; $j < count($translations); $j++) {
					if (!empty($translations[$j][$key])) {
						$values[] = $translations[$j][$key];
					}
				}
				if (count(array_unique($values)) > 1) {
					$unmerged[$compiled_path] = $values;
				}
				else if (count($values) === 0) {
					// No translation for this key, so just skip
				}
				else {
					add_result($compiled_path, $values[0]);
				}
			}
		}
	}
}
function add_result($path, $value) {
	global $results;
	$results_pointer = &$results;
	foreach (explode('.', $path) as $part) {
		$results_pointer = &$results_pointer[$part];
	}
	$results_pointer = $value;
	unset($results_pointer);
}
echo "Comparing translations..." . PHP_EOL;
compare_transations($translations, null);

$unmerged_total = count($unmerged);
$unmerged_total_digits = floor(log10($unmerged_total) + 1);
echo "Found $unmerged_total unmerged items." . PHP_EOL;
if ($unmerged_total > 0) {
	/* Start TUI app */
	$terminal = Terminal::new();
	$display = DisplayBuilder::default(PhpTermBackend::new($terminal))->build();

	try {
		// Enable "raw" mode to disable default terminal behavior
		$terminal->execute(Actions::cursorHide());
		$terminal->enableRawMode();
	} catch (Throwable $err) {
		$terminal->disableRawMode();
		$terminal->execute(Actions::cursorShow());
		throw $err;
		exit(1);
	}

	$i = 1;
	foreach ($unmerged as $path => $conflicts) {
		$display->clear();
		$selected = 0;
		$conflict_index = 0;
		$display->draw(
			$block = BlockWidget::default()
				->borders(Borders::ALL)
				->titles(Title::fromString("Spark Localize Merge"))
				->widget(GridWidget::default()
					->direction(Direction::Vertical)
					->constraints(
						Constraint::length(2),
						Constraint::min(1),
						Constraint::length(1)
					)
					->widgets(
						ParagraphWidget::fromText(Text::fromString("Merge conflict " . str_pad((string) $i, $unmerged_total_digits, pad_type: STR_PAD_LEFT) . " of $unmerged_total")),
						$list = ListWidget::default()
							->state(new ListState(0, $selected))
							->items(
								...array_map(
									function($conflict) use (&$conflict_index) {
										if (is_array($conflict)) {
											$conflict = json_encode($conflict, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
										}
										return ListItem::new( Text::fromString(++$conflict_index . ': ' .$conflict));
									},
									$conflicts
								)
							),
						ParagraphWidget::fromText(Text::fromString("q: quit, " . /* e: edit in editor,*/ "↑/↓: change selection, enter: choose selection")),
					)
				)
		);
		while (true) {
			while (null !== $event = $terminal->events()->next()) {
				if ($event instanceof CharKeyEvent) {
					if ($event->char === 'q') {
						break(3);
					}
					elseif ($event->char === 'e') {
						// TODO: Edit the conflicting translations in the user's default editor
					}
					elseif ($event->char >= '1' && $event->char <= '9') {
						$event_num = (int) $event->char;
						if ($event_num <= count($conflicts)) {
							$selected = $event_num - 1;
							$list->state->selected = $selected;
							$display->draw($block);
							add_result($path, $conflicts[$selected]);
							break(2);
						}
					}
				}
				if ($event instanceof CodedKeyEvent) {
					if ($event->code === KeyCode::Esc) {
						break(3);
					}
					elseif ($event->code === KeyCode::Up) {
						$selected = max(0, $selected - 1);
						$list->state->selected = $selected;
						$display->draw($block);
					}
					elseif ($event->code === KeyCode::Down) {
						$selected = min(count($conflicts) - 1, $selected + 1);
						$list->state->selected = $selected;
						$display->draw($block);
					}
					elseif ($event->code === KeyCode::Enter) {
						add_result($path, $conflicts[$selected]);
						break(2);
					}
				}
			}
			usleep(10000);
		}
		$i++;
	}
}
$terminal->disableRawMode();
$terminal->execute(Actions::cursorShow());

if ($output_path !== null) {
	file_put_contents($output_path, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
else {
	echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo PHP_EOL;
