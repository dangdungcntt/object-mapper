<?php


namespace Nddcoder\ObjectMapper\Tests\Model;


class ModelWithNullUnionType
{
    public DeviceInfo|Keys|null $magicField;
}
