<?php

namespace Tests;

use InvalidArgumentException;
use Ejz\Container;
use Ejz\Exceptions\ContainerException;
use Ejz\Exceptions\NotFoundException;
use Psr\Container\ContainerInterface;

interface I1 { }
class C1 implements I1 { public function __construct() { } }
interface I2 { }
class C2 implements I2 { public function __construct(C1 $c1) { } }
interface I3 { }
class C3 implements I3 { public function __construct(I1 $i1) { } }
interface I4 { }
class C4 implements I4 { public function __construct(I1 $i1, array $a = []) { } }
interface I5 { }
class C5 implements I5 { }
interface I6 { }
class C6 implements I6 { public function __construct(I1 $i1) { } }
interface I7 { }
class C7 implements I7 { }
interface I8 { }
class C8 implements I8 { public function __construct(I1 $i1, array $a) { } }
interface I9 { }
class C9 implements I9 { public function __construct(I1 $i1, array $a) { } }

class TestContainer extends TestCase
{
    /**
     *
     */
    protected function setUp()
    {
        parent::setUp();
    }

    /**
     *
     */
    public function testContainerCommon()
    {
        $container = new Container();
        $container->setDefinitions([
            I1::class => C1::class,
            I2::class => C2::class,
            I3::class => C3::class,
            I5::class => function () {
                return new C5();
            },
            I5::class => function () {
                return new C5();
            },
            I6::class => function (I1 $i1) {
                return new C6($i1);
            },
            I7::class => C7::class,
            I8::class => C8::class,
        ]);
        $i1 = $container->get(I1::class);
        $this->assertInstanceOf(C1::class, $i1);
        $i2 = $container->get(I2::class);
        $this->assertInstanceOf(C2::class, $i2);
        $i3 = $container->get(I3::class);
        $this->assertInstanceOf(C3::class, $i3);
        $c4 = $container->get(C4::class);
        $this->assertInstanceOf(C4::class, $c4);
        $i5 = $container->get(I5::class);
        $this->assertInstanceOf(C5::class, $i5);
        $i6 = $container->get(I6::class);
        $this->assertInstanceOf(C6::class, $i6);
        $i7 = $container->get(I7::class);
        $this->assertInstanceOf(C7::class, $i7);
        $this->expectException(InvalidArgumentException::class);
        $i8 = $container->get(I8::class);
        $this->assertInstanceOf(C8::class, $i8);
    }

    /**
     *
     */
    public function testContainerArguments()
    {
        $container = new Container();
        $container->setDefinitions([
            I1::class => C1::class,
            I7::class => function () {
                return $this->make(C7::class);
            },
            I8::class => C8::class,
            I9::class => function (I1 $i1, $a) {
                return new C9($i1, (array) $a);
            },
        ]);
        $i7 = $container->get(I7::class);
        $this->assertInstanceOf(C7::class, $i7);
        $i8 = $container->get(I8::class, ['a' => []]);
        $this->assertInstanceOf(C8::class, $i8);
        $i9 = $container->get(I9::class, ['a' => []]);
        $this->assertInstanceOf(C9::class, $i9);
        $this->expectException(InvalidArgumentException::class);
        $i8 = $container->get(I8::class, ['a' => '']);
    }
}
