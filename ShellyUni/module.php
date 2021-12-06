<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/ShellyHelper.php';
require_once __DIR__ . '/../libs/VariableProfileHelper.php';
require_once __DIR__ . '/../libs/MQTTHelper.php';

class ShellyUni extends IPSModule
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
		$this->RegisterPropertyBoolean('CB_Shelly_Reachable', true);
		$this->RegisterPropertyBoolean('CB_Shelly_ADC', true);
		$this->RegisterPropertyBoolean('CB_Shelly_State1', true);
		$this->RegisterPropertyBoolean('CB_Shelly_State2', true);
		$this->RegisterPropertyBoolean('CB_Shelly_Input1', true);
		$this->RegisterPropertyBoolean('CB_Shelly_Input2', true);
		// Register Variables
		if ($this->ReadPropertyBoolean('CB_Shelly_Reachable')) {
			$this->RegisterVariableBoolean('Shelly_Reachable', $this->Translate('Reachable'), 'Shelly.Reachable', 1);
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_ADC')) {
			$this->RegisterVariableFloat('Shelly_ADC', $this->Translate('ADC'), '~Volt', 2);
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_State1')) {
			$this->RegisterVariableBoolean('Shelly_State1', $this->Translate('State 1'), '~Switch', 3);
			$this->EnableAction('Shelly_State1');
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_State2')) {
			$this->RegisterVariableBoolean('Shelly_State2', $this->Translate('State 2'), '~Switch', 4);
			$this->EnableAction('Shelly_State2');
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_Input1')) {
			$this->RegisterVariableBoolean('Shelly_Input1', $this->Translate('Input 1'), '~Switch', 5);
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_Input2')) {
			$this->RegisterVariableBoolean('Shelly_Input2', $this->Translate('Input 2'), '~Switch', 6);
		}
		$this->RegisterProfileBooleanEx('Shelly.Reachable', 'Network', '', '', [
			[false, 'Offline',  '', 0xFF0000],
			[true, 'Online',  '', 0x00FF00]
		]);
	}

	public function Destroy()
	{
		//Never delete this line!
		parent::Destroy();
		if (IPS_VariableProfileExists('Shelly.Reachable')) {
			IPS_DeleteVariableProfile('Shelly.Reachable');
		}
	}

	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();
		$this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');
		//Setze Filter für ReceiveData
		$MQTTTopic = $this->ReadPropertyString('MQTTTopic');
		$this->SetReceiveDataFilter('.*' . MQTT_GROUP_TOPIC . '/' . $MQTTTopic . '.*');

		// Register Variables
		if ($this->ReadPropertyBoolean('CB_Shelly_Reachable')) {
			$this->RegisterVariableBoolean('Shelly_Reachable', $this->Translate('Reachable'), 'Shelly.Reachable', 1);
		} else {
			$this->UnregisterVariable('Shelly_Reachable');
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_ADC')) {
			$this->RegisterVariableFloat('Shelly_ADC', $this->Translate('ADC'), '~Volt', 2);
		} else {
			$this->UnregisterVariable('Shelly_ADC');
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_State1')) {
			$this->RegisterVariableBoolean('Shelly_State1', $this->Translate('State 1'), '~Switch', 3);
			$this->EnableAction('Shelly_State1');
		} else {
			$this->UnregisterVariable('Shelly_State1');
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_State2')) {
			$this->RegisterVariableBoolean('Shelly_State2', $this->Translate('State 2'), '~Switch', 4);
			$this->EnableAction('Shelly_State2');
		} else {
			$this->UnregisterVariable('Shelly_State2');
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_Input1')) {
			$this->RegisterVariableBoolean('Shelly_Input1', $this->Translate('Input 1'), '~Switch', 5);
		} else {
			$this->UnregisterVariable('Shelly_Input1');
		}
		if ($this->ReadPropertyBoolean('CB_Shelly_Input2')) {
			$this->RegisterVariableBoolean('Shelly_Input2', $this->Translate('Input 2'), '~Switch', 6);
		} else {
			$this->UnregisterVariable('Shelly_Input2');
		}
	}

	public function RequestAction($Ident, $Value)
	{
		switch ($Ident) {
			case 'Shelly_State1':
				$this->SwitchMode(0, $Value);
				break;
			case 'Shelly_State2':
				$this->SwitchMode(1, $Value);
				break;
			}
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
				// Relay 0
				if (fnmatch('*/relay/0', $Buffer->Topic) && $this->ReadPropertyBoolean('CB_Shelly_State1')) {
					$this->SendDebug('State Payload', $Buffer->Payload, 0);
					$relay = $this->getChannelRelay($Buffer->Topic);
					$this->SendDebug(__FUNCTION__ . ' Relay', $relay, 0);

					switch ($Buffer->Payload) {
					case 'off':
						$this->SetValue('Shelly_State1', 0);
						break;
					case 'on':
						$this->SetValue('Shelly_State1', 1);
						break;
					default:
						break;
					}
				}
				// Relay 1
				if (fnmatch('*/relay/1', $Buffer->Topic) && $this->ReadPropertyBoolean('CB_Shelly_State2')) {
					$this->SendDebug('State Payload', $Buffer->Payload, 0);
					$relay = $this->getChannelRelay($Buffer->Topic);
					$this->SendDebug(__FUNCTION__ . ' Relay', $relay, 0);

					switch ($Buffer->Payload) {
					case 'off':
						$this->SetValue('Shelly_State2', 0);
						break;
					case 'on':
						$this->SetValue('Shelly_State2', 1);
						break;
					default:
						break;
					}
				}
				// Input 0
				if (fnmatch('*/input/0', $Buffer->Topic) && $this->ReadPropertyBoolean('CB_Shelly_Input1')) {
					$this->SendDebug('Input Payload', $Buffer->Payload, 0);
					$relay = $this->getChannelRelay($Buffer->Topic);
					$this->SendDebug(__FUNCTION__ . ' Relay', $relay, 0);

					switch ($Buffer->Payload) {
					case 0:
						$this->SetValue('Shelly_Input1', 0);
						break;
					case 1:
						$this->SetValue('Shelly_Input1', 1);
						break;
					default:
						break;
					}
				}
				// Input 1
				if (fnmatch('*/input/1', $Buffer->Topic) && $this->ReadPropertyBoolean('CB_Shelly_Input2')) {
					$this->SendDebug('Input Payload', $Buffer->Payload, 0);
					$relay = $this->getChannelRelay($Buffer->Topic);
					switch ($Buffer->Payload) {
					case 0:
						$this->SetValue('Shelly_Input2', 0);
						break;
					case 1:
						$this->SetValue('Shelly_Input2', 1);
						break;
					default:
						break;
				}
				}
				// ADC
				if (fnmatch('*/adc/0', $Buffer->Topic) && $this->ReadPropertyBoolean('CB_Shelly_ADC')) {
					$this->SendDebug('ADC Payload', $Buffer->Payload, 0);
					$input = $this->getChannelRelay($Buffer->Topic);
					$this->SetValue('Shelly_ADC', $Buffer->Payload);
				}
			}
			if (fnmatch('*/ext_temperature/[012]', $Buffer->Topic)) {
				$this->SendDebug('Ext_Temperature Payload', $Buffer->Payload, 0);
				$input = $this->getChannelRelay($Buffer->Topic);
				switch ($input) {
					case 0:
						$this->RegisterVariableFloat('Shelly_ExtTemperature0', $this->Translate('External Temperature 1'), '~Temperature');
						$this->SetValue('Shelly_ExtTemperature0', $Buffer->Payload);
						break;
					case 1:
						$this->RegisterVariableFloat('Shelly_ExtTemperature1', $this->Translate('External Temperature 2'), '~Temperature');
						$this->SetValue('Shelly_ExtTemperature1', $Buffer->Payload);
						break;
					case 2:
						$this->RegisterVariableFloat('Shelly_ExtTemperature2', $this->Translate('External Temperature 3'), '~Temperature');
						$this->SetValue('Shelly_ExtTemperature2', $Buffer->Payload);
						break;
				}
			}
			if (fnmatch('*/ext_humidity/[012]', $Buffer->Topic)) {
				$this->SendDebug('Ext_Humidity Payload', $Buffer->Payload, 0);
				$input = $this->getChannelRelay($Buffer->Topic);
				switch ($input) {
					case 0:
						$this->RegisterVariableFloat('Shelly_ExtHumidity0', $this->Translate('External Humidity 1'), '~Humidity.F');
						$this->SetValue('Shelly_ExtHumidity0', $Buffer->Payload);
						break;
					case 1:
						$this->RegisterVariableFloat('Shelly_ExtHumidity1', $this->Translate('External Humidity 2'), '~Humidity.F');
						$this->SetValue('Shelly_ExtHumidity1', $Buffer->Payload);
						break;
					case 2:
						$this->RegisterVariableFloat('Shelly_ExtHumidity2', $this->Translate('External Humidity 3'), '~Humidity.F');
						$this->SetValue('Shelly_ExtHumidity2', $Buffer->Payload);
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
		}
	}

	private function SwitchMode(int $relay, bool $Value)
	{
		$Topic = MQTT_GROUP_TOPIC . '/' . $this->ReadPropertyString('MQTTTopic') . '/relay/' . $relay . '/command';
		if ($Value) {
			$Payload = 'on';
		} else {
			$Payload = 'off';
		}
		$this->sendMQTT($Topic, $Payload);
	}
}
