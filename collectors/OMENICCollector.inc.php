<?php

class OMENICCollector extends Collector
{
    protected $omedata;
    protected $luServerPK;
	protected $oModelLookup;
 
    public function Prepare()
    {
        $bResult = parent::Prepare();
        $omeurl = Utils::GetConfigurationValue('ome_url', '');
        $omeuser = Utils::GetConfigurationValue('ome_user', '');
        $omepass = Utils::GetConfigurationValue('ome_pass', '');

        $this->omedata = $this->getDevices($omeurl, $omeuser, $omepass);

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

    private function getDevices($ome, $user, $pass) {
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
			# Skip any that don't have a service tag
            if(empty((string)$device->ServiceTag)) {
				continue;
			}

            if(!isset($device->NICS) || empty($device->NICS)) {
                continue;
            }

            # Skip any devices that don't have NICs listed
            #print_r($device->NICS);
            foreach($device->NICS->NIC as $nic) {
                if(empty((string)$nic->IPAddress)) {
                    continue;
                }
                
                $mac = (string)$nic->MACAddress;
                $rows[$mac]['connectableci_id'] = trim((string)$device->ServiceTag);
                $rows[$mac]['primary_key'] = $mac;
                $rows[$mac]['macaddress'] = $mac;
                $rows[$mac]['ipaddress'] = (string)$nic->IPAddress;
                $rows[$mac]['name'] = trim((string)($nic->Manufacturer));
                $rows[$mac]['comment'] = "Discovered from OME";
            }
        }
		return $rows;
    }
}
