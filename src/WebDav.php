<?php


namespace NextCloudWebDavSdk;

use Sabre\HTTP;
use Sabre\DAV\Client;
use Sabre\DAV\Xml\Property\ResourceType;

use function Sabre\HTTP\decodePath;

class WebDav
{
    public $client;
    protected $baseUrl;
    protected $rootUrl;
    protected $userSearchScope;
    protected $selectProperties = [
        '{DAV:}getlastmodified',
        '{DAV:}getetag',
        '{DAV:}getcontenttype',
        '{DAV:}resourcetype',
        '{DAV:}getcontentlength',
        '{http://owncloud.org/ns}id',
        '{http://owncloud.org/ns}fileid',
        '{http://owncloud.org/ns}favorite',
        '{http://owncloud.org/ns}comments-href',
        '{http://owncloud.org/ns}comments-count',
        '{http://owncloud.org/ns}comments-unread',
        '{http://owncloud.org/ns}owner-id',
        '{http://owncloud.org/ns}owner-display-name',
        '{http://owncloud.org/ns}share-types',
        '{http://owncloud.org/ns}checksums',
        '{http://owncloud.org/ns}has-preview',
        '{http://owncloud.org/ns}size',
    ];

    public function __construct($host, $login, $pass)
    {
        $this->rootUrl = "$host/remote.php/dav/";
        $this->baseUrl = "$host/remote.php/dav/files/$login/";
        $this->userSearchScope = "/files/$login";
        $this->client = new Client(array(
            'baseUri' => $this->baseUrl,
            'userName' => $login,
            'password' => $pass,
        ));
    }

    /**
     * @param string $folder
     * @return array
     * @throws \Sabre\HTTP\ClientHttpException
     */
    public function getListingFolder($folder = '')
    {
        $structures = $this->client->propFind($folder, $this->selectProperties, 1);
        return $this->prepareStructureResponse($structures, $this->selectProperties);
    }

    /**
     * @param $path
     * @param $savePath
     * @return string
     * @throws \Exception
     */
    public function downloadFile($path, $savePath)
    {
        if (empty($path)) {
            throw new \Exception('Empty download file');
        }
        if (empty($savePath)) {
            throw new \Exception('Empty Save path');
        }
        $parsePath = pathinfo($path);

        $url = $this->baseUrl . $path;
        $response = $this->client->request('GET', $url);
        if ($response['statusCode'] != 200) {
            throw new \Exception('Error download file.' . $response['body']);
        }

        file_put_contents($savePath . '/' . $parsePath['basename'], $response['body']);

        return $savePath . '/' . $parsePath['basename'];
    }

    /**
     * @param $uploadFile
     * @param string $uploadPath
     * @return bool
     * @throws \Exception
     */
    public function uploadFile($uploadFile, $uploadPath = '')
    {
        if (empty($uploadFile)) {
            throw new \Exception('Empty upload file');
        }

        $parseUploadFile = pathinfo($uploadFile);

        $url = $this->baseUrl . $uploadPath . $parseUploadFile['basename'];

        $uploadFile = file_get_contents($uploadFile);

        $response = $this->client->request('PUT', $url, $uploadFile);

        if ($response['statusCode'] != 201) {
            throw new \Exception('Error upload file.' . $response['body']);
        }
        return true;
    }

    /**
     * @param $path
     * @return bool
     * @throws \Exception
     */
    public function createFolder($path)
    {
        if (empty($path)) {
            throw new \Exception('Empty create folder path');
        }

        $url = $this->baseUrl . $path;
        $response = $this->client->request('MKCOL', $url);
        if ($response['statusCode'] != 201) {
            throw new \Exception('Error create folder.' . $response['body']);
        }
        return true;
    }

    /**
     * @param $path
     * @return bool
     * @throws \Exception
     */
    public function removeFileOrDirectory($path)
    {
        if (empty($path)) {
            throw new \Exception('Empty path remove file or directory');
        }
        $url = $this->baseUrl . $path;
        $response = $this->client->request('DELETE', $url);
        if ($response['statusCode'] != 204) {
            throw new \Exception('Error remove file or directory.' . $response['body']);
        }

        return true;
    }

    /**
     * @param $move
     * @param $destination
     * @return bool
     * @throws \Exception
     */
    public function moveFileOrDirectory($move, $destination)
    {
        if (empty($move)) {
            throw new \Exception('Empty move path');
        }
        if (empty($destination)) {
            throw new \Exception('Empty destination path');
        }

        $movePath = $this->baseUrl . $move;
        $destinationPath = $this->baseUrl . $destination;

        $response = $this->client->request(
            'MOVE',
            $movePath,
            null,
            array(
                'Destination' => $destinationPath
            ));

        if ($response['statusCode'] != 201) {
            throw new \Exception('Error move file or directory.' . $response['body']);
        }

        return true;
    }

    /**
     * @param $copy
     * @param $destination
     * @return bool
     * @throws \Exception
     */
    public function copyFileOrDirectory($copy,$destination){
        if (empty($copy)) {
            throw new \Exception('Empty move path');
        }
        if (empty($destination)) {
            throw new \Exception('Empty destination path');
        }
        $copyPath = $this->baseUrl . $copy;
        $destinationPath = $this->baseUrl . $destination;

        $response = $this->client->request(
            'COPY',
            $copyPath,
            null,
            array(
                'Destination' => $destinationPath
            ));

        if ($response['statusCode'] != 201) {
            throw new \Exception('Error move file or directory.' . $response['body']);
        }

        return true;
    }

