<?php

namespace Nddcoder\ObjectMapper\Tests;

use Nddcoder\ObjectMapper\ObjectMapperFacade;
use Nddcoder\ObjectMapper\Tests\Model\ModelWithAppendJsonOutput;
use Nddcoder\ObjectMapper\Tests\Model\ModelWithCustomGetter;
use Nddcoder\ObjectMapper\Tests\Model\ModelWithKeysWithToString;
use Nddcoder\ObjectMapper\Tests\Model\ModelWithProtectedProperty;
use Nddcoder\ObjectMapper\Tests\Model\ModelWithStdClass;
use Nddcoder\ObjectMapper\Tests\Model\ModelWithToStringMethod;
use Nddcoder\ObjectMapper\Tests\Model\User;

class ObjectMapperWriteValueTest extends TestCase
{
    /** @test */
    public function it_can_write_value_as_string()
    {
        $data = $this->getData();
        /** @var User $user */
        $user       = ObjectMapperFacade::readValue(json_encode($data), User::class);
        $jsonString = ObjectMapperFacade::writeValueAsString($user);

        unset($data['not_exists_field']);
        ksort($data);
        $data['groups'] = null;

        $decodedJsonObject = json_decode($jsonString, true);
        ksort($decodedJsonObject);

        $this->assertEquals(json_encode($data), json_encode($decodedJsonObject));
    }

    /** @test */
    public function it_can_override_output_value_via_getter()
    {
        $user          = new ModelWithCustomGetter();
        $user->company = 'nddcoder';

        $json = ObjectMapperFacade::writeValueAsString($user);

        $this->assertEquals('{"company":"NDDCODER"}', $json);
    }

    /** @test */
    public function it_ignore_non_public_property_if_no_getter_exists()
    {
        $user          = new ModelWithProtectedProperty();
        $user->company = 'nddcoder';
        $user->setPassword('123abc');

        $json = ObjectMapperFacade::writeValueAsString($user);

        $this->assertEquals('{"company":"nddcoder"}', $json);
    }

    /** @test */
    public function it_just_return_string_when_value_is_string()
    {
        $json = '123';
        $this->assertEquals('123', ObjectMapperFacade::writeValueAsString($json));
    }

    /** @test */
    public function it_return_empty_string_when_value_is_null()
    {
        $this->assertEquals('', ObjectMapperFacade::writeValueAsString(null));
    }

    /** @test */
    public function it_just_return_json_encoded_string_when_value_is_array()
    {
        $data = [
            'abc' => 123,
        ];
        $this->assertEquals(json_encode($data), ObjectMapperFacade::writeValueAsString($data));
    }

    /** @test */
    public function it_use_to_array_method_of_object()
    {
        $user = new class {
            public string $name = 'nddocoder';

            public function toArray(): array
            {
                return [
                    'name' => 'dungnd',
                ];
            }
        };
        $this->assertEquals(json_encode([
            'name' => 'dungnd',
        ]), ObjectMapperFacade::writeValueAsString($user));
    }

    /** @test */
    public function it_use_to_json_method_of_object()
    {
        $user = new class {
            public string $name = 'nddocoder';

            public function toJson(): string
            {
                return json_encode([
                    'name' => 'dungnd',
                ]);
            }
        };
        $this->assertEquals(json_encode([
            'name' => 'dungnd',
        ]), ObjectMapperFacade::writeValueAsString($user));
    }

    /** @test */
    public function it_use_to_string_method_of_object()
    {
        $user = new class {
            public string $name = 'nddocoder';

            public function __toString(): string
            {
                return json_encode([
                    'name' => 'dungnd',
                ]);
            }
        };

        $this->assertEquals(json_encode([
            'name' => 'dungnd',
        ]), ObjectMapperFacade::writeValueAsString($user));
    }

    /** @test */
    public function it_use_to_string_method_of_class_property()
    {
        $user       = new ModelWithKeysWithToString();
        $user->keys = new ModelWithToStringMethod();

        $this->assertEquals(json_encode([
            'keys' => 'masked data',
        ]), ObjectMapperFacade::writeValueAsString($user));
    }

    /** @test */
    public function it_cast_non_object_to_string()
    {
        $this->assertEquals('9', ObjectMapperFacade::writeValueAsString(9));
    }

    /** @test */
    public function it_can_cast_std_class_to_string()
    {
        $stdClass       = new \stdClass();
        $stdClass->type = 'user';
        $user           = new ModelWithStdClass();
        $user->tags     = $stdClass;

        $this->assertEquals(json_encode([
            'tags' => [
                'type' => 'user',
            ],
        ]), ObjectMapperFacade::writeValueAsString($user));
    }

    /** @test */
    public function it_should_append_field_to_output()
    {
        $model            = new ModelWithAppendJsonOutput();
        $model->firstName = 'Dung';
        $model->lastName  = 'Nguyen Dang';

        $this->assertEquals(json_encode([
            'first_name' => 'Dung',
            'last_name' => 'Nguyen Dang',
            'full_name' => 'Dung Nguyen Dang'
        ]), ObjectMapperFacade::writeValueAsString($model));
    }
}
