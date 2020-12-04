<?php

namespace Nddcoder\ObjectMapper\Tests;

use Nddcoder\ObjectMapper\Exceptions\AttributeMustNotBeNullException;
use Nddcoder\ObjectMapper\Exceptions\CannotConstructUnionTypeException;
use Nddcoder\ObjectMapper\Exceptions\ClassNotFoundException;
use Nddcoder\ObjectMapper\ObjectMapperFacade;
use Nddcoder\ObjectMapper\Tests\Model\DeviceInfo;
use Nddcoder\ObjectMapper\Tests\Model\Keys;
use Nddcoder\ObjectMapper\Tests\Model\Message;
use Nddcoder\ObjectMapper\Tests\Model\ModelWithCustomSetter;
use Nddcoder\ObjectMapper\Tests\Model\ModelWithNullableString;
use Nddcoder\ObjectMapper\Tests\Model\ModelWithNullUnionType;
use Nddcoder\ObjectMapper\Tests\Model\ModelWithStaticProperty;
use Nddcoder\ObjectMapper\Tests\Model\ModelWithStdClass;
use Nddcoder\ObjectMapper\Tests\Model\ModelWithUnionType;
use Nddcoder\ObjectMapper\Tests\Model\User;
use stdClass;

class ObjectMapperReadValueTest extends TestCase
{
    /** @test */
    public function it_can_read_value_from_json_string()
    {
        $data = $this->getData();

        /** @var User $user */
        $user = ObjectMapperFacade::readValue(json_encode($data), User::class);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($data['id'], $user->get_id());
        $this->assertEquals($data['subscription']['endpoint'], $user->subscription->endpoint);
        $this->assertEquals($data['subscription']['keys']['p256dh'], $user->subscription->keys->p256dh);
        $this->assertEquals($data['subscription']['keys']['auth'], $user->subscription->keys->auth);
        $this->assertEquals($data['subscribed_times'], $user->subscribedTimes);
        $this->assertEquals($data['active'], $user->active);
        $this->assertEquals($data['payout'], $user->payout);
        $this->assertEquals($data['user_agent'], $user->userAgent);
        $this->assertEquals(date_create($data['created_at'])->getTimestamp(), $user->createdAt->getTimestamp());
        $this->assertEquals(date_create($data['updated_at'])->getTimestamp(), $user->updatedAt->getTimestamp());
        $this->assertEquals($data['body'], $user->description);
        $this->assertEquals($data['title'], $user->title);
        $this->assertCount(2, $user->messages);
        $this->assertInstanceOf(Message::class, $user->messages[0]);
        $this->assertCount(1, $user->messagesViaSetter);
        $this->assertInstanceOf(Message::class, $user->messagesViaSetter[0]);
        $this->assertCount(3, $user->logs);
        $this->assertCount(3, $user->logs[0]);
        $this->assertNull($user->groups);
    }

    /** @test */
    public function it_should_throw_exception_when_read_value_for_not_exists_class()
    {
        $this->expectException(ClassNotFoundException::class);

        ObjectMapperFacade::readValue('{}', NotExistsClass::class);
    }

    /** @test */
    public function it_should_throw_exception_when_assign_null_for_non_null_property()
    {
        $data         = $this->getData();
        $data['body'] = null;

        $this->expectException(AttributeMustNotBeNullException::class);

        ObjectMapperFacade::readValue(json_encode($data), User::class);
    }

    /** @test */
    public function it_can_custom_setter()
    {
        $data = [
            'company'     => 'nddcoder',
            'auth_key'    => [
                'p256dh' => '123',
                'auth'   => 'authKey',
            ],
            'request_num' => '1234',
        ];

        /** @var ModelWithCustomSetter $user */
        $user = ObjectMapperFacade::readValue(json_encode($data), ModelWithCustomSetter::class);

        $this->assertEquals(1234, $user->requestNumber);
        $this->assertEquals('authKey', $user->keys->auth);
    }

