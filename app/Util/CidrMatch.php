<?php
namespace App\Util;

class CidrMatch
{
    const IP_VERSION_V4 = 'v4';
    const IP_VERSION_V6 = 'v6';

    const IP_VERSION_MASK_MAP = [
        self::IP_VERSION_V4 => 32,
        self::IP_VERSION_V6 => 128,
    ];

    public function match($ip, $cidr)
    {
        $c = explode('/', $cidr);
        $subnet = $c[0] ?? NULL;
        $mask   = $c[1] ?? NULL;

        $ipVersion = $this->getIpVersionByIp($ip);
        if (!$ipVersion) {
            return $ipVersion;
        }

        $mask = $mask ?: self::IP_VERSION_MASK_MAP[$ipVersion];
        if ($ipVersion == self::IP_VERSION_V4) {
            return $this->IPv4Match($ip, $subnet, $mask);
        }

        return $this->IPv6Match($ip, $subnet, $mask);
    }

    public function getDefaultMaskByIp($ip)
    {
        $ipVersion = $this->getIpVersionByIp($ip);
        return self::IP_VERSION_MASK_MAP[$ipVersion] ?? null;
    }

    public function getIpVersionByIp($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return self::IP_VERSION_V4;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return self::IP_VERSION_V6;
        }
        return false;
    }

    // inspired by: http://stackoverflow.com/questions/7951061/matching-ipv6-address-to-a-cidr-subnet
    private function IPv6MaskToByteArray($subnetMask)
    {
        $addr = str_repeat("f", $subnetMask / 4);
        switch ($subnetMask % 4) {
            case 0:
                break;
            case 1:
                $addr .= "8";
                break;
            case 2:
                $addr .= "c";
                break;
            case 3:
                $addr .= "e";
                break;
        }
        $addr = str_pad($addr, 32, '0');
        $addr = pack("H*", $addr);

        return $addr;
    }

    // inspired by: http://stackoverflow.com/questions/7951061/matching-ipv6-address-to-a-cidr-subnet
    private function IPv6Match($address, $subnetAddress, $subnetMask)
    {
        if (!filter_var($subnetAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) || $subnetMask === NULL || $subnetMask === "" || $subnetMask < 0 || $subnetMask > 128) {
            return false;
        }
        $subnet = inet_pton($subnetAddress);
        $addr = inet_pton($address);

        $binMask = $this->IPv6MaskToByteArray($subnetMask);

        return ($addr & $binMask) == $subnet;
    }

    // inspired by: http://stackoverflow.com/questions/594112/matching-an-ip-to-a-cidr-mask-in-php5
    private function IPv4Match($address, $subnetAddress, $subnetMask)
    {
        if (!filter_var($subnetAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || $subnetMask === NULL || $subnetMask === "" || $subnetMask < 0 || $subnetMask > 32) {
            return false;
        }

        $address = ip2long($address);
        $subnetAddress = ip2long($subnetAddress);
        $mask = -1 << (32 - $subnetMask);
        $subnetAddress &= $mask; # nb: in case the supplied subnet wasn't correctly aligned
        return ($address & $mask) == $subnetAddress;
    }

}
