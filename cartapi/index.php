<?php

use akiyatkin\pay\Pay;
use akiyatkin\meta\Meta;


$meta = new Meta();

$meta->addAction('pay', function () {
	return $this->ret();
})