    /**
     * Поиск фалов и папок
     *
     * @param array $selectProperties
     * @param $searchScope
     * @param $searchScopeDepth
     * @param array $searchWhere
     * @param array $searchOrderBy
     * @return array|bool
     * @throws HTTP\ClientException
     * @throws HTTP\ClientHttpException
     */
    function search(array $selectProperties, $searchScope, $searchScopeDepth, array $searchWhere, array $searchOrderBy)
    {
        if (empty($selectProperties)) {
            $selectProperties = $this->selectProperties;
        }
        if (empty($searchScopeDepth)) {
            $searchScopeDepth = 'infinity';
        }
        if (empty($searchScope)) {
            $searchScope = '/';
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $searchrequest = $dom->createElementNS('DAV:', 'd:searchrequest');
        $searchrequest->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:oc', 'http://owncloud.org/ns');
        $basicsearch = $dom->createElement('d:basicsearch');

        $select = $dom->createElement('d:select');
        $prop = $dom->createElement('d:prop');
        foreach ($selectProperties as $property) {
            $prop->appendChild(self::createElementNodeProperty($property, $dom));
        }
        $basicsearch->appendChild($select)->appendChild($prop);

        $from = $dom->createElement('d:from');
        $scope = $dom->createElement('d:scope');
        $scope->appendChild($dom->createElement('d:href', $this->userSearchScope . $searchScope));
        $scope->appendChild($dom->createElement('d:depth', $searchScopeDepth));
        $basicsearch->appendChild($from)->appendChild($scope);

        $where = $dom->createElement('d:where');
        foreach ($searchWhere as $property => $value) {
            $operator = explode('::', $property);
            if (count($operator) == 2) {
                $operator = [
                    'operator' => $operator[0],
                    'property' => $operator[1],
                ];
            } else {
                $operator = [
                    'operator' => 'eq',
                    'property' => $operator[0]
                ];
            }
            $element = $dom->createElement('d:' . $operator['operator']);
            $prop = $dom->createElement('d:prop');
            $prop->appendChild(self::createElementNodeProperty($operator['property'], $dom));
            $element->appendChild($prop);
            $element->appendChild($dom->createElement('d:literal', $value));
            $where->appendChild($element);
        }
        $basicsearch->appendChild($where);

        $orderby = $dom->createElement('d:orderby');
        foreach ($searchOrderBy as $property => $direction) {
            $prop = $dom->createElement('d:prop');
            $prop->appendChild(self::createElementNodeProperty($operator['property'], $dom));
            $orderby->appendChild($prop);
            $orderby->appendChild($dom->createElement('d:' . $direction));
        }
        $basicsearch->appendChild($orderby);

        $dom->appendChild($searchrequest)->appendChild($basicsearch);
        $body = $dom->saveXML();

        $response = $this->client->send(new HTTP\Request(
            'SEARCH',
            $this->rootUrl,
            ['Content-Type' => 'text/xml'],
            $body
        ));

        if ((int)$response->getStatus() >= 400) {
            return false;
        }

        $result = $this->client->parseMultiStatus($response->getBodyAsString());
        $newResult = [];
        foreach ($result as $href => $statusList) {
            $newResult[$href] = isset($statusList[200]) ? $statusList[200] : [];
        }

        return $this->prepareStructureResponse($newResult, $selectProperties);
    }

    /**
     * Создание DOM-элемента для вставки в xml-тело запроса
     *
     * @param $property
     * @param \DOMDocument $dom
     * @return \DOMElement
     */
    public function createElementNodeProperty($property, \DOMDocument $dom)
    {
        list($namespace, $elementName) = \Sabre\Xml\Service::parseClarkNotation($property);
        switch ($namespace) {
            case 'DAV:':
                $element = $dom->createElement('d:' . $elementName);
                break;
            case 'http://owncloud.org/ns':
                $element = $dom->createElement('oc:' . $elementName);
                break;
            default:
                $element = $dom->createElementNS($namespace, 'x:' . $elementName);
        }
        return $element;
    }

    /**
     * Преобразование массива с ответом в удобочитаемый вид
     *
     * @param array $response
     * @param array $properties
     * @return array
     */
    public function prepareStructureResponse(array $response, array $properties)
    {
        $structures = [];

        foreach ($response as $path => $selectProperties) {
            $path = decodePath($path);
            $parsepath = pathinfo($path);

            $structure = [
                'path' => $path,
                'element-name' => $parsepath['filename'],
            ];
            foreach ($selectProperties as $code => $value) {
                if (in_array($code, $properties)) {
                    list($namespace, $propertyCode) = \Sabre\Xml\Service::parseClarkNotation($code);
                    if (is_object($value)) {
                        $value = $value->getValue();
                    } elseif (is_array($value)) {
                        $value = array_column($value, 'value');
                    }
                    $structure[$propertyCode] = $value;
                }
            }

            $structures[] = $structure;
        }

        return $structures;
    }
}