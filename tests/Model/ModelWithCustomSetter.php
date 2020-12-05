<?php


namespace Nddcoder\ObjectMapper\Tests\Model;

class ModelWithCustomSetter
{
    public ?Keys $keys;
    public int $requestNumber;
    public string $company;
    public string $req_id;

    public function setReq_id(string $reqId)
    {
        $this->req_id = 'req-' . $reqId;
    }

    public function setAuth_key(?Keys $keys)
    {
        $this->keys = $keys;
    }

    public function setRequestNum(string $requestNum)
    {
        $this->requestNumber = (int)$requestNum;
    }
}
