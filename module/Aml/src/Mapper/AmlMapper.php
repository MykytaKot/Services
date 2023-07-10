<?php

declare(strict_types=1);

namespace Aml\Mapper;

use Aml\Entity\AmlList;
use Aml\Entity\AmlName;
use Aml\Service\SlugifyService;
use Aml\Traits\AmlTraitMethods;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Laminas\Dom\Query;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use Psr\Http\Message\UriInterface;

class AmlMapper
{
    use AmlTraitMethods;

    private $birthdate;
    private $client;
    private $client_options = [
        RequestOptions::HEADERS => [
            'User-Agent' => 'Mozilla/5.0 (X11; Linux; Linux x86_64; rv:90.0) Gecko/20100101 Firefox/90.0',
        ],
        RequestOptions::HTTP_ERRORS => false,
        RequestOptions::VERIFY => false
    ];
    private $collection_lists;
    private $collection_names;
    private $config;
    private $error = ['error' => true];
    private $fullname;
    private $success = ['success' => true];
    private $uri;

    public function __construct(
        Collection $collection_lists,
        Collection $collection_names,
        array $config
    ) {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $this->setCollectionLists($collection_lists);
        $this->setCollectionNames($collection_names);
        $this->setConfig($config);
        $this->client();
    }

    ///////////////////////////////////////
    ////////// Getters & Setters //////////
    ///////////////////////////////////////

    private function getBirthdate(): ?string
    {
        return $this->birthdate;
    }
    private function setBirthdate(?string $input): void
    {
        $this->birthdate = $input;
    }

    private function getClient(): Client
    {
        return $this->client;
    }
    private function setClient(Client $input): void
    {
        $this->client = $input;
    }

    private function getCollectionLists(): Collection
    {
        return $this->collection_lists;
    }
    private function setCollectionLists(Collection $input): void
    {
        $this->collection_lists = $input;
    }

    private function getCollectionNames(): Collection
    {
        return $this->collection_names;
    }
    private function setCollectionNames(Collection $input): void
    {
        $this->collection_names = $input;
    }

    private function getConfig(): array
    {
        return $this->config;
    }
    private function setConfig(array $input): void
    {
        $this->config = $input;
    }

    private function getError(): array
    {
        return $this->error;
    }
    private function setError(array $input): void
    {
        $this->error = array_merge($this->error, $input);
    }

    private function getFullname(): ?string
    {
        return $this->fullname;
    }
    private function setFullname(?string $input): void
    {
        $this->fullname = $this->preSanitizeFullname($input);
    }

    private function getSuccess(): array
    {
        return $this->success;
    }
    private function setSuccess(array $input): void
    {
        $this->success = array_merge($this->success, $input);
    }

    private function getUri(): UriInterface
    {
        return $this->uri;
    }
    private function setUri(UriInterface $input): void
    {
        $this->uri = $input;
    }

    /////////////////////////////////////
    ////////// Private Methods //////////
    /////////////////////////////////////

    private function client(): void
    {
        $this->client_options[RequestOptions::COOKIES] = new CookieJar();
        $this->setClient(new Client($this->client_options));
    }

    private function uri(string $input): UriInterface
    {
        $return = new Uri($input);
        return $return;
    }

    private function get()
    {
        $request = false;
        $response = false;
        $request = $this->getClient()->request('GET', $this->getUri());
        $response = $request->getBody()->getContents();
        return $response;
    }

    private function post(array $input)
    {
        $request = false;
        $response = false;
        $request = $this->getClient()->request('POST', $this->getUri(), $input);
        $response = $request->getBody()->getContents();
        $response = json_decode($response, true);
        return $response;
    }

    private function process(string $input): array
    {
        $response = $this->{$input}();
        return $response;
    }

    private function processAll(): array
    {
        $response = [];
        $response[] = $this->process_1();
        $response[] = $this->process_2();
        $response[] = $this->process_3();
        $response[] = $this->process_4();
        $response[] = $this->process_5();
        $response[] = $this->process_6();
        $response[] = $this->process_7();
        $response[] = $this->process_8();
        return $response;
    }

