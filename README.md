# Object mapper

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nddcoder/laravel-object-mapper.svg?style=flat-square)](https://packagist.org/packages/nddcoder/laravel-object-mapper)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/dangdungcntt/laravel-object-mapper/run-tests?label=tests)](https://github.com/nddcoder/laravel-object-mapper/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/nddcoder/laravel-object-mapper.svg?style=flat-square)](https://packagist.org/packages/nddcoder/laravel-object-mapper)

An `ObjectMapper` for PHP (inspired by ObjectMapper in java)

## Installation

You can install the package via composer:

```bash
composer require nddcoder/laravel-object-mapper
```

## Usage

``` php

use Nddcoder\ObjectMapper\ObjectMapperFacade;

class User {
    public string $name;
    public string $email;
}

//Make object from json string
$jsonString = '{"name":"Dung Nguyen Dang","email":"dangdungcntt@gmail.com"}';
$user = ObjectMapperFacade::readValue($jsonString, User::class);
echo $user->name; //Dung Nguyen Dang
echo $user->email; //dangdungcntt@gmail.com

//You can pass an associative array to readValue function instead of string
$user = ObjectMapperFacade::readValue(['name' => 'Dung Nguyen Dang', 'email' => 'dangdungcntt@gmail.com'], User::class);

//Convert object to json string
$userJsonString = ObjectMapperFacade::writeValueAsString($user);
echo $userJsonString; //{"name":"Dung Nguyen Dang","email":"dangdungcntt@gmail.com"}
```

#### Array Property

Use `ArrayProperty` Attribute to specific type of array item

``` php

use Nddcoder\ObjectMapper\ObjectMapperFacade;
use Nddcoder\ObjectMapper\Attributes\ArrayProperty;

class Comment {
    public string $from;
    public string $content;
}

class Post {
    public string $title;
    
    #[ArrayProperty(Comment::class)]
    public array $comments;
}

//Make object from json string
$jsonString = '{"title":"New Blog Post","comments":[{"from":"nddcoder","content":"Hello"}]}';
$post = ObjectMapperFacade::readValue($jsonString, Post::class);
echo $post->title; //New Blog Post
print_r($post->comments);
/*
Array
(
    [0] => Comment Object
        (
            [from] => nddcoder
            [content] => Hello
        )

)
*/
```

#### Custom JSON property

You can use `JsonProperty('<propertyName>')` to custom name for a property
``` php

use Nddcoder\ObjectMapper\ObjectMapperFacade;
use Nddcoder\ObjectMapper\Attributes\JsonProperty;

class Post {
    public string $title;
    
    #[JsonProperty('body')]
    public string $content;
}

//Make object from json string
$jsonString = '{"title":"New Blog Post","body":"Blog body here"}';
$post = ObjectMapperFacade::readValue($jsonString, Post::class);
echo $post->title; //New Blog Post
echo $post->content; //Blog body here

//Convert object to json string
$postJsonString = ObjectMapperFacade::writeValueAsString($post);
echo $postJsonString; //{"title":"New Blog Post","body":"Blog body here"}
```

#### Custom behavior via getter/setter

You can define getter/setter to custom behavior when get/set a property
``` php

use Nddcoder\ObjectMapper\ObjectMapperFacade;
use Nddcoder\ObjectMapper\Attributes\JsonProperty;

class User {
    public string $username;
    protected string $password;
    
    public function setUsername(string $username): void
    {
        $this->username = strtolower($username);
    }
    
    public function getPassword(): ?string
    {
        return null;
    }
    
    public function setPassword(string $password): void
    {
        $this->password = md5($password);
    }
}

//Make object from json string
$jsonString = '{"username":"nddcoder","password":"secret"}';
$user = ObjectMapperFacade::readValue($jsonString, User::class);
echo $user->username; //nddcoder
echo $user->content; //5ebe2294ecd0e0f08eab7690d2a6ee69

//Convert object to json string
$userJsonString = ObjectMapperFacade::writeValueAsString($user);
echo $userJsonString; //{"username":"nddcoder","password":null}
```

#### Encoders

By default, package included 2 encoders for `DateTimeInterface` and `stdClass`

You can create your custom encoder by implements `ObjectMapperEncoder` interface

```php
use MongoDB\BSON\ObjectId;
use Nddcoder\ObjectMapper\Contracts\ObjectMapperEncoder;

class ObjectIdEncoder implements ObjectMapperEncoder
{
    public function encode(mixed $value, ?string $className = null): string
    {
        return (string) $value;
    }

    public function decode(mixed $value, ?string $className = null): mixed
    {
        return new ObjectId($value);
    }
}
```

and then add it using `ObjectMapper::addEncoder` function

```php
ObjectMapper::addEncoder(ObjectId::class, ObjectIdEncoder::class);
```

You can remove an encoder of a class using `ObjectMapper::removeEncoder` function 

```php
ObjectMapper::removeEncoder(ObjectId::class);
```

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Dung Nguyen Dang](https://github.com/dangdungcntt)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
