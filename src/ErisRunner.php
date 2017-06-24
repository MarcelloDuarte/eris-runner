<?php

namespace ErisRunner {
    use Phunkie\Types\ImmList;
    use Phunkie\Validation\Validation;
    use Phunkie\Cats\IO;
    use function Phunkie\Functions\immlist\concat;
    use function Phunkie\Functions\option\fromSome;
    use const Phunkie\Functions\option\isDefined;
    use function Phunkie\Functions\io\io;
    use function Phunkie\PatternMatching\Referenced\Success as Valid;
    use function Phunkie\PatternMatching\Referenced\Failure as Invalid;

    final class ForAllAdapter
    {
        use \Eris\TestTrait;

        public function runForAll()
        {
            return $this->forAll(...func_get_args());
        }
    }

    final class World
    {
        private static $world;

        public static function add(Validation $item)
        {
            if (self::$world === null) {
                self::$world = Nil();
            }
            self::$world = concat(self::$world, $item);
        }

        public static function flush()
        {
            return self::$world ? self::$world : Nil();
            self::$world = Nil();
        }
    }

    final class Console
    {
        public static function main(array $argv, int $argc)
        {
            $testDirectory = $argv[count($argv) - 1];

            if (!is_file($testDirectory)) {
                self::showUsage();
            }

            $io = loadFiles()
                ->andThen(runTests())
                ->andThen(printResults())
                    ->run($testDirectory);

            self::unsafelyPerform($io);
        }

        private static function showUsage()
        {
            exit ("usage: phunkie-check directory|file\n");
        }

        private static function unsafelyPerform(IO $io)
        {
            $io->run();
        }
    }

    final class Property
    {
        private $description;

        public function __construct(string $description)
        {
            $this->description = $description;
        }
    }

    final class FailedProperty
    {
        private $error;

        public function __construct(string $error)
        {
            $this->error = $error;
        }
    }

    final class Result
    {
        private $testFile;
        private $properties;

        public function __construct($testFile, ImmList $properties)
        {
            $this->testFile = $testFile;
            $this->properties = $properties;
        }

        public function getProperties()
        {
            return $this->properties;
        }

        public function __toString()
        {
            return "Result({$this->testFile}, " . $this->properties->show() . ")";
        }
    }

    function loadFiles()
    {
        $recursiveDirIterator = function($dir) {
            $rdi = new \RecursiveDirectoryIterator($dir);
            $rii = new \RecursiveIteratorIterator($rdi);
            $rri = new \RegexIterator($rii, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);
            return ImmList(...array_keys(iterator_to_array($rri)));
        };

        return Function1(function($tests) use ($recursiveDirIterator) {
            return is_dir($tests) ? $recursiveDirIterator($tests) : ImmList($tests);
        });
    }

    function runTests()
    {
        return Function1(function(ImmList $testFiles) {
            return $testFiles->map(function($testFile) {
                require_once $testFile;
                return new Result($testFile, World::flush());
            });
        });
    }

    function printResults()
    {   
        return Function1(function(ImmList $results): IO {
            
            return io (function() use ($results) {
                $_ = underscore();
                $errors = $results->map($_->properties)->map(function($properties) {
                    return $properties->map(function($property) { $on = match($property); switch(true) {
                        case $on(Valid($p)) : echo "."; return None(); break;
                        case $on(Invalid($e)) : echo "F"; return Some($e); break;
                    }})->filter(isDefined);
                });

                echo PHP_EOL;

                $errors->flatten()->map(function($error) {
                    echo "  " . fromSome($error) . PHP_EOL;
                });
            });
        });
    }
}

namespace {
    function forAll()
    {
        return (new ErisRunner\ForAllAdapter)
            ->runForAll(...func_get_args());
    }

    function property(string $description, callable $property)
    {

        try {
            $property();
            ErisRunner\World::add(Success(new ErisRunner\Property($description)));
        } catch (\Exception $e) {
            ErisRunner\World::add(Failure($e->getMessage()));
        }
    }

