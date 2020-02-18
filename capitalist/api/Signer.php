<?php
namespace capitalist\api;

use phpseclib\Crypt\RSA;

class Signer
{
	/** @var RSA */
    private $rsa = null;

	public function __construct($in_path, $in_login=null, $in_pass=null)
	{
		$this->rsa = new RSA();
		$key = null;
		
		if(isset($in_login) && isset($in_pass) && (strlen($in_pass) > 0))
		{
			$pos = 0;
			$encrypted_key_lines = file($in_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);				
			$salt = $encrypted_key_lines[0];			
			$keypassplain = $salt.$in_login.$in_pass;			
			$keypass = sha1($keypassplain, true );
						
			$rc4 = new \phpseclib\Crypt\RC4();
			$rc4->setKey($keypass);
		
			$key = $rc4->decrypt(base64_decode($encrypted_key_lines[1]));
		} 
		else	
		{
			$key = file_get_contents($in_path);
		}

		$this->rsa->loadKey($key, RSA::PRIVATE_FORMAT_PKCS1);
		$this->rsa->setSignatureMode(RSA::SIGNATURE_PKCS1);
	}

	public function decryptKeyFile($username, $certPassword, $filename)
    {
        $pos = 0;
        $encrypted_key_lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $salt = $encrypted_key_lines[0];
        $keypassplain = $salt.$username.$certPassword;
        $keypass = sha1($keypassplain, true );

        $rc4 = new \phpseclib\Crypt\RC4();
        $rc4->setKey($keypass);

        $key = $rc4->decrypt(base64_decode($encrypted_key_lines[1]));

        return $key;
    }

	public function sign($plaintext)
	{
		return base64_encode ($this->rsa->sign($plaintext));
	}
}