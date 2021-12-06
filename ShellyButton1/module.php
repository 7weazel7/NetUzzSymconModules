<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/ShellyHelper.php';
require_once __DIR__ . '/../libs/VariableProfileHelper.php';
require_once __DIR__ . '/../libs/MQTTHelper.php';

class ShellyButton1 extends IPSModule
{
	use Shelly;
	use VariableProfileHelper;
	use MQTTHelper;

	public function Create()
	{
		// Never delete this line!
		parent::Create();
		// Connect to MQTT Server
		$this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');
		// Register Properties
		$this->RegisterPropertyString('MQTTTopic', '');
		$this->RegisterPropertyString('Device', '');
		$this->RegisterPropertyBoolean('CB_Shelly_Battery', true);
		$this->RegisterPropertyBoolean('CB_Shelly_Reachable', true);

		// Register Variable - Shelly.Battery
		if ($this->ReadPropertyBoolean('CB_Shelly_Battery')) {
			$this->RegisterVariableInteger('Shelly_Battery', $this->Translate('Battery'), '~Battery.100');
		}
		// Register Variable - Shelly.Reachable
		if ($this->ReadPropertyBoolean('CB_Shelly_Reachable')) {
			$this->RegisterProfileBooleanEx('Shelly.Reachable', 'Network', '', '', [
				[false, 'Offline',  '', 0xFF0000],
				[true, 'Online',  '', 0x00FF00]
			]);
			$this->RegisterVariableBoolean('Shelly_Reachable', $this->Translate('Reachable'), 'Shelly.Reachable');
		}
		// Register Variable - Shelly.Button1Input
		$this->RegisterProfileIntegerEx('Shelly.Button1Input', 'ArrowRight', '', '', [
			[0, $this->Translate('shortpush'),  '', 0x08f26e],
			[1, $this->Translate('double shortpush'),  '', 0x07da63],
			[2, $this->Translate('triple shortpush'),  '', 0x06c258],
			[3, $this->Translate('longpush'),  '', 0x06a94d],
		]);
		$this->RegisterVariableInteger('Shelly_Input', $this->Translate('Input'), 'Shelly.Button1Input');
	}

	public function Destroy()
	{
		//Never delete this line!
		parent::Destroy();
		if (IPS_VariableProfileExists('Shelly.Reachable')) {
			IPS_DeleteVariableProfile('Shelly.Reachable');
		}
		if (IPS_VariableProfileExists('Shelly.Button1Input')) {
			IPS_DeleteVariableProfile('Shelly.Button1Input');
		}
	}

	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();
		$this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

		// Register Variable - Shelly.Battery
		if ($this->ReadPropertyBoolean('CB_Shelly_Battery')) {
			$this->RegisterVariableInteger('Shelly_Battery', $this->Translate('Battery'), '~Battery.100');
		} else {
			$this->UnregisterVariable('Shelly_Battery');
		}

		// Register Variable - Shelly.Reachable
		if ($this->ReadPropertyBoolean('CB_Shelly_Reachable')) {
			$this->RegisterProfileBooleanEx('Shelly.Reachable', 'Network', '', '', [
				[false, 'Offline',  '', 0xFF0000],
				[true, 'Online',  '', 0x00FF00]
			]);
			$this->RegisterVariableBoolean('Shelly_Reachable', $this->Translate('Reachable'), 'Shelly.Reachable');
		} else {
			$this->UnregisterVariable('Shelly_Reachable');
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

			if (property_exists($Buffer, 'Topic')) {
				if (fnmatch('*/input_event/0', $Buffer->Topic)) {
					$Payload = json_decode($Buffer->Payload);
					$this->SendDebug('Input Payload', $Buffer->Payload, 0);
					switch ($Payload->event) {
						case 'S':
							$this->SetValue('Shelly_Input', 0);
							break;
						case 'SS':
							$this->SetValue('Shelly_Input', 1);
							break;
						case 'SSS':
							$this->SetValue('Shelly_Input', 2);
							break;
						case 'L':
							$this->SetValue('Shelly_Input', 3);
							break;
					}
				}
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
				if (fnmatch('*/sensor/battery*', $Buffer->Topic) && $this->ReadPropertyBoolean('CB_Shelly_Battery')) {
					$this->SendDebug('Battery Payload', $Buffer->Payload, 0);
					$this->SetValue('Shelly_Battery', $Buffer->Payload);
				}
			}
		}
	}
}