    /**
     * @method static \Eris\Generator associative(array $generators)
     * @method static \Eris\Generator bind(Generator $inner, callable $outerFactory)
     * @method static \Eris\Generator choose()
     */
    class Gen
    {
        public static function __callStatic($method, $args)
        {
            $generator = "\\Eris\\Generator\\{$method}Generator";
            return new $generator(...$args);
        }
    }

    /**
     * @mixin Webmozart\Assert\Assert
     * 
     * @method static void nullOrString($value, $message = '')
     * @method static void nullOrStringNotEmpty($value, $message = '')
     * @method static void nullOrInteger($value, $message = '')
     * @method static void nullOrIntegerish($value, $message = '')
     * @method static void nullOrFloat($value, $message = '')
     * @method static void nullOrNumeric($value, $message = '')
     * @method static void nullOrBoolean($value, $message = '')
     * @method static void nullOrScalar($value, $message = '')
     * @method static void nullOrObject($value, $message = '')
     * @method static void nullOrResource($value, $type = null, $message = '')
     * @method static void nullOrIsCallable($value, $message = '')
     * @method static void nullOrIsArray($value, $message = '')
     * @method static void nullOrIsTraversable($value, $message = '')
     * @method static void nullOrIsInstanceOf($value, $class, $message = '')
     * @method static void nullOrNotInstanceOf($value, $class, $message = '')
     * @method static void nullOrIsEmpty($value, $message = '')
     * @method static void nullOrNotEmpty($value, $message = '')
     * @method static void nullOrTrue($value, $message = '')
     * @method static void nullOrFalse($value, $message = '')
     * @method static void nullOrEq($value, $value2, $message = '')
     * @method static void nullOrNotEq($value,$value2,  $message = '')
     * @method static void nullOrSame($value, $value2, $message = '')
     * @method static void nullOrNotSame($value, $value2, $message = '')
     * @method static void nullOrGreaterThan($value, $value2, $message = '')
     * @method static void nullOrGreaterThanEq($value, $value2, $message = '')
     * @method static void nullOrLessThan($value, $value2, $message = '')
     * @method static void nullOrLessThanEq($value, $value2, $message = '')
     * @method static void nullOrRange($value, $min, $max, $message = '')
     * @method static void nullOrOneOf($value, $values, $message = '')
     * @method static void nullOrContains($value, $subString, $message = '')
     * @method static void nullOrStartsWith($value, $prefix, $message = '')
     * @method static void nullOrStartsWithLetter($value, $message = '')
     * @method static void nullOrEndsWith($value, $suffix, $message = '')
     * @method static void nullOrRegex($value, $pattern, $message = '')
     * @method static void nullOrAlpha($value, $message = '')
     * @method static void nullOrDigits($value, $message = '')
     * @method static void nullOrAlnum($value, $message = '')
     * @method static void nullOrLower($value, $message = '')
     * @method static void nullOrUpper($value, $message = '')
     * @method static void nullOrLength($value, $length, $message = '')
     * @method static void nullOrMinLength($value, $min, $message = '')
     * @method static void nullOrMaxLength($value, $max, $message = '')
     * @method static void nullOrLengthBetween($value, $min, $max, $message = '')
     * @method static void nullOrFileExists($value, $message = '')
     * @method static void nullOrFile($value, $message = '')
     * @method static void nullOrDirectory($value, $message = '')
     * @method static void nullOrReadable($value, $message = '')
     * @method static void nullOrWritable($value, $message = '')
     * @method static void nullOrClassExists($value, $message = '')
     * @method static void nullOrSubclassOf($value, $class, $message = '')
     * @method static void nullOrImplementsInterface($value, $interface, $message = '')
     * @method static void nullOrPropertyExists($value, $property, $message = '')
     * @method static void nullOrPropertyNotExists($value, $property, $message = '')
     * @method static void nullOrMethodExists($value, $method, $message = '')
     * @method static void nullOrMethodNotExists($value, $method, $message = '')
     * @method static void nullOrKeyExists($value, $key, $message = '')
     * @method static void nullOrKeyNotExists($value, $key, $message = '')
     * @method static void nullOrCount($value, $key, $message = '')
     * @method static void nullOrUuid($values, $message = '')
     * @method static void allString($values, $message = '')
     * @method static void allStringNotEmpty($values, $message = '')
     * @method static void allInteger($values, $message = '')
     * @method static void allIntegerish($values, $message = '')
     * @method static void allFloat($values, $message = '')
     * @method static void allNumeric($values, $message = '')
     * @method static void allBoolean($values, $message = '')
     * @method static void allScalar($values, $message = '')
     * @method static void allObject($values, $message = '')
     * @method static void allResource($values, $type = null, $message = '')
     * @method static void allIsCallable($values, $message = '')
     * @method static void allIsArray($values, $message = '')
     * @method static void allIsTraversable($values, $message = '')
     * @method static void allIsInstanceOf($values, $class, $message = '')
     * @method static void allNotInstanceOf($values, $class, $message = '')
     * @method static void allNull($values, $message = '')
     * @method static void allNotNull($values, $message = '')
     * @method static void allIsEmpty($values, $message = '')
     * @method static void allNotEmpty($values, $message = '')
     * @method static void allTrue($values, $message = '')
     * @method static void allFalse($values, $message = '')
     * @method static void allEq($values, $value2, $message = '')
     * @method static void allNotEq($values,$value2,  $message = '')
     * @method static void allSame($values, $value2, $message = '')
     * @method static void allNotSame($values, $value2, $message = '')
     * @method static void allGreaterThan($values, $value2, $message = '')
     * @method static void allGreaterThanEq($values, $value2, $message = '')
     * @method static void allLessThan($values, $value2, $message = '')
     * @method static void allLessThanEq($values, $value2, $message = '')
     * @method static void allRange($values, $min, $max, $message = '')
     * @method static void allOneOf($values, $values, $message = '')
     * @method static void allContains($values, $subString, $message = '')
     * @method static void allStartsWith($values, $prefix, $message = '')
     * @method static void allStartsWithLetter($values, $message = '')
     * @method static void allEndsWith($values, $suffix, $message = '')
     * @method static void allRegex($values, $pattern, $message = '')
     * @method static void allAlpha($values, $message = '')
     * @method static void allDigits($values, $message = '')
     * @method static void allAlnum($values, $message = '')
     * @method static void allLower($values, $message = '')
     * @method static void allUpper($values, $message = '')
     * @method static void allLength($values, $length, $message = '')
     * @method static void allMinLength($values, $min, $message = '')
     * @method static void allMaxLength($values, $max, $message = '')
     * @method static void allLengthBetween($values, $min, $max, $message = '')
     * @method static void allFileExists($values, $message = '')
     * @method static void allFile($values, $message = '')
     * @method static void allDirectory($values, $message = '')
     * @method static void allReadable($values, $message = '')
     * @method static void allWritable($values, $message = '')
     * @method static void allClassExists($values, $message = '')
     * @method static void allSubclassOf($values, $class, $message = '')
     * @method static void allImplementsInterface($values, $interface, $message = '')
     * @method static void allPropertyExists($values, $property, $message = '')
     * @method static void allPropertyNotExists($values, $property, $message = '')
     * @method static void allMethodExists($values, $method, $message = '')
     * @method static void allMethodNotExists($values, $method, $message = '')
     * @method static void allKeyExists($values, $key, $message = '')
     * @method static void allKeyNotExists($values, $key, $message = '')
     * @method static void allCount($values, $key, $message = '')
     * @method static void allUuid($values, $message = '')
     */
    class Assert
    {
        public static function __callStatic($method, $args)
        {
            return call_user_func_array([Webmozart\Assert\Assert::class, $method], $args);
        }
    }
}

