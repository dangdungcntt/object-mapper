<?php

namespace Nddcoder\ObjectMapper\Tests;

use Nddcoder\ObjectMapper\ObjectMapperServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            ObjectMapperServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    public function getData(): array
    {
        return [
            'id'               => '5fc6dd841c82e300486c028d',
            'subscription'     => [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/cJqlz2VQ_jg:APA91bF23j5czbhbJ9SyV1J4w1R-s6xk0GBOC4JyepZ-WG26cTg2AGvWwodNuuODjywZqH-9VuS26SVpQHSx5WD1M2qe1nZlBp2o7ZA6ZCuNGO4qfapDo0A37MEqJzkPFPtRD5Vl7Zpo',
                'keys'     => [
                    'p256dh' => 'BL2lxnUymlxmEw0RZZaRKxocUaYFjyHN6lsWROI7Vp2YWwukErELQJXkDRvqV5vEb4Ln5tnRthpyZkj3eKw4Wac',
                    'auth'   => 't_xHCoTm1nK1aop36uA1lw'
                ]
            ],
            'subscribed_times' => 1,
            'active'           => true,
            'payout'           => 0.0049,
            'user_agent'       => 'Mozilla/5.0 (Linux; Android 8.0.0; SM-A320F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.99 Mobile Safari/537.36',
            'device_info'      => [
                'device_type'     => 'smartphone',
                'device_brand'    => 'Samsung',
                'device_model'    => 'GALAXY A3 (2017)',
                'browser_name'    => 'Chrome Mobile',
                'browser_version' => '86.0.4240.99',
                'os_name'         => 'Android',
                'os_version'      => '8.0.0'
            ],
            'created_at'       => '2020-12-02T07:19:16+07:00',
            'updated_at'       => '2020-12-02T07:19:16+07:00',
            'body'             => 'json property description',
            'title'            => null,
            'not_exists_field' => 123
        ];
    }
}
