<?php

namespace App\Protocol\At\Key;

use InvalidArgumentException;
use phpseclib3\Crypt\EC;
use phpseclib3\Math\BigInteger;

class Did
{
    const MC_ED25519 = 0xED;
    const MC_P256 = 0x1200;
    const MC_SECP256K1 = 0xE7;

    const KEY_TYPE_ED25519 = 'Ed25519VerificationKey2020';
    const KEY_TYPE_P256 = 'EcdsaSecp256r1VerificationKey2019';
    const KEY_TYPE_SECP256K1 = 'EcdsaSecp256k1VerificationKey2019';

    private $privateKey;
    private $publicKey;
    private $keyType;

    public function __construct($privateKey, $keyType)
    {
        $this->privateKey = $privateKey;
        $this->keyType = $keyType;
        $this->derivePublicKey($privateKey, $keyType);
    }

    /**
     * Generate a new DIDKey object with a randomly generated key pair.
     */
    public static function generate($keyType)
    {
        switch ($keyType) {
            case self::KEY_TYPE_ED25519:
                $privateKey = random_bytes(32);
                break;

            case self::KEY_TYPE_P256:
            case self::KEY_TYPE_SECP256K1:
                $curveName = $keyType === self::KEY_TYPE_P256 ? 'prime256v1' : 'secp256k1';
                $keyPair = EC::createKey($curveName);
                $privateKey = $keyPair->toString('PKCS8');
                break;

            default:
                throw new InvalidArgumentException("Unsupported key type: {$keyType}");
        }

        return new self($privateKey, $keyType);
    }

    /**
     * Derive the public key from the private key.
     */
    private function derivePublicKey($privateKey, $keyType)
    {
        switch ($keyType) {
            case self::KEY_TYPE_ED25519:
                $length = strlen($privateKey);
                if ($length === SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
                    // 完整 Ed25519 私钥
                    $this->privateKey = $privateKey;
                    $this->publicKey = sodium_crypto_sign_publickey_from_secretkey($privateKey);
                } elseif ($length === SODIUM_CRYPTO_SIGN_SEEDBYTES) {
                    // Ed25519 种子（32 字节），生成密钥对
                    $keyPair = sodium_crypto_sign_seed_keypair($privateKey);
                    $this->privateKey = sodium_crypto_sign_secretkey($keyPair);
                    $this->publicKey = sodium_crypto_sign_publickey($keyPair);
                } else {
                    throw new \SodiumException("私钥长度无效，应为 32 字节种子或 64 字节完整私钥！");
                }
                break;

            case self::KEY_TYPE_P256:
            case self::KEY_TYPE_SECP256K1:
                $curveName = $keyType === self::KEY_TYPE_P256 ? 'prime256v1' : 'secp256k1';
                $keyPair = EC::loadPrivateKey($privateKey, $curveName);
                $this->publicKey = $keyPair->getPublicKey();
                break;
            default:
                throw new InvalidArgumentException("Unsupported key type: {$keyType}");
        }
    }

    /**
     * Get the DID for this key.
     */
    public function getDID()
    {
        $multibase = $this->getMultibaseString();
        return "did:key:$multibase";
    }

    /**
     * Get the multibase-encoded string representation of the key.
     */
    private function getMultibaseString()
    {
        $prefix = $this->getMulticodecPrefix();
        $rawKey = $this->getRawPublicKey();
        $prefixedKey = $this->varintEncode($prefix) . $rawKey;
        return $this->multibaseEncode($prefixedKey);
    }

    /**
     * Get the raw bytes of the public key.
     */
    private function getRawPublicKey()
    {
        switch ($this->keyType) {
            case self::KEY_TYPE_ED25519:
                return $this->publicKey;

            case self::KEY_TYPE_P256:
            case self::KEY_TYPE_SECP256K1:
                return $this->publicKey->toString('PKCS8');

            default:
                throw new InvalidArgumentException("Unsupported key type: {$this->keyType}");
        }
    }

    /**
     * Get the multicodec prefix for the key type.
     */
    private function getMulticodecPrefix()
    {
        switch ($this->keyType) {
            case self::KEY_TYPE_ED25519:
                return self::MC_ED25519;

            case self::KEY_TYPE_P256:
                return self::MC_P256;

            case self::KEY_TYPE_SECP256K1:
                return self::MC_SECP256K1;

            default:
                throw new InvalidArgumentException("Unsupported key type: {$this->keyType}");
        }
    }

    /**
     * Encode a value as a varint.
     */
    private function varintEncode($value)
    {
        $result = '';
        while ($value >= 0x80) {
            $result .= chr(($value & 0x7F) | 0x80);
            $value >>= 7;
        }
        $result .= chr($value);
        return $result;
    }

    /**
     * Encode data using multibase (Base58-BTC).
     */
    private function multibaseEncode($data)
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base58 = '';

        $num = new BigInteger(bin2hex($data), 16);
        while (!$num->equals(new BigInteger(0))) {
            list($num, $remainder) = $num->divide(new BigInteger(58));
            $base58 = $alphabet[(int)$remainder->toString()] . $base58;
        }

        return 'z' . $base58; // Base58-BTC prefix is 'z'
    }

    /**
     * Verify a signature against a message.
     */
    public function verify($message, $signature)
    {
        switch ($this->keyType) {
            case self::KEY_TYPE_ED25519:
                return sodium_crypto_sign_verify_detached($signature, $message, $this->publicKey);

            case self::KEY_TYPE_P256:
            case self::KEY_TYPE_SECP256K1:
                $curveName = $this->keyType === self::KEY_TYPE_P256 ? 'prime256v1' : 'secp256k1';
                $publicKey = EC::loadPublicKey($this->publicKey->toString('pem'), $curveName);
                return $publicKey->verify($signature, $message);

            default:
                throw new InvalidArgumentException("Unsupported key type: {$this->keyType}");
        }
    }

    public function getPrivateKey()
    {
        return $this->privateKey;
    }
}