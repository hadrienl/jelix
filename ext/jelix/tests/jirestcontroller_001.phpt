--TEST--
Check for jIRestController interface
--SKIPIF--
<?php if (!extension_loaded("jelix")) print "skip"; ?>
--FILE--
<?php 
if(interface_exists('jIRestController', false)) echo "YES"; else echo "NO";
?>
--EXPECT--
YES