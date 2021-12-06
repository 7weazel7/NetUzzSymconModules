<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/ShellyHelper.php';
require_once __DIR__ . '/../libs/VariableProfileHelper.php';

class ShellyWindow extends IPSModule
{
	use Shelly;
	use VariableProfileHelper;

	public function Create()
	{
		// Never delete this line!
		parent::Create();
		// Connect to MQTT Server
		$this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');
		// Register Properties
		$this->RegisterPropertyString('MQTTTopic', '');
		$this->RegisterPropertyString('Device', '');
		$this->RegisterPropertyBoolean('CB_Shelly_Reachable', true);
		$this->RegisterPropertyBoolean('CB_Shelly_State', true);
		$this->RegisterPropertyBoolean('CB_Shelly_Lux', true);
		$this->RegisterPropertyBoolean('CB_Shelly_Battery', true);
		$this->RegisterPropertyBoolean('CB_Shelly_Temperature', true);
		$this->RegisterPropertyBoolean('CB_Shelly_Vibration', true);
		$this->RegisterPropertyBoolean('CB_Shelly_Tilt', true);

		// Register Variables
		if ($this->ReadPropertyBoolean('CB_Shelly_Reachable')) {
			$this->RegisterProfileBooleanEx('Shelly.Reachable', 'Network', '', '', [
				[false, 'Offline',  '', 0xFF0000],
				[true, 'Online',  '', 0x00FF00]
			]);
			$this->RegisterVariableBoolean('Shelly_Reachable', $this->Translate('Reachable'), 'Shelly.Reachable', 1);
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_State')) {
			$this->RegisterVariableBoolean('Shelly_State', $this->Translate('State'), '~Window', 2);
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_Lux')) {
			$this->RegisterVariableInteger('Shelly_Lux', $this->Translate('Lux'), '~Illumination', 3);
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_Battery')) {
			$this->RegisterVariableInteger('Shelly_Battery', $this->Translate('Battery'), '', 4);
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_Temperature')) {
			$this->RegisterVariableFloat('Shelly_Temperature', $this->Translate('Temperature'), '~Temperature', 5);
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_Vibration')) {
			$this->RegisterVariableBoolean('Shelly_Vibration', $this->Translate('Vibration'), '~Alert', 6);
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_Tilt')) {
			$this->RegisterVariableInteger('Shelly_Tilt', $this->Translate('Tilt'), '', 7);
		}
	}

	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();
		$this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

