<?php

chdir(__DIR__);

include('../vendor/autoload.php');

$languageBatchBo = \Language\LanguageBatchBo::getInstance();
$languageBatchBo->generateLanguageFiles();
$languageBatchBo->generateAppletLanguageXmlFiles();