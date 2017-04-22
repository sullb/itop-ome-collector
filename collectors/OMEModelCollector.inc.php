<?php

class OMEModelCollector extends Collector
{
    protected $omedata;
    protected $oModelLookup;
 
    public function Prepare()
    {
        $bResult = parent::Prepare();
        $omeurl = Utils::GetConfigurationValue('ome_url', '');
        $omeuser = Utils::GetConfigurationValue('ome_user', '');
        $omepass = Utils::GetConfigurationValue('ome_pass', '');

        $this->omedata = $this->getModels($omeurl, $omeuser, $omepass);

        return $bResult;
    }
 
    public function Fetch()
    {
        if(count($this->omedata)>0) {
            $name = array_keys($this->omedata)[0];
            $data = $this->omedata[$name];
            unset($this->omedata[$name]);
			return $data;
        }
        return false;
    }

    private function getModels($ome, $user, $pass) {
        $ch = curl_init($ome . 'Devices/$top=10000');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "${user}:${pass}");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $response = '<?xml version="1.0" encoding="UTF-8"?>' . curl_exec($ch);
        $response = curl_exec($ch);
 
        $xmlDoc=new SimpleXMLElement($response);
		$rows = array();
        foreach($xmlDoc->xpath("/ArrayOfDevice/Device") as $device)
        {  
			$row = array();
			# Skip any that don't have a service tag
			if(empty($device->Type) || empty($device->SystemModel)) {
				continue;
			}
			
            $model = trim((string)$device->SystemModel);
            $rows[$model]['primary_key'] = $model;
            $rows[$model]['name'] = $model;
            $rows[$model]['brand_id'] = 'Dell';
            #if((string)$device->Type == "2" || (string)$device->Type == "4") {
                $rows[$model]['type'] = 'Server';
            #}
        }
		return $rows;
    }
}
