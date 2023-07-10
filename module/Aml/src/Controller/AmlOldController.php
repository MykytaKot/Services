<?php

namespace Aml\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use MongoDB\BSON\ObjectId;
use DateTime;

use GuzzleHttp\RequestOptions;
use GuzzleHttp\Client;
use Psr\Http\Message\UriInterface;
use GuzzleHttp\Psr7\Uri;

// TODO DELETE ME
class AmlOldController extends AbstractActionController {
    protected $Aml_sanctions_list;
    protected $settings;
    private $url = [
        'EU Financial Sanctions List' => 'https://webgate.ec.europa.eu/fsd/fsf/public/files/xmlFullSanctionsList_1_1/content?token=dG9rZW4tMjAxNw',
        'EU Members of Parliament' => 'https://www.europarl.europa.eu/meps/en/full-list/xml',
        'UN Sanctions List' => 'https://scsanctions.un.org/resources/xml/en/consolidated.xml',
        'World Presidents Database' => 'https://www.worldpresidentsdb.com/list/',
        'CoE Parliamentary Assembly' => 'https://pace.coe.int/en/members?page=1',
        'CIA World Leaders' => 'https://www.cia.gov/resources/government/azerbaijan/',
        'Every Politician' => 'https://raw.githubusercontent.com/everypolitician/everypolitician-data/master/countries.json',
        'Every Politician' => 'https://cdn.rawgit.com/everypolitician/everypolitician-data/5fb77e05b3619bf52c310da6e1fdceef57907169/data/Slovakia/National_Council/ep-popolo-v1.0.json',
        'Register partnerov verejného sektora' => 'https://rpvs.gov.sk/rpvs/Partner/Partner/VyhladavaniePodlaFyzickejOsobyData',
    ];
    private $client_options = [
        RequestOptions::HEADERS => [
            'User-Agent' => 'Mozilla/5.0 (X11; Linux; Linux x86_64; rv:90.0) Gecko/20100101 Firefox/90.0',
        ],
        RequestOptions::HTTP_ERRORS => false,
        RequestOptions::VERIFY => false,
    ];
    private $client;
    private $uri;

    public function __construct($database, $config) {
        $this->Aml_sanctions_list = $database->selectCollection('aml_sanctions_list');
        $this->settings = $config;
        $this->client();
    }

    ///////////////////////////////////////
    ////////// Getters & Setters //////////
    ///////////////////////////////////////

    private function getClient(): Client { return $this->client; }
    private function setClient(Client $input): void { $this->client = $input; }

    private function getUri(): UriInterface { return $this->uri; }
    private function setUri(UriInterface $input): void { $this->uri = $input; }

    public function indexAction(){
        echo "index";
    }

    public function findAction(){
        $data = $this->getRequest()->getContent();
        $data = @json_decode($data, true);
        $type = ! empty($data['type']) ? $data['type'] : null;
        $found = [];
        if($type == 'AMLcert'){
            $name = ! empty($data['name']) ? $data['name'] : null;
            $surname = ! empty($data['surname']) ? $data['surname'] : null;
            if(empty($name)){
                return new JsonModel(['Missing data']);
            }
        }else{
            return new JsonModel(['Not correct type']);
        }
        return new JsonModel($found);
    }

