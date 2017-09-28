<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PrivateController extends Controller
{
    public function postAction(Request $request): Response
    {
        $response = new Response();
        $ret_data = [
            'code' => 200,
        ];

        $title = $request->get('title');
        $body  = $request->get('body');

        if ($title == null || $body == null) {
            $ret_data['code'] = 400;

            $response->setContent(json_encode($ret_data));
            return $response;
        }

        $post = $this->get('private.manager')->create($title, $body);

        $response->setContent(json_encode($ret_data));
        return $response;
    }

    public function getByTitleAction(string $title): Response
    {
        $response = new Response();
        $ret_data = [
            'coed' => 200,
            'posts' => []
        ];

        $title = base64_decode($title);

        $posts = $this->get('private.manager')->getByTitle($title);

        foreach($posts as $post) {
            array_push($ret_data['posts'], [
                'id' => $post->getId(),
                'title' => $post->getTitle()
            ]);
        };

        $response->setContent(json_encode($ret_data));
        return $response;
    }

    public function getByIdAction(int $id): Response
    {
        $response = new Response();

        $post = $this->get('private.manager')->getById($id);

        if ($post == null) {
            $response->setContent(json_encode([
                'code' => 404
            ]));

            return $response;
        }

        $response->setContent(json_encode([
            'code' => 200,
            'post' => [
                'title' => $post->getTitle(),
                'body'  => $post->getBody()
            ]
        ]));

        return $response;
    }
}
