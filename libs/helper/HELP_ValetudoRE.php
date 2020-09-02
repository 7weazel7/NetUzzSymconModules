<?php

declare(strict_types=1);

trait HELP_ValetudoRE
{
    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'VRE_Commands':
                $this->setCommand($Value);
                break;
            default:
                $this->SendDebug('Request Action', 'No Action defined: ' . $Ident, 0);
                break;
        }
    }

    public function setCommand(int $Value)
    {   
        $this->SendDebug('setValue', 'Value: ' . $Value, 0);
        switch ($Value) {
            case 1:
                $this->publishCommand("command", "start");
                break;
            case 2:
                $this->publishCommand("command", "return_to_base");
                break;
            case 3:
                $this->publishCommand("command", "stop");
                break;
            case 4:
                $this->publishCommand("command", "clean_spot");
                break;
            case 5:
                $this->publishCommand("command", "locate");
                break;
            case 6:
                $this->publishCommand("command", "pause");
                break;
            case 7:
                $this->publishCommand("custom_command", '{"command": "go_to"}');
                break;
            case 8:
                $this->publishCommand("custom_command", '{"command": "zoned_cleanup"}');
                break;
            case 9:
                $this->publishCommand("custom_command",'{"command": "segmented_cleanup"}');
                break;
            case 10:
                $this->publishCommand("custom_command", '{"command": "reset_consumable"}');
                break;
            case 11:
                $this->publishCommand("custom_command", '{"command": "load_map"}');
                break;
            case 12:
                $this->publishCommand("custom_command", '{"command": "store_map"}');
                break;
            case 13:
                $this->publishCommand("custom_command", '{"command": "get_destinations"}');
                break;
            case 14:
                $this->publishCommand("custom_command", '{"command": "play_sound"}');
                break;
            default:
                $this->SendDebug('VRE_Commands', 'Invalid Value: ' . $Payload->command, 0);
                break;
        }
    }

    private function publishCommand(string $Topic, string $Payload)
    {
        $Data['DataID'] = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
        $Data['PacketType'] = 3;
        $Data['QualityOfService'] = 0;
        $Data['Retain'] = true;
        $Data['Topic'] = $this->ReadPropertyString('TopicPrefix') . '/' . $this->ReadPropertyString('Identifier') . '/' . $Topic;
        $Data['Payload'] = $Payload;
        $DataJSON = json_encode($Data, JSON_UNESCAPED_SLASHES);
        $this->SendDebug(__FUNCTION__ . ': Publish Topic', $Data['Topic'], 0);
        $this->SendDebug(__FUNCTION__ . ': Publish Payload', $Data['Payload'], 0);
        $this->SendDebug(__FUNCTION__ . ': DataJSON', $DataJSON, 0);
        $this->SendDataToParent($DataJSON);
    }
}