<?php
/* 
 * This class allows you to connect with Bookitit webservices.
 * Only build an instance of the class with your public and private key
 * and use the method you want.
 * 
 */

class CRestClient {
    private $_sPublicKey="1975d278f442e6632c5296fb8a54a2cdf"; //set here your public api key
    private $_sPrivateKey="1fab828edb04121d54c141875333fd800"; //set here your private api key
    
    private $_sRoot = "//app.bookitit.com/";
    private $_sVersion = "11";
    
    public function CRestClient($p_sPublicKey=null, $p_sPrivateKey=null) {
        if ($p_sPrivateKey != null)$this->_sPrivateKey = $p_sPrivateKey;
        if ($p_sPublicKey != null) $this->_sPublicKey = $p_sPublicKey;
    }

    public function set_sRoot($p_sRoot) {
        $this->_sRoot = $p_sRoot;
    }
    
    public function get_sRoot() {
        return $this->_sRoot;
    }
    
    public function set_sPublicKey($p_sPublicKey) {
        $this->_sPublicKey = $p_sPublicKey;
    }
    
    public function get_sPublicKey() {
        return $this->_sPrivateKey;
    }
    
    public function set_sPrivateKey($p_sPrivateKey) {
        $this->_sPrivateKey = $p_sPrivateKey;
    }
    
    public function get_sPrivateKey() {
        return $this->_sPrivateKey;
    }
    /**
     * This function starts a connection with the bookitit webservice
     * @param $p_sUrl the url where we want send the request
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     * @param $p_sMethod petition method, can be "GET" or "POST".
     * @param $p_somePostParams an associative array with the POST parameters (if any)
     * @return $sResult a string with xml or json response data
     */
    private function startConnection($p_sUrl, $p_sMode, $p_bSecure, $p_sMethod, $p_somePostParams=null) {
        $sUrl = "";
        
        $sHash = $this->buildHashValue($p_sUrl);
        
        if ($p_bSecure == true) $sUrl = "https:".$this->_sRoot."api/$this->_sVersion/".$p_sUrl;
        else $sUrl = "http:".$this->_sRoot."api/$this->_sVersion/".$p_sUrl;
        
        $oCurl = curl_init();
        
        //set url and timeout headers 
        curl_setopt($oCurl, CURLOPT_URL,$sUrl);
        curl_setopt($oCurl, CURLOPT_TIMEOUT, 30);

        // https support headers
        if ($p_bSecure == true) {
            curl_setopt($oCurl, CURLOPT_SSLVERSION,3);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 2);
        }
        
        // if sending by post, set the corresponding headers
        if($p_sMethod=="POST" || $p_sMethod=="post")
        {
            curl_setopt($oCurl, CURLOPT_POST, 1);
            curl_setopt($oCurl, CURLOPT_POSTFIELDS, $p_somePostParams);
        }
        
