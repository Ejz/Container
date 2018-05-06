<?php

namespace Tests;

use PHPUnit\Framework\TestCase as TestCaseParent;

abstract class TestCase extends TestCaseParent
{
    /* Container */
    protected $container;

    /**
     * __construct
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     *
     */
    protected function setUp()
    {
        if (!$this->container && is_file(CONTAINER_FILE)) {
            $this->container = require CONTAINER_FILE;
        }
    }

    /**
     *
     */
    protected function tearDown()
    {
        $this->container = null;
    }
}
