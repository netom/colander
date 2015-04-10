<?php

class CombinatorTest extends PHPUnit_Framework_TestCase
{
    public function testSeqOk() {
        # Validation functions
        $vfs = [
            function ($in) {
                return $in .= 'a';
            },
            function ($in) {
                return $in .= 'b';
            }
        ];

        $_ = seq($vfs);
        $this->assertEquals('xab', $_('x'));

        $_ = seq_($vfs);
        $this->assertEquals('xab', $_('x'));
    }

    public function testSeqErr() {
        $vfs = [
            function ($in) {
                throw new ValidationException('a');
            },
            function ($in) {
                throw new ValidationException('b');
            }
        ];

        $_ = seq($vfs);

        try {
            $_('dummy');
        } catch (ValidationException $e) {
            $this->assertEquals('a, b', $e->getMessage());
        }

        $_ = seq_($vfs);

        try {
            $_('dummy');
        } catch (ValidationException $e) {
            $this->assertEquals('a', $e->getMessage());
        }
    }

    public function testMapOk() {
        # Validation functions
        $vfs = [
            'a' => function ($in) {
                return $in . 'a';
            },
            'b' => function ($in) {
                return $in . 'b';
            }
        ];

        foreach (['', 'S', '_', '_S'] as $tail) {
            $func = "\map$tail";
            $_ = $func($vfs);
            $this->assertEquals(['a'=>'aa', 'b'=>'bb'], $_(['a'=>'a', 'b'=>'b']));
        }
    }

    public function testMapErr() {
        $vfs = [
            'a' => function ($in) {
                throw new ValidationException('a');
            },
            'b' => function ($in) {
                throw new ValidationException('b');
            }
        ];

        $_ = map($vfs);

        try {
            $_(['a'=>'a', 'a'=>'b']);
        } catch (ValidationException $e) {
            $this->assertEquals("the field a is a\nthe field b is b", $e->getMessage());
        }

        $_ = mapS($vfs);

        try {
            $_('dummy');
        } catch (ValidationException $e) {
            $this->assertEquals("the field a is a\nthe field b is b", $e->getMessage());
        }

        $_ = map_($vfs);

        try {
            $_('dummy');
        } catch (ValidationException $e) {
            $this->assertEquals("the field a is a", $e->getMessage());
        }

        $_ = map_S($vfs);

        try {
            $_('dummy');
        } catch (ValidationException $e) {
            $this->assertEquals("the field a is a", $e->getMessage());
        }
    }

    public function testMapStrict() {
    }

    public function testTupleOk() {
    }

    public function testTupleErr() {
    }

    public function testTupleStrict() {
    }

    public function testLstOk() {
    }

    public function testLstErr() {
    }

}
