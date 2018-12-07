<?php

class RoboticArm
{

	/**
	 * The slots and their blocks in the current state.
	 *
	 * @var array
	 */
	protected $slots = [];

	/**
	 * The commands that have been run
	 *
	 * @var array
	 */
	protected $history = [];

	/**
	 * Set the number of slots. Set persist to true in order to retain any blocks during resizing. If reducing the size, any
	 * block outside of the new range will be lost.
	 *
	 * @param $number_of_slots the number of slots that we should now have
	 * @param bool $persist whether or not to persist the existing blocks in the slots
	 * @return bool|int the number of slots or false if invalid
	 */
	public function size($number_of_slots, $persist = false) {

		//TODO: Give a valid example to help the user.
		if (!is_int($number_of_slots) && !ctype_digit($number_of_slots)) {
			var_export($number_of_slots);
			echo "size is not a valid integer\n";
			return false;
		}

		if ($number_of_slots <= 0) {
			echo "size must be greater than 0\n";
			return false;
		}

		$previous_state = $this->slots;

		// if we are resetting or starting, simply setup the slots
		if (!$persist) {
			$this->slots = [];
			for ($i = 1; $i <= $number_of_slots; $i++)
				$this->slots[] = 0;

		// if we are adding slots, simply add the slots
		} elseif ($number_of_slots > count($this->slots)) {
			for ($i = count($this->slots); $i <= $number_of_slots; $i++)
				$this->slots[] = 0;
		// if we are removing slots, pop them off the end of the array
		} elseif ($number_of_slots < count($this->slots)) {
			for ($i = count($this->slots); $i > $number_of_slots; $i--)
				array_pop($this->slots);
		}

		$this->saveCommand('size', func_get_args(), $previous_state);

		$this->outputState();

		return count($this->slots);
	}

	/**
	 * Add a block to the specified slot.
	 *
	 * @param $key the key of the slot to which the block should be added
	 * @return bool the number of blocks in the slot or false if the input is invalid
	 */
	public function add($key) {

		// TODO: Give a valid example to help the user.
		if (!isset($this->slots[$key])){
			echo "Invalid Slot\n";
			return false;
		}

		$previous_state = $this->slots;

		$this->slots[$key]++;

		$this->saveCommand('add', func_get_args(), $previous_state);

		$this->outputState();

		return $this->slots[$key];
	}

	/**
	 * Move a block from one slot to another
	 *
	 * @param $from the slot which a block will be moved FROM
	 * @param $to the slot which the block will be moved TO
	 * @return bool
	 */
	public function mv($from, $to) {

		// TODO: Combine all errors in case there are more than one.

		if (!isset($this->slots[$from])) {
			// TODO: Give a valid example to help the user.
			echo "Invalid FROM slot\n";
			return false;
		}
		if (!$this->slots[$from]) {
			// TODO: Give a valid example to help the user.
			echo "FROM slot is empty\n";
			return false;
		}
		if (!isset($this->slots[$to])) {
			// TODO: Give a valid example to help the user.
			echo "TO slot does not exist";
			return false;
		}

		$previous_state = $this->slots;

		$this->slots[$from]--;
		$this->slots[$to]++;

		$this->saveCommand('mv', func_get_args(), $previous_state);

		$this->outputState();

		return $this->slots[$to];
	}

	/**
	 * Remove a block from a slot.
	 *
	 * @param $key the key of the slot from which to remove the block
	 * @return bool|int returns the number of blocks in the slot or false if the slot does not exist.
	 */
	public function rm($key) {

		if (!isset($this->slots[$key])) {
			// TODO: Give a valid example to help the user.
			echo "That slot does not exist\n";
			return false;
		}

		$previous_state = $this->slots;

		$this->slots[$key]--;

		$this->saveCommand('rm', func_get_args(), $previous_state);

		$this->outputState();

		return $this->slots[$key] = max(0, $this->slots[$key]);
	}

	/**
	 * Replays the last n commands. If the number is greater than or equal to the total number of commands entered,
	 * all of the commands will be replayed.
	 *
	 * @param $number_of_commands the number of commands to replay
	 * @return bool true if $number_of_commands is a valid number, false if not
	 */
	public function replay($number_of_commands) {
		if (!is_int($number_of_commands) && !ctype_digit($number_of_commands)) {
			// TODO: Give a valid example to help the user.
			echo "size is not a valid integer\n";
			return false;
		}

		$actual_number_to_replay = min(count($this->history), $number_of_commands);

		$to_replay = [];

		for ($i = 1; $i <= $actual_number_to_replay; $i++) {
			$command = array_pop($this->history);
			$this->undoOne($command, false);
			array_unshift($to_replay, $command);
		}

		echo "Replaying\n";
		foreach ($to_replay as $command) {
			echo "\n";
			echo "> {$command['command']} " . implode(" ", $command['params']) . "\n\n";
			call_user_func_array([$this, $command['command']], $command['params']);
			echo "\n";
		}

		return true;
	}

	/**
	 * Undo the last n commands
	 *
	 * @param $number_of_commands the number of commands to undo
	 * @return bool true if $number_of_commands is a valid number, otherwise false
	 */
	public function undo($number_of_commands) {
		if (!is_int($number_of_commands) && !ctype_digit($number_of_commands)) {
			// TODO: Give a valid example to help the user.
			echo "size is not a valid integer\n";
			return false;
		}

		$actual_number_to_undo = min(count($this->history), $number_of_commands);

		for ($i = 1; $i <= $actual_number_to_undo; $i++) {
			$command = array_pop($this->history);
			echo "Undoing {$command['command']} " . implode(" ", $command['params']) . "\n\n";
			$this->undoOne($command);
			echo "\n";
		}

		return true;
	}

	/**
	 * Undo a command.
	 *
	 * @param $command the command to undo
	 * @param bool $output_result whether or not to output the result
	 */
	protected function undoOne($command, $output_result = true) {
		$this->slots = $command['previous_state'];
		if ($output_result)
			$this->outputState();
	}

	/**
	 * Saves a command to history.
	 *
	 * @param $command the method that was executed
	 * @param $params the parameters that were sent to the method
	 * @param $previous_state the state of the slots before the command was executed
	 */
	protected function saveCommand($command, $params, $previous_state) {
		$this->history[] = [
			"command" => $command,
			"params" => $params,
			"previous_state" => $previous_state
		];
	}

	/**
	 * Output the current state of the slots
	 */
	protected function outputState() {
		foreach ($this->slots as $key => $slot) {
			echo "$key: ";
			for ($i = 1; $i <= $slot; $i++) {
				echo "X";
			}
			echo "\n";
		}
	}


	public function list_valid_commands() {
		echo "size [n] - sets the number of slots.\n";
		echo "add [key] - adds a block to the specified slot.\n";
		echo "mv [from] [to] - moves a block from one slot to another.\n";
		echo "rm [key] - removes a block from the specified slot\n";
		echo "replay [n] - replays the last n commands\n";
		echo "undo [n] - undoes the last n commands\n";
		echo "exit - exits the game\n";
	}

}