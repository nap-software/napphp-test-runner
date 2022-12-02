#!/usr/bin/env php
<?php

require_once __DIR__."/index.php";

$tests = loadTestsFromDirectory(__DIR__."/__tests__");

runLoadedTests($tests);
