<?php

// This is the database connection configuration.
return array(
//	'connectionString' => 'sqlite:'.dirname(__FILE__).'/../data/testdrive.db',
	// uncomment the following lines to use a MySQL database

	'connectionString' => 'mysql:host=licenta.cgacwseeu4zt.eu-central-1.rds.amazonaws.com;dbname=licenta',
	'emulatePrepare' => true,
	'username' => 'admin',
	'password' => 'parola05',
	'charset' => 'utf8',

);