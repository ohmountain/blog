<?php
/**
 * Created by PhpStorm.
 * User: renshan
 * Date: 16-8-3
 * Time: 下午4:48
 */

namespace AdminBundle\Common;


use Symfony\Component\HttpFoundation\Response;

trait ResponseTrait
{
    private $response;

    protected $FETCH_TYPE_OK = 700;
    protected $FETCH_TYPE_WITH_ZERO = 701;
    protected $FETCH_TYPE_FAIL = 702;

    protected $FETCH_BLOG_OK = 800;
    protected $FETCH_BLOG_WITH_ZERO = 801;
    protected $FETCH_BLOG_FAIL = 802;

    public function __construct()
    {
        $this->response = new Response();
        $this->response->headers->set('Content-Type', 'application/json');
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function wrapResult($ok=true, $statusCode, Array $data)
    {
        $resData = [
            'ok' => $ok,
            'statusCode' => $statusCode,
            'data' => $data
        ];

        $this->response->setContent(json_encode($resData));
    }
}