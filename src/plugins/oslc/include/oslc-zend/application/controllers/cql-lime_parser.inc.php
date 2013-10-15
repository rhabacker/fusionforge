<?php

/*

DON'T EDIT THIS FILE!

This file was automatically generated by the Lime parser generator.
The real source code you should be looking at is in one or more
grammar files in the Lime format.

THE ONLY REASON TO LOOK AT THIS FILE is to see where in the grammar
file that your error happened, because there are enough comments to
help you debug your grammar.

If you ignore this warning, you're shooting yourself in the brain,
not the foot.

*/

class cql_lime_parser extends lime_parser {
var $qi = 0;
var $i = array (
  0 =>
  array (
    'query_in' => 's 1',
    'term' => 's 27',
    'identifier' => 's 5',
    'value' => 's 17',
    'word' => 's 18',
    'integer' => 's 8',
    'string' => 's 9',
    'query' => 's 28',
    '\'start\'' => 'a \'start\'',
  ),
  1 =>
  array (
    'modifiers' => 's 2',
    'boolean_op' => 's 3',
    'and_kw' => 's 21',
    'sort' => 's 22',
    '\'/\'' => 's 23',
    '#' => 'r 0',
  ),
  2 =>
  array (
    '#' => 'r 1',
  ),
  3 =>
  array (
    'term' => 's 4',
    'identifier' => 's 5',
    'value' => 's 17',
    'word' => 's 18',
    'integer' => 's 8',
    'string' => 's 9',
  ),
  4 =>
  array (
    '\'/\'' => 'r 3',
    'and_kw' => 'r 3',
    '#' => 'r 3',
  ),
  5 =>
  array (
    'operator' => 's 6',
    'equal_kw' => 's 10',
    'not_kw' => 's 11',
    'lt_kw' => 's 13',
    'gt_kw' => 's 15',
  ),
  6 =>
  array (
    'value' => 's 7',
    'integer' => 's 8',
    'string' => 's 9',
  ),
  7 =>
  array (
    'and_kw' => 'r 4',
    '\'/\'' => 'r 4',
    '#' => 'r 4',
  ),
  8 =>
  array (
    'and_kw' => 'r 21',
    '\'/\'' => 'r 21',
    '#' => 'r 21',
  ),
  9 =>
  array (
    'and_kw' => 'r 22',
    '\'/\'' => 'r 22',
    '#' => 'r 22',
  ),
  10 =>
  array (
    'integer' => 'r 6',
    'string' => 'r 6',
  ),
  11 =>
  array (
    'equal_kw' => 's 12',
  ),
  12 =>
  array (
    'integer' => 'r 7',
    'string' => 'r 7',
  ),
  13 =>
  array (
    'equal_kw' => 's 14',
    'integer' => 'r 8',
    'string' => 'r 8',
  ),
  14 =>
  array (
    'integer' => 'r 10',
    'string' => 'r 10',
  ),
  15 =>
  array (
    'equal_kw' => 's 16',
    'integer' => 'r 9',
    'string' => 'r 9',
  ),
  16 =>
  array (
    'integer' => 'r 11',
    'string' => 'r 11',
  ),
  17 =>
  array (
    'and_kw' => 'r 5',
    '\'/\'' => 'r 5',
    '#' => 'r 5',
  ),
  18 =>
  array (
    '\':\'' => 's 19',
    'equal_kw' => 'r 15',
    'not_kw' => 'r 15',
    'lt_kw' => 'r 15',
    'gt_kw' => 'r 15',
    '#' => 'r 15',
  ),
  19 =>
  array (
    'word' => 's 20',
  ),
  20 =>
  array (
    'equal_kw' => 'r 16',
    'not_kw' => 'r 16',
    'lt_kw' => 'r 16',
    'gt_kw' => 'r 16',
    '#' => 'r 16',
  ),
  21 =>
  array (
    'word' => 'r 12',
    'integer' => 'r 12',
    'string' => 'r 12',
  ),
  22 =>
  array (
    '#' => 'r 13',
  ),
  23 =>
  array (
    'sort_kw' => 's 24',
  ),
  24 =>
  array (
    '\'=\'' => 's 25',
  ),
  25 =>
  array (
    'identifier' => 's 26',
    'word' => 's 18',
  ),
  26 =>
  array (
    '#' => 'r 14',
  ),
  27 =>
  array (
    'and_kw' => 'r 2',
    '\'/\'' => 'r 2',
    '#' => 'r 2',
  ),
  28 =>
  array (
    '#' => 'r 23',
  ),
);
function reduce_0_query_1($tokens, &$result) {
#
# (0) query :=  query_in
#
$result = reset($tokens);

	global $parsed_results;
	$parsed_results = $result;

}

function reduce_1_query_2($tokens, &$result) {
#
# (1) query :=  query_in  modifiers
#
$result = reset($tokens);

	global $parsed_results;
	$parsed_results = $result;

}

function reduce_2_query_in_1($tokens, &$result) {
#
# (2) query_in :=  term
#
$result = reset($tokens);

	$result = array( 'terms' => array($tokens[0]) );

	debug_found('query_in', 'single term :'. print_r($result,true) );

}

function reduce_3_query_in_2($tokens, &$result) {
#
# (3) query_in :=  query_in  boolean_op  term
#
$result = reset($tokens);

	$first_subquery = $tokens[0];
	$terms = $first_subquery['terms'];
	$terms[] = $tokens[2];
	$result = array( 'terms' => $terms );

	debug_found('query_in', 'found multiplesubqueries with boolean query_in : '. print_r($result,true) );

}

function reduce_4_term_1($tokens, &$result) {
#
# (4) term :=  identifier  operator  value
#
$result = reset($tokens);
$i =& $tokens[0];
$o =& $tokens[1];
$v =& $tokens[2];

	$result = array($o, $i, $v);

	debug_found('term', 'found identifier operator value term: '. print_r($result,true) );

}

function reduce_5_term_2($tokens, &$result) {
#
# (5) term :=  value
#
$result = reset($tokens);

	debug_found('term', 'found single value term: '. print_r($result,true) );

}

function reduce_6_operator_1($tokens, &$result) {
#
# (6) operator :=  equal_kw
#
$result = reset($tokens);

}

function reduce_7_operator_2($tokens, &$result) {
#
# (7) operator :=  not_kw  equal_kw
#
$result = reset($tokens);
 $result = $tokens[0].$tokens[1];
}

function reduce_8_operator_3($tokens, &$result) {
#
# (8) operator :=  lt_kw
#
$result = reset($tokens);

}

function reduce_9_operator_4($tokens, &$result) {
#
# (9) operator :=  gt_kw
#
$result = reset($tokens);

}

function reduce_10_operator_5($tokens, &$result) {
#
# (10) operator :=  lt_kw  equal_kw
#
$result = reset($tokens);
 $result = $tokens[0].$tokens[1];
}

function reduce_11_operator_6($tokens, &$result) {
#
# (11) operator :=  gt_kw  equal_kw
#
$result = reset($tokens);
 $result = $tokens[0].$tokens[1];
}

function reduce_12_boolean_op_1($tokens, &$result) {
#
# (12) boolean_op :=  and_kw
#
$result = reset($tokens);

}

function reduce_13_modifiers_1($tokens, &$result) {
#
# (13) modifiers :=  sort
#
$result = reset($tokens);

}

function reduce_14_sort_1($tokens, &$result) {
#
# (14) sort :=  '/'  sort_kw  '='  identifier
#
$result = reset($tokens);

}

function reduce_15_identifier_1($tokens, &$result) {
#
# (15) identifier :=  word
#
$result = reset($tokens);

	debug_found('identifier', 'found simple identifier: '. print_r($result,true) );

}

function reduce_16_identifier_2($tokens, &$result) {
#
# (16) identifier :=  word  ':'  word
#
$result = reset($tokens);

	$result = $tokens[0].':'.$tokens[2];

	debug_found('identifier', 'found prefixed identifier: '. print_r($result,true) );

}

function reduce_17_in_val_1($tokens, &$result) {
#
# (17) in_val :=
#
$result = reset($tokens);

	$result=array(); /* problem */
}

function reduce_18_in_val_2($tokens, &$result) {
#
# (18) in_val :=  '['  in_val_in  ']'
#
$result = reset($tokens);
$in =& $tokens[1];
 $result[] = $in;
}

function reduce_19_in_val_in_1($tokens, &$result) {
#
# (19) in_val_in :=  value
#
$result = reset($tokens);
/* problem */
}

function reduce_20_in_val_in_2($tokens, &$result) {
#
# (20) in_val_in :=  ','  in_val_in
#
$result = reset($tokens);

}

function reduce_21_value_1($tokens, &$result) {
#
# (21) value :=  integer
#
$result = reset($tokens);

	debug_found('value', 'found integer: '. print_r($result,true) );

}

function reduce_22_value_2($tokens, &$result) {
#
# (22) value :=  string
#
$result = reset($tokens);

	debug_found('value', 'found string: '. print_r($result,true) );

}

function reduce_23_start_1($tokens, &$result) {
#
# (23) 'start' :=  query
#
$result = reset($tokens);

}

var $method = array (
  0 => 'reduce_0_query_1',
  1 => 'reduce_1_query_2',
  2 => 'reduce_2_query_in_1',
  3 => 'reduce_3_query_in_2',
  4 => 'reduce_4_term_1',
  5 => 'reduce_5_term_2',
  6 => 'reduce_6_operator_1',
  7 => 'reduce_7_operator_2',
  8 => 'reduce_8_operator_3',
  9 => 'reduce_9_operator_4',
  10 => 'reduce_10_operator_5',
  11 => 'reduce_11_operator_6',
  12 => 'reduce_12_boolean_op_1',
  13 => 'reduce_13_modifiers_1',
  14 => 'reduce_14_sort_1',
  15 => 'reduce_15_identifier_1',
  16 => 'reduce_16_identifier_2',
  17 => 'reduce_17_in_val_1',
  18 => 'reduce_18_in_val_2',
  19 => 'reduce_19_in_val_in_1',
  20 => 'reduce_20_in_val_in_2',
  21 => 'reduce_21_value_1',
  22 => 'reduce_22_value_2',
  23 => 'reduce_23_start_1',
);
var $a = array (
  0 =>
  array (
    'symbol' => 'query',
    'len' => 1,
    'replace' => true,
  ),
  1 =>
  array (
    'symbol' => 'query',
    'len' => 2,
    'replace' => true,
  ),
  2 =>
  array (
    'symbol' => 'query_in',
    'len' => 1,
    'replace' => true,
  ),
  3 =>
  array (
    'symbol' => 'query_in',
    'len' => 3,
    'replace' => true,
  ),
  4 =>
  array (
    'symbol' => 'term',
    'len' => 3,
    'replace' => true,
  ),
  5 =>
  array (
    'symbol' => 'term',
    'len' => 1,
    'replace' => true,
  ),
  6 =>
  array (
    'symbol' => 'operator',
    'len' => 1,
    'replace' => true,
  ),
  7 =>
  array (
    'symbol' => 'operator',
    'len' => 2,
    'replace' => true,
  ),
  8 =>
  array (
    'symbol' => 'operator',
    'len' => 1,
    'replace' => true,
  ),
  9 =>
  array (
    'symbol' => 'operator',
    'len' => 1,
    'replace' => true,
  ),
  10 =>
  array (
    'symbol' => 'operator',
    'len' => 2,
    'replace' => true,
  ),
  11 =>
  array (
    'symbol' => 'operator',
    'len' => 2,
    'replace' => true,
  ),
  12 =>
  array (
    'symbol' => 'boolean_op',
    'len' => 1,
    'replace' => true,
  ),
  13 =>
  array (
    'symbol' => 'modifiers',
    'len' => 1,
    'replace' => true,
  ),
  14 =>
  array (
    'symbol' => 'sort',
    'len' => 4,
    'replace' => true,
  ),
  15 =>
  array (
    'symbol' => 'identifier',
    'len' => 1,
    'replace' => true,
  ),
  16 =>
  array (
    'symbol' => 'identifier',
    'len' => 3,
    'replace' => true,
  ),
  17 =>
  array (
    'symbol' => 'in_val',
    'len' => 0,
    'replace' => true,
  ),
  18 =>
  array (
    'symbol' => 'in_val',
    'len' => 3,
    'replace' => true,
  ),
  19 =>
  array (
    'symbol' => 'in_val_in',
    'len' => 1,
    'replace' => true,
  ),
  20 =>
  array (
    'symbol' => 'in_val_in',
    'len' => 2,
    'replace' => true,
  ),
  21 =>
  array (
    'symbol' => 'value',
    'len' => 1,
    'replace' => true,
  ),
  22 =>
  array (
    'symbol' => 'value',
    'len' => 1,
    'replace' => true,
  ),
  23 =>
  array (
    'symbol' => '\'start\'',
    'len' => 1,
    'replace' => true,
  ),
);
}
