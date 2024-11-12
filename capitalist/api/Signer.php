<?php

namespace capitalist\api;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;


class Signer
{
    /** @var RSA\PublicKey|null */
    private $rsaKey = null;

    public function __construct($in_path, $in_login = null, $in_pass = null)
    {
        if (isset($in_login) && isset($in_pass) && (strlen($in_pass) > 0)) {
            $key = $this->decryptKeyFile($in_login, $in_pass, $in_path);
        } else {
            $key = file_get_contents($in_path);
        }

        if (!$key) {
            throw new \Exception('Failed to load key');
        }

        $this->rsaKey = PublicKeyLoader::loadPrivateKey($key);
        $this->rsaKey = $this->rsaKey->withPadding(RSA::SIGNATURE_PKCS1);
        $this->rsaKey = $this->rsaKey->withHash('sha1');

        if (!$this->rsaKey) {
            throw new \Exception('Failed to load key');
        }
    }

    public function decryptKeyFile($username, $certPassword, $filename): ?string
    {
        $pos = 0;
        $encrypted_key_lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $salt = $encrypted_key_lines[0];
        $keypassplain = $salt . $username . $certPassword;
        $keypass = sha1($keypassplain, true);

        $rc4 = new \phpseclib3\Crypt\RC4();
        $rc4->setKey($keypass);

        $key = $rc4->decrypt(base64_decode($encrypted_key_lines[1]));

        return $key;
    }

    public function sign($plaintext): ?string
    {
        return base64_encode($this->rsaKey->sign($plaintext));
    }
}