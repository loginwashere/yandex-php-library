<?php

namespace Yandex\Tests\OAuth;

use Yandex\Disk\DiskClient;
use Yandex\Tests\TestCase;
use Yandex\Disk\Exception\DiskRequestException;

class DiskClientTest extends TestCase
{
    public function testCreate()
    {
        $this->getDiskClient();
    }

    public function testGetClient()
    {
        $diskClient = $this->getDiskClient();

        $getClient = self::getNotAccessibleMethod($diskClient, 'getClient');

        $guzzleClient = $getClient->invokeArgs($diskClient, []);

        $this->assertInstanceOf('\GuzzleHttp\ClientInterface', $guzzleClient);
    }

    public function testDiskRequestExceptionWithCode()
    {
        $diskClient = $this->getDiskClient();

        try {
            $diskClient->createDirectory('/test');
            $this->fail('DiskRequestException has not been thrown');
        } catch (DiskRequestException $e) {
            $this->assertEquals(401, $e->getCode());
        }
    }

    /**
     * @param $url
     * @param $name
     * @param $expectedException
     *
     * @dataProvider dataGetDocviewerUrlWithEmptyUrl
     */
    public function testGetDocviewerUrlWithEmptyUrl($url, $name, $expectedException)
    {
        $this->setExpectedException($expectedException['class'], $expectedException['message']);

        $diskClient = $this->getDiskClient();

        $diskClient->getDocviewerUrl($url, $name);
    }

    /**
     * @return array
     */
    public function dataGetDocviewerUrlWithEmptyUrl()
    {
        return [
            'empty params' => [
                'url' => null,
                'name' => null,
                'expectedException' => [
                    'class' => 'InvalidArgumentException',
                    'message' => "Parameter 'url' must not be empty"
                ]
            ],
            'not valid url' => [
                'url' => 'test',
                'name' => null,
                'expectedException' => [
                    'class' => 'InvalidArgumentException',
                    'message' => "Parameter 'url' is not url"
                ]
            ]
        ];
    }

    /**
     * @param $url
     * @param $name
     * @param $expectedResult
     *
     * @dataProvider dataGetDocviewerUrl
     */
    public function testGetDocviewerUrl($url, $name, $expectedResult)
    {
        $diskClient = $this->getDiskClient();

        $result = $diskClient->getDocviewerUrl($url, $name);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function dataGetDocviewerUrl()
    {
        return [
            'empty name' => [
                'url' => 'https://yandex.ua',
                'name' => null,
                'expectedResult' => 'https://docviewer.yandex.ru/?url=https%3A%2F%2Fyandex.ua'
            ],
            'not empty name' => [
                'url' => 'https://yandex.ua',
                'name' => 'yandex',
                'expectedResult' => 'https://docviewer.yandex.ru/?url=https%3A%2F%2Fyandex.ua&name=yandex'
            ],
            'params with spaces' => [
                'url' => 'https://yandex.ua',
                'name' => 'yandex yandex',
                'expectedResult' => 'https://docviewer.yandex.ru/?url=https%3A%2F%2Fyandex.ua&name=yandex%20yandex'
            ],
            'actually link to file' => [
                'url' => 'https://bitcoin.org/bitcoin.pdf',
                'name' => 'bitcoin',
                'expectedResult' => 'https://docviewer.yandex.ru/?url=https%3A%2F%2Fbitcoin.org%2Fbitcoin.pdf&name=bitcoin'
            ],
            'cyrillic in params' => [
                'url' => 'https://bitcoin.org/bitcoin.pdf',
                'name' => 'биткоин биткоин',
                'expectedResult' => 'https://docviewer.yandex.ru/?url=https%3A%2F%2Fbitcoin.org%2Fbitcoin.pdf&name=%D0%B1%D0%B8%D1%82%D0%BA%D0%BE%D0%B8%D0%BD%20%D0%B1%D0%B8%D1%82%D0%BA%D0%BE%D0%B8%D0%BD'
            ],
            'link to yandex disk' => [
                'url' => 'ya-disk:///disk/000006a3-193f-4d72-b7a2-59d8203e5657.zip',
                'name' => null,
                'expectedResult' => 'https://docviewer.yandex.ru/?url=ya-disk%3A%2F%2F%2Fdisk%2F000006a3-193f-4d72-b7a2-59d8203e5657.zip'
            ],
            'link to yandex disk when file in folder disk:' => [
                'url' => 'ya-disk:///disk/disk:/000006a3-193f-4d72-b7a2-59d8203e5657.zip',
                'name' => null,
                'expectedResult' => 'https://docviewer.yandex.ru/?url=ya-disk%3A%2F%2F%2Fdisk%2Fdisk%3A%2F000006a3-193f-4d72-b7a2-59d8203e5657.zip'
            ],
            'link to yandex disk using only path on yandex disk' => [
                'url' => 'disk:/000006a3-193f-4d72-b7a2-59d8203e5657.zip',
                'name' => null,
                'expectedResult' => 'https://docviewer.yandex.ru/?url=ya-disk%3A%2F%2F%2Fdisk%2F000006a3-193f-4d72-b7a2-59d8203e5657.zip'
            ],
            'link to yandex disk using only path on yandex disk when file in folder disk:' => [
                'url' => 'disk:/disk:/000006a3-193f-4d72-b7a2-59d8203e5657.zip',
                'name' => null,
                'expectedResult' => 'https://docviewer.yandex.ru/?url=ya-disk%3A%2F%2F%2Fdisk%2Fdisk%3A%2F000006a3-193f-4d72-b7a2-59d8203e5657.zip'
            ],
            'link to yandex disk public file' => [
                'url' => 'ya-disk-public://gIjnct53GxRULQKWfHtJt/9frhmdGXXAgFIL6eMruDU=',
                'name' => 'Spetakli_v_noyabre.docx',
                'expectedResult' => 'https://docviewer.yandex.ru/?url=ya-disk-public%3A%2F%2FgIjnct53GxRULQKWfHtJt%2F9frhmdGXXAgFIL6eMruDU%3D&name=Spetakli_v_noyabre.docx'
            ],
            'link to yandex disk public file image' => [
                'url' => 'ya-disk-public://WNVTM/3bBEeOJp6szKT+M1T34W7/2rYD4ITzUXKv7P4=',
                'name' => '18nsju2r17odgjpg.jpg',
                'expectedResult' => 'https://docviewer.yandex.ru/?url=ya-disk-public%3A%2F%2FWNVTM%2F3bBEeOJp6szKT%2BM1T34W7%2F2rYD4ITzUXKv7P4%3D&name=18nsju2r17odgjpg.jpg'
            ],
            'link to yandex disk public file using only public key' => [
                'url' => 'WNVTM/3bBEeOJp6szKT+M1T34W7/2rYD4ITzUXKv7P4=',
                'name' => '18nsju2r17odgjpg.jpg',
                'expectedResult' => 'https://docviewer.yandex.ru/?url=ya-disk-public%3A%2F%2FWNVTM%2F3bBEeOJp6szKT%2BM1T34W7%2F2rYD4ITzUXKv7P4%3D&name=18nsju2r17odgjpg.jpg'
            ]
        ];
    }

    /**
     * @param string $token
     * @return DiskClient
     */
    private function getDiskClient($token = 'test')
    {
        return new DiskClient($token);
    }
}