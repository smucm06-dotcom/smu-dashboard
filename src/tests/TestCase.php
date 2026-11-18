<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    // Tests empty the database they run on, so they must never reach a real one.
    public function createApplication()
    {
        $app = parent::createApplication();

        $config = $app['config'];
        $connection = $config->get('database.default');

        if ($connection !== 'sqlite' || $config->get("database.connections.{$connection}.database") !== ':memory:') {
            throw new RuntimeException('Tests must run on the in-memory database set in phpunit.xml.');
        }

        return $app;
    }
}
