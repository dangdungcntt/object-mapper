<?php

namespace Nddcoder\ObjectMapper\Tests;

use Nddcoder\ObjectMapper\Exceptions\AttributeMustNotBeNullException;
use Nddcoder\ObjectMapper\Exceptions\ClassNotFoundException;
use Nddcoder\ObjectMapper\ObjectMapperFacade;
use Nddcoder\ObjectMapper\Tests\Model\User;
use Nddcoder\ObjectMapper\Tests\Model\UserWithCustomSetter;
use Nddcoder\ObjectMapper\Tests\Model\UserWithNullableString;

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
        $this->assertEquals($data['body'], $user->description);
        $this->assertEquals($data['title'], $user->title);
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
        $data = $this->getData();
        $data['body'] = null;

        $this->expectException(AttributeMustNotBeNullException::class);

        ObjectMapperFacade::readValue(json_encode($data), User::class);
    }

    /** @test */
    public function it_can_custom_setter()
    {
        $data = [
            'company' => 'nddcoder',
            'auth_key' => [
                'p256dh' => '123',
                'auth' => 'authKey'
            ],
            'request_num' => '1234'
        ];

        /** @var UserWithCustomSetter $user */
        $user = ObjectMapperFacade::readValue(json_encode($data), UserWithCustomSetter::class);

        $this->assertEquals(1234, $user->requestNumber);
        $this->assertEquals('authKey', $user->keys->auth);
    }

    /** @test */
    public function it_should_call_setter_with_null_param_when_input_invalid_type()
    {
        $data = [
            'company' => 'nddcoder',
            'auth_key' => 'invalid_type_of_Keys',
            'request_num' => '1234'
        ];

        /** @var UserWithCustomSetter $user */
        $user = ObjectMapperFacade::readValue(json_encode($data), UserWithCustomSetter::class);

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
                'abc' => 1
            ],
        ];

        /** @var UserWithNullableString $user */
        $user = ObjectMapperFacade::readValue(json_encode($data), UserWithNullableString::class);

        $this->assertNull($user->company);
    }
}
