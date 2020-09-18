<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/helper/HELP_RegisterProfile.php';
require_once __DIR__ . '/../libs/helper/HELP_ValetudoRE.php';

    // Klassendefinition
    class ValetudoRE extends IPSModule {

        use HELP_RegisterProfile;
        use HELP_ValetudoRE;

        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            // Diese Zeile nicht löschen.
            parent::Create();
            
            /* Verbinde zur GUID vom MQTT Server (Splitter)
               TX (vom Modul zum Server) {043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}
               RX (vom Server zum Modul) {7F7632D9-FA40-4F38-8DEA-C83CD4325A32} */
            $this->ConnectParent("{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}");

            // Eigenschaften für Identifier und TopicPrefix registrieren
            $this->RegisterPropertyString("VRE_Hostname", "");
            #$this->RegisterPropertyString("Identifier", "rockrobo");
            #$this->RegisterPropertyString("TopicPrefix", "valetudo");

            // Prüfen ob Variablenprofile vorhanden und diese ggf. neu anlegen
            if (IPS_VariableProfileExists("VRE.Commands")) { IPS_DeleteVariableProfile("VRE.Commands"); $this->createModuleVariableProfile("VRE.Commands");
            } else { $this->createModuleVariableProfile("VRE.Commands"); }
            if (IPS_VariableProfileExists("VRE.States")) { IPS_DeleteVariableProfile("VRE.States"); $this->createModuleVariableProfile("VRE.States");
            } else { $this->createModuleVariableProfile("VRE.States"); }
            if (IPS_VariableProfileExists("VRE.FanSpeeds")) { IPS_DeleteVariableProfile("VRE.FanSpeeds"); $this->createModuleVariableProfile("VRE.FanSpeeds");
            } else { $this->createModuleVariableProfile("VRE.FanSpeeds"); }

            // Anlegen der Variablen
            $this->RegisterVariableInteger('VRE_Commands', $this->Translate('Command'), 'VRE.Commands', 10); 
            $this->EnableAction('VRE_Commands'); 
            $this->RegisterVariableInteger('VRE_States', $this->Translate('State'), 'VRE.States', 30);
            $this->RegisterVariableInteger("VRE_BatteryLevel", $this->Translate("Battery level"), "~Battery.100", 40);
            $this->RegisterVariableInteger('VRE_FanSpeeds', $this->Translate('Suction power'), 'VRE.FanSpeeds', 50);
            $this->EnableAction('VRE_FanSpeeds'); 

            #$this->RegisterVariableString('VRE_Error', $this->Translate('Error'), 'VRE.Errors', 20);   
            #$this->RegisterVariableString('cleanTime', $this->Translate('Total duration of cleanings'), '', 300);
            #$this->RegisterVariableFloat('cleanArea', $this->Translate('Total area cleaned'), 'Roborock.Cleanarea', 400);
            #$this->RegisterVariableInteger('cleanCount', $this->Translate('Total number of cleanings'), '', 500);

        }

        public function Destroy()
        {
            // Diese Zeile nicht löschen
            parent::Destroy();

            // Angelgte Variablenprofile löschen
            if (IPS_VariableProfileExists("VRE.Commands")) { IPS_DeleteVariableProfile("VRE.Commands"); }
            if (IPS_VariableProfileExists("VRE.States")) { IPS_DeleteVariableProfile("VRE.States"); }
            if (IPS_VariableProfileExists("VRE.FanSpeeds")) { IPS_DeleteVariableProfile("VRE.FanSpeeds"); }

        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();
            // Benötigt MQTT Server Instanz
            $this->RequireParent("{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}");

            // Auslesen der MQTT Config
            $IpAddress = $this->ReadPropertyString('VRE_Hostname');
            if (filter_var($IpAddress, FILTER_VALIDATE_IP)) {     
                $HttpApiString = 'http://' . $IpAddress . '/api/' . 'mqtt_config';
                $MqttConfigJson = file_get_contents($HttpApiString);
                $this->SendDebug('HttpApiString', $HttpApiString, 0);
                $this->SendDebug('MqttConfigJson', $MqttConfigJson, 0);
                $MqttConfigJsonDecoded = json_decode($MqttConfigJson); // Decode: JSONString
                $MqttIdentifier = $MqttConfigJsonDecoded->identifier;
                $MqttTopicPrefix = $MqttConfigJsonDecoded->topicPrefix;
                $this->SendDebug('MqttIdentifier', $MqttIdentifier, 0);
                $this->SendDebug('MqttTopicPrefix', $MqttTopicPrefix, 0);
                $FullTopic = $MqttTopicPrefix . '/' . $MqttIdentifier;
                $this->SendDebug('FullTopic', $FullTopic, 0);  // Debug: FullTopic
                $this->SetReceiveDataFilter('.*' . $FullTopic . '.*');
            } else { 
                echo("$IpAddress is not a valid IP address");
            }

            #$topic = $this->ReadPropertyString('TopicPrefix') . '/' . $this->ReadPropertyString('Identifier');
            #$this->SendDebug('topic', $topic, 0);  // Debug: topic
            #$this->SetReceiveDataFilter('.*' . $topic . '.*');

        }
 
        public function ReceiveData($JSONString)
        {   
            if (!empty($this->ReadPropertyString('Identifier'))) {             

                #$this->SendDebug("JSONString", $JSONString, 0); // Debug: JSONString
                $data = json_decode($JSONString); // Decode: JSONString
                
                switch ($data->DataID) {
                    case '{7F7632D9-FA40-4F38-8DEA-C83CD4325A32}': // MQTT Server - RX (from Server to Modul)
                        $Buffer = $data;
                        #$this->SendDebug('Buffer', print_r($Buffer, true), 0);  // Debug: Buffer
                        break;
                    default:
                        $this->SendDebug('Invalid Parent', KL_ERROR, 0);
                        return;
                }
            }

            #$this->SendDebug("Buffer->Topic", $Buffer->Topic, 0); // Debug: Buffer->Topic
            #$this->SendDebug("Buffer->Payload", $Buffer->Payload, 0); // Debug: Buffer->Payload

            if (fnmatch('*command_status', $Buffer->Topic)) {
                $Payload = json_decode($Buffer->Payload);
                switch ($Payload->command) {
                    case 'start':
                        SetValue($this->GetIDForIdent('VRE_Commands'), 1);
                        break;
                    case 'return_to_base':
                        SetValue($this->GetIDForIdent('VRE_Commands'), 2);
                        break;
                    case 'stop':
                        SetValue($this->GetIDForIdent('VRE_Commands'), 3);
                        break;
                    case "clean_spot":
                        SetValue($this->GetIDForIdent('VRE_Commands'), 4);
                        break;
                    case "locate":
                        SetValue($this->GetIDForIdent('VRE_Commands'), 5);
                        break;
                    case "pause":
                        SetValue($this->GetIDForIdent('VRE_Commands'), 6);
                        break;
                    case "go_to":
                        SetValue($this->GetIDForIdent('VRE_Commands'), 7);
                        break;
                    case "zoned_cleanup":
                        SetValue($this->GetIDForIdent('VRE_Commands'), 8);
                        break;
                    case "segmented_cleanup":
                        SetValue($this->GetIDForIdent('VRE_Commands'), 9);
                        break;
                    case "reset_consumable":
                        SetValue($this->GetIDForIdent('VRE_Commands'), 10);
                        break;
                    case "load_map":
                        SetValue($this->GetIDForIdent('VRE_Commands'), 11);
                        break;
                    case "store_map":
                        SetValue($this->GetIDForIdent('VRE_Commands'), 12);
                        break;
                    case "get_destinations":
                        SetValue($this->GetIDForIdent('VRE_Commands'), 13);
                        break;
                    case "play_sound":
                        SetValue($this->GetIDForIdent('VRE_Commands'), 14);
                        break;
                    case "set_fan_speed":
                        break;
                    default:
                        $this->SendDebug('VRE_Commands', 'Invalid Value: ' . $Payload->command, 0);
                        break;
                    }
            }

            if (fnmatch('*attributes', $Buffer->Topic)) {
                $Payload = json_decode($Buffer->Payload);
                if (property_exists($Payload, 'valetudo_state')) {
                    SetValue($this->GetIDForIdent('VRE_States'), $Payload->valetudo_state->id);
                }
                /*
                if (property_exists($Payload, 'cleanTime')) {
                    $this->SetValue('cleanTime', $Payload->cleanTime);
                }
                if (property_exists($Payload, 'cleanArea')) {
                    $this->SetValue('cleanArea', $Payload->cleanArea);
                }
                if (property_exists($Payload, 'cleanCount')) {
                    $this->SetValue('cleanCount', $Payload->cleanCount);
                }
                */
            }

            if (fnmatch('*state', $Buffer->Topic)) {
                $Payload = json_decode($Buffer->Payload);
                if (property_exists($Payload, 'battery_level')) {
                    $this->SetValue('VRE_BatteryLevel', $Payload->battery_level);
                }
                if (property_exists($Payload, 'fan_speed')) {
                    switch ($Payload->fan_speed) {
                        case 'min':
                            SetValue($this->GetIDForIdent('VRE_FanSpeeds'), 1);
                            break;
                        case 'medium':
                            SetValue($this->GetIDForIdent('VRE_FanSpeeds'), 2);
                            break;
                        case 'high':
                            SetValue($this->GetIDForIdent('VRE_FanSpeeds'), 3);
                            break;
                        case "max":
                            SetValue($this->GetIDForIdent('VRE_FanSpeeds'), 4);
                            break;
                        case "mop":
                            SetValue($this->GetIDForIdent('VRE_FanSpeeds'), 5);
                            break;
                        default:
                            $this->SendDebug('VRE_FanSpeeds', 'Invalid Value: ' . $Payload->fan_speed, 0);
                            break;
                    }
                }
            }
        }

        public function ReadMQTTValues(string $Hostname){
            $this->SendDebug(__METHOD__, 'Hostname: ' . $Hostnames, 0);
        }

        private function createModuleVariableProfile(string $Name) {
            switch ($Name) {
                case 'VRE.Commands':
                    $Associations = [];
                    $Associations[] = [1, $this->Translate('Start'), '', -1];
                    $Associations[] = [2, $this->Translate('Return to base'), '', -1];
                    $Associations[] = [3, $this->Translate('Stop'), '', -1];
                    $Associations[] = [4, $this->Translate('Clean spot'), '', -1];
                    $Associations[] = [5, $this->Translate('Locate'), '', -1];
                    $Associations[] = [6, $this->Translate('Pause'), '', -1];
                    $Associations[] = [7, $this->Translate('Go to'), '', -1];
                    $Associations[] = [8, $this->Translate('Zone cleanup'), '', -1];
                    $Associations[] = [9, $this->Translate('Segment cleanup'), '', -1];
                    $Associations[] = [10, $this->Translate('Reset consumable'), '', -1];
                    $Associations[] = [11, $this->Translate('Load Map'), '', -1];
                    $Associations[] = [12, $this->Translate('Store Map'), '', -1];
                    $Associations[] = [13, $this->Translate('Get destinations'), '', -1];
                    $Associations[] = [14, $this->Translate('Play sound'), '', -1];
                    $this->RegisterProfileIntegerEx('VRE.Commands', 'Execute', '', '', $Associations);
                    break;
                case 'VRE.States':
                    $Associations = [];
                    $Associations[] = [5, $this->Translate('Cleaning'), '', -1];
                    $Associations[] = [7, $this->Translate('Cleaning'), '', -1];
                    $Associations[] = [11, $this->Translate('Cleaning'), '', -1];
                    $Associations[] = [16, $this->Translate('Cleaning'), '', -1];
                    $Associations[] = [10, $this->Translate('Paused'), '', -1];
                    $Associations[] = [2, $this->Translate('Idle'), '', -1];
                    $Associations[] = [3, $this->Translate('Idle'), '', -1];
                    $Associations[] = [6, $this->Translate('Returning'), '', -1];
                    $Associations[] = [15, $this->Translate('Returning'), '', -1];
                    $Associations[] = [8, $this->Translate('Docked'), '', -1];
                    $Associations[] = [9, $this->Translate('Error'), '', -1];
                    $Associations[] = [12, $this->Translate('Error'), '', -1];
                    $Associations[] = [17, $this->Translate('Zone cleanup'), '', -1];
                    $Associations[] = [18, $this->Translate('Segment cleanup'), '', -1];
                    $this->RegisterProfileIntegerEx('VRE.States', 'Information', '', '', $Associations);
                    break;
                case 'VRE.FanSpeeds':
                    $Associations = [];
                    $Associations[] = [1, $this->Translate('Minimum'), '', -1];
                    $Associations[] = [2, $this->Translate('Medium'), '', -1];
                    $Associations[] = [3, $this->Translate('High'), '', -1];
                    $Associations[] = [4, $this->Translate('Maximum'), '', -1];
                    $Associations[] = [5, $this->Translate('Low level (wipe)'), '', -1];
                    $this->RegisterProfileIntegerEx('VRE.FanSpeeds', 'Intensity', '', '', $Associations);
                    break;
                default:
                    $this->SendDebug(__FUNCTION__, 'Invalid Value', 0);
                    break;
            }
        }  
    }