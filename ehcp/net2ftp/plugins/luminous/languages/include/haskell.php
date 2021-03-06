<?php

// http://www.haskell.org/onlinereport/prelude-index.html


global $luminous_haskell_functions;
global $luminous_haskell_types;
global $luminous_haskell_values;
global $luminous_haskell_keywords;

$luminous_haskell_keywords = array('as',
  'case',
  'of',
  'class',
  'data',
  'family',
  'instance',
  'default',
  'deriving',
  'do',
  'forall',
  'foreign',
  'hiding',
  'if',
  'then',
  'else',
  'import',
  'infix',
  'infixl',
  'infixr',
  'let',
  'in',
  'mdo',
  'module',
  'newtype',
  'proc',
  'qualified',
  'rec',
  'type',
  'where');
  


$luminous_haskell_types = array(
  'Bool',
  'Char',
  'Double',
  'Either',
  'FilePath',
  'Float',
  'Int',
  'Integer',
  'IO',
  'IOError',
  'Maybe',
  'Ordering',
  'ReadS',
  'ShowS',
  'String',
  
  'Bounded',
  'Enum',
  'Eq',
  'Floating',
  'Fractional',
  'Functor',
  'Integral',
  'Monad',
  'Num',
  'Ord',
  'Read',
  'Real',
  'RealFloat',
  'RealFrac',
  'Show'
);

$luminous_haskell_values = array(
  'EQ',
  'False',
  'GT',
  'Just',
  'Left',
  'LT',
  'Nothing',
  'Right',
  'True',
);



$luminous_haskell_functions = array(
'abs',
'acos',
'acosh',
'all',
'and',
'any',
'appendFile',
'applyM',
'asTypeOf',
'asin',
'asinh',
'atan',
'atan2',
'atanh',
'break',
'catch',
'ceiling',
'compare',
'concat',
'concatMap',
'const',
'cos',
'cosh',
'curry',
'cycle',
'decodeFloat',
'div',
'divMod',
'drop',
'dropWhile',
'elem',
'encodeFloat',
'enumFrom',
'enumFromThen',
'enumFromThenTo',
'enumFromTo',
'error',
'even',
'exp',
'exponent',
'fail',
'filter',
'flip',
'floatDigits',
'floatRadix',
'floatRange',
'floor',
'fmap',
'foldl',
'foldl1',
'foldr',
'foldr1',
'fromEnum',
'fromInteger',
'fromIntegral',
'fromRational',
'fst',
'gcd',
'getChar',
'getContents',
'getLine',
'head',
'id',
'init',
'interact',
'ioError',
'isDenormalized',
'isIEEE',
'isInfinite',
'isNaN',
'isNegativeZero',
'iterate',
'last',
'lcm',
'length',
'lex',
'lines',
'log',
'logBase',
'lookup',
'map',
'mapM',
'mapM_',
'max',
'maxBound',
'maximum',
'maybe',
'min',
'minBound',
'minimum',
'mod',
'negate',
'not',
'notElem',
'null',
'odd',
'or',
'otherwise',
'pi',
'pred',
'print',
'product',
'properFraction',
'putChar',
'putStr',
'putStrLn',
'quot',
'quotRem',
'read',
'readFile',
'readIO',
'readList',
'readLn',
'readParen',
'reads',
'readsPrec',
'realToFrac',
'recip',
'rem',
'repeat',
'replicate',
'return',
'reverse',
'round',
'scaleFloat',
'scanl',
'scanl1',
'scanr',
'scanr1',
'seq',
'sequence',
'sequence_',
'show',
'showChar',
'showList',
'showParen',
'showString',
'shows',
'showsPrec',
'significand',
'signum',
'sin',
'sinh',
'snd',
'span',
'splitAt',
'sqrt',
'subtract',
'succ',
'sum',
'tail',
'take',
'takeWhile',
'tan',
'tanh',
'toEnum',
'toInteger',
'toRational',
'truncate',
'uncurry',
'undefined',
'unlines',
'until',
'unwords',
'unzip',
'unzip3',
'userError',
'words',
'writeFile',
'zip',
'zip3',
'zipWith',
'zipWith3',);
