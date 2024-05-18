<?php
class MacAddress
{
    private static $valid_mac = "([0-9A-F]{2}[:-]){5}([0-9A-F]{2})";

    private static $mac_address_vals = array(
        "0", "1", "2", "3", "4", "5", "6", "7",
        "8", "9", "A", "B", "C", "D", "E", "F"
     );

    public static function setFakeMacAddress($interface, $mac = null)
    {

        if (!self::validateMacAddress($mac)) {
            $mac = self::generateMacAddress();
        }

        self::runCommand("ifconfig {$interface} down");
        self::runCommand("ifconfig {$interface} hw ether {$mac}");
        self::runCommand("ifconfig {$interface} up");

        self::runCommand("dhclient {$interface}");

        if (self::getCurrentMacAddress($interface) == $mac) {
            return true;
        }

        return false;
    }

    public static function generateMacAddress()
    {
        $vals = self::$mac_address_vals;
        if (count($vals) >= 1) {
            $mac = array("00");
            while (count($mac) < 6) {
                shuffle($vals);
                $mac[] = $vals[0] . $vals[1];
            }
            $mac = implode(":", $mac);
        }
        return $mac;
    }

    public static function validateMacAddress($mac)
    {
        return (bool) preg_match("/^" . self::$valid_mac . "$/i", $mac);
    }

    protected static function runCommand($command)
    {
        return shell_exec($command);
    }

    public static function getCurrentMacAddress($interface)
    {
        $ifconfig = self::runCommand("ifconfig {$interface}");
        preg_match("/" . self::$valid_mac . "/i", $ifconfig, $ifconfig);
        if (isset($ifconfig[0])) {
            return trim(strtoupper($ifconfig[0]));
        }
        return false;
    }
}