    public function saveAction() {
        ini_set('max_execution_time', 60000);
        ini_set('memory_limit', '6000M');

        $allNames = [];

        //////data.europa.eu/////////////////////
        $file = file_get_contents('https://webgate.ec.europa.eu/fsd/fsf/public/files/xmlFullSanctionsList_1_1/content?token=dG9rZW4tMjAxNw');
        $file = simplexml_load_string($file);
        foreach($file->sanctionEntity as $entity){
            if(!empty($entity->birthdate)){
                $allNames['EU Financial Sanctions List'][] = array(
                    'fullname' => (string)$entity->nameAlias->attributes()->wholeName,
                    'birth_date' => (!empty($entity->birthdate->attributes()->year)) ? (string)$entity->birthdate->attributes()->year : ''
                );
            }else{
                $allNames['EU Financial Sanctions List'][] = array(
                    'fullname' => (string)$entity->nameAlias->attributes()->wholeName,
                    'birth_date' => ''
                );
            }
        }

        ////europarl.europa/////////////////////
        $file = file_get_contents('https://www.europarl.europa.eu/meps/en/full-list/xml');
        $pattern = '#<fullName>.*?</fullName>#i';
        // finalise the regular expression, matching the whole line
        // search, and store all matching occurences in $matches
        if(preg_match_all($pattern, $file, $matches)){
            $matches = str_replace('<fullName>', "", $matches[0]);
            $matches = str_replace('</fullName>', "", $matches);
            foreach($matches as $match){
                $allNames['EU Members of Parliament'][] = array(
                    'fullname' => $match,
                    'birth_date' => ''
                );
            }
        }

        //////United Nations Security Council Consolidated List/////////////////////
        $file = file_get_contents('https://scsanctions.un.org/resources/xml/en/consolidated.xml');
        $file = simplexml_load_string($file);
        $i = 0;
        $foundUNS = false;
        foreach($file->INDIVIDUALS->INDIVIDUAL as $invidual){
            //var_dump($invidual->INDIVIDUAL->FIRST_NAME);
            $allNames['OSN Sanctions List'][] = array(
                'fullname' => $invidual->FIRST_NAME . ' ' . $invidual->SECOND_NAME,
                'birth_date' => (!empty($invidual->INDIVIDUAL_DATE_OF_BIRTH->YEAR)) ? (string)$invidual->INDIVIDUAL_DATE_OF_BIRTH->YEAR : ''
            );
            /*if(strpos($invidual->FIRST_NAME, $name) !== false &&  strpos($invidual->SECOND_NAME, $lastname) !== false){
                echo $invidual->FIRST_NAME . ' ' . $invidual->SECOND_NAME. ';';
                $foundUNS = true;
            }*/
        }
        //var_dump($allNames);

        /** World Presidents Database */
        $this->setUri($this->uri('https://www.worldpresidentsdb.com/list/'));
        $data = $this->processWorldPresidentDatabase($this->get());
        $allNames['World Presidents Database'] = $data;

        //////Parliamentary Assembly/////////////////////
        for($i=1;$i <= 73;$i++){
            $file = file_get_contents('https://pace.coe.int/en/members?page='.$i);
            $pattern = '#<b>.*?</b>#';
            // finalise the regular expression, matching the whole line
            // search, and store all matching occurences in $matches
            if(preg_match_all($pattern, $file, $matches)){
                $matches = str_replace('<b>', "", $matches[0]);
                $matches = str_replace('</b>', "", $matches);
                foreach($matches as $match){
                    $allNames['CoE Parliamentary Assembly'][] = array(
                        'fullname' => $match,
                        'birth_date' => ''
                    );
                }
            }
        }

        // $countries = array("AF.html","AL.html","AG.html","AN.html","AO.html","AC.html","AR.html","AM.html","AA.html","AS.html","AU.html","AJ.html","BF.html","BA.html","BG.html","BB.html","BO.html","BE.html","BH.html","BN.html","BD.html","BT.html","BL.html","BK.html","BC.html","BR.html","BX.html","BU.html","UV.html","BM.html","BY.html","CV.html","CB.html","CM.html","CA.html","CT.html","CD.html","CI.html","CH.html","CO.html","CN.html","CG.html","CF.html","CW.html","CS.html","IV.html","HR.html","CU.html","CY.html","EZ.html","DA.html","DJ.html","DO.html","DR.html","EC.html","EG.html","ES.html","EK.html","ER.html","EN.html","WZ.html","ET.html","FJ.html","FI.html","FR.html","GB.html","GA.html","GG.html","GM.html","GH.html","GR.html","GJ.html","GT.html","GV.html","PU.html","GY.html","HA.html","VT.html","HO.html","HU.html","IC.html","IN.html","ID.html","IR.html","IZ.html","EI.html","IS.html","IT.html","JM.html","JA.html","JO.html","KZ.html","KE.html","KR.html","KN.html","KS.html","KV.html","KU.html","KG.html","LA.html","LG.html","LE.html","LT.html","LI.html","LY.html","LS.html","LH.html","LU.html","MA.html","MI.html","MY.html","MV.html","ML.html","MT.html","RM.html","MR.html","MP.html","MX.html","FM.html","MD.html","MN.html","MG.html","MJ.html","MO.html","MZ.html","WA.html","NR.html","NP.html","NL.html","NZ.html","NU.html","NG.html","NI.html","MK.html","NO.html","MU.html","PK.html","PS.html","PM.html","PP.html","PA.html","PE.html","RP.html","PL.html","PO.html","QA.html","RO.html","RS.html","RW.html","SC.html","ST.html","VC.html","WS.html","SM.html","TP.html","SA.html","SG.html","RI.html","SE.html","SL.html","SN.html","LO.html","SI.html","BP.html","SO.html","SF.html","OD.html","SP.html","CE.html","SU.html","NS.html","SW.html","SZ.html","SY.html","TW.html","TI.html","TZ.html","TH.html","TT.html","TO.html","TN.html","TD.html","TS.html","TU.html","TX.html","TV.html","UG.html","UP.html","AE.html","UK.html","UY.html","UZ.html","NH.html","VE.html","VM.html","YM.html","ZA.html","ZI.html");
        // //$countries = array("AF.html");
        // //////////////////CIA World Leaders/////////////////////
        // foreach($countries as $country){
        //     $file = file_get_contents('https://www.cia.gov/library/publications/resources/world-leaders-1/'.$country);
        //     $pattern = '#<span style="background-color: \#FFFFFF; font-size: 12px; line-height:13px; ">.*?</span>#';
        //     // finalise the regular expression, matching the whole line
        //     // search, and store all matching occurences in $matches
        //     if(preg_match_all($pattern, $file, $matches)){
        //         $matches = str_replace('<span style="background-color: #FFFFFF; font-size: 12px; line-height:13px; ">', "", $matches[0]);
        //         $matches = str_replace('</span>', "", $matches);
        //         //echo implode("\n", $matches);
        //         foreach($matches as $match){
        //             $allNames['CIA World Leaders'][] = array(
        //                 'fullname' => $match,
        //                 'birth_date' => ''
        //             );
        //         }
        //     }
        // }

        //////////////////EveryPolitician////////////////////////////
        $file = file_get_contents('https://raw.githubusercontent.com/everypolitician/everypolitician-data/master/countries.json');
        $countries = json_decode($file,true);
        $i = 0;
        foreach($countries as $country){
            foreach($country['legislatures'] as $legislatures){
                $file = file_get_contents($legislatures['popolo_url']);
                $persons = json_decode($file,true);
                //var_dump($persons);
                foreach($persons['persons'] as $person){
                    $family_name = (isset($person['family_name'])) ? $person['family_name'] : $person['name'];
                    $given_name = (isset($person['given_name'])) ? $person['given_name'] : '';
                    $birth_date = (isset($person['birth_date'])) ? $person['birth_date'] : '';
                    $allNames['Every Politician'][] = array(
                        'fullname' => $family_name . ' ' . $given_name,
                        'birth_date' => $birth_date
                    );
                    /*$i++;
                    if($i == 10){
                        var_dump($allNames);
                        return new JsonModel(['succ']);
                    }*/
                }
            }
        }

        /////////////////Every add SLOVAK////////////////////////////
        $file = file_get_contents('https://cdn.rawgit.com/everypolitician/everypolitician-data/5fb77e05b3619bf52c310da6e1fdceef57907169/data/Slovakia/National_Council/ep-popolo-v1.0.json');
        $persons = json_decode($file,true);
        foreach($persons['persons'] as $person){
            $family_name = (isset($person['family_name'])) ? $person['family_name'] : $person['name'];
            $given_name = (isset($person['given_name'])) ? $person['given_name'] : '';
            $birth_date = (isset($person['birth_date'])) ? $person['birth_date'] : '';
            $allNames['Every Politician'][] = array(
                'fullname' => $family_name . ' ' . $given_name,
                'birth_date' => $birth_date
            );
        }

        /** Register partnerov verejného sektora */
        $this->setUri($this->uri('https://rpvs.gov.sk/rpvs/Partner/Partner/VyhladavaniePodlaFyzickejOsobyData'));
        $data = $this->processRpvsGovSk();
        $allNames['Register partnerov verejného sektora'] = $data;

        $this->Aml_sanctions_list->deleteMany([]);
        $this->Aml_sanctions_list->insertOne($allNames);
        return new JsonModel(['success']);
    }

