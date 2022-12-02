<?php

testing::case("a", function() {
	$a = 0;

	testing::assert($a == 1);
});

testing::case("b", function() {
	$a = 1;

	testing::assert($a == 1);
});
