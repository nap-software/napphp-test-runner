<?php

/**
 * nap-software php test runner version 1.0.0
 * 
 * Remarks:
 * 
 * Assertions made with testing:assert() must always be a ONELINER.
 */

class NAPSoftwareTestingAssertion extends Exception {
	public function __construct($message) {
		parent::__construct($message);
	}
}

abstract class testing {
	static public function assert($expr) {
		if (!$expr) {
			throw new NAPSoftwareTestingAssertion("Assertion failed.");
		}
	}

	static public function arrayEqual($actual, $expected) {
		$actual_keys = array_keys($actual);
		$expected_keys = array_keys($expected);

		if (sizeof($actual_keys) !== sizeof($expected_keys)) {
			fwrite(STDERR, "Key size mismatch\n");

			return false;
		}

		foreach ($expected_keys as $expected_key_name) {
			if (!array_key_exists($expected_key_name, $actual)) {
				fwrite(STDERR, "Key not existing\n");

				return false;
			}

			$actual_value = $actual[$expected_key_name];
			$expected_value = $expected[$expected_key_name];

			if (is_array($actual_value)) {
				if (!self::arrayEqual($actual_value, $expected_value)) {
					fwrite(STDERR, "Array value not equal\n");

					return false;
				}
			} else if ($actual_value !== $expected_value) {
				fwrite(STDERR, "Value not equal\n");

				return false;
			}
		}

		return true;
	}

	static public function case($label, $fn) {
		$ctx = &$GLOBALS["NAPSoftware_napphp_testing_context"];

		if (!is_array($ctx)) {
			throw new Exception("Bogus use of testing::case().");
		}

		array_push($ctx, [
			"label" => $label,
			"fn"    => $fn
		]);
	}
}

function loadTestsFromDirectory($directory_path) {
	$src_entries = scandir($directory_path);
	$module_names = [];
	$ret = [];

	foreach ($src_entries as $src_entry) {
		if (substr($src_entry, 0, 1) === ".") continue;

		if (is_dir("$directory_path/$src_entry")) {
			array_push($module_names, $src_entry);
		}
	}

	foreach ($module_names as $module_name) {
		$ret[$module_name] = [];

		$module_functions = array_filter(scandir("$directory_path/$module_name/"), function($entry) {
			return substr($entry, -4, 4) === ".php";
		});

		foreach ($module_functions as $module_function) {
			$module_function_name = substr($module_function, 0, strlen($module_function) - 4);
			$ret[$module_name][$module_function_name] = [];

			$GLOBALS["NAPSoftware_napphp_testing_context"] = &$ret[$module_name][$module_function_name];

			$include_path = "$directory_path/$module_name/$module_function";

			// load without leaking variables to this scope
			(function() use ($include_path) {
				require $include_path;
			})();
		}
	}

	return $ret;
}

function printStackTrace($exception) {
	$origin = NULL;

	foreach ($exception->getTrace() as $entry) {
		$function = $entry["function"] ?? "";
		$class = $entry["class"] ?? "";
		$type = $entry["type"] ?? "";

		if ($function !== "assert") continue;
		if ($class !== "testing") continue;
		if ($type !== "::") continue;

		$origin = [$entry["file"], $entry["line"]];
	}

	if (!$origin) return;

	list($file, $line) = $origin;

	$file_contents = file($file);

	if (!is_array($file_contents)) return;

	$origin_assert_line = trim($file_contents[$line - 1] ?? "");

	// padding
	fwrite(STDERR, "           ");

	fwrite(STDERR, "\033[0;31mAssertion failed: $origin_assert_line\033[0;0m\n");
}

function runModuleTests(&$context, $module_name, $module_tests) {
	foreach ($module_tests as $function_name => $tests) {
		fwrite(STDERR, "    - $function_name\n");

		foreach ($tests as $test) {
			$label = str_pad($test["label"]." ", 110, ".", STR_PAD_RIGHT);

			fwrite(STDERR, "        :: $label ");

			$fn = $test["fn"];

			try {
				$fn();
				fwrite(STDERR, "\033[0;32mpass\033[0;0m\n");

				++$context["num_passed_tests"];
			} catch (NAPSoftwareTestingAssertion $e) {
				fwrite(STDERR, "\033[0;31mfail\033[0;0m\n");

				printStackTrace($e);

				++$context["num_failed_tests"];
			} catch (Exception $e) {
				fwrite(STDERR, "\033[0;31merror\033[0;0m\n");

				var_dump($e);
				exit(127);
			}
		}
	}
}

function runLoadedTests($loaded_tests) {
	$context = [
		"num_failed_tests" => 0,
		"num_passed_tests" => 0
	];

	foreach ($loaded_tests as $module_name => $module_tests) {
		fwrite(STDERR, "* $module_name\n");

		runModuleTests($context, $module_name, $module_tests);
	}

	fwrite(STDERR, "Num Tests Passed: ".$context["num_passed_tests"]."\n");
	fwrite(STDERR, "Num Tests Failed: ".$context["num_failed_tests"]."\n");

	if ($context["num_failed_tests"] > 0) {
		exit(1);
	}
}
