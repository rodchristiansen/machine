<?php

use CFPropertyList\CFPropertyList;
use munkireport\processors\Processor;

class Machine_processor extends Processor
{
    /**
     * Process data sent by postflight
     *
     * @param string data
     * @author abn290
     **/
    public function run($data)
    {
        // Detect the operating system
        $isWindows = strpos($data, '{') === 0; // Check if the data starts with a curly brace (JSON)

        if ($isWindows) {
            $mylist = json_decode($data, true);
        } else {
            $parser = new CFPropertyList();
            $parser->parse($data, CFPropertyList::FORMAT_XML);
            $mylist = $parser->toArray();
        }

        $mylist['serial_number'] = $this->serial_number;

        // Set default computer_name
        if (! isset($mylist['computer_name']) or trim($mylist['computer_name']) == '') {
            $mylist['computer_name'] = 'No name';
        }

        // Fix Apple Silicon processor count - processor count is the first number
        if (isset($mylist['number_processors'])) {
            $mylist['number_processors'] = preg_replace('/^[^0-9]*(\d+).*/', '$1', $mylist['number_processors']);
        }

        // Convert memory string to int
        if (isset($mylist['physical_memory'])) {
            $mylist['physical_memory'] = intval($mylist['physical_memory']);
        }

        // Convert OS version to int (for macOS)
        if (!$isWindows && isset($mylist['os_version'])) {
            $digits = explode('.', $mylist['os_version']);
            $mult = 10000;
            $mylist['os_version'] = 0;
            foreach ($digits as $digit) {
                $mylist['os_version'] += $digit * $mult;
                $mult = $mult / 100;
            }
        }

        // Dirify buildversion
        if (isset($mylist['buildversion'])) {
            $mylist['buildversion'] = preg_replace('/[^A-Za-z0-9]/', '', $mylist['buildversion']);
        }

        // Retrieve machine record (if existing)
        try {
            $machine = Machine_model::select()
                ->where('serial_number', $this->serial_number)
                ->firstOrFail();
        } catch (\Throwable $th) {
            $machine = new Machine_model();
        }

        // Check if we need to retrieve model from Apple (only for macOS)
        if (!$isWindows && $this->should_run_model_description_lookup($machine)){
            require_once(__DIR__ . '/helpers/model_lookup_helper.php');
            $mylist['machine_desc'] = machine_model_lookup($this->serial_number);
        } 

        $machine->fill($mylist)->save();
    }

    function should_run_model_description_lookup($machine)
    {
        return ( 
            ! $machine['machine_desc'] or 
            in_array($machine['machine_desc'], ['model_lookup_failed', 'unknown_model'])
        );
    }
}