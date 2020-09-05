<?php

declare(strict_types=1);

trait HELP_ValetudoRE
{
    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'VRE_Commands':
                $this->SetCommand($Value);
                break;
            case 'VRE_FanSpeeds':
                $this->SetCommand($Value);
                break;
            default:
                $this->SendDebug('Request Action', 'No Action defined: ' . $Ident, 0);
                break;
        }
    }

    public function SetCommand(int $Value)
    {   
        $this->SendDebug(__METHOD__, 'Value: ' . $Value, 0);
        switch ($Value) {
            case 1:
                $this->PublishCommand("command", "start");
                break;
            case 2:
                $this->PublishCommand("command", "return_to_base");
                break;
            case 3:
                $this->PublishCommand("command", "stop");
                break;
            case 4:
                $this->PublishCommand("command", "clean_spot");
                break;
            case 5:
                $this->PublishCommand("command", "locate");
                break;
            case 6:
                $this->PublishCommand("command", "pause");
                break;
            case 7:
                $this->PublishCommand("custom_command", '{"command": "go_to"}');
                break;
            case 8:
                $this->PublishCommand("custom_command", '{"command": "zoned_cleanup"}');
                break;
            case 9:
                $this->PublishCommand("custom_command",'{"command": "segmented_cleanup"}');
                break;
            case 10:
                $this->PublishCommand("custom_command", '{"command": "reset_consumable"}');
                break;
            case 11:
                $this->PublishCommand("custom_command", '{"command": "load_map"}');
                break;
            case 12:
                $this->PublishCommand("custom_command", '{"command": "store_map"}');
                break;
            case 13:
                $this->PublishCommand("custom_command", '{"command": "get_destinations"}');
                break;
            case 14:
                $this->PublishCommand("custom_command", '{"command": "play_sound"}');
                break;
            default:
                $this->SendDebug('VRE_Commands', 'Invalid Value: ' . $Payload->command, 0);
                break;
        }
    }

    public function SetFanSpeed(int $Value)
    {   
        $this->SendDebug(__METHOD__, 'Value: ' . $Value, 0);
        switch ($Value) {
            case 1:
                $this->PublishCommand("set_fan_speed", "min");
                break;
            case 2:
                $this->PublishCommand("set_fan_speed", "medium");
                break;
            case 3:
                $this->PublishCommand("set_fan_speed", "high");
                break;
            case 4:
                $this->PublishCommand("set_fan_speed", "max");
                break;
            case 5:
                $this->PublishCommand("set_fan_speed", "mop");
                break;
            default:
                $this->SendDebug('VRE_FanSpeeds', 'Invalid Value: ' . $Payload->fan_speed, 0);
                break;
        }
    }

    private function PublishCommand(string $Topic, string $Payload)
    {
        $Data['DataID'] = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
        $Data['PacketType'] = 3;
        $Data['QualityOfService'] = 0;
        $Data['Retain'] = true;
        $Data['Topic'] = $this->ReadPropertyString('TopicPrefix') . '/' . $this->ReadPropertyString('Identifier') . '/' . $Topic;
        $Data['Payload'] = $Payload;
        $DataJSON = json_encode($Data, JSON_UNESCAPED_SLASHES);
        #$this->SendDebug(__FUNCTION__ . ': Publish Topic', $Data['Topic'], 0);
        #$this->SendDebug(__FUNCTION__ . ': Publish Payload', $Data['Payload'], 0);
        #$this->SendDebug(__FUNCTION__ . ': DataJSON', $DataJSON, 0);
        $this->SendDataToParent($DataJSON);
    }
}