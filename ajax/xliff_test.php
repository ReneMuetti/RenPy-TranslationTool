<?php
ini_set("memory_limit", '512M');
ini_set("max_execution_time", '0');

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'ajax_xliff_test');

// ######################### REQUIRE BACK-END ############################
chdir('../');
require_once( realpath('include/global.php') );

// ########################### INIT VARIABLES ############################

// ########################### IDENTIFY USER #############################
loggedInOrReturn();

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

//$currDir = '/1680845359';
//$currFile = '/aliceroom.xliff';

//$processor = new Xliff_Process();
//$processor -> setProcessedFilename($currDir, $currFile);

echo '<pre>';

// first step
//$processor -> parseCurrentXliffFile();

// secound step
//$processor -> processCurrentXliffFile();

// output RPY
//$processor = new Xliff_Download();
//echo $processor -> downloadTranslationFromFileAsRPY('game/scripts/engine/items.rpy', 2, '', false);
//echo $processor -> downloadTranslationFromFileAsRPY('game/scripts/story_01/bathroom.rpy', 2, '', false);
//echo $processor -> downloadTranslationFromFileAsRPY('game/scripts/story_01/dreams.rpy', 2, '', false);
//echo $processor -> downloadTranslationFromFileAsRPY('game/scripts/engine/characters.rpy', 2, '', false);

echo '<pre>';
