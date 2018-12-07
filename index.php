<?php

// Load our RoboticArm class and instantiate the object
require_once __DIR__ . DIRECTORY_SEPARATOR . "RoboticArm.php";
$robotic_arm = new RoboticArm();

// Welcome the user.
echo "ROBOTIC ARM\n\n";
echo "Enter the size to begin\nHINT: size [n]\n";

// Make sure they start with the size command
while(true) {

	// give the user a prompt
	echo "> ";

	// get and parse the input
	$line = trim(fgets(STDIN));
	$argv = explode(" ", $line);

	// validate
	if ($argv[0] !== 'size') {
		echo "You must enter the size to begin. Please try again\nHINT: size [n]\n";
		continue;
	}

	echo "\n";

	// run the command
	$command = array_shift($argv);
	if ($robotic_arm->size(implode(" ", $argv)) !== false) {
		echo "\n";
		break;
	}

}

// let the user play the game until they 'exit'
while (true) {

	// give the user a prompt.
	echo "> ";

	// get and parse the input
	$line = trim(fgets(STDIN));
	$argv = explode(" ", $line);

	// pull out the command
	$command = array_shift($argv);

	// if it is an exit command, say goodbye
	if ($command === 'exit')
		die("Thank you for playing!\n");

	// validate
	if (!method_exists($robotic_arm, $command) || !is_callable([$robotic_arm, $command])) {
		echo "\nInvalid Command. Please try again\n";
		$robotic_arm->list_valid_commands();
		echo "\n";
		continue;
	}

	// validate the parameters
	$reflection_method = new ReflectionMethod($robotic_arm, $command);
	$number_of_params = $reflection_method->getNumberOfParameters();
	$number_of_required_params = $reflection_method->getNumberOfRequiredParameters();

	// too many params
	if (count($argv) > $number_of_params) {
		echo "\nToo many parameters sent. Please try again\n";
		$robotic_arm->list_valid_commands();
		echo "\n";
		continue;
	}

	// too few params
	if (count($argv) < $number_of_required_params) {
		echo "\nNot enough parameters sent. Please try again\n";
		$robotic_arm->list_valid_commands();
		echo "\n";
		continue;
	}

	echo "\n";

	// run the command
	call_user_func_array([$robotic_arm, $command], $argv);

	echo "\n";
}