    // OK
    private function process_1(): array
    {
        $url = 'https://webgate.ec.europa.eu/fsd/fsf/public/files/xmlFullSanctionsList_1_1/content?token=dG9rZW4tMjAxNw';
        $list = $this->getCollectionLists()->findOne(['id' => 1]);
        if (empty($list))
        {
            $this->getCollectionLists()->insertOne([
                'id' => 1,
                'active' => true,
                'label' => 'EU Financial Sanctions List',
                'logs' => [],
                'name' => 'eu_sanctions',
                'order' => 1,
                'updated' => null
            ]);
            $list = $this->getCollectionLists()->findOne(['id' => 1]);
        }
        $aml_list = new AmlList();
        $aml_list->setAmlList($list);
        unset($list);
        $this->setError(['label' => $aml_list->getLabel(), 'name' => $aml_list->getName()]);
        $this->setSuccess(['label' => $aml_list->getLabel(), 'name' => $aml_list->getName()]);
        if (! $aml_list->getActive())
        {
            $this->setSuccess(['message' => 'disabled']);
            return $this->getSuccess();
        }
        $data = null;
        $this->setUri($this->uri($url));
        $data = $this->get();
        if (empty($data))
        {
            $this->setError(['message' => 'empty-response']);
            $aml_list->addLog([
                'created' => new UTCDateTime(),
                'error' => 'empty-response',
            ]);
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
            return $this->getError();
        }
        $xml = simplexml_load_string($data);
        $data = [];
        foreach ($xml->sanctionEntity as $item)
        {
            if (count($item))
            {
                $birthdate = (! empty($item->birthdate) && ! empty($item->birthdate->attributes()->birthdate)) ? $item->birthdate->attributes()->birthdate->__toString() : null;
                $fullname = (! empty($item->nameAlias) && ! empty($item->nameAlias->attributes()->wholeName)) ? $item->nameAlias->attributes()->wholeName->__toString() : null;
                if (empty($fullname))
                {
                    continue;
                }
                $data[] = [
                    'birthdate' => $this->sanitizeBirthdate($birthdate),
                    'fullname' => $this->sanitize($fullname),
                    'list_id' => $aml_list->getId(),
                ];
            }
        }
        if (empty($data))
        {
            $this->setError(['message' => 'empty-data']);
            $aml_list->addLog([
                'created' => new UTCDateTime(),
                'error' => 'empty-data',
            ]);
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
            return $this->getError();
        }
        $data = $this->arraySortUniqueByKey($data, 'fullname');
        $names = iterator_to_array($this->getCollectionNames()->find(['list_id' => $aml_list->getId()]));
        $to_delete = $this->arrayDiff($names, $data, 'fullname', false);
        if (! empty($to_delete))
        {
            foreach ($to_delete as $del_name)
            {
                $this->getCollectionNames()->deleteOne(['list_id' => $aml_list->getId(), 'fullname' => $del_name]);
            }
            $aml_list->addLog([
                'action' => 'delete',
                'count' => count($to_delete),
                'created' => new UTCDateTime(),
                'data' => $to_delete
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }

        $to_insert = $this->arrayDiff($names, $data, 'fullname', true);
        $to_update = $this->arrayIntersect($names, $data, 'fullname', 'birthdate', true);;
        foreach ($data as $item) {
            if(
                in_array($item['fullname'], $to_insert)
                || in_array($item['fullname'], $to_update)
            ) {
                $aml_name = new AmlName();
                $aml_name->setAmlName($item);
                $this->getCollectionNames()->updateOne(['list_id' => $aml_name->getListId(), 'fullname' => $aml_name->getFullname()], ['$set' => $aml_name->getAmlName()], ['upsert' => true]);
            }
        }
        if (! empty($to_insert))
        {
            $aml_list->addLog([
                'action' => 'insert',
                'count' => count($to_insert),
                'created' => new UTCDateTime(),
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }
        if (! empty($to_update))
        {
            $aml_list->addLog([
                'action' => 'update',
                'count' => count($to_update),
                'created' => new UTCDateTime(),
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }
        $this->setSuccess([
            'deleted' => count($to_delete),
            'inserted' => count($to_insert),
            'updated' => count($to_update),
        ]);
        return $this->getSuccess();
    }

    // OK - long
    private function process_2(): array
    {
        $url = 'https://www.europarl.europa.eu/meps/en/full-list/xml';
        $list = $this->getCollectionLists()->findOne(['id' => 2]);
        if (empty($list)) {
            $this->getCollectionLists()->insertOne([
                'id' => 2,
                'active' => true,
                'label' => 'MEPs European Parliament',
                'logs' => [],
                'name' => 'eu_parliament',
                'order' => 2,
                'updated' => null
            ]);
            $list = $this->getCollectionLists()->findOne(['id' => 2]);
        }
        $aml_list = new AmlList();
        $aml_list->setAmlList($list);
        unset($list);
        $this->setError(['label' => $aml_list->getLabel(), 'name' => $aml_list->getName()]);
        $this->setSuccess(['label' => $aml_list->getLabel(), 'name' => $aml_list->getName()]);
        if (! $aml_list->getActive()) {
            $this->setSuccess(['message' => 'disabled']);
            return $this->getSuccess();
        }
        $data = null;
        $this->setUri($this->uri($url));
        $data = $this->get();
        if (empty($data)) {
            $this->setError(['message' => 'empty-response']);
            $aml_list->addLog([
                'created' => new UTCDateTime(),
                'error' => 'empty-response',
            ]);
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
            return $this->getError();
        }
        $xml = simplexml_load_string($data);
        $data = [];
        foreach ($xml->mep as $item) {
            if (count($item)) {
                $birthdate = null;
                $fullname = (! empty($item->fullName)) ? $item->fullName->__toString() : null;
                if (empty($fullname)) {
                    continue;
                }
                if (! empty($item->id)) {
                    $url = null;
                    $url = 'http://www.europarl.europa.eu/meps/en/'.$item->id->__toString();
                    $this->setUri($this->uri($url));
                    $html = null;
                    $html = $this->get();
                    $pattern = "/\<time\s+class\=\"\s*sln\-birth\-date\"\s+datetime\=\"\s*(.*)\s*\"\>/umi";
                    $match = null;
                    preg_match($pattern, $html, $match);
                    if (! empty($match) && ! empty($match[1])) {
                        $date = new DateTime($match[1]);
                        $birthdate = $date->format('Y-m-d');
                    }
                }
                $data[] = [
                    'birthdate' => $this->sanitizeBirthdate($birthdate),
                    'fullname' => $this->sanitize($fullname),
                    'list_id' => $aml_list->getId(),
                ];
            }
        }
        if (empty($data))
        {
            $this->setError(['message' => 'empty-data']);
            $aml_list->addLog([
                'created' => new UTCDateTime(),
                'error' => 'empty-data',
            ]);
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
            return $this->getError();
        }
        $data = $this->arraySortUniqueByKey($data, 'fullname');
        $names = iterator_to_array($this->getCollectionNames()->find(['list_id' => $aml_list->getId()]));

        $to_delete = $this->arrayDiff($names, $data, 'fullname', false);
        if (! empty($to_delete))
        {
            foreach ($to_delete as $del_name)
            {
                $this->getCollectionNames()->deleteOne(['list_id' => $aml_list->getId(), 'fullname' => $del_name]);
            }
            $aml_list->addLog([
                'action' => 'delete',
                'count' => count($to_delete),
                'created' => new UTCDateTime(),
                'data' => $to_delete
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }

        $to_insert = $this->arrayDiff($names, $data, 'fullname', true);
        $to_update = $this->arrayIntersect($names, $data, 'fullname', 'birthdate', false);;
        foreach ($data as $item) {
            if(
                in_array($item['fullname'], $to_insert)
                || in_array($item['fullname'], $to_update)
            ) {
                $aml_name = new AmlName();
                $aml_name->setAmlName($item);
                $this->getCollectionNames()->updateOne(['list_id' => $aml_name->getListId(), 'fullname' => $aml_name->getFullname()], ['$set' => $aml_name->getAmlName()], ['upsert' => true]);
            }
        }
        if (! empty($to_insert))
        {
            $aml_list->addLog([
                'action' => 'insert',
                'count' => count($to_insert),
                'created' => new UTCDateTime(),
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }
        if (! empty($to_update))
        {
            $aml_list->addLog([
                'action' => 'update',
                'count' => count($to_update),
                'created' => new UTCDateTime(),
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }
        $this->setSuccess([
            'deleted' => count($to_delete),
            'inserted' => count($to_insert),
            'updated' => count($to_update),
        ]);
        return $this->getSuccess();
    }

    // OK
    private function process_3(): array
    {
        $url = 'https://scsanctions.un.org/resources/xml/en/consolidated.xml';
        $list = $this->getCollectionLists()->findOne(['id' => 3]);
        if (empty($list)) {
            $this->getCollectionLists()->insertOne([
                'id' => 3,
                'active' => true,
                'label' => 'UN Security Council Consolidated List',
                'logs' => [],
                'name' => 'un_sanctions',
                'order' => 3,
                'updated' => null
            ]);
            $list = $this->getCollectionLists()->findOne(['id' => 3]);
        }
        $aml_list = new AmlList();
        $aml_list->setAmlList($list);
        unset($list);
        $this->setError(['label' => $aml_list->getLabel(), 'name' => $aml_list->getName()]);
        $this->setSuccess(['label' => $aml_list->getLabel(), 'name' => $aml_list->getName()]);
        if (! $aml_list->getActive()) {
            $this->setSuccess(['message' => 'disabled']);
            return $this->getSuccess();
        }
        $data = null;
        $this->setUri($this->uri($url));
        $data = $this->get();
        if (empty($data)) {
            $this->setError(['message' => 'empty-response']);
            $aml_list->addLog([
                'created' => new UTCDateTime(),
                'error' => 'empty-response',
            ]);
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
            return $this->getError();
        }
        $xml = simplexml_load_string($data);
        if (empty($xml) || empty($xml->INDIVIDUALS->INDIVIDUAL)) {
            $this->setError(['message' => 'empty-response']);
            $aml_list->addLog([
                'created' => new UTCDateTime(),
                'error' => 'empty-response',
            ]);
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
            return $this->getError();
        }
        $data = [];
        foreach ($xml->INDIVIDUALS->INDIVIDUAL as $item) {
            if (count($item)) {
                $birthdate = ! empty($item->INDIVIDUAL_DATE_OF_BIRTH->DATE) ? $item->INDIVIDUAL_DATE_OF_BIRTH->DATE->__toString() : null;
                $firstname = ! empty($item->FIRST_NAME) ? $item->FIRST_NAME->__toString() : null;
                $secondname = ! empty($item->SECOND_NAME) ? $item->SECOND_NAME->__toString() : null;
                $thirdname = ! empty($item->THIRD_NAME) ? $item->THIRD_NAME : null;
                $fullname = $firstname . ' ' . $secondname . ' ' . $thirdname;
                if (empty($fullname)) {
                    continue;
                }
                $data[] = [
                    'birthdate' => $this->sanitizeBirthdate($birthdate),
                    'fullname' => $this->sanitize($fullname),
                    'list_id' => $aml_list->getId(),
                ];
            }
        }
        if (empty($data))
        {
            $this->setError(['message' => 'empty-data']);
            $aml_list->addLog([
                'created' => new UTCDateTime(),
                'error' => 'empty-data',
            ]);
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
            return $this->getError();
        }
        $data = $this->arraySortUniqueByKey($data, 'fullname');
        $names = iterator_to_array($this->getCollectionNames()->find(['list_id' => $aml_list->getId()]));

        $to_delete = $this->arrayDiff($names, $data, 'fullname', false);
        if (! empty($to_delete))
        {
            foreach ($to_delete as $del_name)
            {
                $this->getCollectionNames()->deleteOne(['list_id' => $aml_list->getId(), 'fullname' => $del_name]);
            }
            $aml_list->addLog([
                'action' => 'delete',
                'count' => count($to_delete),
                'created' => new UTCDateTime(),
                'data' => $to_delete
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }

        $to_insert = $this->arrayDiff($names, $data, 'fullname', true);
        $to_update = $this->arrayIntersect($names, $data, 'fullname', 'birthdate', true);;
        foreach ($data as $item) {
            if(
                in_array($item['fullname'], $to_insert)
                || in_array($item['fullname'], $to_update)
            ) {
                $aml_name = new AmlName();
                $aml_name->setAmlName($item);
                $this->getCollectionNames()->updateOne(['list_id' => $aml_name->getListId(), 'fullname' => $aml_name->getFullname()], ['$set' => $aml_name->getAmlName()], ['upsert' => true]);
            }
        }
        if (! empty($to_insert))
        {
            $aml_list->addLog([
                'action' => 'insert',
                'count' => count($to_insert),
                'created' => new UTCDateTime(),
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }
        if (! empty($to_update))
        {
            $aml_list->addLog([
                'action' => 'update',
                'count' => count($to_update),
                'created' => new UTCDateTime(),
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }
        $this->setSuccess([
            'deleted' => count($to_delete),
            'inserted' => count($to_insert),
            'updated' => count($to_update),
        ]);
        return $this->getSuccess();
    }

    // OK
    private function process_4(): array
    {
        $url = 'https://www.worldpresidentsdb.com/list/';
        $list = $this->getCollectionLists()->findOne(['id' => 4]);
        if (empty($list)) {
            $this->getCollectionLists()->insertOne([
                'id' => 4,
                'active' => true,
                'label' => 'Presidents and Leaders of the World',
                'logs' => [],
                'name' => 'world_presidents',
                'order' => 4,
                'updated' => null
            ]);
            $list = $this->getCollectionLists()->findOne(['id' => 4]);
        }
        $aml_list = new AmlList();
        $aml_list->setAmlList($list);
        unset($list);
        $this->setError(['label' => $aml_list->getLabel(), 'name' => $aml_list->getName()]);
        $this->setSuccess(['label' => $aml_list->getLabel(), 'name' => $aml_list->getName()]);
        if (! $aml_list->getActive()) {
            $this->setSuccess(['message' => 'disabled']);
            return $this->getSuccess();
        }
        $data = null;
        $this->setUri($this->uri($url));
        $data = $this->get();
        if (empty($data)) {
            $this->setError(['message' => 'empty-response']);
            $aml_list->addLog([
                'created' => new UTCDateTime(),
                'error' => 'empty-response',
            ]);
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
            return $this->getError();
        }
        $pattern = "/href=\"\/\w+(?:\-\w+)+\/\".*\s+.*img\s+src.*\>\s+(.*)\<\/a\>/umi";
        preg_match_all($pattern, $data, $match);
        if (empty($match) || empty($match[1])) {
            $this->setError(['message' => 'empty-data']);
            $aml_list->addLog([
                'created' => new UTCDateTime(),
                'error' => 'empty-response',
            ]);
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
            return $this->getError();
        }
        $data = [];
        foreach ($match[1] as $item) {
            $fullname = ! empty($item) ? $this->sanitize($item) : null;
            if (empty($fullname)) {
                continue;
            }
            $data[] = [
                'birthdate' => null,
                'fullname' => $fullname,
                'list_id' => $aml_list->getId(),
            ];
        }
        if (empty($data))
        {
            $this->setError(['message' => 'empty-data']);
            $aml_list->addLog([
                'created' => new UTCDateTime(),
                'error' => 'empty-data',
            ]);
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
            return $this->getError();
        }
        $data = $this->arraySortUniqueByKey($data, 'fullname');
        $names = iterator_to_array($this->getCollectionNames()->find(['list_id' => $aml_list->getId()]));

        $to_delete = $this->arrayDiff($names, $data, 'fullname', false);
        if (! empty($to_delete))
        {
            foreach ($to_delete as $del_name)
            {
                $this->getCollectionNames()->deleteOne(['list_id' => $aml_list->getId(), 'fullname' => $del_name]);
            }
            $aml_list->addLog([
                'action' => 'delete',
                'count' => count($to_delete),
                'created' => new UTCDateTime(),
                'data' => $to_delete
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }

        $to_insert = $this->arrayDiff($names, $data, 'fullname', true);
        $to_update = $this->arrayIntersect($names, $data, 'fullname', 'birthdate', true);;
        foreach ($data as $item) {
            if(
                in_array($item['fullname'], $to_insert)
                || in_array($item['fullname'], $to_update)
            ) {
                $aml_name = new AmlName();
                $aml_name->setAmlName($item);
                $this->getCollectionNames()->updateOne(['list_id' => $aml_name->getListId(), 'fullname' => $aml_name->getFullname()], ['$set' => $aml_name->getAmlName()], ['upsert' => true]);
            }
        }
        if (! empty($to_insert))
        {
            $aml_list->addLog([
                'action' => 'insert',
                'count' => count($to_insert),
                'created' => new UTCDateTime(),
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }
        if (! empty($to_update))
        {
            $aml_list->addLog([
                'action' => 'update',
                'count' => count($to_update),
                'created' => new UTCDateTime(),
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }
        $this->setSuccess([
            'deleted' => count($to_delete),
            'inserted' => count($to_insert),
            'updated' => count($to_update),
        ]);
        return $this->getSuccess();
    }

    // TODO
    private function process_5(): array
    {
        // cannot crawl target
        // target has protection against crawlers
        // 2022-01-18
        $url = 'https://pace.coe.int/en/members';
        $list = $this->getCollectionLists()->findOne(['id' => 5]);
        if (empty($list)) {
            $this->getCollectionLists()->insertOne([
                'id' => 5,
                'active' => false, // TODO DON'T FORGET TO CHENGE ME
                'label' => 'Council of Europe Parliamentary Assembly',
                'logs' => [],
                'name' => 'eu_council',
                'order' => 5,
                'updated' => null
            ]);
            $list = $this->getCollectionLists()->findOne(['id' => 5]);
        }
        $aml_list = new AmlList();
        $aml_list->setAmlList($list);
        unset($list);
        $this->setError(['label' => $aml_list->getLabel(), 'name' => $aml_list->getName()]);
        $this->setSuccess(['label' => $aml_list->getLabel(), 'name' => $aml_list->getName()]);
        if (! $aml_list->getActive())
        {
            $this->setSuccess(['message' => 'disabled']);
            return $this->getSuccess();
        }

        // TODO FROM HERE
        $pages = 150;
        for ($i = 1; $i <= $pages; $i++) {
            $data = null;
            $this->setUri($this->uri($url . '?page=' . $i));
            $data = $this->get(); // TODO: doesn't follows redirect, returns wrong page
            if (empty($data)) {
                $this->setError(['message' => 'empty-response']);
                return $this->getError();
            }
            $dom = new Query($data);
            $results = $dom->execute('.cards-members > article > p > a');
            $count = count($results); // get number of matches: 4
            foreach ($results as $result) {
                // $result is a DOMElement
            }
        }
        // TODO TILL HERE

        if (empty($data))
        {
            $this->setError(['message' => 'empty-data']);
            $aml_list->addLog([
                'created' => new UTCDateTime(),
                'error' => 'empty-data',
            ]);
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
            return $this->getError();
        }
        $data = $this->arraySortUniqueByKey($data, 'fullname');
        $names = iterator_to_array($this->getCollectionNames()->find(['list_id' => $aml_list->getId()]));

        $to_delete = $this->arrayDiff($names, $data, 'fullname', false);
        if (! empty($to_delete))
        {
            foreach ($to_delete as $del_name)
            {
                $this->getCollectionNames()->deleteOne(['list_id' => $aml_list->getId(), 'fullname' => $del_name]);
            }
            $aml_list->addLog([
                'action' => 'delete',
                'count' => count($to_delete),
                'created' => new UTCDateTime(),
                'data' => $to_delete
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }

        $to_insert = $this->arrayDiff($names, $data, 'fullname', true);
        $to_update = $this->arrayIntersect($names, $data, 'fullname', 'birthdate', true);;
        foreach ($data as $item) {
            if(
                in_array($item['fullname'], $to_insert)
                || in_array($item['fullname'], $to_update)
            ) {
                $aml_name = new AmlName();
                $aml_name->setAmlName($item);
                $this->getCollectionNames()->updateOne(['list_id' => $aml_name->getListId(), 'fullname' => $aml_name->getFullname()], ['$set' => $aml_name->getAmlName()], ['upsert' => true]);
            }
        }
        if (! empty($to_insert))
        {
            $aml_list->addLog([
                'action' => 'insert',
                'count' => count($to_insert),
                'created' => new UTCDateTime(),
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }
        if (! empty($to_update))
        {
            $aml_list->addLog([
                'action' => 'update',
                'count' => count($to_update),
                'created' => new UTCDateTime(),
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }
        $this->setSuccess([
            'deleted' => count($to_delete),
            'inserted' => count($to_insert),
            'updated' => count($to_update),
        ]);
        return $this->getSuccess();
    }

    // OK
    private function process_6(): array
    {
        // https://www.cia.gov/resources/world-leaders/foreign-governments
        $url = 'https://www.cia.gov/page-data/resources/world-leaders/foreign-governments/page-data.json';
        $url_base = 'https://www.cia.gov';
        $list = $this->getCollectionLists()->findOne(['id' => 6]);
        if (empty($list)) {
            $this->getCollectionLists()->insertOne([
                'id' => 6,
                'active' => true,
                'label' => 'CIA World Leaders',
                'logs' => [],
                'name' => 'cia_world_leaders',
                'order' => 6,
                'updated' => null
            ]);
            $list = $this->getCollectionLists()->findOne(['id' => 6]);
        }
        $aml_list = new AmlList();
        $aml_list->setAmlList($list);
        unset($list);
        $this->setError(['label' => $aml_list->getLabel(), 'name' => $aml_list->getName()]);
        $this->setSuccess(['label' => $aml_list->getLabel(), 'name' => $aml_list->getName()]);
        if (! $aml_list->getActive()) {
            $this->setSuccess(['message' => 'disabled']);
            return $this->getSuccess();
        }
        $data = null;
        $this->setUri($this->uri($url));
        $data = $this->get();
        if (empty($data)) {
            $this->setError(['message' => 'empty-response']);
            $aml_list->addLog([
                'created' => new UTCDateTime(),
                'error' => 'empty-response',
            ]);
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
            return $this->getError();
        }
        $data = @json_decode($data, true);
        if (! is_array($data)
            && empty($data['result'])
            && empty($data['result']['data'])
            && empty($data['result']['data']['governments'])
            && empty($data['result']['data']['governments']['edges'])
            && count($data['result']['data']['governments']['edges']) == 0
        ) {
            $this->setError(['message' => 'wrong-response']);
            $aml_list->addLog([
                'created' => new UTCDateTime(),
                'error' => 'wrong-response',
            ]);
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
            return $this->getError();
        }
        $countries = $data['result']['data']['governments']['edges'];
        $data = [];
        foreach ($countries as $item) {
            if (! empty($item['node']) && ! empty($item['node']['path'])) {
                $url_page = $url_base . '/page-data' . $item['node']['path'] . 'page-data.json';
                $country = null;
                $this->setUri($this->uri($url_page));
                $country = $this->get();
                if (! empty($country)) {
                    $country = @json_decode($country, true);
                    if (is_array($country)
                        && ! empty($country['result'])
                        && ! empty($country['result']['data'])
                        && ! empty($country['result']['data']['page'])
                        && ! empty($country['result']['data']['page']['acf'])
                        && ! empty($country['result']['data']['page']['acf']['blocks'])
                        && ! empty($country['result']['data']['page']['acf']['blocks'][0])
                        && ! empty($country['result']['data']['page']['acf']['blocks'][0]['free_form_content'])
                        && ! empty($country['result']['data']['page']['acf']['blocks'][0]['free_form_content']['content'])
                    ) {
                        $content = $country['result']['data']['page']['acf']['blocks'][0]['free_form_content']['content'];
                        // find all names
                        preg_match_all("/\<p\>\s*(.*)\s*\<\/p\>/uim", $content, $match);
                        if (! empty($match)
                            && count($match) == 2
                            && ! empty($match[1])
                        ) {
                            // remove from names `, Dr.` or `(Acting)`
                            $names = preg_replace(["/(?:\s*,\s+|\s*\(|&nbsp;).*/uim", "/&[#\w]+;/uim"], '', $match[1]);
                            if (is_array($names)) {
                                sort($names);
                                $names = array_unique($names);
                                foreach ($names as $fullname) {
                                    if (empty($fullname)) {
                                        continue;
                                    }
                                    $data[] = [
                                        'birthdate' => null,
                                        'fullname' => $this->sanitize($fullname),
                                        'list_id' => $aml_list->getId(),
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
        if (empty($data))
        {
            $this->setError(['message' => 'empty-data']);
            $aml_list->addLog([
                'created' => new UTCDateTime(),
                'error' => 'empty-data',
            ]);
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
            return $this->getError();
        }
        $data = $this->arraySortUniqueByKey($data, 'fullname');
        $names = iterator_to_array($this->getCollectionNames()->find(['list_id' => $aml_list->getId()]));

        $to_delete = $this->arrayDiff($names, $data, 'fullname', false);
        if (! empty($to_delete))
        {
            foreach ($to_delete as $del_name)
            {
                $this->getCollectionNames()->deleteOne(['list_id' => $aml_list->getId(), 'fullname' => $del_name]);
            }
            $aml_list->addLog([
                'action' => 'delete',
                'count' => count($to_delete),
                'created' => new UTCDateTime(),
                'data' => $to_delete
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }

        $to_insert = $this->arrayDiff($names, $data, 'fullname', true);
        $to_update = $this->arrayIntersect($names, $data, 'fullname', 'birthdate', true);;
        foreach ($data as $item) {
            if(
                in_array($item['fullname'], $to_insert)
                || in_array($item['fullname'], $to_update)
            ) {
                $aml_name = new AmlName();
                $aml_name->setAmlName($item);
                $this->getCollectionNames()->updateOne(['list_id' => $aml_name->getListId(), 'fullname' => $aml_name->getFullname()], ['$set' => $aml_name->getAmlName()], ['upsert' => true]);
            }
        }
        if (! empty($to_insert))
        {
            $aml_list->addLog([
                'action' => 'insert',
                'count' => count($to_insert),
                'created' => new UTCDateTime(),
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }
        if (! empty($to_update))
        {
            $aml_list->addLog([
                'action' => 'update',
                'count' => count($to_update),
                'created' => new UTCDateTime(),
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }
        $this->setSuccess([
            'deleted' => count($to_delete),
            'inserted' => count($to_insert),
            'updated' => count($to_update),
        ]);
        return $this->getSuccess();
    }

    // OK - long
    private function process_7(): array
    {
        // https://github.com/everypolitician/everypolitician-data
        $url = 'https://raw.githubusercontent.com/everypolitician/everypolitician-data/master/countries.json';
        $url_base = 'https://raw.githubusercontent.com/everypolitician/everypolitician-data/master/';
        $list = $this->getCollectionLists()->findOne(['id' => 7]);
        if (empty($list)) {
            $this->getCollectionLists()->insertOne([
                'id' => 7,
                'active' => true,
                'label' => 'EveryPolitician',
                'logs' => [],
                'name' => 'everypolitician',
                'order' => 7,
                'updated' => null
            ]);
            $list = $this->getCollectionLists()->findOne(['id' => 7]);
        }
        $aml_list = new AmlList();
        $aml_list->setAmlList($list);
        unset($list);
        $this->setError(['label' => $aml_list->getLabel(), 'name' => $aml_list->getName()]);
        $this->setSuccess(['label' => $aml_list->getLabel(), 'name' => $aml_list->getName()]);
        if (! $aml_list->getActive()) {
            $this->setSuccess(['message' => 'disabled']);
            return $this->getSuccess();
        }
        $data = null;
        $this->setUri($this->uri($url));
        $data = $this->get();
        $countries = @json_decode($data, true);
        if (! is_array($countries) && empty($countries)) {
            $this->setError(['message' => 'empty-response']);
            $aml_list->addLog([
                'created' => new UTCDateTime(),
                'error' => 'empty-response',
            ]);
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
            return $this->getError();
        }
        $data = [];
        foreach ($countries as $item) {
            if (! empty($item['legislatures'])
                && ! empty($item['legislatures'][0])
                && ! empty($item['legislatures'][0]['names'])
            ) {
                $url_page = $url_base . $item['legislatures'][0]['names'];
                $names = null;
                $this->setUri($this->uri($url_page));
                $names = $this->get();
                $names = $this->csv2Json($names);
                $unique = array_unique(array_column($names, 'name'));
                if (! empty($unique)) {
                    foreach ($unique as $fullname) {
                        if (empty($fullname)) {
                            continue;
                        }
                        $data[] = [
                            'birthdate' => null,
                            'fullname' => $this->sanitize($fullname),
                            'list_id' => $aml_list->getId(),
                        ];
                    }
                }
            }
        }
        if (empty($data))
        {
            $this->setError(['message' => 'empty-data']);
            $aml_list->addLog([
                'created' => new UTCDateTime(),
                'error' => 'empty-data',
            ]);
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
            return $this->getError();
        }
        $data = $this->arraySortUniqueByKey($data, 'fullname');
        $names = iterator_to_array($this->getCollectionNames()->find(['list_id' => $aml_list->getId()]));

        $to_delete = $this->arrayDiff($names, $data, 'fullname', false);
        if (! empty($to_delete))
        {
            foreach ($to_delete as $del_name)
            {
                $this->getCollectionNames()->deleteOne(['list_id' => $aml_list->getId(), 'fullname' => $del_name]);
            }
            $aml_list->addLog([
                'action' => 'delete',
                'count' => count($to_delete),
                'created' => new UTCDateTime(),
                'data' => $to_delete
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }

        $to_insert = $this->arrayDiff($names, $data, 'fullname', true);
        $to_update = $this->arrayIntersect($names, $data, 'fullname', 'birthdate', true);;
        foreach ($data as $item) {
            if(
                in_array($item['fullname'], $to_insert)
                || in_array($item['fullname'], $to_update)
            ) {
                $aml_name = new AmlName();
                $aml_name->setAmlName($item);
                $this->getCollectionNames()->updateOne(['list_id' => $aml_name->getListId(), 'fullname' => $aml_name->getFullname()], ['$set' => $aml_name->getAmlName()], ['upsert' => true]);
            }
        }
        if (! empty($to_insert))
        {
            $aml_list->addLog([
                'action' => 'insert',
                'count' => count($to_insert),
                'created' => new UTCDateTime(),
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }
        if (! empty($to_update))
        {
            $aml_list->addLog([
                'action' => 'update',
                'count' => count($to_update),
                'created' => new UTCDateTime(),
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }
        $this->setSuccess([
            'deleted' => count($to_delete),
            'inserted' => count($to_insert),
            'updated' => count($to_update),
        ]);
        return $this->getSuccess();
    }

    // OK`
    private function process_8(): array
    {
        $url = 'https://rpvs.gov.sk/rpvs/Partner/Partner/VyhladavaniePodlaFyzickejOsobyData';
        $list = $this->getCollectionLists()->findOne(['id' => 8]);
        if (empty($list)) {
            $this->getCollectionLists()->insertOne([
                'id' => 8,
                'active' => true,
                'label' => 'Register partnerov verejného sektora',
                'logs' => [],
                'name' => 'sk_public_sector',
                'order' => 8,
                'updated' => null
            ]);
            $list = $this->getCollectionLists()->findOne(['id' => 8]);
        }
        $aml_list = new AmlList();
        $aml_list->setAmlList($list);
        unset($list);
        $this->setError(['label' => $aml_list->getLabel(), 'name' => $aml_list->getName()]);
        $this->setSuccess(['label' => $aml_list->getLabel(), 'name' => $aml_list->getName()]);
        if (! $aml_list->getActive()) {
            $this->setSuccess(['message' => 'disabled']);
            return $this->getSuccess();
        }
        $data = null;
        $this->setUri($this->uri($url));
        $limit = 100000;
        $post_data = ['start' => 0, 'length' => 1];
        $data = $this->post(['form_params' => $post_data]);
        if (! is_array($data)
            || empty($data['recordsTotal'])
            || empty($data['data'])
        ) {
            $this->setError(['message' => 'empty-response']);
            $aml_list->addLog([
                'created' => new UTCDateTime(),
                'error' => 'empty-response',
            ]);
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
            return $this->getError();
        }
        if ($data['recordsTotal'] > 0) {
            $limit = $data['recordsTotal'];
        }
        $data = null;
        $post_data = ['start' => 0, 'length' => $limit];
        $data = $this->post(['form_params' => $post_data]);
        if (! is_array($data)
            || empty($data['recordsTotal'])
            || empty($data['data'])
        ) {
            $this->setError(['message' => 'empty-response']);
            $aml_list->addLog([
                'created' => new UTCDateTime(),
                'error' => 'empty-response',
            ]);
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
            return $this->getError();
        }
        $arr = $data['data'];
        $data = [];
        foreach ($arr as $item) {
            if (count($item)) {
                $birthdate = (isset($item['DatumNarodeniaFyzickejOsoby']) && ! empty($item['DatumNarodeniaFyzickejOsoby'])) ? $this->sanitizeBirthdate($item['DatumNarodeniaFyzickejOsoby']) : null;
                $fullname = (isset($item['MenoFyzickejOsoby']) && ! empty($item['MenoFyzickejOsoby'])) ? $this->sanitize($item['MenoFyzickejOsoby']) : null;
                if (empty($fullname)) {
                    continue;
                }
                $data[] = [
                    'birthdate' => $birthdate,
                    'fullname' => $fullname,
                    'list_id' => $aml_list->getId(),
                ];
            };
        }
        if (empty($data))
        {
            $this->setError(['message' => 'empty-data']);
            $aml_list->addLog([
                'created' => new UTCDateTime(),
                'error' => 'empty-data',
            ]);
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
            return $this->getError();
        }
        $data = $this->arraySortUniqueByKey($data, 'fullname');
        $names = iterator_to_array($this->getCollectionNames()->find(['list_id' => $aml_list->getId()]));

        $to_delete = $this->arrayDiff($names, $data, 'fullname', false);
        if (! empty($to_delete))
        {
            foreach ($to_delete as $del_name)
            {
                $this->getCollectionNames()->deleteOne(['list_id' => $aml_list->getId(), 'fullname' => $del_name]);
            }
            $aml_list->addLog([
                'action' => 'delete',
                'count' => count($to_delete),
                'created' => new UTCDateTime(),
                'data' => $to_delete
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }

        $to_insert = $this->arrayDiff($names, $data, 'fullname', true);
        $to_update = $this->arrayIntersect($names, $data, 'fullname', 'birthdate', true);;
        foreach ($data as $item) {
            if(
                in_array($item['fullname'], $to_insert)
                || in_array($item['fullname'], $to_update)
            ) {
                $aml_name = new AmlName();
                $aml_name->setAmlName($item);
                $this->getCollectionNames()->updateOne(['list_id' => $aml_name->getListId(), 'fullname' => $aml_name->getFullname()], ['$set' => $aml_name->getAmlName()], ['upsert' => true]);
            }
        }
        if (! empty($to_insert))
        {
            $aml_list->addLog([
                'action' => 'insert',
                'count' => count($to_insert),
                'created' => new UTCDateTime(),
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }
        if (! empty($to_update))
        {
            $aml_list->addLog([
                'action' => 'update',
                'count' => count($to_update),
                'created' => new UTCDateTime(),
            ]);
            $aml_list->setUpdated(new UTCDateTime());
            $this->getCollectionLists()->updateOne(['id' => $aml_list->getId()], ['$set' => $aml_list->getAmlList()]);
        }
        $this->setSuccess([
            'deleted' => count($to_delete),
            'inserted' => count($to_insert),
            'updated' => count($to_update),
        ]);
        return $this->getSuccess();
    }

    private function resetSearch(): void
    {
        $this->birthdate = null;
        $this->fullname = null;
    }

    ////////////////////////////////////
    ////////// Public Methods //////////
    ////////////////////////////////////

    public function lists(): array
    {
        $results = iterator_to_array($this->getCollectionLists()->find(
            ['active' => true],
            [
                'projection' => [
                    'id' => true,
                    'label' => true,
                    'name' => true
                ],
                'sort' => [
                    'order' => 1
                ]
            ]
        ));
        if (empty($results))
        {
            return [
                'error' => true,
                'message' => 'empty-response'
            ];
        }
        foreach ($results as $key => $value)
        {
            unset($results[$key]['_id']);
        }
        return [
            'success' => true,
            'data' => $results
        ];
    }

    public function search(?string $fullname, ?string $birthdate = null): array
    {
        $this->resetSearch();
        $origin_fullname = $fullname;
        if (! empty($fullname)) {
            $this->setFullname($fullname);
        }
        if (! empty($birthdate)) {
            $date = $this->sanitizeBirthdate($birthdate);
            if (empty($date)) {
                return [
                    'error' => true,
                    'message' => 'invalid-birthdate'
                ];
            }
            $this->setBirthdate($date);
        }
        $data = [];
        $data_strict = [];
        $slug = new SlugifyService();
        $name = $this->sanitizeFullname($slug->get($this->getFullname()));
        if (empty($name)) {
            return [
                'error' => true,
                'message' => 'invalid-fullname'
            ];
        }
        $results = iterator_to_array($this->getCollectionNames()->find(['$text' => ['$search' => $name]]));
        if (! empty($results))
        {
            $col = array_column($results, 'fullname');
            $explode = explode(' ', $name);
            foreach($col as $col_key => $col_value)
            {
                $col_value = $this->preSanitizeFullname($col_value);
                if (empty($col_value))
                {
                    continue;
                }
                $target = count($explode);
                $source_name = $this->sanitizeFullname($slug->get($col_value));
                $source = count(explode(' ', $source_name));
                if ($target != $source)
                {
                    continue;
                }
                $count = 0;
                foreach($explode as $name_part)
                {
                    if(preg_match("/\b" . $name_part ."\b/ui", $source_name))
                    {
                        $count++;
                    }
                }
                if ($target == $count)
                {
                    $data[] = $results[$col_key];
                    if (
                        ! empty($this->getBirthdate())
                        && $this->getBirthdate() == $results[$col_key]['birthdate']
                    ) {
                        $data_strict[] = $results[$col_key];
                    }
                }
            }
        }
        foreach ($data as $data_key => $data_value)
        {
            unset($data[$data_key]['_id']);
        }
        foreach ($data_strict as $data_strict_key => $data_strict_value)
        {
            unset($data_strict[$data_strict_key]['_id']);
        }
        return [
            'success' => true,
            'birthdate' => $this->getBirthdate(),
            'data' => $data,
            'data_strict' => $data_strict,
            'fullname' => $origin_fullname,
            'fullname_slug' => implode('_', $explode),
            'unique_id' => substr(strtoupper(preg_replace("/[\d\Wo]/i", '', password_hash(uniqid(), PASSWORD_DEFAULT))), 5, 10)
        ];
    }

    public function update(string $input): array
    {
        $response = $this->process('process_'.$input);
        return $response;
    }

    public function updateAll(): array
    {
        $response = $this->processAll();
        return $response;
    }
}