    /** @test */
    public function it_should_call_setter_with_null_param_when_input_invalid_type()
    {
        $data = [
            'company'     => 'nddcoder',
            'auth_key'    => 'invalid_type_of_Keys',
            'request_num' => '1234',
        ];

        /** @var ModelWithCustomSetter $user */
        $user = ObjectMapperFacade::readValue(json_encode($data), ModelWithCustomSetter::class);

        $this->assertEquals('nddcoder', $user->company);
        $this->assertIsInt($user->requestNumber);
        $this->assertEquals(1234, $user->requestNumber);

        $this->assertEquals(null, $user->keys);
    }

    /** @test */
    public function it_should_set_null_when_invalid_cast_type()
    {
        $data = [
            'company' => [
                'abc' => 1,
            ],
        ];

        /** @var ModelWithNullableString $user */
        $user = ObjectMapperFacade::readValue(json_encode($data), ModelWithNullableString::class);

        $this->assertNull($user->company);
    }

    /** @test */
    public function it_can_set_std_class_value()
    {
        $user = ObjectMapperFacade::readValue(
            json_encode(
                [
                    'tags' => [
                        'type' => 'user',
                    ],
                ]
            ),
            ModelWithStdClass::class
        );

        $this->assertInstanceOf(ModelWithStdClass::class, $user);
        $this->assertInstanceOf(stdClass::class, $user->tags);

        $this->assertEquals('user', $user->tags->type);
    }

    /** @test */
    public function it_can_set_union_type_value()
    {
        $modelWithDeviceInfo = ObjectMapperFacade::readValue(
            json_encode(
                [
                    'magic_field' => [
                        'device_type'     => 'smartphone',
                        'device_brand'    => 'Samsung',
                        'device_model'    => 'GALAXY A3 (2017)',
                        'browser_name'    => 'Chrome Mobile',
                        'browser_version' => '86.0.4240.99',
                        'os_name'         => 'Android',
                        'os_version'      => '8.0.0',
                    ],
                ]
            ),
            ModelWithUnionType::class
        );

        $this->assertInstanceOf(ModelWithUnionType::class, $modelWithDeviceInfo);
        $this->assertInstanceOf(DeviceInfo::class, $modelWithDeviceInfo->magicField);
        $this->assertEquals('smartphone', $modelWithDeviceInfo->magicField->deviceType);

        $modelWithKeys = ObjectMapperFacade::readValue(
            json_encode(
                [
                    'magic_field' => [
                        'p256dh' => 'BL2lxnUZkj3eKw4Wac',
                        'auth'   => 't_xHCouA1lw',
                    ],
                ]
            ),
            ModelWithUnionType::class
        );

        $this->assertInstanceOf(ModelWithUnionType::class, $modelWithKeys);
        $this->assertInstanceOf(Keys::class, $modelWithKeys->magicField);
        $this->assertEquals('t_xHCouA1lw', $modelWithKeys->magicField->auth);
    }

    /** @test */
    public function it_should_throw_exception_when_cannot_set_union_type_value()
    {
        $this->expectException(CannotConstructUnionTypeException::class);
        ObjectMapperFacade::readValue(
            json_encode(
                [
                    'magic_field' => [
                        'not_valid_field' => 'smartphone',
                    ],
                ]
            ),
            ModelWithUnionType::class
        );
    }

    /** @test */
    public function it_should_not_throw_exception_when_union_has_type_null()
    {
        $model = ObjectMapperFacade::readValue(
            json_encode(
                [
                    'magic_field' => [
                        'not_valid_field' => 'smartphone',
                    ],
                ]
            ),
            ModelWithNullUnionType::class
        );

        $this->assertInstanceOf(ModelWithNullUnionType::class, $model);
        $this->assertNull($model->magicField);
    }

    /** @test */
    public function it_should_skip_static_property()
    {
        /** @var ModelWithStaticProperty $model */
        $model = ObjectMapperFacade::readValue(
            json_encode(
                [
                    'company' => 'nddcoder',
                    'cache'   => ['cache_item' => true],
                ]
            ),
            ModelWithStaticProperty::class
        );

        $this->assertInstanceOf(ModelWithStaticProperty::class, $model);
        $this->assertEquals('nddcoder', $model->company);
        $this->assertCount(0, $model::$cache);
    }
}
