<?php

declare(strict_types=1);
require_once __DIR__ . "/../libs/mqtt/IOTLinkService.php";
require_once __DIR__ . "/../libs/helper/VariableProfileHelper.php";

    class WindowsMonitor extends IPSModule
    {
        use VariableProfileHelper;

        public function Create()
        {
            // Never delete this line!
            parent::Create();
            // Connect to MQTT Server
            $this->ConnectParent("{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}");
            // Register Property String
            $this->RegisterPropertyString("Prefix", "iotlink");
            $this->RegisterPropertyString("DomainName", "netuzz");
            $this->RegisterPropertyString("Computername", "bellau-n001");
            // Check Boxes for Variables
            $this->RegisterPropertyBoolean("CurrentUser", false);
            $this->RegisterPropertyBoolean("BootTime", false);
            $this->RegisterPropertyBoolean("Uptime", false);
            $this->RegisterPropertyBoolean("IdleTime", false);
            $this->RegisterPropertyBoolean("CpuUsage", false);
            $this->RegisterPropertyBoolean("MemoryTotalCapacity", false);
            $this->RegisterPropertyBoolean("MemoryUsage", false);
            $this->RegisterPropertyBoolean("UsedMemory", false);
            $this->RegisterPropertyBoolean("AvailableMemory", false);
            $this->RegisterPropertyBoolean("PowerSupply", false);
            $this->RegisterPropertyBoolean("BatteryState", false);
            $this->RegisterPropertyBoolean("BatteryChargeStatus", false);
            $this->RegisterPropertyBoolean("RemainingBatteryRuntime", false);
            $this->RegisterPropertyBoolean("HardDrivePartition_C", false);
            $this->RegisterPropertyBoolean("HardDrivePartition_D", false);
            $this->RegisterPropertyBoolean("WiredNetworkState", false);
            $this->RegisterPropertyBoolean("IPv4Address", false);
            $this->RegisterPropertyBoolean("IPv6Address", false);
            $this->RegisterPropertyBoolean("PortSpeed", false);

            // MQTT Connection Client --> Server
            $this->IOT_CreateVariableProfile_Connected();
            $this->RegisterVariableBoolean("lwt", $this->Translate("MQTT Connection"), "IOT.Connected", 10);

            /*
            +++ Currently not implemented, because the values are considered unimportant ++++ 
            console-connect
            console-disconnect
            remote-connect
            remote-disconnect
            session-lock
            session-unlock
            stats/battery/full-lifetime
            stats/display/0/screen ---> Possibly interesting, but no background knowledge how to handle the screenshot correctly
            stats/display/0/screen-height
            stats/display/0/screen-width
            stats/hard-drive/c/available-free-space --> No difference to "available-free space" found. Same value
            stats/hard-drive/d/available-free-space --> No difference to "available-free space" found. Same value
            stats/network/0/bytes-received
            stats/network/0/bytes-received-per-second
            stats/network/0/bytes-send
            stats/network/0/bytes-send-per-second
            */

        }

        public function Destroy()
        {
            //Never delete this line!
            parent::Destroy();

            if (IPS_VariableProfileExists("IOT.Available")) { IPS_DeleteVariableProfile("IOT.Available"); }
            if (IPS_VariableProfileExists("IOT.Attached")) { IPS_DeleteVariableProfile("IOT.Attached"); }
            if (IPS_VariableProfileExists("IOT.Battery")) { IPS_DeleteVariableProfile("IOT.Battery"); }
            if (IPS_VariableProfileExists("IOT.Clock")) { IPS_DeleteVariableProfile("IOT.Clock"); }
            if (IPS_VariableProfileExists("IOT.ClockString")) { IPS_DeleteVariableProfile("IOT.ClockString"); }
            if (IPS_VariableProfileExists("IOT.Connected")) { IPS_DeleteVariableProfile("IOT.Connected"); }
            if (IPS_VariableProfileExists("IOT.Network")) { IPS_DeleteVariableProfile("IOT.Network"); }
            if (IPS_VariableProfileExists("IOT.NetworkSpeed")) { IPS_DeleteVariableProfile("IOT.NetworkSpeed"); }
            if (IPS_VariableProfileExists("IOT.MemoryGB")) { IPS_DeleteVariableProfile("IOT.MemoryGB"); }
            if (IPS_VariableProfileExists("IOT.MemoryMB")) { IPS_DeleteVariableProfile("IOT.MemoryMB"); }
            if (IPS_VariableProfileExists("IOT.People")) { IPS_DeleteVariableProfile("IOT.People"); }
        }

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();

            $topic = $this->ReadPropertyString("Prefix") . "/" . $this->ReadPropertyString("DomainName") . "/" . $this->ReadPropertyString("Computername");
            $this->SetReceiveDataFilter(".*" . $topic . ".*");

            // +++ Create only selected Variables +++
            // Currently logged in user
            if($this->ReadPropertyBoolean("CurrentUser")) {
                if (!IPS_VariableProfileExists("IOT.People")) { $this->RegisterProfileString("IOT.People", "People"); }
                $this->RegisterVariableString("current_user", $this->Translate("Currently logged in user"), "IOT.People", 20);
            } else {
                $this->UnregisterVariable("current_user");
            }
            // Boot time
            if($this->ReadPropertyBoolean("BootTime")) {
                if (!IPS_VariableProfileExists("IOT.ClockString")) { $this->RegisterProfileString("IOT.ClockString", "Clock", "", "", 0, 0, 1); }
                $this->RegisterVariableString("boot_time", $this->Translate("Boot time"), "IOT.ClockString", 30);
            } else {
                $this->UnregisterVariable("boot_time");
            }
            // Uptime
            if($this->ReadPropertyBoolean("Uptime")) {
                if (!IPS_VariableProfileExists("IOT.ClockString")) { $this->RegisterProfileString("IOT.ClockString", "Clock", "", "", 0, 0, 1); }
                $this->RegisterVariableString("uptime", $this->Translate("Uptime"), "IOT.ClockString", 40);
            } else {
                $this->UnregisterVariable("uptime");
            }
            // Idle time
            if($this->ReadPropertyBoolean("IdleTime")) {
                if (!IPS_VariableProfileExists("IOT.Clock")) { $this->RegisterProfileInteger("IOT.Clock", "Clock", "", $this->Translate(" Min."), 0, 0, 1); }
                $this->RegisterVariableInteger("idle_time", $this->Translate("Idle time"), "IOT.Clock", 50);
            } else {
                $this->UnregisterVariable("idle_time");
            }
            // CPU usage
            if($this->ReadPropertyBoolean("CpuUsage")) {
                $this->RegisterVariableInteger("cpu_usage", $this->Translate("CPU usage"), "~Intensity.100", 60);
            } else {
                $this->UnregisterVariable("cpu_usage");
            }
            // Memory Total capacity
            if($this->ReadPropertyBoolean("MemoryTotalCapacity")) {
                if (!IPS_VariableProfileExists("IOT.MemoryMB")) { $this->RegisterProfileInteger("IOT.MemoryMB", "Graph", "", $this->Translate(" MB"), 0, 0, 1); }
                $this->RegisterVariableInteger("memory_total", $this->Translate("Memory Total capacity"), "IOT.MemoryMB", 70);
            } else {
                $this->UnregisterVariable("memory_total");
            }
            // Memory usage
            if($this->ReadPropertyBoolean("MemoryUsage")) {
                $this->RegisterVariableInteger("memory_usage", $this->Translate("Memory usage"), "~Intensity.100", 80);
            } else {
                $this->UnregisterVariable("memory_usage");
            }
            // Used memory
            if($this->ReadPropertyBoolean("UsedMemory")) {
                if (!IPS_VariableProfileExists("IOT.MemoryMB")) {$this->RegisterProfileInteger("IOT.MemoryMB", "Graph", "", $this->Translate(" MB"), 0, 0, 1); }
                $this->RegisterVariableInteger("memory_used", $this->Translate("Used memory"), "IOT.MemoryMB", 90);
            } else {
                $this->UnregisterVariable("memory_used");
            }
            // Available memory
            if($this->ReadPropertyBoolean("AvailableMemory")) {
                if (!IPS_VariableProfileExists("IOT.MemoryMB")) {$this->RegisterProfileInteger("IOT.MemoryMB", "Graph", "", $this->Translate(" MB"), 0, 0, 1); }
                $this->RegisterVariableInteger("memory_available", $this->Translate("Available memory"), "IOT.MemoryMB", 100);
            } else {
                $this->UnregisterVariable("memory_available");
            }
            // Power supply
            if($this->ReadPropertyBoolean("PowerSupply")) {
                $this->IOT_CreateVariableProfile_Attached();
                $this->RegisterVariableBoolean("power_state", $this->Translate("Power supply"), "IOT.Attached", 110);
            } else {
                $this->UnregisterVariable("power_state");
            }
            // Battery
            if($this->ReadPropertyBoolean("BatteryState")) {
                $this->IOT_CreateVariableProfile_Available();
                $this->RegisterVariableBoolean("battery_state", $this->Translate("Battery"), "IOT.Available", 120);
            } else {
                $this->UnregisterVariable("battery_state");
            }
            // Battery Remaining Percent
            if($this->ReadPropertyBoolean("BatteryChargeStatus")) {
                $this->RegisterVariableInteger("battery_remaining_percent", $this->Translate("Battery charge status"), "~Battery.100", 130);
            } else {
                $this->UnregisterVariable("battery_remaining_percent");
            }
            // Battery Remaining Time
            if($this->ReadPropertyBoolean("RemainingBatteryRuntime")) {
                if (!IPS_VariableProfileExists("IOT.Battery")) { $this->RegisterProfileInteger("IOT.Battery", "Battery", "", $this->Translate(" Min."), 0, 0, 1); }
                $this->RegisterVariableInteger("battery_remaining_time", $this->Translate("Remaining battery runtime"), "IOT.Battery", 140);
            } else {
                $this->UnregisterVariable("battery_remaining_time");
            }
            // Hard drive partition C:\\
            if($this->ReadPropertyBoolean("HardDrivePartition_C")) {
                if (!IPS_VariableProfileExists("IOT.MemoryGB")) { $this->RegisterProfileInteger("IOT.MemoryGB", "Graph", "", $this->Translate(" GB"), 0, 0, 1); }
                if (!IPS_VariableProfileExists("IOT.HDD")) { $this->RegisterProfileString("IOT.HDD", "Information"); }
                $this->RegisterVariableString("hdd_c_label", $this->Translate("Description Partition C:\\"), "IOT.HDD", 150);
                $this->RegisterVariableString("hdd_c_format", $this->Translate("File System Partition C:\\"), "IOT.HDD", 151);
                $this->RegisterVariableInteger("hdd_c_total_size", $this->Translate("Total size partition C:\\"), "IOT.MemoryGB", 152);
                $this->RegisterVariableInteger("hdd_c_usage", $this->Translate("Disk usage Partition C:\\"), "~Intensity.100", 153);
                $this->RegisterVariableInteger("hdd_c_used_space", $this->Translate("Used disk space Partition C:\\"), "IOT.MemoryGB", 154);
                $this->RegisterVariableInteger("hdd_c_free_space", $this->Translate("Free disk space partition C:\\"), "IOT.MemoryGB", 155);
            } else {
                $this->UnregisterVariable("hdd_c_total_size");
            }
            // Hard drive partition D:\\
            if($this->ReadPropertyBoolean("HardDrivePartition_D")) {
                if (!IPS_VariableProfileExists("IOT.MemoryGB")) { $this->RegisterProfileInteger("IOT.MemoryGB", "Graph", "", $this->Translate(" GB"), 0, 0, 1); }
                if (!IPS_VariableProfileExists("IOT.HDD")) { $this->RegisterProfileString("IOT.HDD", "Information"); }
                $this->RegisterVariableString("hdd_d_label", $this->Translate("Description Partition D:\\"), "IOT.HDD", 160);
                $this->RegisterVariableString("hdd_d_format", $this->Translate("File System Partition D:\\"), "IOT.HDD", 161);
                $this->RegisterVariableInteger("hdd_d_total_size", $this->Translate("Total size partition D:\\"), "IOT.MemoryGB", 162);
                $this->RegisterVariableInteger("hdd_d_usage", $this->Translate("Disk usage Partition D:\\"), "~Intensity.100", 163);
                $this->RegisterVariableInteger("hdd_d_used_space", $this->Translate("Used disk space Partition D:\\"), "IOT.MemoryGB", 164);
                $this->RegisterVariableInteger("hdd_d_free_space", $this->Translate("Free disk space partition D:\\"), "IOT.MemoryGB", 165);
            } else {
                $this->UnregisterVariable("hdd_d_total_size");
            }
            // Wired Network State
            if($this->ReadPropertyBoolean("WiredNetworkState")) {
                $this->RegisterVariableBoolean("wired_state", $this->Translate("Wired Network State"), "IOT.Connected", 170);
            } else {
                $this->UnregisterVariable("wired_state");
            }
            // IPv4 Address
            if($this->ReadPropertyBoolean("IPv4Address")) {
                if (!IPS_VariableProfileExists("IOT.Network")) { $this->RegisterProfileString("IOT.Network", "Network"); }
                $this->RegisterVariableString("ipv4", $this->Translate("IPv4 Address"), "IOT.Network", 180);
            } else {
                $this->UnregisterVariable("ipv4");
            }
            // IPv6 Address
            if($this->ReadPropertyBoolean("IPv6Address")) {
                if (!IPS_VariableProfileExists("IOT.Network")) { $this->RegisterProfileString("IOT.Network", "Network"); }
                $this->RegisterVariableString("ipv6", $this->Translate("IPv6 Addresse"), "IOT.Network", 190);
            } else {
                $this->UnregisterVariable("ipv6");
            }
            // Port Speed
            if($this->ReadPropertyBoolean("PortSpeed")) {
                if (!IPS_VariableProfileExists("IOT.NetworkSpeed")) { $this->RegisterProfileInteger("IOT.NetworkSpeed", "Network", "", " Mbps", 0, 0, 1); }
                $this->RegisterVariableInteger("port_speed", $this->Translate("Port Speed"), "IOT.NetworkSpeed", 200);
            } else {
                $this->UnregisterVariable("port_speed");
            }
        }

        public function ReceiveData($JSONString)
        {
            $this->SendDebug("JSON", $JSONString, 0);

            if (!empty($this->ReadPropertyString("Computername"))) {
                $data = json_decode($JSONString);
                switch ($data->DataID) {
                    case "{7F7632D9-FA40-4F38-8DEA-C83CD4325A32}": // MQTT Server
                        $Buffer = $data;
                        break;
                    case "{DBDA9DF7-5D04-F49D-370A-2B9153D00D9B}": //MQTT Client
                        $Buffer = json_decode($data->Buffer);
                        break;
                    default:
                        $this->LogMessage("Invalid Parent", KL_ERROR);
                        return;
                }
                if (fnmatch("*/lwt", $Buffer->Topic)) {
                    switch ($Buffer->Payload) {
                        case "OFF":
                            $this->SetValue("lwt", false);
                            break;
                        case "ON":
                            $this->SetValue("lwt", true);
                            break;
                    }
                }
                if (fnmatch("*windows-monitor/stats/system/current-user", $Buffer->Topic)) {
                    $this->SetValue("current_user", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/system/boot-time", $Buffer->Topic)) {
                    $this->SetValue("boot_time", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/system/uptime", $Buffer->Topic)) {
                    $this->SetValue("uptime", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/system/idle-time", $Buffer->Topic)) {
                    $this->SetValue("idle_time", ($Buffer->Payload) / 60);
                }
                if (fnmatch("*windows-monitor/stats/cpu/usage", $Buffer->Topic)) {
                    $this->SetValue("cpu_usage", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/memory/total", $Buffer->Topic)) {
                    $this->SetValue("memory_total", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/memory/usage", $Buffer->Topic)) {
                    $this->SetValue("memory_usage", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/memory/used", $Buffer->Topic)) {
                    $this->SetValue("memory_used", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/memory/available", $Buffer->Topic)) {
                    $this->SetValue("memory_available", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/power/status", $Buffer->Topic)) {
                    $this->SetValue("power_state", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/battery/status", $Buffer->Topic)) {
                    $this->SetValue("battery_state", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/battery/remaining-percent", $Buffer->Topic)) {
                    $this->SetValue("battery_remaining_percent", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/battery/remaining-time", $Buffer->Topic)) {
                    $this->SetValue("battery_remaining_time", ($Buffer->Payload) / 60);
                }
                if (fnmatch("*windows-monitor/stats/hard-drive/c/volume-label", $Buffer->Topic)) {
                    $this->SetValue("hdd_c_label", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/hard-drive/c/drive-format", $Buffer->Topic)) {
                    $this->SetValue("hdd_c_format", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/hard-drive/c/total-size", $Buffer->Topic)) {
                    $this->SetValue("hdd_c_total_size", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/hard-drive/c/drive-usage", $Buffer->Topic)) {
                    $this->SetValue("hdd_c_usage", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/hard-drive/c/used-space", $Buffer->Topic)) {
                    $this->SetValue("hdd_c_used_space", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/hard-drive/c/available-free-space", $Buffer->Topic)) {
                    $this->SetValue("hdd_c_free_space", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/hard-drive/d/volume-label", $Buffer->Topic)) {
                    $this->SetValue("hdd_d_label", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/hard-drive/d/drive-format", $Buffer->Topic)) {
                    $this->SetValue("hdd_d_format", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/hard-drive/d/total-size", $Buffer->Topic)) {
                    $this->SetValue("hdd_d_total_size", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/hard-drive/d/drive-usage", $Buffer->Topic)) {
                    $this->SetValue("hdd_d_usage", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/hard-drive/d/used-space", $Buffer->Topic)) {
                    $this->SetValue("hdd_d_used_space", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/hard-drive/d/available-free-space", $Buffer->Topic)) {
                    $this->SetValue("hdd_d_free_space", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/network/0/wired", $Buffer->Topic)) {
                    $this->SetValue("wired_state", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/network/0/ipv4", $Buffer->Topic)) {
                    $this->SetValue("ipv4", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/network/0/ipv6", $Buffer->Topic)) {
                    $this->SetValue("ipv6", $Buffer->Payload);
                }
                if (fnmatch("*windows-monitor/stats/network/0/speed", $Buffer->Topic)) {
                    $this->SetValue("port_speed", $Buffer->Payload);
                }
            }
        }
    private function IOT_CreateVariableProfile_Connected()
    {
        $this->RegisterProfileBooleanEx("IOT.Connected", "Network", "", "", [
            [false, "Nicht Verbunden",  "", 0xFF0000],
            [true, "Verbunden",  "", 0x00FF00]
        ]);
    }
    private function IOT_CreateVariableProfile_Attached()
    {
        $this->RegisterProfileBooleanEx("IOT.Attached", "Plug", "", "", [
            [false, "Nicht angeschlossen",  "", 0xFF0000],
            [true, "Angeschlossen",  "", 0x00FF00]
        ]);
    }
    private function IOT_CreateVariableProfile_Available()
    {
        $this->RegisterProfileBooleanEx("IOT.Available", "Battery", "", "", [
            [false, "Nicht vorhanden",  "", 0xFF0000],
            [true, "Vorhanden",  "", 0x00FF00]
        ]);
    }
}