		if ($this->ReadPropertyBoolean('CB_Shelly_Reachable')) {
			$this->RegisterProfileBooleanEx('Shelly.Reachable', 'Network', '', '', [
				[false, 'Offline',  '', 0xFF0000],
				[true, 'Online',  '', 0x00FF00]
			]);
			$this->RegisterVariableBoolean('Shelly_Reachable', $this->Translate('Reachable'), 'Shelly.Reachable', 1);
		} else {
			$this->UnregisterVariable('Shelly_Reachable');
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_State')) {
			$this->RegisterVariableBoolean('Shelly_State', $this->Translate('State'), '~Window', 2);
		} else {
			$this->UnregisterVariable('Shelly_State');
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_Lux')) {
			$this->RegisterVariableInteger('Shelly_Lux', $this->Translate('Lux'), '~Illumination', 3);
		} else {
			$this->UnregisterVariable('Shelly_Lux');
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_Battery')) {
			$this->RegisterVariableInteger('Shelly_Battery', $this->Translate('Battery'), '', 4);
		} else {
			$this->UnregisterVariable('Shelly_Battery');
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_Temperature')) {
			$this->RegisterVariableFloat('Shelly_Temperature', $this->Translate('Temperature'), '~Temperature', 5);
		} else {
			$this->UnregisterVariable('Shelly_Temperature');
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_Vibration')) {
			$this->RegisterVariableBoolean('Shelly_Vibration', $this->Translate('Vibration'), '~Alert', 6);
		} else {
			$this->UnregisterVariable('Shelly_Vibration');
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_Tilt')) {
			$this->RegisterVariableInteger('Shelly_Tilt', $this->Translate('Tilt'), '', 7);
		} else {
			$this->UnregisterVariable('Shelly_Tilt');
		}
		//Setze Filter für ReceiveData
		$MQTTTopic = $this->ReadPropertyString('MQTTTopic');
		$this->SetReceiveDataFilter('.*' . $MQTTTopic . '.*');
	}

	public function ReceiveData($JSONString)
	{
		$this->SendDebug('JSON', $JSONString, 0);
		if (!empty($this->ReadPropertyString('MQTTTopic'))) {
			$data = json_decode($JSONString);

			switch ($data->DataID) {
				case '{7F7632D9-FA40-4F38-8DEA-C83CD4325A32}': // MQTT Server
					$Buffer = $data;
					break;
				case '{DBDA9DF7-5D04-F49D-370A-2B9153D00D9B}': //MQTT Client
					$Buffer = json_decode($data->Buffer);
					break;
				default:
					$this->LogMessage('Invalid Parent', KL_ERROR);
					return;
			}

			$this->SendDebug('MQTT Topic', $Buffer->Topic, 0);

			if (fnmatch('*/online', $Buffer->Topic) && $this->ReadPropertyBoolean('CB_Shelly_Reachable')) {
				$this->SendDebug('Online Payload', $Buffer->Payload, 0);
				switch ($Buffer->Payload) {
					case 'true':
						$this->SetValue('Shelly_Reachable', true);
						break;
					case 'false':
						$this->SetValue('Shelly_Reachable', false);
						break;
				}
			}

			if (property_exists($Buffer, 'Topic') && $this->ReadPropertyBoolean('CB_Shelly_State')) {
				if (fnmatch('*/state', $Buffer->Topic)) {
					$this->SendDebug('State Payload', $Buffer->Payload, 0);
					switch ($Buffer->Payload) {
						case 'close':
							$this->SetValue('Shelly_State', false);
							break;
						case 'open':
							$this->SetValue('Shelly_State', true);
							break;
						default:
							$this->SendDebug('Invalid Payload for State', $Buffer->Payload, 0);
							break;
						}
				}
				if (fnmatch('*/lux', $Buffer->Topic) && $this->ReadPropertyBoolean('CB_Shelly_Lux')) {
					$this->SendDebug('Lux Payload', $Buffer->Payload, 0);
					$this->SetValue('Shelly_Lux', $Buffer->Payload);
				}
				if (fnmatch('*/battery', $Buffer->Topic) && $this->ReadPropertyBoolean('CB_Shelly_Battery')) {
					$this->SendDebug('Battery Payload', $Buffer->Payload, 0);
					$this->SetValue('Shelly_Battery', $Buffer->Payload);
				}
				if (fnmatch('*/temperature', $Buffer->Topic) && $this->ReadPropertyBoolean('CB_Shelly_Temperature')) {
					$this->SendDebug('Temperature Payload', $Buffer->Payload, 0);
					$this->SetValue('Shelly_Temperature', $Buffer->Payload);
				}
				if (fnmatch('*/vibration', $Buffer->Topic) && $this->ReadPropertyBoolean('CB_Shelly_Vibration')) {
					$this->SendDebug('Vibration Payload', $Buffer->Payload, 0);
					switch ($Buffer->Payload) {
						case 1:
							$this->SetValue('Shelly_Vibration', true);
							break;
						case 0:
							$this->SetValue('Shelly_Vibration', false);
							break;
						default:
							$this->SendDebug('Invalid Payload for Vibration', $Buffer->Payload, 0);
							break;
						}
				}
				if (fnmatch('*/tilt', $Buffer->Topic) && $this->ReadPropertyBoolean('CB_Shelly_Tilt')) {
					$this->SendDebug('Tilt Payload', $Buffer->Payload, 0);
					$this->SetValue('Shelly_Tilt', $Buffer->Payload);
				}
			}
		}
	}
}
