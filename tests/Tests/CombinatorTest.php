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
            $func = "map$tail";
            $_ = $func($vfs);
            $this->assertEquals(['a'=>'aa', 'b'=>'bb'], $_(['a'=>'a', 'b'=>'b']), "Failed at variant: $func");
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
            $_(['a'=>'a', 'b'=>'b']);
        } catch (ValidationException $e) {
            $this->assertEquals("the field a is a\nthe field b is b", $e->getMessage());
        }

        $_ = mapS($vfs);

        try {
            $_(['a'=>'a', 'b'=>'b']);
        } catch (ValidationException $e) {
            $this->assertEquals("the field a is a\nthe field b is b", $e->getMessage());
        }

        $_ = map_($vfs);

        try {
            $_(['a'=>'a', 'b'=>'b']);
        } catch (ValidationException $e) {
            $this->assertEquals("the field a is a", $e->getMessage());
        }

        $_ = map_S($vfs);

        try {
            $_(['a'=>'a', 'b'=>'b']);
        } catch (ValidationException $e) {
            $this->assertEquals("the field a is a", $e->getMessage());
        }
    }

    public function testMapStrict() {
        # Validation functions
        $vfs = [
            'a' => function ($in) {
                return $in . 'a';
            },
            'b' => function ($in) {
                return $in . 'b';
            }
        ];

        $_ = map($vfs);
        $this->assertEquals(['a'=>'aa', 'b'=>'bb', 'c' => 'c'], $_(['a'=>'a', 'b'=>'b', 'c'=>'c']));

        $_ = map_($vfs);
        $this->assertEquals(['a'=>'aa', 'b'=>'bb', 'c' => 'c'], $_(['a'=>'a', 'b'=>'b', 'c'=>'c']));

        try {
            $_ = mapS($vfs);
            $this->assertEquals(['a'=>'aa', 'b'=>'bb', 'c' => 'c'], $_(['a'=>'a', 'b'=>'b', 'c'=>'c']));
        } catch (ValidationException $e) {
            $this->assertEquals('the following fields lack validation rules: c', $e->getMessage());
        }

        try {
            $_ = map_S($vfs);
            $this->assertEquals(['a'=>'aa', 'b'=>'bb', 'c' => 'c'], $_(['a'=>'a', 'b'=>'b', 'c'=>'c']));
        } catch (ValidationException $e) {
            $this->assertEquals('the following fields lack validation rules: c', $e->getMessage());
        }

    }

    public function testTupleOk() {
        $vfs = [
            function ($in) {
                return $in . 'a';
            },
            function ($in) {
                return $in . 'b';
            }
        ];

        $_ = tpl($vfs);
        $this->assertEquals(['aa', 'bb'], $_(['a', 'b']));

        $_ = tpl_($vfs);
        $this->assertEquals(['aa', 'bb'], $_(['a', 'b']));
    }

    public function testTupleErr() {
        $vfs = [
            function ($in) {
                throw new ValidationException('a');
            },
            function ($in) {
                throw new ValidationException('b');
            }
        ];

        $thrown = true;
        try {
            $_ = tpl($vfs);
            $_(['a', 'b']);
            $thrown = false;
        } catch (ValidationException $e) {
            $this->assertEquals("the value 0 is a\nthe value 1 is b", $e->getMessage());
        }
        $this->assertTrue($thrown);

        $thrown = true;
        try {
            $_ = tpl_($vfs);
            $_(['a', 'b']);
            $thrown = false;
        } catch (ValidationException $e) {
            $this->assertEquals("the value 0 is a", $e->getMessage());
        }
        $this->assertTrue($thrown);
    }

    public function testLstOk() {
        $vf = function ($in) {
            return $in . 'a';
        };

        $_ = lst($vf);
        $this->assertEquals(['aa', 'ba'], $_(['a', 'b']));

        $_ = lst_($vf);
        $this->assertEquals(['aa', 'ba'], $_(['a', 'b']));
    }

    public function testLstErr() {
        $vf = function ($in) {
            throw new ValidationException('x');
        };

        $thrown = true;
        try {
            $_ = lst($vf);
            $_(['a', 'b']);
        } catch (ValidationException $e) {
            $this->assertEquals("the value 0 is x\nthe value 1 is x", $e->getMessage());
        }
        $this->assertTrue($thrown);

        $thrown = true;
        try {
            $_ = lst_($vf);
            $_(['a', 'b']);
        } catch (ValidationException $e) {
            $this->assertEquals("the value 0 is x", $e->getMessage());
        }
        $this->assertTrue($thrown);
    }

}
