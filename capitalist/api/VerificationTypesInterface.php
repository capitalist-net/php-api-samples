<?php


namespace capitalist\api;


interface VerificationTypesInterface
{
    const SMS_CODE_MOBILE = 'MOBILE',
        CERTIFICATE_SIGNATURE = 'SIGNATURE',
        PIN_PASSWORD = 'PIN';
}