    /////////////////////////////////////
    ////////// Private Methods //////////
    /////////////////////////////////////

    private function client(): void {
        $this->setClient(new Client($this->client_options));
    }

    private function uri(string $input): UriInterface {
        $return = new Uri($input);
        return $return;
    }

    private function get() {
        $request =  false;
        $response = false;
        $request = $this->getClient()->request('GET', $this->getUri());
        $response = $request->getBody()->getContents();
        return $response;
    }

    private function post(array $input) {
        $request =  false;
        $response = false;
        $request = $this->getClient()->request('POST', $this->getUri(), $input);
        $response = $request->getBody()->getContents();
        $response = json_decode($response, true);
        return $response;
    }

    /** World Presidents Database */
    private function processWorldPresidentDatabase(?string $input): array {
        $return = [];
        if(empty($input)) { return $return; }
        $pattern = "/href=\"\/\w+(?:\-\w+)+\/\".*\s+.*img\s+src.*\>\s+(.*)\<\/a\>/umi";
        preg_match_all($pattern, $input, $match);
        if(!empty($match) && count($match) == 2 && isset($match[1]) && !empty($match[1])) {
            foreach($match[1] as $name) {
                $return[] = [
                    'fullname' => $name,
                    'birth_date' => ''
                ];
            }
        }
        return $return;
    }

    /** World Presidents Database */
    private function processRpvsGovSk(): array {
        $return = [];
        $limit = 100000;
        $post_data = [
            'start' => 0,
            'length' => 1
        ];
        $result = $this->post(['form_params' => $post_data]);
        if(isset($result['recordsTotal']) && $result['recordsTotal'] > 0) { $limit = $result['recordsTotal'];}
        $result = null;
        $post_data = [
            'start' => 0,
            'length' => $limit
        ];
        $result = $this->post(['form_params' => $post_data]);
        if(!empty($result) && isset($result['data']) && !empty($result['data'])) {
            foreach($result['data'] as $person){
                $return[] = [
                    'fullname' => $person['MenoFyzickejOsoby'],
                    'birth_date' => $person['DatumNarodeniaFyzickejOsoby']
                ];
            }
        }
        return $return;
    }
}
