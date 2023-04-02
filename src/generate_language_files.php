<?php

use Language\LanguageBatchBo;

chdir(__DIR__);

include('../vendor/autoload.php');

try {
	$languageBatchBo = LanguageBatchBo::getInstance();
	$languageBatchBo->generateLanguageFiles();
	$languageBatchBo->generateAppletLanguageXmlFiles();

} catch (Exception $exception) {
	printf('Error: %s%s', $exception->getMessage(), PHP_EOL);
	printf('File: %s%s', $exception->getFile(), PHP_EOL);
	printf('Line: %s%s', $exception->getLine(), PHP_EOL);

}
