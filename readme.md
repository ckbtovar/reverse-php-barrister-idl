# Transliterate Barrister IDL and PHP

The goal of this project is to be able to translate back-and-forth between
[Barrister](http://barrister.bitmechanic.com/) IDL files and PHP code.

The includes:

 * IDL -> PHP Class Skeleton
 * IDL -> Barrister Code Completion Class Skeleton
 * PHP Class Skeleton -> IDL

Usage:

Run idl-json-to-php.php script with "make" as the first argument.

The only required arguments are an input JSON file (generated from an IDL using Barrister) and an output directory.
Optionally, you can specify a package (namespace) using dot notation.

For example, if you want the auto-generate code to belong to the Foo\Bar namespace you would enter:

$> php idl-json-to-php.php make idl.json code Foo.Bar

This would read the idl.json file, parse structs, enums, and services into separate classes under the Foo\Bar namespace
and store them in the "code" directory.
