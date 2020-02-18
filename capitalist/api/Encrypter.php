<?php
namespace capitalist\api;

class Encrypter
{
	/** @var \phpseclib\Crypt\RSA */
    private $rsa = null;

	/** @var string */
    private $modulus = null;
	
	/** @var string */
    private $exponent = null;
	
	public function __construct($in_modulus, $in_exponent)                                   
    {                                                                                  
		$this->modulus = new \phpseclib\Math\BigInteger($in_modulus, 16);
		$this->exponent = new \phpseclib\Math\BigInteger($in_exponent, 16);

		$this->rsa = new \phpseclib\Crypt\RSA();
		$this->rsa->loadKey(array('n' => $this->modulus, 'e' => $this->exponent));
		$this->rsa->setPublicKey();
		$this->rsa->setEncryptionMode(\phpseclib\Crypt\RSA::ENCRYPTION_PKCS1);
    }  

	public function getPublicKey()
    {
		return $this->rsa->getPublicKey();
	}
	
	public function getModulus()
    {
        return $this->modulus;
    }
	
	public function getExponent()
    {
        return $this->exponent;
    }
	
	public function encrypt($plaintext)
	{
		return $this->str2hex($this->rsa->encrypt($plaintext));
	}
	
	public function str2hex( $str ) {
		$unpacked = unpack('H*', $str);
		return array_shift( $unpacked );
	}
}