        // response in json or xml
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, array ('Accept: ' . "application/$p_sMode"));
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, true);

        // set basic authentication parameters
        curl_setopt($oCurl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($oCurl, CURLOPT_USERPWD, $this->_sPublicKey . ':' . $sHash);
        $sResult = curl_exec ($oCurl);
        curl_close($oCurl);
       
        return $sResult;

    }

    /**
     * This function hashes a string using the private key as key, with HMAC-MD5 algorithm. 
     * @param <array> $p_sUrl string to hash
     * @return <string> $sHashedValues hashed string
     */
    private function buildHashValue($p_sUrl) {
        $sHashedValues = hash_hmac("md5", $p_sUrl, $this->_sPrivateKey);
        return $sHashedValues;
    }
    
    private function buildHashValueGet($p_someParametersToHash) {
            $sToHash=implode("/",$p_someParametersToHash);
            $sHashedValues = hash_hmac("md5", $sToHash, $this->_sPrivateKey);
            return $sHashedValues;
        }

    /**
     * This function gets the services of your company
     *
     * @param $p_sMode get response data in xml or json
     * @param $p_bSecure connection security, true for https, false for http
     *
     * @return
     * - on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <services>
     *       - <status>true</status>
     *       - <i>one or more</i> <service>
     *         - <id>id of the service (format bktXXXXXXXX)</id>
     *         - <name>name of the service</name>
     *         - <duration>duration of the service in minutes</duration>
     *         - <prepay>flag to indicate if it is a prepay service:
     *           - 0: not prepay service
     *           - 1: prepay service
     *         - </prepay>
     *         - <price>price of the service</price>
     *       - </service>
     *     - </services>
     *
     * - on success, if json was chosen:
     *   - {"services":
     *     - {
     *       - "status":true,
     *       - <i>one or more</i> "service":
     *         - {
     *           - "id":id of the service (format bktXXXXXXXX),
     *           - "name":name of the service,
     *           - "duration":duration of the service in minutes,
     *           - "prepay": 0 (is not prepay service) or 1 (is prepay service),
     *           - "price":price of the service
     *         - }
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <services>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </services>
     *
     * - on failure, if json was chosen:
     *   - {"services":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function getServices($p_sMode, $p_bSecure) {
        //url and somedata must have the same values
        $sUrl = "getservices/$this->_sPublicKey";
        $sMethod="get";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod);

        return $sResult;
    }

    /**
     * This function gets the agendas of your company. Optionally, if a service ID is given, it will filter the agendas that offer that service.
     
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     * @param $p_sServiceID optional, a service id (format "bktXXXXXXX")
     *
     * @return
     * -on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <agendas>
     *       - <status>true</status>
     *       - <i>one or more</i> <agenda>
     *         - <id>id of the agenda (format "bktXXXXXXX")</id>
     *         - <name>name of the agenda</name>
     *         - <phone>mobile phone of the agenda</phone>
     *         - <email>email of the agenda</email>
     *         - <public>indicates if the agenda is public (1) or private (0)</public>
     *         - <synchro_id>id of the agenda if it is shared with other software, NULL otherwise</synchro_id>
     *         - <photo>url of the photo</photo>
     *         - <description>description of the agenda</description>
     *       - </agenda>
     *     - </agendas>
     *
     * - on success, if json was chosen:
     *   - {"agendas":
     *     - {
     *       - "status":false,
     *       - <i>one or more</i> "agenda":
     *         - {
     *           - id:id of the agenda (format "bktXXXXXXX"),
     *           - name: name of the agenda,
     *           - phone: mobile phone of the agenda
     *           - email: email of the agenda
     *           - public:indicates if the agenda is public (1) or private (0)
     *           - synchro_id:id of the agenda if it is shared with other software, NULL otherwise
     *           - photo:url of the photo,
     *           - description:description of the agenda
     *         - }
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <agendas>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </agendas>
     *
     * - on failure, if json was chosen:
     *   - {"agendas":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function getAgendas($p_sMode, $p_bSecure, $p_sServiceID=null) {
        $sMethod="get";
        $sUrl = "getagendas/$this->_sPublicKey";
        if($p_sServiceID!=null){
            $sUrl .= "/$p_sServiceID";
        }
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod);
        return $sResult;
    }

    /**
     * This function gets the free minutes for a service of an agenda
     *
     * @param p_sServiceID service id (format bktXXXXXXX)
     * @param p_sAgendaID agenda id (format bktXXXXXXX)
     * @param p_sDate the date to show the slots of (format YYYY-MM-DD)
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     * @return
     * on success, if xml was chosen:
     * - <slots>
     *   - <status>true</status>
     *   - <hours>
     *     - <i>one or more</i><hour>a time where a reservation could be made (format HH:MM)</hour>
     *   - </hours> 
     * - </slots>
     *
     * - on success, if json was chosen:
     *   - {"slots":
     *     - {
     *       - "status":true,
     *       - "hours":
     *         - {
     *           - <i>one or more</i>"hour": a time where a reservation could be made (format HH:MM)
     *         - }
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <slots>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </slots>
     *
     * - on failure, if json was chosen:
     *   - {"slots":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function getFreeSlots($p_iIdService, $p_iIdAgenda, $p_sDate, $p_sMode, $p_bSecure) {
        $sUrl = "getfreeslots/$this->_sPublicKey/$p_iIdService/$p_iIdAgenda/$p_sDate";
        $p_sMethod ="get";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $p_sMethod);

        return $sResult;
    }

    /**
     * This function adds an agenda for your company.
     *
     * @param $p_sName agenda name
     * @param $p_sPhone agenda phone (optional)
     * @param $p_sEmail agenda email (optional)
     * @param $p_sPublic (optional) indicates if the agenda is "public" or "private". Default is "public"
     * @param $p_sSynchroid (optional) id of the agenda in your software (MUST USE in case of an integration or synchronization between your software and Bookitit)
     * @param $p_sServiceid (optional) id of a previously created service, which associate with this agenda (format bktXXXXXXX)
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     * @return
     * on success, if xml was chosen:
     * - <agenda>
     *   - <status>true</status>
     *   - <id>id of the created agenda (format bktXXXXXXXX)</id>
     * - </agenda>
     *
     * - on success, if json was chosen:
     *   - {"agenda":
     *     - {
     *       - "status":true,
     *       - "id":id of the created agenda (format bktXXXXXXXX)
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <agenda>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </agenda>
     *
     * - on failure, if json was chosen:
     *   - {"agenda":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function addAgenda($p_sName,$p_sPhone,$p_sEmail,$p_sPublic,$p_sSynchroID,$p_sServiceID, $p_sMode, $p_bSecure){
        $somePostParams = array("p_sName"=>$p_sName,"p_sPhone"=>$p_sPhone,"p_sEmail"=>$p_sEmail,"p_sPublic"=>$p_sPublic,"p_sSynchroID"=>$p_sSynchroID,"p_sServiceID"=>$p_sServiceID);
        $sUrl = "addagenda/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }

    /**
     * This function adds a service for your company.
     *
     * @param p_sName name of the service
     * @param p_iTime duration of the service in minutes
     * @param p_dPrice price of the service (only numbers in format XX.XX)
     * @param p_sPublic (optional) service marked as "public" or "private". Default is "public".
     * @param p_iPrepay (optional) "prepay" or "not_prepay". Default is "not_prepay".
     * @param p_sCurrency in which currency would be the service the price, accepted values are "EUR" for euro "GBP" for british pound and "USD" for United States dollar. Default is euro.
     * @param p_sSynchroID (optional) id of the service in your software (MUST USE in case of an integration or synchronization between your software and Bookitit)
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     * @return
     * on success, if xml was chosen:
     * - <service>
     *   - <status>true</status>
     *   - <id>id of the created service (format bktXXXXXXXX)</id>
     * - </service>
     *
     * - on success, if json was chosen:
     *   - {"service":
     *     - {
     *       - "status":true,
     *       - "id":id of the created service (format bktXXXXXXXX)
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <service>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </service>
     *
     * - on failure, if json was chosen:
     *   - {"service":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function addService($p_sName, $p_iTime, $p_dPrice, $p_sPublic, $p_sPrepay, $p_sCurrency, $p_sSynchroID, $p_sMode, $p_bSecure){
        $somePostParams = array("p_sName"=>$p_sName, "p_iTime"=>$p_iTime, "p_dPrice"=>$p_dPrice, "p_sPublic"=>$p_sPublic, "p_sPrepay"=>$p_sPrepay, "p_sCurrency"=>$p_sCurrency, "p_sSynchroID"=>$p_sSynchroID,);
        $sUrl = "addservice/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }

    /**
     * This function connects an agenda and a service for your company. Both must be already created, and its IDs supplied.
     * It can be the IDs of the agenda and the service in Bookitit or, if you are synchronizing/integrating services between your software and Bookitit, the ID of the agenda and the service in your software (we call it Synchro IDs).
     * If both IDs are provided, the Synchro IDs have priority.
     *
     * @param p_sAgendaID agenda ID (format bktXXXXXXX)
     * @param p_sServiceID service ID (format bktXXXXXXX)
     * @param p_sAgendaSynchroID agenda ID in your software (use in case of software integration)
     * @param p_sServiceSynchroID service ID in your software (use in case of software integration)
     * @param p_iDuration (optional) Duration of the service specific for this agenda, in minutes.
     * @param p_dPrice (optional) Price of the service specific for this agenda, in your currency
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     * @return
     * on success, if xml was chosen:
     * - <agenda_service>
     *   - <status>true</status>
     * - </agenda_service>
     *
     * - on success, if json was chosen:
     *   - {"agenda_service":
     *     - {
     *       - "status":true,
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <agenda_service>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </agenda_service>
     *
     * - on failure, if json was chosen:
     *   - {"agenda_service":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function addAgendaService($p_sAgendaID,$p_sServiceID,$p_sAgendaSynchroID,$p_sServiceSynchroID,$p_iDuration,$p_dPrice, $p_sMode, $p_bSecure){
        $somePostParams = array("p_sAgendaID"=>$p_sAgendaID,"p_sServiceID"=>$p_sServiceID,"p_sAgendaSynchroID"=>$p_sAgendaSynchroID,"p_sServiceSynchroID"=>$p_sServiceSynchroID,"p_iDuration"=>$p_iDuration,"p_dPrice"=>$p_dPrice);
        $sUrl = "addagendaservice/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function creates an event.
     *
     * @warning If you are creating an event for a social network, you might want to use the "addsocialevent" function instead.
     *
     * @param p_sAgendaID ID of the agenda (format bktXXXXXXXX)
     * @param p_sServiceID ID of the service (format bktXXXXXXXX)
     * @param p_dStartDate start date of the event (YYYY-MM-DD), if date doesn't exist, date will be 0000-00-00
     * @param p_dEndDate end date of the event (YYYY-MM-DD), if date doesn't exist, date will be 0000-00-00
     * @param p_iStartTime start time of the event (in minutes, since 00:00): 01:00 am = 60, 02:00 am = 120 ...
     * @param p_iEndTime end time of the event (in minutes, since 00:00): 01:00 am = 60, 02:00 am = 120 ...
     * @param p_sTitle (optional) title of the event, if it's set p_sServiceID the title will get automatically the name of the service. @deprecated the p_sServiceID is allways needed.
     * @param p_sDescription (optional) description of the event @deprecated, use p_sComments
     * @param p_sComments (optional) comments of the event
     * @param p_sEventSynchroID (optional) In case you are synchronizing your software with Bookitit, provide your event ID.
     * @param p_sAgendaSynchroID (optional) (mandatory if $p_sAgendaID is not set) In case you are synchronizing your software with Bookitit, provide your agenda ID.
     * @param p_sServiceSynchroID (optional) (mandatory if $p_sServiceID is not set) In case you are synchronizing your software with Bookitit, provide your service ID.
     * @param p_sUserID (optional) ID of the final user who makes the event (format bktXXXXXXXX) @deprecated, use p_sClientID
     * @param p_sUserPhone (optional) a phone number of the final user who makes the event. @deprecated
     * @param p_sClientID ID of the client who makes the event (format bktXXXXXXXX) 
     * @param p_sClientName (optional) name of the client, to create the new client if p_sUserID or p_sClientID is not set
     * @param p_sClientPhone (optional) phone of the client, to create the new client if p_sUserID or p_sClientID is not set
     * @param p_sClientEmail (optional) email of the client, to create the new client if p_sUserID or p_sClientID is not set
     * @param p_someCustomFields (optional) If your company account is using custom fields for the users, you can set it there.
     * 
     * p_someCustomFields param example:
     *      array(                                                    
                "customevent1" => "data in text format",
                "customevent2"=> "data in text format", 
                "customevent3"=> "data in text format", 
                "customevent4"=> "data in text format", 
                "customevent5"=> "data in text format" 
            )
     * 
     * @return
     * on success, if xml was chosen:
     * - <event>
     *   - <status>true</status>
     *   - <id>id of the created event (format bktXXXXXXXX)</id>
     * - </event>
     *
     * - on success, if json was chosen:
     *   - {"event":
     *     - {
     *       - "status":true,
     *       - "id":id of the created event (format bktXXXXXXXX)
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <event>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </event>
     *
     * - on failure, if json was chosen:
     *   - {"event":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function addEvent(
     $p_sAgendaID,
     $p_sServiceID,
     $p_dStartDate,
     $p_dEndDate,
     $p_iStartTime,
     $p_iEndTime,
     $p_sTitle,
     $p_sDescription,
     $p_sComments,
     $p_sEventSynchroID,
     $p_sAgendaSynchroID,
     $p_sServiceSynchroID,
     $p_sUserID,
     $p_sUserPhone,
     $p_sClientID,
     $p_sClientName,
     $p_sClientPhone,
     $p_sClientEmail,
     $p_sMode,
     $p_bSecure,
     $p_someCustomFields = array()
     ){
        $somePostParams = array(
            "p_sAgendaID" => $p_sAgendaID,
            "p_sServiceID" => $p_sServiceID,
            "p_dStartDate" => $p_dStartDate,
            "p_dEndDate" => $p_dEndDate,
            "p_iStartTime" => $p_iStartTime,
            "p_iEndTime" => $p_iEndTime,
            "p_sTitle" => $p_sTitle,
            "p_sDescription" => $p_sDescription,
            "p_sComments" => $p_sComments,
            "p_sEventSynchroID" => $p_sEventSynchroID,
            "p_sAgendaSynchroID" => $p_sAgendaSynchroID,
            "p_sServiceSynchroID" => $p_sServiceSynchroID,
            "p_sUserID" => $p_sUserID,
            "p_sUserPhone" => $p_sUserPhone,
            "p_sClientID" => $p_sClientID,
            "p_sClientName" => $p_sClientName,
            "p_sClientPhone" => $p_sClientPhone,
            "p_sClientEmail" => $p_sClientEmail
        );
        
        foreach($p_someCustomFields as $sKey => $sValue){
            $somePostParams["p_someCustomFields[".$sKey."]"] = $sValue;
        }
        
        $sUrl = "addevent/$this->_sPublicKey";
        $sMethod="post";
        
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        
        return $sResult;
    }
    
    /**
     * This function creates a "social" event. The user must be a "social" user.
     *
     * A "social" event will always show for a user in different companies of the same social network. This function must be used
      * by an admin/multicenter api key.
     *
     * @warning you need an Admin API KEY linked to a multicenter account to use this method. 
     * @warning This function will only create the event if the user is a "social" user.  
     * If you are creating the event for a normal, single-site user, please use the "addevent" function instead.
     * 
     *
     *
     * @param p_sPublicKeyCenter The public key of the company where will be the appointment
     * @param p_sUserEmail User's email
     * @param p_sAgendaID id of the agenda (format bktXXXXXXXX)
     * @param p_sServiceID id of the service (format bktXXXXXXXX)
     * @param p_dStartDate start date of the event (YYYY-MM-DD)
     * @param p_dEndDate end date of the event (YYYY-MM-DD)
     * @param p_iStartTime start time of the event (in minutes, since 00:00), 01:00 am = 60, 02:00 am = 120 ...
     * @param p_iEndTime end time of the event (in minutes, since 00:00), 01:00 am = 60, 02:00 am = 120 ...
     * @param p_sTitle title of the event
     * @param p_sComments Comments for the event
     * @param p_sMode xml or json
     * @param p_bSecure true for https, false for http
     *
     * @return
     * on success, if xml was chosen:
     * - <event>
     *   - <status>true</status>
     *   - <id>id of the created event (format bktXXXXXXXX)</id>
     * - </event>
     *
     * - on success, if json was chosen:
     *   - {"event":
     *     - {
     *       - "status":true,
     *       - "id":id of the created event (format bktXXXXXXXX)
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <event>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </event>
     *
     * - on failure, if json was chosen:
     *   - {"event":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function addSocialEvent($p_sPublicCenterApiKey, $p_sEmail,$p_sAgendaID,$p_sServiceID,$p_dStartDate, $p_dEndDate,$p_iStartTime,$p_iEndTime,$p_sComments,$p_sTitle, $p_sMode, $p_bSecure){
        $somePostParams = array("p_sEmail"=>$p_sEmail,"p_sAgendaID"=>$p_sAgendaID,"p_sServiceID"=>$p_sServiceID,"p_dStartDate"=>$p_dStartDate,
            "p_dEndDate"=>$p_dEndDate,"p_iStartTime"=>$p_iStartTime,"p_iEndTime"=>$p_iEndTime,
            "p_sComments"=>$p_sComments,"p_sTitle"=>$p_sTitle);
        $sUrl = "addsocialevent/$this->_sPublicKey/$p_sPublicCenterApiKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * @deprecated, use addclient
     * 
     * This function adds a user given his basic data.
     * 
     * Initially, the user will be inserted but INACTIVE.
     * An activation code will be sent to the given phone number.
     *
     * @warning If you are adding a user for a social network, you might want to use the "addsocialuser" function instead.
     * 
     * @param p_sName Name of the user     
     * @param p_sEmail email of the user
     * @param p_sPassword Encrypted (md5) password for the user     
     * @param p_sMode xml or json
     * @param p_bSecure true for https, false for http
     * @param p_sPhone (optional) Phone number of the user
     * @param p_sDocument (optional) Document of the user
     * @param p_sAddress (optional) The address of the user
     * @param p_someCustomFields (optional) If your company account is using custom fields for the users, you can set it there. Remember that you have to maintain the index label.
     * array(                                                    
                                                    "customvalidate1" => "data in text format", 
                                                    "customvalidate2"=> "data in text format",                                                     
                                                    "custom1"=> "data in text format", 
                                                    "custom2"=> "data in text format", 
                                                    "custom3"=> "data in text format", 
                                                    "custom4"=> "data in text format", 
                                                    "custom5"=> "data in text format"
                                                    
                                                    
           )
     *
     * @return
     * - on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <user>
     *       - <status>true</status>
     *       - <id>id of the created user (format bktXXXXXXXXX)</id>
     *     - </user>
     *
     * - on success, if json was chosen:
     *   - {"user":
     *     - {
     *       - "status":true,
     *       - "id":id of the created user (format bktXXXXXXXXX)
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <user>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </user>
     *
     *
     * - on failure, if json was chosen:
     *   - {"user":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     * @see addsocialuser
     * @see setphoneandsendvalidate
     * @see confirmphone
     */
    public function addUser($p_sName, $p_sEmail, $p_sPassword, $p_sMode, $p_bSecure,  $p_sPhone="", $p_sDocument="", $p_sAddress="", $p_someCustomFields=array()){        
        $somePostParams = array("p_sName"=>$p_sName, "p_sPhone"=>$p_sPhone, "p_sEmail"=>$p_sEmail, 
            "p_sPassword"=>$p_sPassword, "p_sDocument" => $p_sDocument, "p_sAddress" => $p_sAddress);
        foreach($p_someCustomFields as $sKey=>$sValue){
            $somePostParams["p_someCustomFields[".$sKey."]"]=$sValue;
        }
        $sUrl = "adduser/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function adds a client with or without web access
     *
     * URL to call this function: http://app.bookitit.com/api/11/addclient/<i>p_sPublicKey</i>
     *
     * A client with web access can login from widget. With this action the client can create new appointments without register again, or delete pending appointments.
     * This client registration requires some mandatory fields.
     * 
     * A client without web access can't login at widget. This type of client only contains its data that allow to center create new appointment
     * This client registration only requires to fill some fields (one field at least)
     * 
     
     * @param $p_bWebaccess If the client has web access or not true or false, as boolean.
     * @param $p_sMode xml or json as string.
     * @param $p_bSecure true or false as boolean
     * @param $p_sPassword (optional) Encrypted (md5) password for the client. If you don't want to send this parameter set it as "".
     * @param $p_sEmail (optional) Email of the client. If you don't want to send this parameter set it as "".
     * @param $p_sCellphone (optional) Cellphone of the client without international code. If you don't want to send this parameter set it as "".
     * @param $p_sInternationalCode (optional) The country international code (Ex: +34), if you don't set this value the default value inserted will be the international code of the company. If you don't want to send this parameter set it as "".
     * @param $p_sDocument (optional) Document of the client. If you don't want to send this parameter set it as "".
     * @param $p_sName (optional) Name of the client. If you don't want to send this parameter set it as "".
     * @param $p_sAddress (optional) Address of the client. If you don't want to send this parameter set it as "".
     * @param $p_sPhone (optional) Phone of the client. If you don't want to send this parameter set it as "".
     * @param $p_someCustomFields (optional) If your company account is using custom fields for the users, you can set it there.     
     *
     * $p_someCustomFields param example:
     *      array(                                                    
                "customvalidate1" => "data in text format",
                "customvalidate2"=> "data in text format", 
                "custom1"=> "data in text format", 
                "custom2"=> "data in text format", 
                "custom3"=> "data in text format", 
                "custom4"=> "data in text format", 
                "custom5"=> "data in text format" 
            )
     *
     * @return
     * - on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <client>
     *       - <status>true</status>
     *       - <id>id of the created client (format bktXXXXXXXXX)</id>
     *     - </client>
     *
     * - on success, if json was chosen:
     *   - {"client":
     *     - {
     *       - "status":true,
     *       - "id":id of the created client (format bktXXXXXXXXX)
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <client>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </client>
     *
     *
     * - on failure, if json was chosen:
     *   - {"client":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     */
    public function addClient($p_sWebaccess, $p_sMode, $p_bSecure, $p_sPassword = "", $p_sEmail = "", $p_sCellphone = "", $p_sInternationalCode = "", $p_sDocument = "", $p_sName = "", $p_sAddress = "", $p_sPhone = "", $p_someCustomFields = array()){
        $somePostParams = array(
            "p_sPassword" => $p_sPassword, 
            "p_sEmail" => $p_sEmail, 
            "p_sCellphone" => $p_sCellphone, 
            "p_sInternationalCode" => $p_sInternationalCode, 
            "p_sDocument" => $p_sDocument,
            "p_sName" => $p_sName,
            "p_sAddress" => $p_sAddress,
            "p_sPhone" => $p_sPhone
        );
        
        foreach($p_someCustomFields as $sKey => $sValue){
            $somePostParams["p_someCustomFields[".$sKey."]"] = $sValue;
        }
        
        $sWebaccess = "false";
        if($p_sWebaccess == "true"){
            $sWebaccess = "true";
        }
        
        $sUrl = "addclient/$this->_sPublicKey/$sWebaccess";
        $sMethod = "post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        
        return $sResult;
    }
    
    /**
    * This function updates a client of your company. You can modify different fields, name, cellphone,... as well as change the web access of the client
    *
    * URL to call this function: http://app.bookitit.com/api/11/updateclient/<i>p_sPublicKey</i>
    *
    * A client with web access can login from widget. With this action the client can create new appointments without register again, or delete pending appointments.
    * This client registration requires some mandatory fields.
    * 
    * A client without web access can't login at widget. This type of client only contains its data and allows the center create new appointments to him.
    * This client registration only requires to fill some fields (one field at least)
    
    * @param $p_sClientId (mandatory) The id of the client that will be updated. (format bktXXXXXXXXX)
    * @param $p_sWebaccess (optional). If you want to modify the web access set "true" or "false" state otherwise it will do nothing.
    * @param $p_sPassword (optional). If you are changing a client from without webaccess to webaccess it will be mandatory. Encrypted (md5) password for the client if has webaccess. If you don't want to modify this parameter set it as "".
    * @param $p_sEmail (optional) Email of the client. If you don't want to modify this parameter set it as "".
    * @param $p_sCellphone (optional) Cellphone of the client without international code. If you don't want to modify this parameter set it as "".
    * @param $p_sInternationalCode (optional) The country international code (Ex: +34), if you don't set this value the default value inserted will be the international code of the company. 
    * If you don't want to modify this parameter set it as "".
    * @param $p_sDocument (optional) Document of the client. If you don't want to modify this parameter set it as "". If you want to delete this field send a "null" string.
    * @param $p_sName (optional) Name of the client. If you don't want to modify this parameter set it as "". If you want to delete this field send a "null" string.
    * @param $p_sAddress (optional) Address of the client. If you don't want to modify this parameter set it as "". If you want to delete this field send a "null" string.
    * @param $p_sPhone (optional) Phone of the client. If you don't want to modify this parameter set it as "". If you want to delete this field send a "null" string.    
    * @param $p_sBlocked (optional) If you want to block a client you will have to set it as "true" otherwise "false".
    * @param $p_someCustomFields (optional) If your company account is using custom fields for the users, you can set it there. If you want to delete this field send a "null" string in the 
     * correct position of the array.
    *
    * $p_someCustomFields param example:
    *      array(                                                    
               "customvalidate1" => "data in text format",
               "customvalidate2"=> "data in text format", 
               "custom1"=> "data in text format", 
               "custom2"=> "data in text format", 
               "custom3"=> "data in text format", 
               "custom4"=> "data in text format", 
               "custom5"=> "data in text format" 
           )
    *
    * @return
    * - on success, if xml was chosen:
    *   - <?xml version='1.0' encoding='utf-8'?>
    *     - <client>
    *       - <status>true</status>
    *       - <id>id of the updated client (format bktXXXXXXXXX)</id>
    *     - </client>
    *
    * - on success, if json was chosen:
    *   - {"client":
    *     - {
    *       - "status":true,
    *       - "id":id of the updated client (format bktXXXXXXXXX)
    *     - }
    *   - }
    *
    * - on failure, if xml was chosen:
    *   - <?xml version='1.0' encoding='utf-8'?>
    *     - <client>
    *       - <status>false</status>
    *       - <id>error id</id>
    *       - <message>error message</message>
    *     - </client>
    *
    *
    * - on failure, if json was chosen:
    *   - {"client":
    *     - {
    *       - "status":false,
    *       - "id":error id,
    *       - "message":error message
    *     - }
    *   - }
    *
    */
    public function updateClient($p_sClientId,
    $p_sMode,
     $p_bSecure,
            $p_bWebaccess=null,
             $p_sPassword="",
             $p_sEmail="",
             $p_sCellphone="",
             $p_sInternationalCode="",
             $p_sDocument="",
            $p_sName = "",
             $p_sAddress = "",
             $p_sPhone = "",
             $p_sBlocked = "",
             $p_someCustomFields=array()){
        
        $sWebAccess = "";
        if ($p_bWebaccess === true) {
            $sWebAccess = "true";
        }
        elseif($p_bWebaccess === false) {
            $sWebAccess = "false";
        }
        
        $somePostParams = array("p_sClientId"=>$p_sClientId, "p_sWebaccess" => $sWebAccess, 
            "p_sPassword" => $p_sPassword, "p_sEmail"=>$p_sEmail, "p_sCellphone" => $p_sCellphone, 
            "p_sInternationCode" => $p_sInternationalCode, "p_sDocument" => $p_sDocument, 
            "p_sName"=>$p_sName, "p_sAddress" => $p_sAddress, "p_sPhone"=>$p_sPhone,            
            "p_sBlocked" => $p_sBlocked);
        
        foreach($p_someCustomFields as $sKey=>$sValue){
            $somePostParams["p_someCustomFields[".$sKey."]"]=$sValue;
        }
        
        $sUrl = "updateclient/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    
    /**     
     * This function adds a "social" user given his basic data. 
     * The social user can book in any company of a social network, in our case, all the companies of a multicenter account. This social
     * user is the same for all the companies of a multicenter account. A social user has to sign in his social network before
     * the developer call this method to create an account in Bookitit. 
     *
     * URL to call this function: http://app.bookitit.com/api/11/addsocialuser/<i>p_sPublicKey</i>
     *
     * @warning If you are adding a user for a single site, please use the "adduser" function instead.
     * @warning you need an Admin API KEY linked to a multicenter account to use this method.
     * 
     * @param p_sName Name of the user
     * @param p_sPhone Phone number of the user
     * @param p_sEmail email of the user     
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     * @return
     * - on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <user>
     *       - <status>true</status>
     *       - <id>id of the created user (format bktXXXXXXXXX)</id>     
     *     - </user>
     *
     * - on success, if json was chosen:
     *   - {"user":
     *     - {
     *       - "status":true,
     *       - "id":id of the created user (format bktXXXXXXXXX)
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *   - <user>
     *     - <status>false</status>
     *     - <id>error id</id>
     *     - <message>error message</message>
     *   - </user>
     *
     * - on failure, if json was chosen:
     *   - {"user":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     * @see adduser
     * @see setphoneandsendvalidate
     * @see confirmphone
     */
    public function addSocialUser($p_sName, $p_sPhone, $p_sEmail, $p_sPassword, $p_sMode, $p_bSecure){
        $somePostParams = array("p_sName"=>$p_sName, "p_sPhone"=>$p_sPhone, "p_sEmail"=>$p_sEmail, "p_sPassword" => $p_sPassword);
        $sUrl = "addsocialuser/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function creates an order for your company when a client intends to book a prepay service.
     * The order is created in an initial state (not paid).
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     * 
     * @return
     * on success, if xml was chosen:
     * - <order>
     *   - <status>true</status>
     *   - <id>id of the created order (format bktXXXXXXXX)</id>
     * - </order>
     *
     * - on success, if json was chosen:
     *   - {"order":
     *     - {
     *       - "status":true,
     *       - "id":id of the created order (format bktXXXXXXXX)
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <order>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </order>
     *
     * - on failure, if json was chosen:
     *   - {"order":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function addOrder($p_sMode, $p_bSecure){
        $somePostParams = array();
        $sUrl = "addorder/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function is used to add a new company to Bookitit.
     * Only administrator users, who have an administrator api key, can create
     * companies with this function.
     *
     * @param p_sEmail email of the company
     * @param p_sPassword Encrypted password (md5) of the company.
     * @param p_sName Name of the company
     * @param p_sCellPhone Cellphone of the company (optional)
     * @param p_sPhone Phone of the company (optional)
     * @param p_sExpirationDate the expiration date of the account (format YYYY-MM-DD). Optional, default is one year from today.
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     * @return
     * - on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <company>
     *       - <status>true</status>
     *       - <id>id of the created company (format bkt XXXXXXXXX)</id>
     *       - <public>the public key of the created company</public>
     *       - <private>the private key of the created company</private>
     *     - </company>
     *
     * - on success, if json was chosen:
     *   - {"company":
     *     - {
     *       - "status":true,
     *       - "id":id of the created company (format bkt XXXXXXXXX),
     *       - "public":the public key of the created company,
     *       - "private":the private key of the created company
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <company>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </company>
     *
     * - on failure, if json was chosen:
     *   - {"company":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function addCompany($p_sName,$p_sCellPhone, $p_sPhone, $p_sEmail, $p_sPassword, $p_iDisabled, $p_sMode, $p_bSecure){
        $somePostParams = array("p_sName"=>$p_sName,"p_sCellPhone"=>$p_sCellPhone, "p_sPhone"=>$p_sPhone, "p_sEmail"=>$p_sEmail, "p_sPassword"=>$p_sPassword, "p_iDisabled"=>$p_iDisabled);
        $sUrl = "addcompany/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function changes the email of one of your users (if a user's ID is given) or the email of your company (if no ID is given).
     * Also marks the user or company as ACTIVE.
     * It should be used after the user or company enters the activation code received in a cellphone.
     *
     * @param p_sEmail email of the user
     * @param p_sUserID the id of the user (format bktXXXXXXXXX)
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     * @return
     * - on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <user>
     *       - <status>true</status>
     *     - </user>
     *
     * - on success, if json was chosen:
     *   - {"user":
     *     - {
     *       - "status":true
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <user>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </user>
     *
     * - on failure, if json was chosen:
     *   - {"user":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     * @see setphoneandsendvalidate
     */
    public function confirmEmail($p_sUserID,$p_sEmail,$p_sRemember,$p_sMode, $p_bSecure){
        $somePostParams = array("p_sUserID"=>$p_sUserID,"p_sEmail"=>$p_sEmail,"p_sRemember"=>$p_sRemember);
        $sUrl = "confirmemail/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function is used to set an event of your company to status CONFIRMED.
     * If synchronization is activated for your company, it will try to send the event to your local software. 
     *
     * @param p_sEventID the id of the event (format "bktXXXXXXXX")
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     * @return
     * - on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <event>
     *       - <status>true</status>
     *     - </event>
     *
     * - on success, if json was chosen:
     *   - {"event":
     *     - {
     *       - "status":true
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <event>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </event>
     *
     * - on failure, if json was chosen:
     *   - {"event":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     */
    public function confirmEvent($p_sEventID,$p_sMode, $p_bSecure){
        $somePostParams = array("p_sEventID"=>$p_sEventID);
        $sUrl = "confirmevent/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function changes the phone of one of your users (if a user's ID is given) or the phone of your company (if no ID is given).
     * Also marks the user or company as ACTIVE.
     * It should be used after the user or company enters the activation code received in a cellphone.
     *
     * @param p_sPhone Phone number of the user
     * @param p_sUserID the id of the user (optional, format bktXXXXXXXXX)
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     * @return
     * - on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <user>
     *       - <status>true</status>
     *     - </user>
     *
     * - on success, if json was chosen:
     *   - {"user":
     *     - {
     *       - "status":true
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <user>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </user>
     *
     * - on failure, if json was chosen:
     *   - {"user":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     * @see setphoneandsendvalidate
     */
    public function confirmPhone($p_sUserID,$p_sPhone,$p_sMode, $p_bSecure){
        $somePostParams = array("p_sUserID"=>$p_sUserID, "p_sPhone"=>$p_sPhone);
        $sUrl = "confirmphone/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function deletes an agenda of your company.
     * An agenda ID must be provided. It can be the ID of the agenda in Bookitit or, if you are synchronizing or integrating agendas between your software and Bookitit, the ID of the agenda in your software (we call it Synchro ID). If both are provided, the Synchro ID has preference.
     * 
     * @param type p_sAgendaID ID of the agenda (format bktXXXXXXX)
     * @param type p_sSynchroID ID of the agenda in your software
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     * 
     * @return
     * on success, if xml was chosen:
     * - <agenda>
     *   - <status>true</status>
     * - </agenda>
     *
     * - on success, if json was chosen:
     *   - {"agenda":
     *     - {
     *       - "status":true,
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <agenda>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </agenda>
     *
     * - on failure, if json was chosen:
     *   - {"agenda":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     */
    public function deleteAgenda($p_sAgendaID,$p_sSynchroID,$p_sMode, $p_bSecure){
        $somePostParams = array("p_sAgendaID"=>$p_sAgendaID, "p_sSynchroID"=>$p_sSynchroID);
        $sUrl = "deleteagenda/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function deletes a service of your company.
     * A service ID must be provided. It can be the ID of the service in Bookitit or, if you are synchronizing or integrating services between your software and Bookitit, the ID of the service in your software (we call it Synchro ID). If both are provided, the Synchro ID has preference.
     * 
     * @param type p_sServiceID ID of the service (format bktXXXXXXX)
     * @param type p_sSynchroID ID of the service in your software
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     * 
     * @return
     * on success, if xml was chosen:
     * - <service>
     *   - <status>true</status>
     * - </service>
     *
     * - on success, if json was chosen:
     *   - {"service":
     *     - {
     *       - "status":true,
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <service>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </service>
     *
     * - on failure, if json was chosen:
     *   - {"service":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     */
    public function deleteService($p_sServiceID,$p_sSynchroID,$p_sMode, $p_bSecure){
        $somePostParams = array("p_sServiceID"=>$p_sServiceID, "p_sSynchroID"=>$p_sSynchroID);
        $sUrl = "deleteservice/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function deletes an agenda-service connection of your company.
     * Agenda ID and service ID must be provided. They can be the IDs in Bookitit or, if you are synchronizing or integrating agendas between your software and Bookitit, the IDs of your software (we call it Synchro ID).
     * If both are given, Synchro IDs have preference.
     * 
     * @param p_sAgendaID agenda ID (format bktXXXXXXX)
     * @param p_sServiceID service ID (format bktXXXXXXX)
     * @param p_sAgendaSynchroID agenda ID in your software (use in case of software integration)
     * @param p_sServiceSynchroID service ID in your software (use in case of software integration)
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *  
     * @return
     * on success, if xml was chosen:
     * - <agenda_service>
     *   - <status>true</status>
     * - </agenda_service>
     *
     * - on success, if json was chosen:
     *   - {"agenda_service":
     *     - {
     *       - "status":true,
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <agenda_service>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </agenda_service>
     *
     * - on failure, if json was chosen:
     *   - {"agenda_service":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     * 
     * 
     */
    public function deleteAgendaService($p_sAgendaID,$p_sServiceID,$p_sAgendaSynchroID,$p_sServiceSynchroID,$p_sMode, $p_bSecure){
        $somePostParams = array("p_sAgendaID"=>$p_sAgendaID, "p_sServiceID"=>$p_sServiceID,"p_sAgendaSynchroID"=>$p_sAgendaSynchroID,"p_sServiceSynchroID"=>$p_sServiceSynchroID);
        $sUrl = "deleteagendaservice/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function deletes an event of your company.
     *
     * @param p_iEventID id of the event to delete (format bktXXXXXXX)
     * @param p_sEventSynchroID id of the event in your software (use in case of software integration)
     * @param p_bSendNotification (optional) Indicates if an email will be sent to notify the client
     *                            "true": send email
     *                            "false": don't send email
     *                            default is "false"
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     * @return
     * on success, if xml was chosen:
     * - <event>
     *   - <status>true</status>
     * - </event>
     *
     * - on success, if json was chosen:
     *   - {"event":
     *     - {
     *       - "status":true,
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <event>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </event>
     *
     * - on failure, if json was chosen:
     *   - {"event":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function deleteEvent(
     $p_sMode,
     $p_bSecure,
     $p_sEventID,
     $p_sEventSynchroID = "",
     $p_bSendNotification = false
     ){
        $somePostParams = array(
            "p_sEventID" => $p_sEventID, 
            "p_sEventSynchroID" => $p_sEventSynchroID,
            "p_bSendNotification" => $p_bSendNotification
        );
        
        $sUrl = "deleteevent/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
     /**
     * This function deletes a client of your company.
     *
     * URL to call this function: http://app.bookitit.com/api/11/deleteclient/<i>p_sPublicKey</i>
     *     
     * 
     * POST PARAMETERS
     * @param p_sClientId id of the client to be deleted (format bktXXXXXXX)               
     *
     * @return
     * on success, if xml was chosen:
     * - <client>
     *   - <status>true</status>
     * - </client>
     *
     * - on success, if json was chosen:
     *   - {"client":
     *     - {
     *       - "status":true,
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <client>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </event>
     *
     * - on failure, if json was chosen:
     *   - {"client":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function deleteClient($p_sClientId,$p_sMode, $p_bSecure) {
        try {
            $somePostParams = array("p_sClientId" => $p_sClientId);
            $sUrl = "deleteclient/$this->_sPublicKey";
            $sMethod="post";
            $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);            
        }
        catch(CException $e) {
            throw $e;
        }
        return $sResult;
    }
    
    /**
     * This function is used to delete a company in Bookitit.
     * Only administrator users, who have an administrator api key, can delete
     * companies with this function.
     * Moreover, an administrator can only delete the companies created by himself.
     *
     * URL to call this function: http://app.bookitit.com/api/11/deletecompany/<i>p_sPublicKey</i>
     *     
     * @param (optional) p_sPublicKeyToDelete Public key of the company to delete. If you want to use a company id set it as "".
     * @param (optional) p_sCompanyId the id of the company in bkt format (BKTXXXXXXX)
     * At least you need one of these parameters.
     *
     * @return
     *
     * - on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <company>
     *       - <status>true</status>
     *     - </company>
     *
     * - on success, if json was chosen:
     *   - {"company":
     *     - {
     *       - "status":true
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <company>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </company>
     *
     * - on failure, if json was chosen:
     *   - {"company":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     */
    public function deleteCompany($p_sPublicKeyToDelete,$p_sMode, $p_bSecure, $p_sCompanyId="") {
        try {
            
            $somePostParams = array();
            if (!empty($p_sCompanyId)) {
                $somePostParams = array("p_sCompanyId" => $p_sCompanyId);
            }
            else {
                $somePostParams = array("p_sPublicKeyToDelete" => $p_sPublicKeyToDelete);
            }
            
            $sUrl = "deletecompany/$this->_sPublicKey";
            $sMethod="post";
            $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        }
        catch(CException $e) {
            throw $e;
        }
        
        return $sResult;
    }
    
   /**
    * This function gets the schedule of your company
    *
    * URL to call this function: http://app.bookitit.com/api/11/getschedule/<i>p_sPublicKey</i>
    *
    * @param p_sPublicKey Your public key
    *
    * @return
    * - on success, if xml was chosen:
    *   - <?xml version='1.0' encoding='utf-8'?>
    *     - <schedule>
    *       - <status>true</status>
    *       - <i>one or more</i> <opentime>XXX</opentime>
    *     - </schedule>
    *
    * - on success, if json was chosen:
    *   - {"schedule":
    *     - {
    *       - "status":true,
    *       - <i>one or more</i> "opentime": XXX
    *     - }
    *   - }
    *
    * - on failure, if xml was chosen:
    *   - <?xml version='1.0' encoding='utf-8'?>
    *     - <services>
    *       - <status>false</status>
    *       - <id>error id</id>
    *       - <message>error message</message>
    *     - </services>
    *
    * - on failure, if json was chosen:
    *   - {"services":
    *     - {
    *       - "status":false,
    *       - "id":error id,
    *       - "message":error message
    *     - }
    *   - }
    *
    */
    public function getSchedule($p_sMode, $p_bSecure) {
        try {
            $sUrl = "getschedule/$this->_sPublicKey";        
            $sMethod = "get";
            $sResult = $this->startConnection( $sUrl, $p_sMode, $p_bSecure, $sMethod);
        }
        catch(CException $e) {
            throw $e;
        }
        return $sResult;
    }
    
    
    /**
     * This function gets the holidays of your company
     *
     * URL to call this function: http://app.bookitit.com/api/11/getholidays/<i>p_sPublicKey</i>
     *
     * @param p_sPublicKey Your public key
     *
     * @return
     * - on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <holidays>
     *       - <status>true</status>
     *       - <i>zero or more</i> <date>YYYY-MM-DD</date>
     *     - </holidays>
     *
     * - on success, if json was chosen:
     *   - {"holidays":
     *     - {
     *       - "status":true,
     *       - <i>zero or more</i> "date": XXX
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <services>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </services>
     *
     * - on failure, if json was chosen:
     *   - {"services":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function getHolidays($p_sMode, $p_bSecure) {
        try {
            $sUrl = "getholidays/$this->_sPublicKey";        
            $sMethod = "get";
            $sResult = $this->startConnection( $sUrl, $p_sMode, $p_bSecure, $sMethod);
        }
        catch(CException $e) {
            throw $e;
        }
        return $sResult;
    }
    
    
    
    /**
     * This function makes an agenda schedule to be the same as the previously set company general schedule.
     *
     * @param p_sAgendaID id of the agenda (format bktXXXXXXX)
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     * @return
     * on success, if xml was chosen:
     * - <agenda>
     *   - <status>true</status>
     * - </agenda>
     *
     * - on success, if json was chosen:
     *   - {"agenda":
     *     - {
     *       - "status":true,
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <agenda>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </agenda>
     *
     * - on failure, if json was chosen:
     *   - {"agenda":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function setAgendaToCompanySchedule($p_sAgendaID,$p_sMode, $p_bSecure){
        $somePostParams = array("p_sAgendaID"=>$p_sAgendaID);
        $sUrl = "setagendatocompanyschedule/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function sets the cellphone of one of your company's users (if a user's ID is given) or the cellphone of your company (if no ID is given).
     *
     * Since cellphone numbers have to be always confirmed, the user or company will be set
     * as INACTIVE, and a new confirmation code will be returned by the function
     * and sent to the user's or company's cellphone.
     * Please store that code for later confirmation of the phone.
     * 
     * @see confirmphone
     *
     * @param p_sPhone new phone number
     * @param p_sUserID optional, id of your user (format bktXXXXXXXXX)
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     * @return
     * - on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <phone>
     *       - <status>true</status>
     *       - <validatephonekey>the new activation code</validatephonekey>
     *     - </phone>
     *
     * - on success, if json was chosen:
     *   - {"phone":
     *     - {
     *       - "status":true,
     *       - "validatephonekey":the new activation code,
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <phone>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </phone>
     *
     * - on failure, if json was chosen:
     *   - {"phone":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function setPhoneAndSendValidate($p_sPhone,$p_sUserID,$p_sMode, $p_bSecure){
        $somePostParams = array("p_sPhone"=>$p_sPhone, "p_sUserID"=>$p_sUserID);
        $sUrl = "setphoneandsendvalidate/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }

    /**
     * This function tests the connection.
     * The sent value is just echoed by the server.
     *
     * @param p_sEchoValue A value defined by the user.
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http     
     * @return
     * - on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <connection>
     *       - <status>true</status>
     *       - <echovalue>$p_sEchoValue</echovalue>
     *     - </connection>
     *
     * - on success, if json was chosen:
     *   - {"connection":
     *     - {
     *       - "status":true,
     *       - "echovalue":p_sEchoValue
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - nothing (can't return data if the connection fails)
     *
     * - on failure, if json was chosen:
     *   - nothing (can't return data if the connection fails)
     */
    public function testConnection($p_sYourString,$p_sMode, $p_bSecure) {
        $sUrl = "testconnection/$p_sYourString";
        $sMethod = "get";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure,$sMethod );        
        return $sResult;
    }
    
    /**
     * This function updates data of an agenda of your company.
     * An agenda ID must be provided. It can be the ID of the agenda in Bookitit or, if you are synchronizing or integrating agendas between your software and Bookitit, the ID of the agenda in your software (we call it Synchro ID).
     * 
     * @param p_sAgendaID ID of the agenda (format bktXXXXXXX)
     * @param p_sSynchroID ID of the agenda in your software
     * @param p_sName agenda name (optional)
     * @param p_sPhone agenda phone (optional)
     * @param p_sEmail agenda email (optional)
     * @param p_sPublic (optional) indicates if the agenda is "public" or "private". Default is "public"
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     * 
     * @return
     * on success, if xml was chosen:
     * - <agenda>
     *   - <status>true</status>
     * - </agenda>
     *
     * - on success, if json was chosen:
     *   - {"agenda":
     *     - {
     *       - "status":true,
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <agenda>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </agenda>
     *
     * - on failure, if json was chosen:
     *   - {"agenda":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     */
    public function updateAgenda($p_sAgendaID,$p_sSynchroID,$p_sName,$p_sPhone,$p_sEmail,$p_sPublic,$p_sMode, $p_bSecure){
        $somePostParams = array("p_sAgendaID"=>$p_sAgendaID, "p_sSynchroID"=>$p_sSynchroID, "p_sName"=>$p_sName, "p_sPhone"=>$p_sPhone, "p_sEmail"=>$p_sEmail, "p_sPublic"=>$p_sPublic);
        $sUrl = "updateagenda/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function updates the configuration for an agenda of your company.
     *
     * @param p_sAgendaID id of the agenda (format bktXXXXXXX)
     * @param p_iMinAdvanceMakeEvent integer, the minimum advance that users can make reservations with (in days)
     * @param p_iMinAdvanceSeeAgenda integer, the maximum anticipation that users can see the free hours with (in days)
     * @param p_iMinAdvanceCancelEvent integer, the minimum advance that users can cancel reservations with (in days)
     * @param p_sConfirmEvents indicates if the company has to manually confirm the reservations. Options are "yes" or "no", default is "no".
     * @param p_sUserValidate indicates if the users need to validate before making reservations. Options are "yes" or "no", default is "no".
     * @param p_sKeySendMethod indicates how to send the activation keys to users. Options are "sms" and "email", default is "email".
     * @param p_iWidgetIntervalSize how to set the interval between reservation hours in the widget. Options are:
     *  -  "service": Interval time depends on the duration of selected service
     *  -  any number: in minutes, Interval time is globally set
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     * @return
     * on success, if xml was chosen:
     * - <agenda>
     *   - <status>true</status>
     * - </agenda>
     *
     * - on success, if json was chosen:
     *   - {"agenda":
     *     - {
     *       - "status":true,
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <agenda>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </agenda>
     *
     * - on failure, if json was chosen:
     *   - {"agenda":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function updateAgendaConfiguration($p_sAgendaID,$p_iMinAdvanceMakeEvent,$p_iMinAdvanceSeeAgenda,$p_iMinAdvanceCancelEvent,$p_sConfirmEvents, $p_sUserValidate,$p_sKeySendMethod,$p_iWidgetIntervalSize,$p_sMode, $p_bSecure){
        $somePostParams = array("p_sAgendaID"=>$p_sAgendaID,"p_iMinAdvanceMakeEvent"=>$p_iMinAdvanceMakeEvent,"p_iMinAdvanceSeeAgenda"=>$p_iMinAdvanceSeeAgenda,"p_iMinAdvanceCancelEvent"=>$p_iMinAdvanceCancelEvent,"p_sConfirmEvents"=>$p_sConfirmEvents, "p_sUserValidate"=>$p_sUserValidate,"p_sKeySendMethod"=>$p_sKeySendMethod,"p_iWidgetIntervalSize"=>$p_iWidgetIntervalSize);
        $sUrl = "updateagendaconfiguration/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
   /**
    * This function sets or updates agenda communications
    *
    * URL to call this function: http://app.bookitit.com/api/11/updateagendacommunications/<i>p_sPublicKey</i>
    *
    * AVAILABLE VALUES FOR EACH PARAMETER
    * "email" for email communication
    * "sms" for sms communication
    * "both" for email and sms communication
    * "none" for none communication
    * "" it will no change a value 
    
    * @param $p_sAgendaID id of the agenda (format bktXXXXXXX)
    * @param $p_sClient_event_add_admin (optional) Send to admin when client adds an event. "email" / "sms" / "both" / "none" / ""
    * @param $p_sAdmin_event_add_admin (optional) Sent to admin when an event its added from calendar. "email" / "sms" / "both" / "none" / ""
    * @param $p_sUser_event_confirm_admin (optional) Sent to admin when agenda/admin confirms an event. "email" / "sms" / "both" / "none" / ""
    * @param $p_sUser_event_unconfirm_admin (optional) Sent to admin when agenda/admin unconfirms an event. "email" / "sms" / "both" / "none" / ""
    * @param $p_sUser_event_delete_admin (optional) Sent to admin when client deletes an event. "email" / "sms" / "both" / "none" / ""
    * @param $p_sClient_event_add (optional) Sent to agenda when client adds an event. "email" / "sms" / "both" / "none" / ""
    * @param $p_sUser_event_add_admin (optional) Sent to agenda when an event its added from calendar. "email" / "sms" / "both" / "none" / ""
    * @param $p_sUser_event_confirm_agenda (optional) Sent to agenda when agenda/admin confirms an event. "email" / "sms" / "both" / "none" / ""
    * @param $p_sUser_event_unconfirm_agenda (optional) Sent to agenda when agenda/admin unconfirms an event. "email" / "sms" / "both" / "none" / ""
    * @param $p_sUser_event_delete_agenda (optional) Sent to agenda when client deletes an event . "email" / "sms" / "both" / "none" / ""
    * @param $p_sUser_event_add (optional) Sent to client when an event is added from calendar. "email" / "sms" / "both" / "none" / ""
    * @param $p_sUser_event_modify (optional) Sent to client when an event is modify from calendar. "email" / "sms" / "both" / "none" / ""
    * @param $p_sUser_event_delete (optional) Sent to client when an event is deleted from calendar. "email" / "sms" / "both" / "none" / ""
    * @param $p_sUser_event_confirm (optional) Sent to client when an event is confirmed. "email" / "sms" / "both" / "none" / ""
    * @param $p_sUser_event_uncofirm (optional) Sent to client when an event is unconfirmed. "email" / "sms" / "both" / "none" / ""
    * @param $p_sClient_event_remember (optional) Sent reminder to client. "email" / "sms" / "both" / "none" / ""
    * @param $p_iClient_event_remember_min (optional) Minutes before the event to send the reminder to the client.
    * @param $p_sUser_event_remember (optional) Sent reminder to agenda. "email" / "sms" / "both" / "none" / ""
    * @param $p_iUser_event_remember_min (optional) Minutes before the event to send the reminder to the agenda. 
    *   
    *
    * @return
    * on success, if xml was chosen:
    * - <agenda>
    *   - <status>true</status>
    * - </agenda>
    *
    * - on success, if json was chosen:
    *   - {"agenda":
    *     - {
    *       - "status":true,
    *     - }
    *   - }
    *
    * - on failure, if xml was chosen:
    *   - <?xml version='1.0' encoding='utf-8'?>
    *     - <agenda>
    *       - <status>false</status>
    *       - <id>error id</id>
    *       - <message>error message</message>
    *     - </agenda>
    *
    * - on failure, if json was chosen:
    *   - {"agenda":
    *     - {
    *       - "status":false,
    *       - "id":error id,
    *       - "message":error message
    *     - }
    *   - }
    *
    */
    public function updateAgendaCommunications($p_sAgendaID, $p_sMode, $p_bSecure, $p_sClient_event_add_admin = "", $p_sAdmin_event_add_admin = "",
            $p_sUser_event_confirm_admin = "", $p_sUser_event_unconfirm_admin = "", $p_sUser_event_delete_admin = "",
            $p_sClient_event_add = "", $p_sUser_event_add_admin = "", $p_sUser_event_confirm_agenda = "",
            $p_sUser_event_unconfirm_agenda = "", $p_sUser_event_delete_agenda = "", $p_sUser_event_add = "", $p_sUser_event_modify = "",
            $p_sUser_event_delete = "", $p_sUser_event_confirm = "", $p_sUser_event_uncofirm = "", $p_sClient_event_remember = "",
            $p_iClient_event_remember_min = "", $p_sUser_event_remember = "", $p_iUser_event_remember_min = "") {
        
        $somePostParams = array("p_sAgendaID"=>$p_sAgendaID,
            "p_sClient_event_add_admin" => $p_sClient_event_add_admin,
            "p_sAdmin_event_add_admin" => $p_sAdmin_event_add_admin,
            "p_sUser_event_confirm_admin" => $p_sUser_event_confirm_admin,
            "p_sUser_event_unconfirm_admin" => $p_sUser_event_unconfirm_admin,
            "p_sUser_event_delete_admin" => $p_sUser_event_delete_admin,
            "p_sClient_event_add" => $p_sClient_event_add,
            "p_sUser_event_add_admin" => $p_sUser_event_add_admin,
            "p_sUser_event_confirm_agenda" => $p_sUser_event_confirm_agenda,
            "p_sUser_event_unconfirm_agenda" => $p_sUser_event_unconfirm_agenda,
            "p_sUser_event_delete_agenda" => $p_sUser_event_delete_agenda,
            "p_sUser_event_add" => $p_sUser_event_add,
            "p_sUser_event_modify" => $p_sUser_event_modify,
            "p_sUser_event_delete" => $p_sUser_event_delete,
            "p_sUser_event_confirm" => $p_sUser_event_confirm,
            "p_sUser_event_uncofirm" => $p_sUser_event_uncofirm,
            "p_sClient_event_remember" => $p_sClient_event_remember,
            "p_iClient_event_remember_min" => $p_iClient_event_remember_min,
            "p_sUser_event_remember" => $p_sUser_event_remember,
            "p_iUser_event_remember_min" => $p_iUser_event_remember_min);
        $sUrl = "updateagendacommunications/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function updates an agenda-service connection of your company.
     * Agenda ID and service ID must be provided. They can be the IDs in Bookitit or, if you are synchronizing or integrating agendas between your software and Bookitit, the IDs of your software (we call it Synchro ID). If both are given, Synchro IDs have preference.
     * 
     * @param p_sAgendaID agenda ID (format bktXXXXXXX)
     * @param p_sServiceID service ID (format bktXXXXXXX)
     * @param p_sAgendaSynchroID agenda ID in your software (use in case of software integration)
     * @param p_sServiceSynchroID service ID in your software (use in case of software integration)
     * @param p_iDuration (optional) New duration of the service specific for this agenda, in minutes.
     * @param p_dPrice (optional) New price of the service specific for this agenda, in your currency
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *  
     * @return
     * on success, if xml was chosen:
     * - <agenda_service>
     *   - <status>true</status>
     * - </agenda_service>
     *
     * - on success, if json was chosen:
     *   - {"agenda_service":
     *     - {
     *       - "status":true,
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <agenda_service>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </agenda_service>
     *
     * - on failure, if json was chosen:
     *   - {"agenda_service":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     * 
     * 
     */
    public function updateAgendaService($p_sAgendaID,$p_sServiceID, $p_sAgendaSynchroID, $p_sServiceSynchroID, $p_iDuration,$p_dPrice,$p_sMode, $p_bSecure){
        $somePostParams = array("p_sAgendaID"=>$p_sAgendaID,"p_sServiceID"=>$p_sServiceID, "p_sAgendaSynchroID"=>$p_sAgendaSynchroID, "p_sServiceSynchroID"=>$p_sServiceSynchroID, "p_iDuration"=>$p_iDuration,"p_dPrice"=>$p_dPrice);
        $sUrl = "updateagendaservice/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function is used to update data of a company in Bookitit.
     * Only administrator users, who have an administrator api key, can update
     * companies with this function.
     * Moreover, an administrator can only update the companies created by himself.
     *
     * If any field in array is not set or the value is void string ( "" ), the value won't be changed.
     * To unset or delete any data the array field must be a string with "null" value
     *
     * @param p_sPublicKeyToModify Public key of the company to modify
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     * @param p_sName (optional) Name of the company, can't be unset or delete
     * @param p_sCellPhone (optional) Cellphone of the company
     * @param p_sPhone (optional) Phone of the company
     * @param p_sPassword (optional) Encrypted password (md5) of the company, can't be unset or delete
     * @param p_sAddress (optional) Address of the company
     * @param p_sPostalCode (optional) Postal code of the company
     * @param p_sWeb (optional) Url of the company
     * @param $p_iIdCountry (optional) the id of the country for the company in bktXXXX format, can't be unset or delete
     * @param $p_iIdRegion (optional) the id of the region for the company in bktXXXX format, can't be unset or delete
     * @param $p_iIdCity (optional) the id of the city for the company in bktXXXX format, can't be unset or delete
     * @param $p_sDocument (optional) Document of the company
     *
     * @return
     *
     * - on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <company>
     *       - <status>true</status>
     *     - </company>
     *
     * - on success, if json was chosen:
     *   - {"company":
     *     - {
     *       - "status":true
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <company>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </company>
     *
     * - on failure, if json was chosen:
     *   - {"company":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     */
    public function updateCompany($p_sPublicKeyToModify, $p_sMode, $p_bSecure, $p_sName = "", $p_sCellPhone = "", $p_sPhone = "", $p_sPassword = "", $p_sAddress = "", $p_sPostalCode = "", $p_sWeb = "", $p_iCountryId = "", $p_iRegionId = "", $p_iCityId = "", $p_sDocument = ""){
        $somePostParams = array(
            "p_sPublicKeyToModify" => $p_sPublicKeyToModify,
            "p_sName" => $p_sName,
            "p_sCellPhone" => $p_sCellPhone,
            "p_sPhone" => $p_sPhone,
            "p_sPassword" => $p_sPassword,
            "p_sAddress" => $p_sAddress,
            "p_sPostalCode" => $p_sPostalCode,
            "p_sWeb" => $p_sWeb, 
            "p_sDocument" => $p_sDocument, 
            "p_iIdCountry" => $p_iCountryId, 
            "p_iIdRegion" => $p_iRegionId, 
            "p_iIdCity" => $p_iCityId
        );
        
        $sUrl = "updatecompany/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function updates an event.
     *
     * @param p_sEventID id of the event (format bktXXXXXXXX)
     * @param p_sAgendaID id of the agenda (format bktXXXXXXXX)
     * @param p_dStartDate start date of the event (YYYY-MM-DD)
     * @param p_dEndDate end date of the event (YYYY-MM-DD)
     * @param p_iStartTime start time of the event (in minutes, since 00:00)
     * @param p_iEndTime end time of the event (in minutes, since 00:00)
     * @param p_sServiceID (optional) id of the service (format bktXXXXXXXX), To unset or delete this data the array field must be a "null" value
     * @param p_sEventSynchroID (optional) synchro ID of the event
     * @param p_sAgendaSynchroID (optional) synchro ID of the agenda
     * @param p_sServiceSynchroID (optional) synchro ID of the service
     * @param p_sTitle (optional) title of the event, To unset or delete this data the array field must be a "null" value
     * @param p_sDescription (optional) description of the event, To unset or delete this data the array field must be a "null" value
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     * @param $p_bSendNotification (optional) Indicates if an email will be sent to notify the client
     *                            "true": send email
     *                            "false": don't send email
     *                            default is "false"
     *
     * @return
     * on success, if xml was chosen:
     * - <event>
     *   - <status>true</status>
     * - </event>
     *
     * - on success, if json was chosen:
     *   - {"event":
     *     - {
     *       - "status":true,
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <event>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </event>
     *
     * - on failure, if json was chosen:
     *   - {"event":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function updateEvent($p_sMode, $p_bSecure, $p_sEventID, $p_sAgendaID, $p_sServiceID, $p_dStartDate, $p_dEndDate, $p_iStartTime, $p_iEndTime, $p_sTitle = "", $p_sDescription = "", $p_sEventSynchroID = "", $p_sAgendaSynchroID = "", $p_sServiceSynchroID = "", $p_bSendNotification = false){
        $somePostParams = array(
            "p_sEventID" => $p_sEventID,
            "p_sAgendaID" => $p_sAgendaID,
            "p_sServiceID" => $p_sServiceID,
            "p_dStartDate" => $p_dStartDate,
            "p_dEndDate" => $p_dEndDate,
            "p_iStartTime" => $p_iStartTime,
            "p_iEndTime" => $p_iEndTime,
            "p_sTitle" => $p_sTitle,
            "p_sDescription" => $p_sDescription,
            "p_sEventSynchroID" => $p_sEventSynchroID,
            "p_sAgendaSynchroID" => $p_sAgendaSynchroID,
            "p_sServiceSynchroID" => $p_sServiceSynchroID,
            "p_bSendNotification" => $p_bSendNotification
        );
        
        $sUrl = "updateevent/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function updates the status of an event. 
     *
     * @param p_sEventID id of the event (format bktXXXXXXX)
     * @param p_sStatus the new status of the event, options are "canceled", "completed", "pending", "pending_accepted", "pending_rejected", "in_payment", "wiretransfer" and "time_block"
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     * 
     * @return
     * on success, if xml was chosen:
     * - <event>
     *   - <status>true</status>     
     * - </event>
     *
     * - on success, if json was chosen:
     *   - {"event":
     *     - {
     *       - "status":true     
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <event>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </event>
     *
     * - on failure, if json was chosen:
     *   - {"event":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function updateEventStatus($p_sEventID,$p_sStatus,$p_sMode, $p_bSecure){
        $somePostParams = array("p_sEventID"=>$p_sEventID,"p_sStatus"=>$p_sStatus);
        $sUrl = "updateeventstatus/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function updates an previously created order of a prepay service.
     *
     * @param $p_sPublicKey Your public key
     * @param $p_sOrderID id of the order (format bktXXXXXXX)
     * @param $p_sServiceID id of the related service (format bktXXXXXXX)
     * @param $p_sEventID id of the related event (format bktXXXXXXX)
     * @param $p_sStatus  new status of the order. Options are:
     *  -  "not_paid": the initial status (default)
     *  -  "bank_selected": User has selected the bank.
     *  -  "itemized": The bank request the order with lines.
     *  -  "transaction_ok": The bank confirms the payment.
     *  -  "paid": The service is definitely paid.
     *  -  "error": There has been a problem with the payment.
     *  -  "wiretransfer": This order is paid by wire transfer.
     * @param $p_sBank name of the bank. Current options are:
     *  -  "paypal" (default)
     *  -  "pasat4b"
     * @param $p_sStore id of the store to which the payment is made (more concrete than the bank)
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     * @return
     * on success, if xml was chosen:
     * - <order>
     *   - <status>true</status>
     *   - <id>id of the created agenda (format bktXXXXXXXX)</id>
     * - </order>
     *
     * - on success, if json was chosen:
     *   - {"order":
     *     - {
     *       - "status":true,
     *       - "id":id of the created service (format bktXXXXXXXX)
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <order>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </order>
     *
     * - on failure, if json was chosen:
     *   - {"order":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function updateOrder($p_sOrderID, $p_sServiceID, $p_iEventID, $p_sStatus, $p_sBank, $p_sStore,$p_sMode, $p_bSecure){
        $somePostParams = array("p_sOrderID"=>$p_sOrderID, "p_sServiceID"=>$p_sServiceID, "p_iEventID"=>$p_iEventID, "p_sStatus"=>$p_sStatus, "p_sBank"=>$p_sBank, "p_sStore"=>$p_sStore);
        $sUrl = "updateorder/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function updates data of a service of your company.
     * A service ID must be provided. It can be the ID of the service in Bookitit or, if you are synchronizing or integrating services between your software and Bookitit, the ID of the service in your software (we call it Synchro ID).
     * 
     * @param p_sPublicKey Your public key
     * @param p_sServiceID ID of the service (format bktXXXXXXX)
     * @param p_sSynchroID ID of the service in your local software
     * @param p_sName service name (optional)
     * @param p_sDuration service duration in minutes (optional)
     * @param p_sPrice service price (format XX.XX)(optional)
     * @param p_sPublic (optional) indicates if the service is "public" or "private". Default is "public"
     * @param p_sPrepay (optional) indicates is the services is "prepay" or "not_prepay". Default is "not_prepay"
     * @param p_sCurrency (optional) in which currency is the price, accepted values are "EUR" for euro "GBP" for british pound and "USD" for United States dollar. Default is euro.
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     * 
     * @return
     * on success, if xml was chosen:
     * - <order>
     *   - <status>true</status>
     * - </order>
     *
     * - on success, if json was chosen:
     *   - {"order":
     *     - {
     *       - "status":true,
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <order>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </order>
     *
     * - on failure, if json was chosen:
     *   - {"order":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     * 
     */
    public function updateService($p_sServiceID,$p_sSynchroID,$p_sName,$p_sDuration,$p_sPrice,$p_sPublic,$p_sPrepay,$p_sCurrency,$p_sMode, $p_bSecure){
        $somePostParams = array("p_sServiceID"=>$p_sServiceID,"p_sSynchroID"=>$p_sSynchroID,"p_sName"=>$p_sName,"p_sDuration"=>$p_sDuration,"p_sPrice"=>$p_sPrice,"p_sPublic"=>$p_sPublic,"p_sPrepay"=>$p_sPrepay,"p_sCurrency"=>$p_sCurrency);
        $sUrl = "updateservice/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
    * This function updates a social user given his basic data.
    *
    * @param p_sUserID The id of the user you want to update (format bktXXXXXXX)
    * @param p_sName Name of the user
    * @param p_sPhone Phone number of the user
    * @param p_sEmail email of the user
    * @param $p_sMode xml or json
    * @param $p_bSecure true for https, false for http
    * @param $p_iCountryId (optional) the id of the country for the company in bktXXXX format
    * @param $p_iRegionId (optional) the id of the region for the company in bktXXXX format 
    * @param $p_iCityId (optiona) the id of the city for the company in bktXXXX format
    *
    * @return
    * - on success, if xml was chosen:
    *   - <?xml version='1.0' encoding='utf-8'?>
    *     - <user>
    *       - <status>true</status>
    *     - </user>
    *
    * - on success, if json was chosen:
    *   - {"user":
    *     - {
    *       - "status":true,
    *     - }
    *   - }
    *
    * - on failure, if xml was chosen:
    *   - <?xml version='1.0' encoding='utf-8'?>
    *     - <user>
    *       - <status>false</status>
    *       - <id>error id</id>
    *       - <message>error message</message>
    *     - </user>
    *
    *
    * - on failure, if json was chosen:
    *   - {"user":
    *     - {
    *       - "status":false,
    *       - "id":error id,
    *       - "message":error message
    *     - }
    *   - }
    *
    * @see addsocialuser
    * @see setphoneandsendvalidate
    */
    public function updateSocialUser($p_sUserID,$p_sName,$p_sPhone,$p_sEmail,$p_sMode, $p_bSecure, $p_iCountryId = "", $p_iRegionId = "", $p_iCityId = ""){
        $somePostParams = array("p_sUserID"=>$p_sUserID,"p_sName"=>$p_sName,"p_sPhone"=>$p_sPhone,"p_sEmail"=>$p_sEmail, "p_iCountryId"=>$p_iCountryId, "p_iRegionId"=>$p_iRegionId, "p_iCityId"=>$p_iCityId);
        $sUrl = "updatesocialuser/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
      /**
    * This function updates a user given his basic data.
    * 
    * URL to call this function: http://app.bookitit.com/api/11/updateuser/<i>p_sPublicKey</i>
    *
    *
    * @param p_sUserID The id of the user you want to update (format bktXXXXXXX)
    * @param p_sName (optional) Name of the user. If you don't want to send this parameter set it as "".     
    * @param p_sPhone (optional) CellPhone number of the user. If you don't want to send this parameter set it as "".
    * @param p_sEmail (optional) email of the user. If you don't want to send this parameter set it as "".
    * @param p_sDocument (optional) Document of the user. If you don't want to send this parameter set it as "".
    * @param p_sAddress (optional) The address of the user. If you don't want to send this parameter set it as "".
    * @param $p_sBlocked (optional) If you want to block a client you will have to set it as "true" otherwise "false". If you don't want to send this parameter set it as "".
    * @param $p_someCustomFields (optional) If your company account is using custom fields for the users, you can set it there.     
    * array(
                                                    "customvalidate1" => "data in text format",
                                                    "customvalidate2"=> "data in text format", 
                                                    "custom1"=> "data in text format", 
                                                    "custom2"=> "data in text format", 
                                                    "custom3"=> "data in text format", 
                                                    "custom4"=> "data in text format", 
                                                    "custom5"=> "data in text format" 
           )
    * 
    * @return
    * - on success, if xml was chosen:
    *   - <?xml version='1.0' encoding='utf-8'?>
    *     - <user>
    *       - <status>true</status>
    *     - </user>
    *
    * - on success, if json was chosen:
    *   - {"user":
    *     - {
    *       - "status":true,
    *     - }
    *   - }
    *
    * - on failure, if xml was chosen:
    *   - <?xml version='1.0' encoding='utf-8'?>
    *     - <user>
    *       - <status>false</status>
    *       - <id>error id</id>
    *       - <message>error message</message>
    *     - </user>
    *
    *
    * - on failure, if json was chosen:
    *   - {"user":
    *     - {
    *       - "status":false,
    *       - "id":error id,
    *       - "message":error message
    *     - }
    *   - }
    *
    * @see addsocialuser
    * @see setphoneandsendvalidate
    */
    public function updateUser($p_sUserID,$p_sMode, $p_bSecure, $p_sName="", $p_sPhone="", $p_sEmail="", $p_sDocument="", $p_sAddress="", $p_sBlocked="", $p_someCustomFields=array()){
        $somePostParams = array("p_sUserID"=>$p_sUserID,"p_sName"=>$p_sName,"p_sPhone"=>$p_sPhone,
            "p_sEmail"=>$p_sEmail, "p_sDocument" => $p_sDocument, "p_sAddress" => $p_sAddress, "p_sBlocked" => $p_sBlocked);
        foreach($p_someCustomFields as $sKey=>$sValue){
            $somePostParams["p_someCustomFields[".$sKey."]"]=$sValue;
        }
        $sUrl = "updateuser/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function updates the schedule of your company for a single weekday.
     * That is, at which time your company is open or closed.
     * Should be called once per day you want to update.
     * @Warning Only works for standard companies. 
     *
     * @param $p_sWeekday the weekday you want to update (from "monday" to "sunday")
     * @param $p_someOpeningTimesStrings an array with strings representing the opening times, grouped by periods of half an hour.
     *        For example, to indicate that the company is open from 10:00 to 12:00, the array should be ("10:00", "10:30", "11:00", "11:30")
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     * @return
     * - on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <schedule>
     *       - <status>true</status>
     *     - </schedule>
     *
     * - on success, if json was chosen:
     *   - {"schedule":
     *     - {
     *       - "status":true
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <schedule>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </schedule>
     *
     * - on failure, if json was chosen:
     *   - {"schedule":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function updateCompanyScheduleDay($p_sWeekDay,$p_someOpeningTimesStrings, $p_sMode, $p_bSecure){
        $iWeekDay="";
        switch(strtolower($p_sWeekDay)){
            case "monday":
                $iWeekDay=1;
                break;
            case "tuesday":
                $iWeekDay=2;
                break;
            case "wednesday":
                $iWeekDay=3;
                break;
            case "thursday":
                $iWeekDay=4;
                break;
            case "friday":
                $iWeekDay=5;
                break;
            case "saturday":
                $iWeekDay=6;
                break;
            case "sunday":
                $iWeekDay=7;
                break;
            default:
                $iWeekDay=1;
                break;
        }
        
        $somePostParams=array("var0"=>$iWeekDay);
        
        $someOpeningTimesInts=array();
        
        foreach($p_someOpeningTimesStrings as $sOpeningTime){
            $someOpeningTimesInts[]=$this->renderMinutes($sOpeningTime);
        }
           
        $j=0;
        for($i=0; $i<=1410; $i=$i+30){
            $somePostParams["var".strval($j+1)]=$i;
            if(in_array($i, $someOpeningTimesInts)){
                $somePostParams["var".strval($j+2)]="o";
            }
            else{
                $somePostParams["var".strval($j+2)]="c";
            }
            $j=$j+2;
        }
        
        $sUrl = "updatecompanyscheduleday/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function checks if the amount of an order is valid
     * 
     * @param p_sOrderID id of the order (format bktXXXXXXX)
     * @param p_iAmount id of the related service (format bktXXXXXXX)
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     * @return
     * on success, if xml was chosen:
     * - <order>
     *   - <status>true</status>
     * - </order>
     *
     * - on success, if json was chosen:
     *   - {"order":
     *     - {
     *       - "status":true,
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <order>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </order>
     *
     * - on failure, if json was chosen:
     *   - {"order":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function validateOrder($p_sOrderID, $p_iAmount,$p_sMode, $p_bSecure){
        $somePostParams = array("p_sOrderID"=>$p_sOrderID, "p_iAmount"=>$p_iAmount);
        $sUrl = "validateorder/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }

    /**
     * This function translates a time in format "HH:MM" into the number of minutes elapsed from 00:00
     * @param $p_sTime the time (in format HH:MM)
     * @return $iMinutes number of minutes elapsed from 00:00 to $p_sTime
     */
    public function renderMinutes($p_sTime) {
      
        $someHorasMinutos = explode(":",$p_sTime);
        $iMinutes = ((int)$someHorasMinutos[0]) * 60;
        $iMinutes = $iMinutes + ((int)$someHorasMinutos[1]);
      
        return $iMinutes;
    }

    /**
     * This function translates a number in minutes elapsed from 00:00 to hour format
     * @param $p_iHourInMinutes the hour in minutes elapsed from 00:00
     * @return $sTime string with the time (format HH:MM)
     */
    public function renderHour($p_iHourInMinutes) {
        $sTime = "";
       
        $iHours = $p_iHourInMinutes/60;
        if($p_iHourInMinutes%60!=0){
                $iHours = ($p_iHourInMinutes - ($p_iHourInMinutes%60))/60;
                if ($iHours < 10) {
                        $iHours = "0".$iHours;
                }
        }
        else {
                if ($iHours < 10) {
                        $iHours = "0".$iHours;
                }
        }

        $iMinute = $p_iHourInMinutes%60;
        if($iMinute==0){
                $sTime = $iHours.":00";
        }else{
                if($iMinute<10)
                {
                        $sTime = $iHours.":0".$iMinute;
                }
                else
                {
                        $sTime = $iHours.":".$iMinute;
                }

        }
      
        return $sTime;
    }

    /**
     * This function gets the data of a user of your company.
     *
     * @param $p_sPublicKey Your public key
     * @param $p_sUserID Identifier of the user to retrieve data of. The format is bktXXXXXXXXX.
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     * @return
     * - on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <user>
     *       - <status>true</status>
     *       - <id>id of the user (format bktXXXXXXXXX)</id>
     *       - <name>name of the user</name>
     *       - <email>email of the user</email>
     *       - <phone>phone of the user</phone>
     *   - </user>
     *
     * - on success, if json was chosen:
     *   - {"user":
     *     - {
     *       - "status":true,
     *       - "id":id of the user (format bktXXXXXXXXX),
     *       - "name":name of the user,
     *       - "email":email of the user,
     *       - "phone":phone of the user
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <user>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </user>
     *
     * - on failure, if json was chosen:
     *   - {"user":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     */
    public function getUser($p_sIdUser, $p_sMode, $p_bSecure) {
        $sUrl = "getuser/$this->_sPublicKey/$p_sIdUser";
        $sMethod = "get";
        $sResult = $this->startConnection( $sUrl, $p_sMode, $p_bSecure, $sMethod);
        return $sResult;
    }
    
    /**
     * This function validates (checks) if user's (or company's) credentials are correct.
     * Company Phone API keys are returned (they are generated if they don't exist).
     * 
     * @param p_sUserMail User's email
     * @param p_sPassword User's enrypted (md5) password
     * @param p_sType The type of user, can be "administrator", "client", "company, "social". Default is "client";
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     * @return
     * on success, if xml was chosen:
     * - <validate>
     *   - <status>true</status>
     *   - <public_key>mobile public key</public_key>
     *   - <private_key>mobile private key</private_key>
     * - </validate>
     *
     * - on success, if json was chosen:
     *   - {"validate":
     *     - {
     *       - "status":true
     *       - "public_key":mobile public key
     *       - "private_key":mobile private key
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <validate>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </validate>
     *
     * - on failure, if json was chosen:
     *   - {"validate":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function userValidate($p_sUserMail, $p_sPassword, $p_sType, $p_sMode, $p_bSecure) {
        $sUrl = "uservalidate/$p_sUserMail/$p_sPassword/$p_sType";
        $sMethod = "get";
        $sResult = $this->startConnection( $sUrl, $p_sMode, $p_bSecure, $sMethod);
        return $sResult;
    }
    
    /**
     * This function gets a list of events of the company between two dates (limit to max 30 days)
     * If no agenda ID is given, it will get the events of all the agendas. If an agenda ID is supplied, it will only get the events for that agenda.
     * For the second option it will be ordered by the id of the agenda.
     * If you want to get the events of a user you must use method "getEventsOfUser" instead.
     *
     * @param p_sPublicKey Your public key
     * @param p_sStartDate the start day to get the events
     * @param p_sEndDate the end day to get the events
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     * @param p_sAgendaID (optional) ID of the agenda. If no ID is given, you will get all the events of your company.
     *
     * @return
     * -on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <events>
     *       - <status>true</status>
     *       - <i>one or more</i> <events>
     *         - <id>id of the event (format "bktXXXXXXX")</id>
     *         - <title>title of the event, if exists. empty otherwise</title>
     *         - <description>description of the event, if exists. empty otherwise</description>
     *         - <comments>comments for the event, if exists. empty otherwise</comments>
     *         - <synchro_id>id of the event if it is shared with other software, empty otherwise</synchro_id>
     *         - <startdate>the start date of the event</startdate>
     *         - <enddate>the end time of the event</enddate>    
     *         - <starttime>the starttime of the event (format HH:MM)</starttime>
     *         - <endtime>the endtime of the event (format HH:MM)</endtime>     
     *         - <coupon_code>the coupon code of the offer, if exists. empty otherwise</coupon_code>     
     *         - <agenda_id>id of the agenda (format "bktXXXXX")</agenda_id>
     *         - <user_id>id of the user/client (format "bktXXXXX"), if exists. empty otherwise</user_id>
     *         - <user_name>name of the user/client, if exists. empty otherwise</user_name>
     *         - <user_cellphone>cellphone of the user/client, if exists. empty otherwise</user_cellphone>
     *         - <user_email>email of the user/client, if exists. empty otherwise</user_email>     
     *         - <service_id>id service of the event (format "bktXXXXX"), if exists. empty otherwise</services_id>     
     *         - <locator>locator of the event, if exists. empty otherwise</locator>
     *         - <updated>last update of the event</updated>
     *         - <customvalidate1>user custom validate field 1, if exists. empty otherwise</customvalidate1>
     *         - <customvalidateX>user custom validate field X, if exists. empty otherwise</customvalidateX>
     *         - <custom1>user custom field 1, if exists. empty otherwise</custom1>
     *         - <custom2>user custom field 2, if exists. empty otherwise</custom2>
     *         - <customX>user custom field X, if exists. empty otherwise</customX>
     *         - <customevent1>event custom field 1, if exists. empty otherwise</customevent1>
     *         - <customevent2>event custom field 2, if exists. empty otherwise</customevent2>
     *         - <customeventX>event custom field X, if exists. empty otherwise</customeventX>
     *       - </event>
     *     - </events>
     *
     * - on success, if json was chosen:
     *   - {"events":
     *     - {
     *       - "status":false,
     *       - <i>one or more</i> "events":
     *         - {
     *           - id: id of the event (format "bktXXXXXXX")
     *           - title: title of the event, if exists. empty otherwise
     *           - description: description of the event, if exists. empty otherwise
     *           - comments: comments for the event, if exists. empty otherwise
     *           - synchro_id: id of the event if it is shared with other software, empty otherwise
     *           - agenda_synchro_id: id of the agenda synchro if it is shared with other software, empty otherwise
     *           - startdate: the start date of the event
     *           - enddate: the end time of the event
     *           - starttime: the starttime of the event (format HH:MM)
     *           - endtime: the endtime of the event (format HH:MM)
     *           - coupon_code: the coupon code of the offer, if exists. empty otherwise
     *           - agenda_id: id of the agenda (format "bktXXXXX")
     *           - user_id: id of the user/client (format "bktXXXXX"), if exists. empty otherwise
     *           - user_name: name of the user/client, if exists. empty otherwise
     *           - user_cellphone: cellphone of the user/client, if exists. empty otherwise
     *           - user_email: email of the user/client, if exists. empty otherwise
     *           - service_id: id service of the event (format "bktXXXXX"), if exists. empty otherwise
     *           - locator: locator of the event, if exists. empty otherwise
     *           - updated: last update of the event
     *           - "customvalidate1": user custom validate field 1, if exists. empty otherwise
     *           - "customvalidateX": user custom validate field X, if exists. empty otherwise
     *           - "custom1": user custom field 1, if exists. empty otherwise
     *           - "custom2": user custom field 2, if exists. empty otherwise
     *           - "customX": user custom field X, if exists. empty otherwise
     *           - "customevent1": event custom field 1, if exists. empty otherwise
     *           - "customevent2": event custom field 2, if exists. empty otherwise
     *           - "customeventX": event custom field X, if exists. empty otherwise
     *         - }
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <events>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </events>
     *
     * - on failure, if json was chosen:
     *   - {"events":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function getEvents($p_sDateFrom,
     $p_sDateTo,
     $p_sMode,
     $p_bSecure,
     $p_sAgendaID = null){
        $sUrl = "getevents/$this->_sPublicKey/$p_sDateFrom/$p_sDateTo";
        
        if($p_sAgendaID != null){
            $sUrl .= "/$p_sAgendaID";
        }
        
        $sMethod = "get";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod);
        
        return $sResult;
    }
    
    /**
     * @Deprecated @see getClientEvents
     * This function gets a list of user events between two dates.
     * If no agenda ID is given, it will get the events of all the agendas. If an agenda ID is supplied, it will only get the events for that agenda.
     * For the second option it will be ordered by the id of the agenda.
     * If you want to get the events of a user you must use method "getEventsOfUser" instead.
     *
     * @param p_sEmail the user email
     * @param p_sStartDate the start day to get the events
     * @param p_sEndDate the end day to get the events
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     * 
     *
     * @return
     * -on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <events>
     *       - <status>true</status>
     *       - <i>one or more</i> <events>
     *         - <id>id of the event (format "bktXXXXXXX")</id>
     *         - <title>title of the event</name>
     *         - <description>description of the event</description>
     *         - <comments>comments of the event</comments>
     *         - <synchro_id>id of the event if it is shared with other software, NULL otherwise</synchro_id>
     *         - <startdate>start date of the event (format YYYY-MM-DD)</startdate>
     *         - <enddate>end date of the event</enddate>    
     *         - <starttime>start date of the event (format HH:MM)</starttime>
     *         - <endtime>end date of the event</endtime>     
     *         - <coupon_code>the coupon code</coupon_code>     
     *         - <agenda_id>id of the agenda (format "bktXXXXX")</agenda_id>
     *         - <user_id>id of the user/client for the event(format "bktXXXXX")</user_id>
     *         - <user_name>name of the user/client</user_name>
     *         - <user_cellphone>phone of the user/client</user_cellphone>
     *         - <user_email>email of the user/client</user_email>     
     *         - <service_id>id of the service</client</services_id>     
     *       - </event>
     *     - </events>
     *
     * - on success, if json was chosen:
     *   - {"events":
     *     - {
     *       - "status":false,
     *       - <i>one or more</i> "events":
     *         - {
     *           - id:id of the event (format "bktXXXXXXX"),
     *           - title: title of the event,
     *           - description: description of the event, if exists. NULL otherwise
     *           - comments: comments for the event, if exists. NULL otherwise
     *           - synchro_id:id of the event if it is shared with other software, NULL otherwise
     *           - startdate:the start date of the event
     *           - enddate:the end time of the event
     *           - starttime:the starttime of the event
     *           - endtime:the endtime of the event
     *           - coupon_code:the coupon code of the offer, if exists. NULL otherwise.
     *           - agenda_id:if of the agenda(format "bktXXXXX")
     *           - user_id:id of the user/client(format "bktXXXXX")
     *           - user_name:name of the user/client
     *           - user_cellphone:cellphone of the user/client
     *           - user_email:email of the user/client
     *           - service_id: service of the event
     *         - }
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <agendas>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </agendas>
     *
     * - on failure, if json was chosen:
     *   - {"agendas":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function getUserEvents($p_sUserId, $p_sDateFrom, $p_sDateTo, $p_sMode, $p_bSecure) {
        $sUrl = "getuserevents/$this->_sPublicKey/$p_sUserId/$p_sDateFrom/$p_sDateTo";        
        $sMethod = "get";
        $sResult = $this->startConnection( $sUrl, $p_sMode, $p_bSecure, $sMethod);
        return $sResult;
    }
    
    /**
     * This function gets the data for all clients of your company. (limit to max 30 days)
     *
     * 
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     * @param p_sStartDate the start day to get the clients (when the client was registered)
     * @param p_sEndDate the end day to get the clients (when the client was registered)
     *
     * @return
     * - on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <clients>
     *       - <status>true</status>
     *       - one or more <client>
     *         - <id>id of the client (format bktXXXXXXXXX)</id>
     *         - <user_id>id of the user (format bktXXXXXXXXX). If this parameter is void the client will be without web access, otherwise it will be a client with web access</user_id>
     *         - <name>name of the user</name>
     *         - <email>email of the user</email>
     *         - <phone>phone of the user</phone>
     *         - <document>document of the user</document>
     *         - <cellphone>cellphone of the user</cellphone>
     *         - <customvalidate1>user custom validate field 1</customvalidate1>
     *         - <customvalidateX>user custom validate field X</customvalidateX>
     *         - <custom1>user custom field 1</custom1>
     *         - <custom2>user custom field 2</custom2>
     *         - <customX>user custom field X</customX>
     *       - </client>
     *     - </clients>
     *
     * - on success, if json was chosen:
     *   - {"clients":
     *     - one or more {"client":
     *       - {
     *         - "status":true,
     *         - "id":id of the user (format bktXXXXXXXXX),
     *         - "user_id":id of the user (format bktXXXXXXXXX). If this parameter is void the client will be without web access, otherwise it will be a client with web access,
     *         - "name":name of the user,
     *         - "email":email of the user,
     *         - "phone":phone of the user,
     *         - "document":document of the user,
     *         - "cellphone":cellphone of the user,
     *         - "customvalidate1":user custom validate field 1,
     *         - "customvalidateX":user custom validate field X,
     *         - "custom1":user custom field 1,
     *         - "custom2":user custom field 2,
     *         - "customX":user custom field X
     *       - }
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <clients>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </clients>
     *
     * - on failure, if json was chosen:
     *   - {"clients":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     */
    public function getClients($p_sMode, $p_bSecure, $p_sDateFrom, $p_sDateTo){
        $sUrl = "getclients/$this->_sPublicKey/$p_sDateFrom/$p_sDateTo";
        $sMethod = "get";
        $sResult = $this->startConnection( $sUrl, $p_sMode, $p_bSecure, $sMethod);
        return $sResult;
    }
    
      /**
     * This function gets a client of one event. If you have an event and you want to know who went to the appointment, you must use this function.     
     *
     * @param $p_sEventID the identifier of one event (format bktXXXXX)     
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     *
     * @return
     * -on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <clients>     
     *         - <status>true</status>     
     *         - <i>one or more</i> "client":
     *         - <client>
     *          - <name>the name of the client</name>
     *          - <email>the email of the client</email>
     *          - <address>the address of the client</address> 
     *          - <document>the document of the client</document>
     *          - <cellphone>the cellphone of the client</cellphone>
     *          - <phone>the phone of the client</phone>
     *          - <custom1>the first custom field of the client</custom1>
     *          - <customn>the N custom field of the client</customn>
     *          - <id>The id of the client</id>
     *          - <event_id>The id of the event</event_id>
     *          - <agendas_id>The id of the agenda where the event was created</agendas_id>
     *          - <start_date>The start date of the event</start_date>
     *          - <end_date>The end date of the event</end_date>
     *          - <start_time>660</start_time>
     *          - <end_time>720</end_time>
     *          - <title>The title of the event</title>
     *          - <description>The description of the event</description>
     *          - <coupons_code>The code of the coupon for the event</coupons_code>
     *          - <coupon_id>The id of the coupon related to the event</coupon_id>
     *          - <comments>The comments of the event</comments>
     *          - <synchro_id>the id of the event of your database</synchro_id>
     *          - <people>The people that went to the event</people>
     *          - <showup>The client went to the appointment, yes or not</showup>
     *          - <locator>The locator of the event</locator>
     *          - <price_total>The total price of the event</price_total>
     *          - <price_paid>The event price</price_paid>
     *          - <agenda_name>The name of the agenda selected for the event</agenda_name>
     *          - <customevent1>The first custom field of the event</customevent1>
     *          - <customeventN>The N custom field of the event</customeventN>
     *         - </client>
     *     - </clients>
     *
     * 
     * 
     * - on success, if json was chosen:
     *      -{"clients":
     *          -{"0":
     *              -{
     *                 "name":"the name of the client",
     *                 "email":"the email of the client",
     *                 "address":"the address of the client",
     *                 "document":"the document of the client",     
     *                 "cellphone":"the cellphone of the client",
     *                 "phone":"the phone of the client",     
     *                 "custom1":"the first custom field of the client",
     *                 "customN": "the N custom field of the client",
     *                 "id":"The id of the client",
     *                 "event_id":"The id of the event",
     *                 "agendas_id":"The id of the agenda where the event was created",
     *                 "services_id":"The id of the selected service for the event",
     *                 "start_date":"The start date of the event",
     *                 "end_date":"The end date of the event",
     *                 "start_time":"The start time of the event",
     *                 "end_time":"The end time of the event",
     *                 "title":"The title of the event",
     *                 "description":"The description of the event",
     *                 "coupons_code": "The code of the coupon for the event",
     *                 "coupon_id":"The id of the coupon",
     *                 "comments":"The comments inside the event",
     *                 "synchro_id":"the id of the event of your database",
     *                 "people":"the number of people that went to the event",
     *                 "showup":"The client went to the appointment, yes or not",
     *                 "locator":"The locator of the event",
     *                 "price_total":"The total price of the event",
     *                 "price_paid":"The event price",
     *                 "agenda_name":"The name of the agenda selected for the event",
     *                 "customevent1":"The first custom field of the event",
     *                 "customeventN":"The N custom field of the event",
     *              },
     *          -"status":"true"}}
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <clients>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </clients>
     *
     * - on failure, if json was chosen:
     *   - {"clients":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function getClientsByEvent($p_sIdEvent, $p_sMode, $p_bSecure) {
        $sUrl = "getclientsbyeventid/$this->_sPublicKey/$p_sIdEvent";        
        $sMethod = "get";
        $sResult = $this->startConnection( $sUrl, $p_sMode, $p_bSecure, $sMethod);
        return $sResult;
    }
    
    
    /**
    * This function gets one client from its client id. The format is bktXXXXX
    * 
    * @param $p_sIdClient the id of the client in bktXXXXXXXXX format
    * @param $p_sMode xml or json
    * @param $p_bSecure true for https, false for http
    *
    * @return
    * - on success, if xml was chosen:
    *   - <?xml version='1.0' encoding='utf-8'?>
          - <client>
    *          - <email>The email of the client</email>
    *          - <address>The address of the client</address>
    *          - <cellphone>The cellphone of the client</cellphone>
    *          - <phone>The phone of the client</phone>
    *          - <document>The document of the client</document>
    *          - <name>The name of the client</name>
    *          - <custom1>The first custom field</custom1>
    *          - <custom2>The second custom field</custom2>
    *          - <customN>The N custom field</customN>
    *          - <status>if success "true" otherwise "false"</status>
    *     - </client>
    *
    * - on success, if json was chosen:
    *    {"client":
    *      {"email":"The email of the client",
    *       "address":"The address of the client",
    *       "cellphone":"The cellphone of the client",
    *       "phone":"The phone of the client",
    *       "document":"The document of the client",
    *       "name":"Raulope",
    *       "custom1":"The first custom field",
    *       "custom2":"The second custom field",
    *       "customN":"The N custom field",
    *       "status":"true"}}
    *
    * - on failure, if xml was chosen:
    *   - <?xml version='1.0' encoding='utf-8'?>
    *     - <client>
    *       - <status>false</status>
    *       - <id>error id</id>
    *       - <message>error message</message>
    *     - </client>
    *
    * - on failure, if json was chosen:
    *   - {"client":
    *     - {
    *       - "status":false,
    *       - "id":error id,
    *       - "message":error message
    *     - }
    *   - }
    */
    public function getClient($p_sIdClient, $p_sMode, $p_bSecure) {
        $sUrl = "getclient/$this->_sPublicKey/$p_sIdClient";        
        $sMethod = "get";
        $sResult = $this->startConnection( $sUrl, $p_sMode, $p_bSecure, $sMethod);
        return $sResult;
    }
    
    /**
     * This function gets the agenda-service assignments for your company.
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     * 
     * @return
     * -on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <agenda_services>
     *       - <status>true</status>
     *       - <i>one or more</i> <agenda_service>
     *         - <agendas_id>id of the agenda (format "bktXXXXXXX")</agendas_id>
     *         - <services_id>id of the service (format "bktXXXXXXX")</services_id>
     *         - <agendas_synchro_id>id of the agenda in your software (in case of software integration)</agendas_synchro_id>
     *         - <services_synchro_id>id of the service in your software (in case of software integration)</services_synchro_id>
     *         - <duration>duration of the service (specific for this agenda)</duration>
     *         - <price>price of the service (specific for this agenda)</price>
     *       - </agenda_service>
     *     - </agenda_services>
     *
     * - on success, if json was chosen:
     *   - {"agenda_services":
     *     - {
     *       - "status":true,
     *       - <i>one or more</i> "agenda_service":
     *         - {
     *           - agendas_id: id of the agenda (format "bktXXXXXXX"),
     *           - services_id: id of the service (format "bktXXXXXXX"),
     *           - agendas_synchro_id: id of the agenda in your software (in case of software integration),
     *           - services_synchro_id: id of the service in your software (in case of software integration),
     *           - duration: duration of the service (specific for this agenda),
     *           - price: price of the service (specific for this agenda),
     *         - }
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <agenda_services>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </agenda_services>
     *
     * - on failure, if json was chosen:
     *   - {"agenda_services":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */    
    public function getAgendasServices($p_sMode, $p_bSecure) {
        $sUrl = "getagendasservices/$this->_sPublicKey";
        $sMethod = "get";
        $sResult = $this->startConnection( $sUrl, $p_sMode, $p_bSecure, $sMethod);
        return $sResult;
    }
         
    /**
     * This function gets the languages of the Bookitit city database
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
      * 
     * @return
     * -on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <languages>
     *       - <status>true</status>
     *       - <i>one or more</i> <language>
     *         - <id>id of the language (format "bktXXXXXXX")</id>
     *         - <code>international code of the language</code>
     *       - </language>
     *     - </languages>
     *
     * - on success, if json was chosen:
     *   - {"languages":
     *     - {
     *       - "status":false,
     *       - <i>one or more</i> "language":
     *         - {
     *           - id:id of the language (format "bktXXXXXXX"),
     *           - code: international code of the language,
     *         - }
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <languages>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </languages>
     *
     * - on failure, if json was chosen:
     *   - {"languages":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */    
    public function getLanguages($p_sMode, $p_bSecure) {
        $sUrl = "getlanguages/$this->_sPublicKey";
        $sMethod = "get";
        $sResult = $this->startConnection( $sUrl, $p_sMode, $p_bSecure, $sMethod);
        return $sResult;
    }
    
    /**
     * This function gets the countries of the Bookitit city database, in a given language
     
     * @param p_sLanguageID ID of the language (format bktXXXXXX)
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     * @return
     * -on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <countries>
     *       - <status>true</status>
     *       - <i>one or more</i> <countrie>
     *         - <id>id of the country (format "bktXXXXXXX")</id>
     *         - <name>name of the country in the choosed language</name>
     *         - <longitude>longitude of the country's center</longitude>
     *         - <latitude>latitude of the country's center</latitude>
     *         - <phone_code>phone code of the country</phone_code>
     *       - </countrie>
     *     - </countries>
     *
     * - on success, if json was chosen:
     *   - {"countries":
     *     - {
     *       - "status":false,
     *       - <i>one or more</i> "countrie":
     *         - {
     *           - id:id of the country (format "bktXXXXXXX"),
     *           - name: name of the country,
     *           - longitude: longitude of the country's center,
     *           - latitude: latitude of the country's center,
     *           - phone_code: phone code of the country,
     *         - }
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <countries>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </countries>
     *
     * - on failure, if json was chosen:
     *   - {"countries":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function getCountries($p_sLanguageID, $p_sMode, $p_bSecure) {
        $sUrl = "getcountries/$this->_sPublicKey/$p_sLanguageID";
        $sMethod = "get";
        $sResult = $this->startConnection( $sUrl, $p_sMode, $p_bSecure, $sMethod);
        return $sResult;
    }
    
    /**
     * This function gets the regions of a given country, in a given language, from the Bookitit city database
     *
     * @param p_sLanguageID ID of the chosen language (format bktXXXXXX)
     * @param p_sCountryID ID of the chosen country (format bktXXXXXX)
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     * @return
     * -on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <regions>
     *       - <status>true</status>
     *       - <i>one or more</i> <region>
     *         - <id>id of the region (format "bktXXXXXXX")</id>
     *         - <name>name of the region in the chosen language</name>
     *         - <longitude>longitude of the region's center</longitude>
     *         - <latitude>latitude of the region's center</latitude>
     *         - <gmt>time from GMT</gmt>
     *       - </region>
     *     - </regions>
     *
     * - on success, if json was chosen:
     *   - {"regions":
     *     - {
     *       - "status":false,
     *       - <i>one or more</i> "region":
     *         - {
     *           - id:id of the region (format "bktXXXXXXX"),
     *           - name: name of the region in the chosen language,
     *           - longitude: longitude of the region's center,
     *           - latitude: latitude of the region's center,
     *           - gmt: time from GMT,
     *         - }
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <regions>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </regions>
     *
     * - on failure, if json was chosen:
     *   - {"regions":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */    
    public function getRegions($p_sLanguageID, $p_sCountryID, $p_sMode, $p_bSecure) {
        $sUrl = "getregions/$this->_sPublicKey/$p_sLanguageID/$p_sCountryID";
        $sMethod = "get";
        $sResult = $this->startConnection( $sUrl, $p_sMode, $p_bSecure, $sMethod);
        return $sResult;
    }
    
    /**
     * This function gets the cities of a given region, in a given language, from the Bookitit city database
     *
     * @param p_sLanguageID ID of the chosen language (format bktXXXXXX)
     * @param p_sRegionID ID of the chosen region (format bktXXXXXX)
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *
     * @return
     * -on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <cities>
     *       - <status>true</status>
     *       - <i>one or more</i> <citie>
     *         - <id>id of the city (format "bktXXXXXXX")</id>
     *         - <name>name of the city in the chosen language</name>
     *         - <longitude>longitude of the city's center</longitude>
     *         - <latitude>latitude of the city's center</latitude>
     *       - </citie>
     *     - </cities>
     *
     * - on success, if json was chosen:
     *   - {"cities":
     *     - {
     *       - "status":false,
     *       - <i>one or more</i> "citie":
     *         - {
     *           - id:id of the city (format "bktXXXXXXX"),
     *           - name: name of the city in the chosen language,
     *           - longitude: longitude of the city's center,
     *           - latitude: latitude of the city's center,
     *         - }
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <cities>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </cities>
     *
     * - on failure, if json was chosen:
     *   - {"cities":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */    
    public function getCities($p_sLanguageID, $p_sRegionID, $p_sMode, $p_bSecure) {
        $sUrl = "getcities/$this->_sPublicKey/$p_sLanguageID/$p_sRegionID";
        $sMethod = "get";
        $sResult = $this->startConnection( $sUrl, $p_sMode, $p_bSecure, $sMethod);
        return $sResult;
    }
    
    /**
     * This function gets the orders of your company
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
      * 
     * @return
     * -on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <orders>
     *       - <status>true</status>
     *       - <i>one or more</i> <order>
     *         - <id>id of the order (format "bktXXXXXXX")</id>
     *         - <created>creation date of the order</created>
     *         - <updated>modification date of the order</updated>
     *         - <state>state of the event</state>
     *         - <events_id>id of the event associated to the order (format "bktXXXXXXX")</events_id>
     *         - <clients_id>id of the client associated to the order (format "bktXXXXXXX")</clients_id>
     *         - <transaction_id>id of the economic transaction in the payment platform</transaction_id>
     *       - </order>
     *     - </orders>
     *
     * - on success, if json was chosen:
     *   - {"orders":
     *     - {
     *       - "status":false,
     *       - <i>one or more</i> "order":
     *         - {
     *           - id: id of the order (format "bktXXXXXXX"),
     *           - created: creation date of the order,
     *           - updated: modification date of the order,
     *           - state: state of the event,
     *           - events_id: id of the event associated to the order (format "bktXXXXXXX"),
     *           - clients_id: id of the client associated to the order (format "bktXXXXXXX"),
     *           - transaction_id: id of the economic transaction in the payment platform,
     *         - }
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <orders>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </languages>
     *
     * - on failure, if json was chosen:
     *   - {"languages":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */    
    public function getOrders($p_sMode, $p_bSecure) {
        $sUrl = "getorders/$this->_sPublicKey";
        $sMethod = "get";
        $sResult = $this->startConnection( $sUrl, $p_sMode, $p_bSecure, $sMethod);
        return $sResult;
    }
    
    /**
     * This function validates a professional user against bookitit through API. 
     * It could be used if we want to access from another web application or dashboard backoffice to your bookitit account without user and password. 
     * The function will return a dynamic url that will allow the access temporally. When you get this url, you will have to redirect the user from your server side to this url.
     * 
     * @param $p_sUserEmail the email of the user account to access
     * @param $p_sAccountType possible values "single" or "multicenter". It will log into a single account or multicenter. In this version only allowed single.
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     * 
     * @return String dynamic url that will allow the access temporally.
     
     * on success, if xml was chosen:
     * - <auth>
     *   - <status>true</status>
     *   - <authenticated_url>the url</authenticated_url>
     * - </auth>
     *
     * - on success, if json was chosen:
     *   - {"auth":
     *     - {
     *       - "status":true
     *       - "authenticated_url": the url where the client software will redirect     
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <auth>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </auth>
     *
     * - on failure, if json was chosen:
     *   - {"auth":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function auth($p_sUserEmail, $p_sAccountType, $p_sMode, $p_bSecure) {
        $sUrl = "auth/$this->_sPublicKey/$p_sAccountType";
        $sMethod = "post";        
        $somePostParams = array("p_sUserEmail"=> $p_sUserEmail);
        $sResult = $this->startConnection( $sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function is used to get the companies of one multicenter account.     
     * 
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     *   
     * @return 
     * - on success, if xml was chosen:
            - <companies>
     *       - <status>true</status>
     *       - <i>one or more</i> <companie>
     *         - <id>id of the company (format "bktXXXXXXX")</id>
     *         - <name>name of the company</name>    
     *         - <email>email of the company</email>
     *         - <expiration_date>expiration date of the account</expiration_date>     
     *       - </companie>
     *     - </company>
     *
     * - on success, if json was chosen:
     *   - {"companies":
     *     - {
     *       - "status":false,
     *       - <i>one or more</i> "companie":
     *         - {
     *           - id:id of the companie (format "bktXXXXXXX"),
     *           - name: name of the companie,     
     *           - email: email of the companie
     *           - expiration_date: expiration date of the account
     *         - }
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <auth>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </auth>
     *
     * - on failure, if json was chosen:
     *   - {"auth":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     *
     */
    public function getCompanies($p_sMode, $p_bSecure) {
        $sUrl = "getcompanies/$this->_sPublicKey";
        $sMethod = "get";                
        $sResult = $this->startConnection( $sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
     * This function add an array of holidays to Bookitit.
     * @param type $p_someHolidays the array of holidays will have this format: myarray["2012-01-01"] = "title 1";myarray["2012-01-02"] = "title 2"; ...
     * @param type $p_sMode xml or json
     * @param type $p_bSecure true or false (https or http)
     * @return String with json or xml format
     */
    public function addHolidays($p_someHolidays, $p_sMode, $p_bSecure){
        $somePostParams = array();
        foreach($p_someHolidays as $sKey=>$sValue){
            $somePostParams["p_someHolidays[".$sKey."]"]=$sValue;
        }
        $sUrl = "addholidays/$this->_sPublicKey";
        $sMethod="post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        return $sResult;
    }
    
    /**
         * This function is used to check if company email already exist in Bookitit.
         * Only administrator users, who have an administrator api key, can check this.
         *
         *
         *
         * @param $p_sEmail Email of the company
         *
         * @return
         * - on success, if xml was chosen:
         *   - <?xml version='1.0' encoding='utf-8'?>
         *     - <company>
         *       - <status>true</status>
         *     - </company>
         *
         * - on success, if json was chosen:
         *   - {"company":
         *     - {
         *       - "status":true,
         *       - "id":id of the created company (format bkt XXXXXXXXX),
         *     - }
         *   - }
         *
         * - on failure, if xml was chosen:
         *   - <?xml version='1.0' encoding='utf-8'?>
         *     - <company>
         *       - <status>false</status>
         *       - <id>error id</id>
         *       - <message>error message</message>
         *     - </company>
         *
         * - on failure, if json was chosen:
         *   - {"company":
         *     - {
         *       - "status":false,
         *       - "id":error id,
         *       - "message":error message
         *     - }
         *   - }
         *
         */
    public function checkCompanyEmail($p_sEmail,  $p_sMode, $p_bSecure) {            
            $sUrl = "checkifcompanyemailexist/$this->_sPublicKey/$p_sEmail";
            $sMethod = "get";
            $sResult = $this->startConnection( $sUrl, $p_sMode, $p_bSecure, $sMethod);
            return $sResult;
        }
        
    /**
         * this function deletes the schedule OF ONE DAY of a company or agenda         
         * @param <string> $p_sMode xml or json
         * @param <boolean> $p_bSecure true or false
         * @param <string> $p_sAgendaID Agenda's id if known(optional),
         * @return <type>
         */
    public function deleteCompanySchedule($p_sMode,$p_bSecure, $p_sAgendaID=null){                       
            $somePostParams = array("p_sAgendaID"=>$p_sAgendaID);
            $sUrl = "deletecompanyschedule/$this->_sPublicKey";
            $sMethod="post";
            $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
            return $sResult;
         }
         
    /**
         * This function sets the cellphone of one of your company's users (if a user's ID is given) or the cellphone of your company (if no ID is given).
         *
         * Since cellphone numbers have to be always confirmed, the user or company will be set
         * as INACTIVE, and a new confirmation code will be returned by the function
         * and sent to the user's or company's cellphone.
         * Please store that code for later verification of the phone.
         *
         * @param $p_sPublicKey Your public key
         * @param $p_sEmail new phone number
         * @param $p_sUserId optional, id of your user (format bktXXXXXXXXX)
         *
         * @return
         * - on success, if xml was chosen:
         *   - <?xml version='1.0' encoding='utf-8'?>
         *     - <phone>
         *       - <status>true</status>
         *       - <validatephonekey>the new activation code</validatephonekey>
         *     - </phone>
         *
         * - on success, if json was chosen:
         *   - {"phone":
         *     - {
         *       - "status":true,
         *       - "validatephonekey":the new activation code,
         *     - }
         *   - }
         *
         * - on failure, if xml was chosen:
         *   - <?xml version='1.0' encoding='utf-8'?>
         *     - <phone>
         *       - <status>false</status>
         *       - <id>error id</id>
         *       - <message>error message</message>
         *     - </phone>
         *
         * - on failure, if json was chosen:
         *   - {"phone":
         *     - {
         *       - "status":false,
         *       - "id":error id,
         *       - "message":error message
         *     - }
         *   - }
         *
         */
    public function setEmailAndSendValidate($p_sEmail,$p_sUserId, $p_sMode, $p_bSecure) {                       
            $somePostParams = array("p_sEmail"=>$p_sEmail, "p_sUserID"=>$p_sUserId);
            $sUrl = "setemailandsendvalidate/$this->_sPublicKey";
            $sMethod="post";
            $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
            return $sResult;
        }

    /**
     * This function gets an event by id, locator or both
     *
     * @param p_sPublicKey Your public key
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     * @param $p_iEventId the event id (optional)
     * @param $p_sLocator the event locator (optional)
     *
     * @return
     * -on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <event>
     *         - <status>true</status>     
     *         - <id>id of the event (format "bktXXXXXXX")</id>
     *         - <title>title of the event service, if exists. empty otherwise</title>
     *         - <description>description of the event, if exists. empty otherwise</description>
     *         - <comments>comments of the event, if exists. empty otherwise</comments>
     *         - <synchro_id>id of the event if it is shared with other software, if exists. empty otherwise</synchro_id>
     *         - <startdate>start date of the event (format YYYY-MM-DD)</startdate>
     *         - <enddate>end date of the event</enddate>    
     *         - <starttime>start date of the event (format HH:MM)</starttime>
     *         - <endtime>end date of the event</endtime>     
     *         - <updated>the date when the event was updated, if exists. empty otherwise</updated>
     *         - <coupon>the event has associated a coupon, if exists. empty otherwise</coupon>
     *         - <agenda_id>the agenda where the event was created (format "bktXXXXXXX")</agenda_id>
     *         - <service_id>the service linked to the event, if exists. empty otherwise (format "bktXXXXXXX")</service_id>
     *         - <people> the number of people that will go to the event, if exists. empty otherwise</people>
     *         - <locator>the locator of the event/appointment, if exists. empty otherwise. Each event has one locator</locator>     
     *         - <customevent1>event custom field 1, if exists. empty otherwise</customevent1>
     *         - <customevent2>event custom field 2, if exists. empty otherwise</customevent2>
     *         - <customeventX>event custom field X, if exists. empty otherwise</customeventX>
     *     - </event>
     *
     * - on success, if json was chosen:
     *   - {"event":
     *     - {
     *         - "status":false,     
     *         - id: id of the event (format "bktXXXXXXX")
     *         - title: title of the event service, if exists. empty otherwise
     *         - description: description of the event, if exists. empty otherwise
     *         - comments: comments of the event, if exists. empty otherwise
     *         - synchro_id: id of the event if it is shared with other software, if exists. empty otherwise
     *         - startdate: start date of the event (format YYYY-MM-DD)
     *         - enddate: end date of the event
     *         - starttime: start date of the event (format HH:MM)
     *         - endtime: end date of the event
     *         - updated: the date when the event was updated, if exists. empty otherwise
     *         - coupon: the event has associated a coupon, if exists. empty otherwise
     *         - agenda_id: the agenda where the event was created (format "bktXXXXXXX")
     *         - service_id: the service linked to the event, if exists. empty otherwise (format "bktXXXXXXX")
     *         - people: the number of people that will go to the event, if exists. empty otherwise
     *         - locator: the locator of the event/appointment, if exists. empty otherwise. Each event has one locator
     *         - customevent1: event custom field 1, if exists. empty otherwise
     *         - customevent2: event custom field 2, if exists. empty otherwise
     *         - customeventX: event custom field X, if exists. empty otherwise
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <event>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </event>
     *
     * - on failure, if json was chosen:
     *   - {"event":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function getEvent($p_sMode, $p_bSecure, $p_iEventId = null, $p_sLocator = null){
        $sUrl = "geteventbyid/$this->_sPublicKey";
        
        if($p_iEventId != null){
            $sUrl .= "/$p_iEventId";
        }
        
        if($p_sLocator != null){
            $sUrl .= "/$p_sLocator";
        }
        
        $sMethod = "get";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod);
        
        return $sResult;
    }
 
    /**
     * This function gets a list of events of a client between two dates or all events. 
     * URL to call this function: http://app.bookitit.com/api/getclientevents/<i>$p_sPublicKey</i>/<i>$p_iClientId</i>/<i>$p_sDateFrom</i>/<i>$p_sDateTo</i>
     *
     * @param $p_sPublicKey Your public key
     * @param $p_iClientId the id of the client with format bktXXXXX
     * @param $p_sMode xml or json
     * @param $p_bSecure true for https, false for http
     * @param $p_sDateFrom the start day to get the events
     * @param $p_sDateTo the end day to get the events.
     *
     * @return
     * -on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <events>
     *       - <status>true</status>
     *       - <i>one or more</i> 
     *       - <event>
     *         - <id>id of the event (format "bktXXXXXXX")</id>
     *         - <startdate>start date of the event (format YYYY-MM-DD)</startdate>
     *         - <enddate>end date of the event</enddate>
     *         - <starttime>start date of the event (format HH:MM)</starttime>
     *         - <endtime>end date of the event (format HH:MM)</endtime>
     *         - <agenda_id>id of the agenda (format "bktXXXXX")</agenda_id>
     *         - <created>create date of the event</created>
     *         - <updated>last update of the event</updated>
     *         - <agenda_name>name of agenda</agenda_name>
     *         - <client_id>id of the client for the event (format "bktXXXXX")</client_id>
     *         - <client_name>name of the client, if exists. empty otherwise</client_name>
     *         - <client_cellphone>phone of the client, if exists. empty otherwise</client_cellphone>
     *         - <client_email>email of the client, if exists. empty otherwise</client_email>
     *         - <created_from>where event was create</created_from>
     *         - <created_by>who created the event</created_by>
     *         - <confirmed>event confirmation</confirmed>
     *         - <showup>the client attended the appointment</showup>
     *         - <locator>event locator</locator>
     *         - <description>description of the event, if exists. empty otherwise</description>
     *         - <comments>comments of the event, if exists. empty otherwise</comments>
     *         - <people>number of people, if exists. empty otherwise</people>
     *         - <price_total>total price, if exists. empty otherwise</price_total>
     *         - <price_paid>paid price, if exists. empty otherwise</price_paid>
     *         - <coupons_code>coupons codes, if exists. empty otherwise</coupons_code>
     *         - <service_id>id of the service (format "bktXXXXX"), if exists. empty otherwise</service_id>
     *         - <title>title of the event, if exists. empty otherwise</title>
     *         - <agenda_synchro_id>id of the agenda if it is shared with other software, if exists. empty otherwise</agenda_synchro_id>
     *         - <synchro_id>id of the event if it is shared with other software, if exists. empty otherwise</synchro_id>
     *         - <customvalidate1>client custom validate field 1, if exists. empty otherwise</customvalidate1>
     *         - <customvalidateX>client custom validate field X, if exists. empty otherwise</customvalidateX>
     *         - <custom1>client custom field 1, if exists. empty otherwise</custom1>
     *         - <custom2>client custom field 2, if exists. empty otherwise</custom2>
     *         - <customX>client custom field X, if exists. empty otherwise</customX>
     *         - <customevent1>client custom field 1, if exists. empty otherwise</customevent1>
     *         - <customevent2>client custom field 2, if exists. empty otherwise</customevent2>
     *         - <customeventX>client custom field X, if exists. empty otherwise</customeventX>
     *       - </event>
     *     - </events>
     * 
     * - on success, if json was chosen:
     *   - {"events":
     *     - {
     *       - "status":false,
     *       - <i>one or more</i> "events":
     *         - {
     *           - id: id of the event (format "bktXXXXXXX")
     *           - startdate: start date of the event (format YYYY-MM-DD)
     *           - enddate: end date of the event
     *           - starttime: start date of the event (format HH:MM)
     *           - endtime: end date of the event (format HH:MM)
     *           - agenda_id: id of the agenda (format "bktXXXXX")
     *           - created: create date of the event
     *           - updated: last update of the event
     *           - agenda_name: name of agenda
     *           - client_id: id of the client for the event (format "bktXXXXX")
     *           - client_name: name of the client, if exists. empty otherwise
     *           - client_cellphone: phone of the client, if exists. empty otherwise
     *           - client_email: email of the client, if exists. empty otherwise
     *           - created_from: where event was create
     *           - created_by: who created the event
     *           - confirmed: event confirmation
     *           - showup: the client attended the appointment
     *           - locator: event locator
     *           - description: description of the event, if exists. empty otherwise
     *           - comments: comments of the event, if exists. empty otherwise
     *           - people: number of people, if exists. empty otherwise
     *           - price_total: total price, if exists. empty otherwise
     *           - price_paid: paid price, if exists. empty otherwise
     *           - coupons_code: coupons codes, if exists. empty otherwise
     *           - service_id: id of the service (format "bktXXXXX"), if exists. empty otherwise
     *           - title: title of the event, if exists. empty otherwise
     *           - agenda_synchro_id: id of the agenda if it is shared with other software, if exists. empty otherwise
     *           - synchro_id: id of the event if it is shared with other software, if exists. empty otherwise
     *           - customvalidate1: client custom validate field 1, if exists. empty otherwise
     *           - customvalidateX: client custom validate field X, if exists. empty otherwise
     *           - custom1: client custom field 1, if exists. empty otherwise
     *           - custom2: client custom field 2, if exists. empty otherwise
     *           - customX: client custom field X, if exists. empty otherwise
     *           - customevent1: client custom field 1, if exists. empty otherwise
     *           - customevent2: client custom field 2, if exists. empty otherwise
     *           - customeventX: client custom field X, if exists. empty otherwise
     *         - }
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <events>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </events>
     *
     * - on failure, if json was chosen:
     *   - {"events":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function getClientEvents($p_iClientId, $p_sMode, $p_bSecure, $p_sDateFrom = "", $p_sDateTo = ""){
        $sUrl = "getclientevents/$this->_sPublicKey/$p_iClientId";

        if(strlen(trim($p_sDateFrom)) > 0 && strlen(trim($p_sDateTo)) > 0){
            $sUrl .= "/$p_sDateFrom/$p_sDateTo";
        }
        else if(strlen(trim($p_sDateFrom)) > 0){
            $sUrl .= "/$p_sDateFrom";
        }
        else if(strlen(trim($p_sDateTo)) > 0){
            $sUrl .= "//$p_sDateTo";
        }
        
        $sMethod = "get";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod);
        
        return $sResult;
    }
    
    /**
     * This function gets some client data by a validation field.
     * URL to call this function: http://app.bookitit.com/api/getclientbyvalidationfield/<i>$p_sPublicKey</i>
     *
     * @param $p_sPublicKey Your public key
     * 
     * POST PARAMETERS
     * @param $p_sFieldType the field type text ("email", "cellphone", "document", "customvalidate1", ... ). If not value is set (empty sring "") the search will be do for all validation fields configured
     * @param $p_sFieldvalue the field value to search
     *
     * @return
     * -on success, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <clients>
     *       - <status>true</status>
     *       - <i>one or more</i> 
     *       - <client>
     *         - <id>id of the client (format "bktXXXXXXX")</id>
     *         - <client_webaccess>if client has webaccess or not. (true => webaccess)</client_webaccess>
     *         - <client_email>email of the client, if exists. empty otherwise</client_email>
     *         - <client_address>address of the client, if exists. empty otherwise</client_address>
     *         - <client_cellphone>cellphone of the client, if exists. empty otherwise</client_cellphone>
     *         - <client_phone>phone of the client, if exists. empty otherwise</client_phone>
     *         - <client_document>document of the client, if exists. empty otherwise</client_document>
     *         - <client_name>name of the client, if exists. empty otherwise</client_name>
     *         - <customvalidate1>client custom validate field 1, if exists. empty otherwise</customvalidate1>
     *         - <customvalidateX>client custom validate field X, if exists. empty otherwise</customvalidateX>
     *         - <custom1>client custom field 1, if exists. empty otherwise</custom1>
     *         - <custom2>client custom field 2, if exists. empty otherwise</custom2>
     *         - <customX>client custom field X, if exists. empty otherwise</customX>
     *       - </client>
     *     - </clients>
     * 
     * - on success, if json was chosen:
     *   - {"clients":
     *     - {
     *       - "status":false,
     *       - <i>one or more</i> 
     *         - "client":
     *         - {
     *           - id: id of the client (format "bktXXXXXXX")
     *           - client_webaccess: if client has webaccess or not. (true => webaccess)
     *           - client_email: email of the client, if exists. empty otherwise
     *           - client_address: address of the client, if exists. empty otherwise
     *           - client_cellphone: cellphone of the client, if exists. empty otherwise
     *           - client_phone: phone of the client, if exists. empty otherwise
     *           - client_document: document of the client, if exists. empty otherwise
     *           - client_name: name of the client, if exists. empty otherwise
     *           - customvalidate1: client custom validate field 1, if exists. empty otherwise
     *           - customvalidateX: client custom validate field X, if exists. empty otherwise
     *           - custom1: client custom field 1, if exists. empty otherwise
     *           - custom2: client custom field 2, if exists. empty otherwise
     *           - customX: client custom field X, if exists. empty otherwise
     *         - }
     *     - }
     *   - }
     *
     * - on failure, if xml was chosen:
     *   - <?xml version='1.0' encoding='utf-8'?>
     *     - <clients>
     *       - <status>false</status>
     *       - <id>error id</id>
     *       - <message>error message</message>
     *     - </clients>
     *
     * - on failure, if json was chosen:
     *   - {"clients":
     *     - {
     *       - "status":false,
     *       - "id":error id,
     *       - "message":error message
     *     - }
     *   - }
     *
     */
    public function getClientByValidationField($p_sFieldType, $p_sFieldvalue, $p_sMode, $p_bSecure){
        $sUrl = "getclientbyvalidationfield/$this->_sPublicKey";

        $somePostParams = array(
            "p_sFieldType" => $p_sFieldType, 
            "p_sFieldvalue" => $p_sFieldvalue
        );
        
        $sMethod = "post";
        $sResult = $this->startConnection($sUrl, $p_sMode, $p_bSecure, $sMethod, $somePostParams);
        
        return $sResult;
    }
}
?>
  